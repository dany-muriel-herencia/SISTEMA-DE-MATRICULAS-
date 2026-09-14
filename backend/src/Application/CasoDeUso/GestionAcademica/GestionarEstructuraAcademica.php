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

    public function registrarFacultad(string $nombre, string $codigo): array
    {
        if (empty(trim($nombre))) {
            throw new InvalidArgumentException('El nombre de la facultad es obligatorio.');
        }

        $facultad = new Facultad(0, $nombre, $codigo);
        $this->facultadRepo->guardar($facultad);

        return [
            'nombre' => $nombre,
            'codigo' => $codigo,
            'mensaje' => 'Facultad registrada correctamente.'
        ];
    }

    public function registrarEscuela(int $idFacultad, string $nombre, string $codigo): array
    {
        if (empty(trim($nombre))) {
            throw new InvalidArgumentException('El nombre de la escuela profesional es obligatorio.');
        }

        $facultad = $this->facultadRepo->buscarPorId($idFacultad);
        if ($facultad === null) {
            throw new DomainException("Facultad con ID {$idFacultad} no encontrada.");
        }

        $escuela = new Escuela(0, $idFacultad, $nombre, $codigo);
        $this->escuelaRepo->guardar($escuela);

        return [
            'id_facultad' => $idFacultad,
            'nombre' => $nombre,
            'codigo' => $codigo,
            'mensaje' => 'Escuela profesional registrada correctamente.'
        ];
    }

    public function registrarCarrera(int $idEscuela, string $nombre, string $codigo): array
    {
        if (empty(trim($nombre))) {
            throw new InvalidArgumentException('El nombre de la carrera profesional es obligatorio.');
        }

        $escuela = $this->escuelaRepo->buscarPorId($idEscuela);
        if ($escuela === null) {
            throw new DomainException("Escuela con ID {$idEscuela} no encontrada.");
        }

        $carrera = new Carrera(0, $idEscuela, $nombre, $codigo);
        $this->carreraRepo->guardar($carrera);

        return [
            'id_escuela' => $idEscuela,
            'nombre' => $nombre,
            'codigo' => $codigo,
            'mensaje' => 'Carrera profesional registrada correctamente.'
        ];
    }

    public function listarEstructura(): array
    {
        return [
            'facultades' => array_map(fn($f) => [
                'id_facultad' => $f->getIdFacultad(),
                'nombre' => $f->getNombre(),
                'codigo' => $f->getCodigo()
            ], $this->facultadRepo->listar()),
            'escuelas' => array_map(fn($e) => [
                'id_escuela' => $e->getIdEscuela(),
                'id_facultad' => $e->getIdFacultad(),
                'nombre' => $e->getNombre(),
                'codigo' => $e->getCodigo()
            ], $this->escuelaRepo->listar()),
            'carreras' => array_map(fn($c) => [
                'id_carrera' => $c->getIdCarrera(),
                'id_escuela' => $c->getIdEscuela(),
                'nombre' => $c->getNombre(),
                'codigo' => $c->getCodigo()
            ], $this->carreraRepo->listar())
        ];
    }
}
