<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Estudiante;
use App\Dominio\Repositorios\EstudianteRepositorio;
use DateTimeImmutable;

final class MySQLEstudianteRepositorio extends MySQLRepositorioBase implements EstudianteRepositorio {

    public function buscarPorId(int $idUsuario): ?Estudiante
    {
        $sql = "
            SELECT
                u.id_usuario,
                u.nombre,
                u.email,
                u.contrasenha,
                u.rol,
                u.estado,
                u.fecha_creacion,

                e.codigo_universitario,
                e.dni,
                e.fecha_nacimiento,
                e.fecha_ingreso,
                e.promedio_academico

            FROM usuario u
            INNER JOIN estudiante e
                ON e.id_usuario = u.id_usuario

            WHERE e.id_usuario = :id
            LIMIT 1
        ";

        $r = $this->one(
            $sql,
            [
                ':id' => $idUsuario
            ]
        );

        return $r ? $this->map($r) : null;
    }


    public function buscarPorCodigo(string $codigo): ?Estudiante
    {
        $sql = "
            SELECT
                u.id_usuario,
                u.nombre,
                u.email,
                u.contrasenha,
                u.rol,
                u.estado,
                u.fecha_creacion,

                e.codigo_universitario,
                e.dni,
                e.fecha_nacimiento,
                e.fecha_ingreso,
                e.promedio_academico

            FROM usuario u
            INNER JOIN estudiante e
                ON e.id_usuario = u.id_usuario

            WHERE e.codigo_universitario = :codigo
            LIMIT 1
        ";

        $r = $this->one(
            $sql,
            [
                ':codigo' => $codigo
            ]
        );

        return $r ? $this->map($r) : null;
    }


    public function buscarPorDni(string $dni): ?Estudiante
    {
        $sql = "
            SELECT
                u.id_usuario,
                u.nombre,
                u.email,
                u.contrasenha,
                u.rol,
                u.estado,
                u.fecha_creacion,

                e.codigo_universitario,
                e.dni,
                e.fecha_nacimiento,
                e.fecha_ingreso,
                e.promedio_academico

            FROM usuario u
            INNER JOIN estudiante e
                ON e.id_usuario = u.id_usuario

            WHERE e.dni = :dni
            LIMIT 1
        ";

        $r = $this->one(
            $sql,
            [
                ':dni' => $dni
            ]
        );

        return $r ? $this->map($r) : null;
    }


    public function guardar(Estudiante $e): void {
        $this->exec('INSERT INTO estudiante (id_usuario,codigo_universitario,dni,fecha_nacimiento,fecha_ingreso,promedio_academico) VALUES (:id,:codigo,:dni,:nacimiento,:ingreso,:promedio)',[
            'id'=>$e->getIdUsuario(),'codigo'=>$e->getCodigoUniversitario(),'dni'=>$e->getDni(),
            'nacimiento'=>$e->getFechaNacimiento()->format('Y-m-d'),'ingreso'=>$e->getFechaIngreso()->format('Y-m-d'),'promedio'=>$e->getPromedioAcademico()
        ]);
    }

    public function actualizar(Estudiante $e): void
    {
        $sql = "
            UPDATE estudiante
            SET
                codigo_universitario = :codigo,
                dni = :dni,
                fecha_nacimiento = :nacimiento,
                fecha_ingreso = :ingreso,
                promedio_academico = :promedio

            WHERE id_usuario = :id
        ";

        $this->exec(
            $sql,
            [
                ':id' => $e->getIdUsuario(),

                ':codigo' =>
                    $e->getCodigoUniversitario(),

                ':dni' =>
                    $e->getDni(),

                ':nacimiento' =>
                    $e->getFechaNacimiento()->format('Y-m-d'),

                ':ingreso' =>
                    $e->getFechaIngreso()->format('Y-m-d'),

                ':promedio' =>
                    $e->getPromedioAcademico()
            ]
        );
    }


    private function map(array $r): Estudiante
    {
        return new Estudiante(
            (int) $r['id_usuario'],
            (string) $r['nombre'],
            (string) $r['email'],
            (string) $r['contrasenha'],
            (string) $r['rol'],
            (bool) $r['estado'],
            new DateTimeImmutable(
                $r['fecha_creacion']
            ),

            (string) $r['codigo_universitario'],
            (string) $r['dni'],

            new DateTimeImmutable(
                $r['fecha_nacimiento']
            ),

            new DateTimeImmutable(
                $r['fecha_ingreso']
            ),

            (float) $r['promedio_academico']
        );
    }

    public function listar(int $limit=50,int $offset=0): array {
        $limit=max(1,min(200,$limit)); $offset=max(0,$offset);
        return array_map(fn($r)=>$this->map($r),$this->all("SELECT u.*,e.codigo_universitario,e.dni,e.fecha_nacimiento,e.fecha_ingreso,e.promedio_academico FROM usuario u JOIN estudiante e ON u.id_usuario=e.id_usuario ORDER BY u.id_usuario LIMIT $limit OFFSET $offset"));
    }

}
