<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Pago;

use App\Application\DTO\RegistrarConceptoPagoDTO;
use App\Dominio\Entidades\ConceptoPago;
use App\Dominio\Repositorios\ConceptoPagoRepositorio;
use DomainException;

/**
 * CU-43: Registrar conceptos de pago.
 * Actor: Tesorería, Administrador.
 *
 * Valida que no exista un concepto con el mismo nombre
 * antes de persistirlo.
 */
class RegistrarConceptoPago
{
    private ConceptoPagoRepositorio $conceptoRepo;

    public function __construct(ConceptoPagoRepositorio $conceptoRepo)
    {
        $this->conceptoRepo = $conceptoRepo;
    }

    public function ejecutar(RegistrarConceptoPagoDTO $dto): array
    {
        // Validar nombre único
        $existentes = $this->conceptoRepo->listar();
        foreach ($existentes as $concepto) {
            if (strtolower($concepto->getNombre()) === strtolower($dto->getNombre())) {
                throw new DomainException(
                    "Ya existe un concepto de pago con el nombre '{$dto->getNombre()}'."
                );
            }
        }

        // Construir entidad — id=1 es placeholder; el INSERT no incluye id (auto-increment)
        $concepto = new ConceptoPago(
            1,  // placeholder para pasar validación > 0; BD asigna el id real
            $dto->getNombre(),
            $dto->getDescripcion(),
            $dto->getMonto(),
            $dto->esObligatorio()
        );

        $this->conceptoRepo->guardar($concepto);

        // Retornamos los datos enviados como confirmación
        return [
            'nombre'      => $dto->getNombre(),
            'descripcion' => $dto->getDescripcion(),
            'monto'       => $dto->getMonto(),
            'obligatorio' => $dto->esObligatorio(),
        ];
    }
}
