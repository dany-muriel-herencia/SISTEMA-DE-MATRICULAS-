<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\GestionAcademica;

use App\Dominio\Entidades\Carrera;
use App\Dominio\Entidades\Escuela;
use App\Dominio\Entidades\Facultad;
use App\Dominio\Repositorios\CarreraRepositorio;
use App\Dominio\Repositorios\EscuelaRepositorio;
use App\Dominio\Repositorios\FacultadRepositorio;
use DomainException;
use InvalidArgumentException;

/**
 * CU-12: Registrar facultades, escuelas profesionales y carreras.
 * Actor Principal: Administrador.
 */
class GestionarEstructuraAcademica
{
    private FacultadRepositorio $facultadRepo;
    private EscuelaRepositorio $escuelaRepo;
    private CarreraRepositorio $carreraRepo;

    public function __construct(
        FacultadRepositorio $facultadRepo,
        EscuelaRepositorio $escuelaRepo,
        CarreraRepositorio $carreraRepo
    ) {
        $this->facultadRepo = $facultadRepo;
        $this->escuelaRepo  = $escuelaRepo;
        $this->carreraRepo  = $carreraRepo;
    }

    public function registrarFacultad(string $nombre, string $descripcion = '', string $decano = ''): array
    {
        if (empty(trim($nombre))) {
            throw new InvalidArgumentException('El nombre de la facultad es obligatorio.');
        }

        $facultad = new Facultad(0, $nombre, $descripcion, $decano);
        $this->facultadRepo->guardar($facultad);

        return [
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'decano'      => $decano,
            'mensaje'     => 'Facultad registrada correctamente.'
        ];
    }

    public function registrarEscuela(
        int $idFacultad,
        string $nombre,
        string $descripcion = '',
        string $director = ''
    ): array {
        if ($idFacultad <= 0) {
            throw new InvalidArgumentException('El ID de la facultad debe ser mayor a 0.');
        }

        if (empty(trim($nombre))) {
            throw new InvalidArgumentException('El nombre de la escuela profesional es obligatorio.');
        }

        $facultad = $this->facultadRepo->buscarPorId($idFacultad);
        if ($facultad === null) {
            throw new DomainException("Facultad con ID {$idFacultad} no encontrada.");
        }

        $escuela = new Escuela(0, $idFacultad, $nombre, $descripcion, $director);
        $this->escuelaRepo->guardar($escuela);

        return [
            'id_facultad' => $idFacultad,
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'director'    => $director,
            'mensaje'     => 'Escuela profesional registrada correctamente.'
        ];
    }

    public function registrarCarrera(
        int $idEscuela,
        string $nombre,
        string $codigo,
        int $duracion = 5,
        bool $estado = true
    ): array {
        if ($idEscuela <= 0) {
            throw new InvalidArgumentException('El ID de la escuela debe ser mayor a 0.');
        }

        if (empty(trim($nombre))) {
            throw new InvalidArgumentException('El nombre de la carrera profesional es obligatorio.');
        }

        if (empty(trim($codigo))) {
            throw new InvalidArgumentException('El código de la carrera es obligatorio.');
        }

        $escuela = $this->escuelaRepo->buscarPorId($idEscuela);
        if ($escuela === null) {
            throw new DomainException("Escuela con ID {$idEscuela} no encontrada.");
        }

        $carrera = new Carrera(0, $idEscuela, $nombre, $codigo, $duracion, $estado);
        $this->carreraRepo->guardar($carrera);

        return [
            'id_escuela' => $idEscuela,
            'nombre'     => $nombre,
            'codigo'     => $codigo,
            'duracion'   => $duracion,
            'estado'     => $estado,
            'mensaje'    => 'Carrera profesional registrada correctamente.'
        ];
    }

    public function listarEstructura(): array
    {
        return [
            'facultades' => array_map(fn($f) => [
                'id_facultad' => $f->getIdFacultad(),
                'nombre'      => $f->getNombre(),
                'descripcion' => $f->getDescripcion(),
                'decano'      => $f->getDecano()
            ], $this->facultadRepo->listar()),
            'escuelas' => array_map(fn($e) => [
                'id_escuela'  => $e->getIdEscuela(),
                'id_facultad' => $e->getIdFacultad(),
                'nombre'      => $e->getNombre(),
                'descripcion' => $e->getDescripcion(),
                'director'    => $e->getDirector()
            ], $this->escuelaRepo->listar()),
            'carreras' => array_map(fn($c) => [
                'id_carrera' => $c->getIdCarrera(),
                'id_escuela' => $c->getIdEscuela(),
                'nombre'     => $c->getNombre(),
                'codigo'     => $c->getCodigo(),
                'duracion'   => $c->getDuracion(),
                'estado'     => $c->getEstado()
            ], $this->carreraRepo->listar())
        ];
    }
}
