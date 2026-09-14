<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Docente;
use App\Dominio\Repositorios\DocenteRepositorio;

final class MySQLDocenteRepositorio
    extends MySQLRepositorioBase
    implements DocenteRepositorio
{
    public function buscarPorId(int $idUsuario): ?Docente
    {
        $sql = "
            SELECT
                id_usuario,
                codigo,
                especialidad,
                grado_academico
            FROM docente
            WHERE id_usuario = :id_usuario
            LIMIT 1
        ";

        $resultado = $this->one($sql, [
            ':id_usuario' => $idUsuario
        ]);

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function buscarPorCodigo(string $codigo): ?Docente
    {
        $sql = "
            SELECT
                id_usuario,
                codigo,
                especialidad,
                grado_academico
            FROM docente
            WHERE codigo = :codigo
            LIMIT 1
        ";

        $resultado = $this->one($sql, [
            ':codigo' => $codigo
        ]);

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function guardar(Docente $docente): void
    {
        $sql = "
            INSERT INTO docente (
                id_usuario,
                codigo,
                especialidad,
                grado_academico
            )
            VALUES (
                :id_usuario,
                :codigo,
                :especialidad,
                :grado_academico
            )
        ";

        $this->exec($sql, [
            ':id_usuario' => $docente->getIdUsuario(),
            ':codigo' => $docente->getCodigo(),
            ':especialidad' => $docente->getEspecialidad(),
            ':grado_academico' => $docente->getGradoAcademico()
        ]);
    }

    public function actualizar(Docente $docente): void
    {
        $sql = "
            UPDATE docente
            SET
                codigo = :codigo,
                especialidad = :especialidad,
                grado_academico = :grado_academico
            WHERE id_usuario = :id_usuario
        ";

        $this->exec($sql, [
            ':id_usuario' => $docente->getIdUsuario(),
            ':codigo' => $docente->getCodigo(),
            ':especialidad' => $docente->getEspecialidad(),
            ':grado_academico' => $docente->getGradoAcademico()
        ]);
    }

    private function map(array $fila): Docente
    {
        return new Docente(
            (int) $fila['id_usuario'],
            (string) $fila['codigo'],
            (string) $fila['especialidad'],
            (string) $fila['grado_academico']
        );
    }
}   