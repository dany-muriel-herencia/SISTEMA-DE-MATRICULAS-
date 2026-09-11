<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\ConceptoPago;

interface ConceptoPagoRepositorio
{
    public function buscarPorId( int $idConcepto ): ?ConceptoPago;

    public function listar(): array;

    public function guardar( ConceptoPago $conceptoPago ): void;

    public function actualizar( ConceptoPago $conceptoPago ): void;
}