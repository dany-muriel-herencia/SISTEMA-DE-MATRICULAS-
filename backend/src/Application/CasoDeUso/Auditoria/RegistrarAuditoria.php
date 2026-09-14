<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Auditoria;

use App\Application\DTO\RegistrarAuditoriaDTO;
use App\Dominio\Entidades\Auditoria;
use App\Dominio\Repositorios\AuditoriaRepositorio;
use DateTimeImmutable;

/**
 * CU-56: Registrar operaciones realizadas por usuarios.
 * CU-57: Guardar fecha, hora, usuario, acción y datos afectados.
 *
 * El Sistema invoca este caso de uso automáticamente tras
 * cada operación significativa (CREATE / UPDATE / DELETE).
 */
class RegistrarAuditoria
{
    private AuditoriaRepositorio $auditoriaRepo;

    public function __construct(AuditoriaRepositorio $auditoriaRepo)
    {
        $this->auditoriaRepo = $auditoriaRepo;
    }

    /**
     * Ejecuta el registro de auditoría.
     *
     * @param RegistrarAuditoriaDTO $dto Datos de la operación a registrar.
     * @return Auditoria Entidad persistida con fecha/hora asignada automáticamente.
     */
    public function ejecutar(RegistrarAuditoriaDTO $dto): Auditoria
    {
        // CU-57: La fecha y hora se asignan automáticamente por el sistema
        $auditoria = new Auditoria(
            0,                          // Auto-increment en BD
            $dto->getIdUsuario(),
            $dto->getAccion(),
            $dto->getTablaAfectada(),
            new DateTimeImmutable('now'),
            $dto->getDatosAnteriores(),
            $dto->getDatosNuevos(),
            $dto->getIp()
        );

        $this->auditoriaRepo->guardar($auditoria);

        return $auditoria;
    }

    /**
     * Atajo estático para registrar auditoría desde cualquier caso de uso
     * sin necesidad de inyectar el DTO manualmente.
     */
    public function registrarOperacion(
        int $idUsuario,
        string $accion,
        string $tablaAfectada,
        ?string $datosAnteriores = null,
        ?string $datosNuevos = null,
        ?string $ip = null
    ): Auditoria {
        $dto = RegistrarAuditoriaDTO::fromArray([
            'id_usuario'       => $idUsuario,
            'accion'           => $accion,
            'tabla_afectada'   => $tablaAfectada,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos'     => $datosNuevos,
            'ip'               => $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
        ]);

        return $this->ejecutar($dto);
    }
}
