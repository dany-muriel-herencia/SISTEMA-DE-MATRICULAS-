<?php

declare(strict_types=1);

namespace App\Modules\Matricula;

use App\Core\ApiResponse;
use App\Core\Router;

final class EnrollmentController
{
    private EnrollmentRepository $repo;

    public function __construct()
    {
        $this->repo = new EnrollmentRepository();
    }

    private function responderSegunSp(array $out): void
    {
        $codigo = (string)($out['codigo'] ?? 'EXITO');
        $mensaje = (string)($out['mensaje'] ?? 'Operación completada.');
        if ($codigo === 'EXITO') {
            ApiResponse::success($out, $mensaje);
            return;
        }
        ApiResponse::error($mensaje, 400, $codigo, $out);
    }

    // TX-01: Matrícula transaccional completa (sp_registrar_matricula_completa)
    public function registrarMatriculaCompleta(): void
    {
        $body = Router::getJsonBody();
        $idEstudiante = (int)($body['idEstudiante'] ?? 0);
        $idPeriodo = (int)($body['idPeriodo'] ?? 0);
        $idAdmin = isset($body['idAdmin']) ? (int)$body['idAdmin'] : null;
        $numeroRd = isset($body['numeroRd']) ? (string)$body['numeroRd'] : null;
        $secciones = $body['secciones'] ?? $body['listaSecciones'] ?? '';

        if (is_array($secciones)) {
            $secciones = implode(',', array_map('intval', $secciones));
        }

        if ($idEstudiante <= 0 || $idPeriodo <= 0 || trim((string)$secciones) === '') {
            ApiResponse::error('idEstudiante, idPeriodo y secciones son obligatorios.', 400);
            return;
        }

        $out = $this->repo->registrarMatriculaCompleta($idEstudiante, $idPeriodo, $idAdmin, $numeroRd, (string)$secciones);
        $this->responderSegunSp($out);
    }

    public function crearPendiente(): void
    {
        $body = Router::getJsonBody();
        $out = $this->repo->crearMatriculaPendiente(
            (int)($body['idEstudiante'] ?? 0),
            (int)($body['idPeriodo'] ?? 0),
            isset($body['idAdmin']) ? (int)$body['idAdmin'] : null,
            isset($body['numeroRd']) ? (string)$body['numeroRd'] : null
        );
        $this->responderSegunSp($out);
    }

    public function confirmar(): void
    {
        $body = Router::getJsonBody();
        $out = $this->repo->confirmarMatricula(
            (int)($body['idMatricula'] ?? 0),
            isset($body['idAdmin']) ? (int)$body['idAdmin'] : null
        );
        $this->responderSegunSp($out);
    }

    public function inscribirCurso(): void
    {
        $body = Router::getJsonBody();
        $out = $this->repo->inscribirCurso((int)($body['idMatricula'] ?? 0), (int)($body['idSeccion'] ?? 0));
        $this->responderSegunSp($out);
    }

    public function retirarCurso(): void
    {
        $body = Router::getJsonBody();
        $out = $this->repo->retirarCurso((int)($body['idMatricula'] ?? 0), (int)($body['idSeccion'] ?? 0));
        $this->responderSegunSp($out);
    }

    public function anular(): void
    {
        $body = Router::getJsonBody();
        $out = $this->repo->anularMatricula(
            (int)($body['idMatricula'] ?? 0),
            (int)($body['idAdmin'] ?? 0),
            (string)($body['motivo'] ?? 'Anulación administrativa')
        );
        $this->responderSegunSp($out);
    }

    public function aplicarExcepcion(): void
    {
        $body = Router::getJsonBody();
        $out = $this->repo->aplicarExcepcion(
            (int)($body['idMatricula'] ?? 0),
            (int)($body['idAdmin'] ?? 0),
            (string)($body['tipoExcepcion'] ?? ''),
            (string)($body['numeroRd'] ?? ''),
            (string)($body['descripcion'] ?? ''),
            (int)($body['creditosExtra'] ?? 0)
        );
        $this->responderSegunSp($out);
    }

    public function consultarMatricula(string $idEstudiante, string $idPeriodo): void
    {
        ApiResponse::success($this->repo->consultarMatricula((int)$idEstudiante, (int)$idPeriodo));
    }

    public function ingresarCola(): void
    {
        $body = Router::getJsonBody();
        $out = $this->repo->ingresarCola((int)($body['idEstudiante'] ?? 0), (int)($body['idPeriodo'] ?? 0));
        $this->responderSegunSp($out);
    }

    public function atenderSiguienteCola(): void
    {
        $body = Router::getJsonBody();
        $out = $this->repo->atenderSiguienteCola(
            (int)($body['idPeriodo'] ?? 0),
            (int)($body['maxEnTurno'] ?? 30)
        );
        $this->responderSegunSp($out);
    }

    public function finalizarTurnoCola(): void
    {
        $body = Router::getJsonBody();
        $out = $this->repo->finalizarTurnoCola((int)($body['idCola'] ?? 0));
        $this->responderSegunSp($out);
    }

    public function abandonarCola(): void
    {
        $body = Router::getJsonBody();
        $out = $this->repo->abandonarCola((int)($body['idEstudiante'] ?? 0), (int)($body['idPeriodo'] ?? 0));
        $this->responderSegunSp($out);
    }

    public function consultarPosicionCola(string $idEstudiante, string $idPeriodo): void
    {
        ApiResponse::success($this->repo->consultarPosicionCola((int)$idEstudiante, (int)$idPeriodo));
    }

    public function inscribirListaEspera(): void
    {
        $body = Router::getJsonBody();
        $out = $this->repo->inscribirListaEspera((int)($body['idMatricula'] ?? 0), (int)($body['idSeccion'] ?? 0));
        $this->responderSegunSp($out);
    }

    public function cancelarListaEspera(): void
    {
        $body = Router::getJsonBody();
        $out = $this->repo->cancelarListaEspera((int)($body['idMatricula'] ?? 0), (int)($body['idSeccion'] ?? 0));
        $this->responderSegunSp($out);
    }

    public function promoverListaEspera(): void
    {
        $body = Router::getJsonBody();
        $out = $this->repo->promoverListaEspera((int)($body['idSeccion'] ?? 0));
        $this->responderSegunSp($out);
    }

    public function consultarListaEspera(string $idMatricula): void
    {
        ApiResponse::success($this->repo->consultarListaEspera((int)$idMatricula));
    }
}
