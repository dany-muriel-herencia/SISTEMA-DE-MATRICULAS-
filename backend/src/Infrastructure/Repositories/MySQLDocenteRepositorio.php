<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Docente;
use App\Dominio\Repositorios\DocenteRepositorio;
use PDO;
use PDOException;

final class MySQLDocenteRepositorio extends MySQLRepositorioBase implements DocenteRepositorio
{
    private PDO $conection;

    public function __construct(PDO $conection)
    {
        $this->conection = $conection;
    }


    
    public function buscarPorId(int $idUsuario): ?Docente
    {
        $sql = "
            SELECT
                u.id_usuario,
                u.nombre,
                u.email,
                u.estado,
                u.fecha_creacion,
                d.codigo,
                d.especialidad,
                d.grado_academico
            FROM docente d
            INNER JOIN usuario u
                ON d.id_usuario = u.id_usuario
            WHERE d.id_usuario = :id_usuario
            LIMIT 1
        ";

        $stmt = $this->conection->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $idUsuario
        ]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fila === false) {
            return null;
        }

        return $this->mapearDocente($fila);
    }


    public function buscarPorCodigo(string $codigo): ?Docente
    {
        $sql = "
            SELECT
                u.id_usuario,
                u.nombre,
                u.email,
                u.estado,
                u.fecha_creacion,
                d.codigo,
                d.especialidad,
                d.grado_academico
            FROM docente d
            INNER JOIN usuario u
                ON d.id_usuario = u.id_usuario
            WHERE d.codigo = :codigo
            LIMIT 1
        ";

        $stmt = $this->conection->prepare($sql);

        $stmt->execute([
            ':codigo' => $codigo
        ]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fila === false) {
            return null;
        }

        return $this->mapearDocente($fila);
    }

    public function guardar(Docente $docente): void
    {
        try {
            $this->conection->beginTransaction();


            $sqlUsuario = "
                INSERT INTO usuario (
                    nombre,
                    email,
                    contrasenha,
                    rol,
                    estado
                ) VALUES (
                    :nombre,
                    :email,
                    :contrasenha,
                    :rol,
                    :estado
                )
            ";

            $stmtUsuario = $this->conection->prepare($sqlUsuario);

            $stmtUsuario->execute([
                ':nombre' => $docente->getNombre(),
                ':email' => $docente->getEmail(),
                ':contrasenha' => $docente->getContrasenha(),
                ':rol' => 'DOCENTE',
                ':estado' => $docente->getEstado()
            ]);


            $idUsuario = (int) $this->conection->lastInsertId();


            $sqlDocente = "
                INSERT INTO docente (
                    id_usuario,
                    codigo,
                    especialidad,
                    grado_academico
                ) VALUES (
                    :id_usuario,
                    :codigo,
                    :especialidad,
                    :grado_academico
                )
            ";

            $stmtDocente = $this->conection->prepare($sqlDocente);

            $stmtDocente->execute([
                ':id_usuario' => $idUsuario,
                ':codigo' => $docente->getCodigo(),
                ':especialidad' => $docente->getEspecialidad(),
                ':grado_academico' => $docente->getGradoAcademico()
            ]);

            $this->conection->commit();

        } catch (PDOException $e) {

            if ($this->conection->inTransaction()) {
                $this->conection->rollBack();
            }

            throw $e;
        }
    }


    public function actualizar(Docente $docente): void
    {
        try {
            $this->conection->beginTransaction();

            $sqlUsuario = "
                UPDATE usuario
                SET
                    nombre = :nombre,
                    email = :email,
                    estado = :estado
                WHERE id_usuario = :id_usuario
            ";

            $stmtUsuario = $this->conection->prepare($sqlUsuario);

            $stmtUsuario->execute([
                ':nombre' => $docente->getNombre(),
                ':email' => $docente->getEmail(),
                ':estado' => $docente->getEstado(),
                ':id_usuario' => $docente->getIdUsuario()
            ]);


            $sqlDocente = "
                UPDATE docente
                SET
                    codigo = :codigo,
                    especialidad = :especialidad,
                    grado_academico = :grado_academico
                WHERE id_usuario = :id_usuario
            ";

            $stmtDocente = $this->conection->prepare($sqlDocente);

            $stmtDocente->execute([
                ':codigo' => $docente->getCodigo(),
                ':especialidad' => $docente->getEspecialidad(),
                ':grado_academico' => $docente->getGradoAcademico(),
                ':id_usuario' => $docente->getIdUsuario()
            ]);

            $this->conection->commit();

        } catch (PDOException $e) {

            if ($this->conection->inTransaction()) {
                $this->conection->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Convertir una fila de la BD en una entidad Docente.
     */
    private function mapearDocente(array $fila): Docente
    {
        return new Docente(
            idUsuario: (int) $fila['id_usuario'],
            nombre: $fila['nombre'],
            email: $fila['email'],
            codigo: $fila['codigo'],
            estado: $fila['estado'],
            fechaCreacion: new \DateTimeImmutable($fila['fecha_creacion']),
            especialidad: $fila['especialidad'],
            gradoAcademico: $fila['grado_academico']
        );
    }
}