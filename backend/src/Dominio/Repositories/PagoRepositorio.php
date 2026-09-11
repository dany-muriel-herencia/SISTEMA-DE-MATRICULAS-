<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Pago;

interface PagoRepositorio
{
    public function buscarPorId(int $idPago): ?Pago;

    public function buscarPorEstudiante(int $idEstudiante): array;

    public function guardar(Pago $pago): void;

    public function actualizar(Pago $pago): void;
}