<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Pago;

use App\Application\Assembler\ConceptoPagoAssembler;
use App\Dominio\Repositorios\ConceptoPagoRepositorio;

/**
 * CU-43 (listado): Consultar conceptos de pago disponibles.
 * Actor: Tesorería, Administrador.
 */
class ConsultarConceptosPago
{
    private ConceptoPagoRepositorio $conceptoRepo;

    public function __construct(ConceptoPagoRepositorio $conceptoRepo)
    {
        $this->conceptoRepo = $conceptoRepo;
    }

    /** Retorna todos los conceptos de pago como arrays serializables. */
    public function listar(): array
    {
        $conceptos = $this->conceptoRepo->listar();
        return ConceptoPagoAssembler::toArrayList($conceptos);
    }

    /** Busca un concepto por ID. */
    public function buscarPorId(int $id): array
    {
        $concepto = $this->conceptoRepo->buscarPorId($id);

        if (!$concepto) {
            throw new \DomainException(
                "No se encontró el concepto de pago con ID {$id}."
            );
        }

        return ConceptoPagoAssembler::toArray($concepto);
    }
}
