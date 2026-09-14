<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Estudiante;

use App\Dominio\Entidades\Estudiante;
use App\Dominio\Repositorios\EstudianteRepositorio;
use App\Dominio\Repositorios\UsuarioRepositorio;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

/**
 * CU-07: Registrar datos personales y académicos del estudiante.
 * CU-08: Asignar código institucional único a cada estudiante (RN-01).
 * Actor Principal: Administrador. Actor Secundario: Sistema.
 */
class RegistrarEstudiante
{
    private EstudianteRepositorio $estudianteRepo;
    private UsuarioRepositorio $usuarioRepo;

    public function __construct(
        EstudianteRepositorio $estudianteRepo,
        UsuarioRepositorio $usuarioRepo
    ) {
        $this->estudianteRepo = $estudianteRepo;
        $this->usuarioRepo    = $usuarioRepo;
    }

    public function ejecutar(array $datos): array
    {
        $nombre          = trim($datos['nombre'] ?? '');
        $email           = trim($datos['email'] ?? '');
        $contrasenha     = $datos['contrasenha'] ?? '123456';
        $dni             = trim($datos['dni'] ?? '');
        $fechaNacimiento = $datos['fecha_nacimiento'] ?? null;

        if (empty($nombre)) {
            throw new InvalidArgumentException('El nombre del estudiante es obligatorio.');
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Se requiere un email válido para el estudiante.');
        }

        if (empty($dni)) {
            throw new InvalidArgumentException('El DNI del estudiante es obligatorio.');
        }

        // Validar DNI único
        if ($this->estudianteRepo->buscarPorDni($dni) !== null) {
            throw new DomainException("Ya existe un estudiante registrado con el DNI {$dni}.");
        }

        // Validar Email único
        if ($this->usuarioRepo->buscarPorEmail($email) !== null) {
            throw new DomainException("El email {$email} ya está en uso.");
        }

        // CU-08: Generación automática de código universitario único (EST-YYYY-XXXX)
        $codigoUniversitario = $datos['codigo_universitario'] ?? $this->generarCodigoUnico();

        // Validar código único en repositorio
        if ($this->estudianteRepo->buscarPorCodigo($codigoUniversitario) !== null) {
            $codigoUniversitario = $this->generarCodigoUnico();
        }

        $hashPassword = password_hash($contrasenha, PASSWORD_DEFAULT);
        $fechaNacObj  = $fechaNacimiento ? new DateTimeImmutable($fechaNacimiento) : new DateTimeImmutable('2000-01-01');
        $fechaIngreso = new DateTimeImmutable();

        $estudiante = new Estudiante(
            0, // id_usuario auto-generado
            $nombre,
            $email,
            $hashPassword,
            'ESTUDIANTE',
            true,
            new DateTimeImmutable(),
            $codigoUniversitario,
            $dni,
            $fechaNacObj,
            $fechaIngreso,
            0.0 // promedio inicial
        );

        $this->estudianteRepo->guardar($estudiante);

        // Obtener el estudiante recién creado
        $creado = $this->estudianteRepo->buscarPorCodigo($codigoUniversitario);

        return [
            'id_usuario'           => $creado ? $creado->getIdUsuario() : null,
            'codigo_universitario' => $codigoUniversitario,
            'nombre'               => $nombre,
            'email'                => $email,
            'dni'                  => $dni,
            'fecha_ingreso'        => $fechaIngreso->format('Y-m-d'),
            'promedio_academico'   => 0.0,
            'mensaje'              => 'Estudiante registrado correctamente con código asignado.'
        ];
    }

    private function generarCodigoUnico(): string
    {
        $year = date('Y');
        $random = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "EST-{$year}-{$random}";
    }
}
