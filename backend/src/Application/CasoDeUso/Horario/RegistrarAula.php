<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Horario;

use App\Dominio\Entidades\Aula;
use App\Dominio\Repositorios\AulaRepositorio;
use DomainException;
use InvalidArgumentException;

/**
 * CU-32: Registrar aulas, laboratorios y su capacidad (RN-07).
 * Actor Principal: Administrador.
 */
class RegistrarAula
{
    private AulaRepositorio $aulaRepo;

    public function __construct(AulaRepositorio $aulaRepo)
    {
        $this->aulaRepo = $aulaRepo;
    }

    public function ejecutar(array $datos): array
    {
        $nombre    = trim($datos['nombre'] ?? '');
        $ubicacion = trim($datos['ubicacion'] ?? 'Pabellón Principal');
        $capacidad = (int) ($datos['capacidad'] ?? 0);
        $tipo      = strtoupper(trim($datos['tipo'] ?? 'TEORIA'));
        $disponible= (bool) ($datos['disponible'] ?? true);

        if (empty($nombre)) {
            throw new InvalidArgumentException('El nombre o código del aula/laboratorio es obligatorio.');
        }

        // RN-07: Capacidad debe ser mayor que cero
        if ($capacidad <= 0) {
            throw new InvalidArgumentException('La capacidad del aula debe ser mayor a cero (RN-07).');
        }

        $tiposValidos = ['TEORIA', 'LABORATORIO', 'TALLER', 'AUDITORIO', 'VIRTUAL'];
        if (!in_array($tipo, $tiposValidos, true)) {
            throw new InvalidArgumentException('Tipo de aula inválido. Tipos permitidos: ' . implode(', ', $tiposValidos));
        }

        // Fictional ID 1 for new entity before DB auto-increment
        $aula = new Aula(
            1,
            $nombre,
            $ubicacion,
            $capacidad,
            $tipo,
            $disponible,
            true
        );

        $this->aulaRepo->guardar($aula);

        return [
            'nombre'     => $nombre,
            'ubicacion'  => $ubicacion,
            'capacidad'  => $capacidad,
            'tipo'       => $tipo,
            'disponible' => $disponible,
            'mensaje'    => 'Aula/laboratorio registrado correctamente.'
        ];
    }

    public function listarAulas(): array
    {
        $aulas = $this->aulaRepo->listar();
        return array_map(fn($a) => [
            'id_aula'    => $a->getIdAula(),
            'nombre'     => $a->getNombre(),
            'ubicacion'  => $a->getUbicacion(),
            'capacidad'  => $a->getCapacidad(),
            'tipo'       => $a->getTipo(),
            'disponible' => $a->getDisponible(),
            'estado'     => $a->getEstado()
        ], $aulas);
    }
}
