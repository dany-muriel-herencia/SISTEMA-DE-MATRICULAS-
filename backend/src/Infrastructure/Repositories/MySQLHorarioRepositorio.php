<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Horario;
use App\Dominio\Repositorios\HorarioRepositorio;

final class MySQLHorarioRepositorio
    extends MySQLRepositorioBase
    implements HorarioRepositorio
{
    public function buscarPorId(int $idHorario): ?Horario
    {
        $sql = "
            SELECT
                id_horario,
                id_seccion,
                id_aula,
                dia_semana,
                hora_inicio,
                hora_fin,
                modalidad
            FROM horario
            WHERE id_horario = :id_horario
            LIMIT 1
        ";

        $resultado = $this->one($sql, [
            ':id_horario' => $idHorario
        ]);

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function listar(): array
    {
        $sql = "
            SELECT
                id_horario,
                id_seccion,
                id_aula,
                dia_semana,
                hora_inicio,
                hora_fin,
                modalidad
            FROM horario
            ORDER BY dia_semana ASC, hora_inicio ASC
        ";

        $resultados = $this->all($sql);

        return array_map(
            fn(array $fila): Horario => $this->map($fila),
            $resultados
        );
    }

    public function guardar(Horario $horario): void
    {
        $sql = "
            INSERT INTO horario (
                id_seccion,
                id_aula,
                dia_semana,
                hora_inicio,
                hora_fin,
                modalidad
            )
            VALUES (
                :id_seccion,
                :id_aula,
                :dia_semana,
                :hora_inicio,
                :hora_fin,
                :modalidad
            )
        ";

        $this->exec($sql, [
            ':id_seccion' => $horario->getIdSeccion(),
            ':id_aula' => $horario->getIdAula(),
            ':dia_semana' => $horario->getDiaSemana(),
            ':hora_inicio' => $horario->getHoraInicio(),
            ':hora_fin' => $horario->getHoraFin(),
            ':modalidad' => $horario->getModalidad()
        ]);
    }

    public function actualizar(Horario $horario): void
    {
        $sql = "
            UPDATE horario
            SET
                id_seccion = :id_seccion,
                id_aula = :id_aula,
                dia_semana = :dia_semana,
                hora_inicio = :hora_inicio,
                hora_fin = :hora_fin,
                modalidad = :modalidad
            WHERE id_horario = :id_horario
        ";

        $this->exec($sql, [
            ':id_horario' => $horario->getIdHorario(),
            ':id_seccion' => $horario->getIdSeccion(),
            ':id_aula' => $horario->getIdAula(),
            ':dia_semana' => $horario->getDiaSemana(),
            ':hora_inicio' => $horario->getHoraInicio(),
            ':hora_fin' => $horario->getHoraFin(),
            ':modalidad' => $horario->getModalidad()
        ]);
    }

    public function eliminar(int $idHorario): void
    {
        $sql = "
            DELETE FROM horario
            WHERE id_horario = :id_horario
        ";

        $this->exec($sql, [
            ':id_horario' => $idHorario
        ]);
    }

    public function existeConflictoAula(
        int $idAula,
        string $diaSemana,
        string $horaInicio,
        string $horaFin
    ): bool {
        $sql = "
            SELECT COUNT(*) AS total
            FROM horario
            WHERE id_aula = :id_aula
              AND UPPER(dia_semana) = UPPER(:dia_semana)
              AND :hora_inicio < hora_fin
              AND :hora_fin > hora_inicio
        ";

        $r = $this->one($sql, [
            ':id_aula'     => $idAula,
            ':dia_semana'  => $diaSemana,
            ':hora_inicio' => $horaInicio,
            ':hora_fin'    => $horaFin
        ]);

        return isset($r['total']) && ((int) $r['total']) > 0;
    }

    public function existeConflictoDocente(
        int $idDocente,
        string $diaSemana,
        string $horaInicio,
        string $horaFin
    ): bool {
        $sql = "
            SELECT COUNT(*) AS total
            FROM horario h
            INNER JOIN seccion s ON s.id_seccion = h.id_seccion
            WHERE s.id_docente = :id_docente
              AND UPPER(h.dia_semana) = UPPER(:dia_semana)
              AND :hora_inicio < h.hora_fin
              AND :hora_fin > h.hora_inicio
        ";

        $r = $this->one($sql, [
            ':id_docente'  => $idDocente,
            ':dia_semana'  => $diaSemana,
            ':hora_inicio' => $horaInicio,
            ':hora_fin'    => $horaFin
        ]);

        return isset($r['total']) && ((int) $r['total']) > 0;
    }

    private function map(array $fila): Horario
    {
        return new Horario(
            (int) $fila['id_horario'],
            (string) $fila['dia_semana'],
            new \DateTimeImmutable($fila['hora_inicio']),
            new \DateTimeImmutable($fila['hora_fin']),
            (string) $fila['modalidad']
        );
    }
}