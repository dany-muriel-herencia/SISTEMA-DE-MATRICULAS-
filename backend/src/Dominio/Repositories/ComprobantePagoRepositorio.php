<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\ComprobantePago;

interface ComprobantePagoRepositorio
{
    public function buscarPorId( int $idComprobante): ?ComprobantePago;

    public function buscarPorPago( int $idPago): ?ComprobantePago;

    public function guardar( ComprobantePago $comprobantePago): void;

    public function actualizar( ComprobantePago $comprobantePago ): void;
}