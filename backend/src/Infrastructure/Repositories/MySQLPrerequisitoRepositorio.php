<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Prerequisito;
use App\Dominio\Repositorios\PrerequisitoRepositorio;

final class MySQLPrerequisitoRepositorio
    extends MySQLRepositorioBase
    implements PrerequisitoRepositorio
{
    public function buscarPorId(int $idPrerequisito): ?Prerequisito
    {
        $sql = "
            SELECT
                id_prerequisito,
                id_curso,
                id_curso_requerido
            FROM prerequisito
            WHERE id_prerequisito = :id_prerequisito
            LIMIT 1
        ";

        $resultado = $this->one($sql, [
            ':id_prerequisito' => $idPrerequisito
        ]);

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function listar(): array
    {
        $sql = "
            SELECT
                id_prerequisito,
                id_curso,
                id_curso_requerido
            FROM prerequisito
            ORDER BY id_curso ASC, id_curso_requerido ASC
        ";

        $resultados = $this->all($sql);

        return array_map(
            fn(array $fila): Prerequisito => $this->map($fila),
            $resultados
        );
    }

    public function guardar(Prerequisito $prerequisito): void
    {
        $sql = "
            INSERT INTO prerequisito (
                id_curso,
                id_curso_requerido
            )
            VALUES (
                :id_curso,
                :id_curso_requerido
            )
        ";

        $this->exec($sql, [
            ':id_curso' => $prerequisito->getIdCurso(),
            ':id_curso_requerido' => $prerequisito->getIdCursoRequerido()
        ]);
    }

    public function eliminar(int $idPrerequisito): void
    {
        $sql = "
            DELETE FROM prerequisito
            WHERE id_prerequisito = :id_prerequisito
        ";

        $this->exec($sql, [
            ':id_prerequisito' => $idPrerequisito
        ]);
    }

    private function map(array $fila): Prerequisito
    {
        return new Prerequisito(
            (int) $fila['id_prerequisito'],
            (int) $fila['id_curso'],
            (int) $fila['id_curso_requerido']
        );
    }
}