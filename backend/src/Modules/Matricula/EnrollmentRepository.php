<?php

declare(strict_types=1);

namespace App\Modules\Matricula;

use App\Core\Database;

/**
 * M05 & M06: Repositorio Transaccional de Matrícula, Cola en Vivo y Lista de Espera
 * (ISSUE-05 Dany Muriel, ISSUE-07 Franklin Anahua).
 * 100% PDO Nativo + Stored Procedures ACID (Cero ORM).
 */
final class EnrollmentRepository
{
    public function registrarMatriculaCompleta(
        int $idEstudiante,
        int $idPeriodo,
        ?int $idAdmin,
        ?string $numeroRd,
        string $listaSeccionesCsv
    ): array {
        $res = Database::callStoredProcedure(
            'CALL sp_registrar_matricula_completa(?, ?, ?, ?, ?, @p_id_matricula, @p_codigo_resp, @p_mensaje)',
            [$idEstudiante, $idPeriodo, $idAdmin, $numeroRd, $listaSeccionesCsv],
            [
                'idMatricula' => '@p_id_matricula',
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }

    public function crearMatriculaPendiente(int $idEstudiante, int $idPeriodo, ?int $idAdmin, ?string $numeroRd): array
    {
        $res = Database::callStoredProcedure(
            'CALL sp_crear_matricula_pendiente(?, ?, ?, ?, @p_id_matricula, @p_nro_constancia, @p_codigo_resp, @p_mensaje)',
            [$idEstudiante, $idPeriodo, $idAdmin, $numeroRd],
            [
                'idMatricula' => '@p_id_matricula',
                'nroConstancia' => '@p_nro_constancia',
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }

    public function confirmarMatricula(int $idMatricula, ?int $idAdmin): array
    {
        $res = Database::callStoredProcedure(
            'CALL sp_confirmar_matricula(?, ?, @p_codigo_resp, @p_mensaje)',
            [$idMatricula, $idAdmin],
            [
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }

    public function inscribirCurso(int $idMatricula, int $idSeccion): array
    {
        $res = Database::callStoredProcedure(
            'CALL sp_inscribir_curso_matricula(?, ?, @p_codigo_resp, @p_mensaje)',
            [$idMatricula, $idSeccion],
            [
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }

    public function retirarCurso(int $idMatricula, int $idSeccion): array
    {
        $res = Database::callStoredProcedure(
            'CALL sp_retirar_curso(?, ?, @p_codigo_resp, @p_mensaje)',
            [$idMatricula, $idSeccion],
            [
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }

    public function anularMatricula(int $idMatricula, int $idAdmin, string $motivo): array
    {
        $res = Database::callStoredProcedure(
            'CALL sp_anular_matricula(?, ?, ?, @p_codigo_resp, @p_mensaje)',
            [$idMatricula, $idAdmin, $motivo],
            [
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }

    public function aplicarExcepcion(
        int $idMatricula,
        int $idAdmin,
        string $tipoExcepcion,
        string $numeroRd,
        string $descripcion,
        int $creditosExtra
    ): array {
        $res = Database::callStoredProcedure(
            'CALL sp_aplicar_excepcion(?, ?, ?, ?, ?, ?, @p_codigo_resp, @p_mensaje)',
            [$idMatricula, $idAdmin, $tipoExcepcion, $numeroRd, $descripcion, $creditosExtra],
            [
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }

    public function consultarMatricula(int $idEstudiante, int $idPeriodo): array
    {
        $res = Database::callStoredProcedure('CALL sp_consultar_matricula(?, ?)', [$idEstudiante, $idPeriodo]);
        return [
            'cabecera' => $res['result_sets'][0][0] ?? null,
            'cursos' => $res['result_sets'][1] ?? [],
        ];
    }

    public function ingresarCola(int $idEstudiante, int $idPeriodo): array
    {
        $res = Database::callStoredProcedure(
            'CALL sp_ingresar_cola_matricula(?, ?, @p_posicion, @p_codigo_resp, @p_mensaje)',
            [$idEstudiante, $idPeriodo],
            [
                'posicion' => '@p_posicion',
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }

    public function atenderSiguienteCola(int $idPeriodo, int $maxEnTurno = 30): array
    {
        $res = Database::callStoredProcedure(
            'CALL sp_atender_siguiente_cola(?, ?, @p_id_cola, @p_id_estudiante, @p_codigo_resp, @p_mensaje)',
            [$idPeriodo, $maxEnTurno],
            [
                'idCola' => '@p_id_cola',
                'idEstudiante' => '@p_id_estudiante',
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }

    public function finalizarTurnoCola(int $idCola): array
    {
        $res = Database::callStoredProcedure(
            'CALL sp_finalizar_turno_cola(?, @p_codigo_resp, @p_mensaje)',
            [$idCola],
            [
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }

    public function abandonarCola(int $idEstudiante, int $idPeriodo): array
    {
        $res = Database::callStoredProcedure(
            'CALL sp_abandonar_cola(?, ?, @p_codigo_resp, @p_mensaje)',
            [$idEstudiante, $idPeriodo],
            [
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }

    public function consultarPosicionCola(int $idEstudiante, int $idPeriodo): ?array
    {
        $res = Database::callStoredProcedure('CALL sp_consultar_posicion_cola(?, ?)', [$idEstudiante, $idPeriodo]);
        return $res['rows'][0] ?? null;
    }

    public function inscribirListaEspera(int $idMatricula, int $idSeccion): array
    {
        $res = Database::callStoredProcedure(
            'CALL sp_espera_inscribir(?, ?, @p_posicion, @p_codigo_resp, @p_mensaje)',
            [$idMatricula, $idSeccion],
            [
                'posicion' => '@p_posicion',
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }

    public function cancelarListaEspera(int $idMatricula, int $idSeccion): array
    {
        $res = Database::callStoredProcedure(
            'CALL sp_espera_cancelar(?, ?, @p_codigo_resp, @p_mensaje)',
            [$idMatricula, $idSeccion],
            [
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }

    public function promoverListaEspera(int $idSeccion): array
    {
        $res = Database::callStoredProcedure(
            'CALL sp_espera_promover(?, @p_procesados, @p_codigo_resp, @p_mensaje)',
            [$idSeccion],
            [
                'procesados' => '@p_procesados',
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }

    public function consultarListaEspera(int $idMatricula): array
    {
        $res = Database::callStoredProcedure('CALL sp_espera_consultar(?)', [$idMatricula]);
        return $res['rows'];
    }
}
