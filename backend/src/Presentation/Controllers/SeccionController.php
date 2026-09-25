<?php
declare(strict_types=1);
namespace App\Presentation\Controllers;

use App\Application\CasoDeUso\Seccion\GestionarProgramacion;
use App\Presentation\Responses\ApiResponse;

final class SeccionController
{
    public function __construct(private GestionarProgramacion $programacion) {}

    private function entrada(): array
    {
        $json = file_get_contents('php://input');
        $datos = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($datos) || !str_starts_with(ltrim($json), '{')) {
            throw new \InvalidArgumentException('Se requiere un objeto JSON válido.');
        }
        return $datos;
    }

    private function responder(callable $operacion, bool $crear = false): void
    {
        try {
            $resultado = $operacion();
            if ($resultado === null) { ApiResponse::notFound('La sección no existe.'); }
            if ($crear) { ApiResponse::created($resultado); }
            ApiResponse::success($resultado);
        } catch (\InvalidArgumentException|\DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (\Throwable $e) {
            error_log((string)$e);
            ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function listar(): void { $this->responder(fn() => $this->programacion->listar($_GET)); }
    public function catalogos(): void { $this->responder(fn() => $this->programacion->catalogos()); }
    public function consultar(string $id): void { $this->responder(fn() => $this->programacion->consultar($id)); }
    public function registrar(): void { $this->responder(fn() => $this->programacion->crearSeccion($this->entrada()), true); }
    public function registrarHorario(string $id): void { $this->responder(fn() => $this->programacion->crearHorario($id, $this->entrada()), true); }
}
