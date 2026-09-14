<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Reporte;

use InvalidArgumentException;

/**
 * CU-55: Exportar reportes en distintos formatos.
 * Actor: Administrador, Docente, Tesorería. Secundario: Sistema.
 *
 * Formatos soportados (sin dependencias externas):
 *   - json  → array listo para ApiResponse::success()
 *   - csv   → string con contenido CSV (separador coma)
 *   - html  → página HTML imprimible (Ctrl+P → Guardar como PDF)
 */
class ExportarReporte
{
    private const FORMATOS_VALIDOS = ['json', 'csv', 'html'];

    /**
     * Transforma el array de datos del reporte al formato solicitado.
     *
     * @param array  $datos   Datos del reporte (salida de cualquier CU de reporte).
     * @param string $formato json | csv | html
     * @param string $titulo  Título del reporte para la cabecera HTML o CSV.
     * @return string|array   String para csv/html; array para json.
     */
    public function exportar(array $datos, string $formato, string $titulo = 'Reporte'): string|array
    {
        $formato = strtolower(trim($formato));

        if (!in_array($formato, self::FORMATOS_VALIDOS, true)) {
            throw new InvalidArgumentException(
                "Formato inválido. Valores permitidos: " . implode(', ', self::FORMATOS_VALIDOS)
            );
        }

        return match ($formato) {
            'json' => $datos,
            'csv'  => $this->toCsv($datos, $titulo),
            'html' => $this->toHtml($datos, $titulo),
        };
    }

    // ──────────────────────────────────────────
    // CSV (PHP nativo — sin dependencias)
    // ──────────────────────────────────────────

    private function toCsv(array $datos, string $titulo): string
    {
        // Buscamos la clave de la lista principal dentro de los datos
        $filas = $this->extraerFilas($datos);

        if (empty($filas)) {
            return "# {$titulo}\n# Sin datos\n";
        }

        ob_start();
        $handle = fopen('php://output', 'w');

        // BOM UTF-8 para Excel
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabecera con título y metadatos
        fputcsv($handle, ["# {$titulo}"]);
        fputcsv($handle, ["# Generado: " . date('Y-m-d H:i:s')]);
        fputcsv($handle, []);

        // Columnas (keys del primer elemento)
        fputcsv($handle, array_keys($filas[0]));

        // Datos
        foreach ($filas as $fila) {
            fputcsv($handle, array_values($fila));
        }

        fclose($handle);
        return ob_get_clean();
    }

    // ──────────────────────────────────────────
    // HTML imprimible (sin dependencias)
    // ──────────────────────────────────────────

    private function toHtml(array $datos, string $titulo): string
    {
        $filas = $this->extraerFilas($datos);
        $fecha = date('d/m/Y H:i');

        $html  = "<!DOCTYPE html><html lang='es'><head>";
        $html .= "<meta charset='UTF-8'>";
        $html .= "<title>{$titulo}</title>";
        $html .= "<style>
            body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
            h1 { color: #1a237e; border-bottom: 2px solid #1a237e; padding-bottom: 8px; }
            .meta { color: #666; margin-bottom: 16px; font-size: 11px; }
            table { width: 100%; border-collapse: collapse; margin-top: 8px; }
            th { background: #1a237e; color: #fff; padding: 6px 8px; text-align: left; font-size: 11px; }
            td { padding: 5px 8px; border-bottom: 1px solid #e0e0e0; }
            tr:nth-child(even) td { background: #f5f5f5; }
            @media print {
                body { margin: 0; }
                th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            }
        </style></head><body>";

        $html .= "<h1>{$titulo}</h1>";
        $html .= "<div class='meta'>Generado el {$fecha}</div>";

        // Metadatos del reporte (campos escalares)
        $meta = array_filter($datos, fn($v) => !is_array($v));
        if (!empty($meta)) {
            $html .= "<table><tr>";
            foreach ($meta as $k => $v) {
                $html .= "<th>" . htmlspecialchars((string) $k) . "</th>";
            }
            $html .= "</tr><tr>";
            foreach ($meta as $v) {
                $html .= "<td>" . htmlspecialchars((string) $v) . "</td>";
            }
            $html .= "</tr></table><br>";
        }

        if (!empty($filas)) {
            $html .= "<table><thead><tr>";
            foreach (array_keys($filas[0]) as $col) {
                $html .= "<th>" . htmlspecialchars($col) . "</th>";
            }
            $html .= "</tr></thead><tbody>";
            foreach ($filas as $fila) {
                $html .= "<tr>";
                foreach ($fila as $celda) {
                    $html .= "<td>" . htmlspecialchars((string) ($celda ?? '')) . "</td>";
                }
                $html .= "</tr>";
            }
            $html .= "</tbody></table>";
        } else {
            $html .= "<p><em>Sin datos para este reporte.</em></p>";
        }

        $html .= "<script>window.onload = function() { window.print(); }</script>";
        $html .= "</body></html>";

        return $html;
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    /**
     * Extrae la lista de filas de dentro del array de datos del reporte.
     * Busca la primera clave cuyo valor es un array de arrays.
     */
    private function extraerFilas(array $datos): array
    {
        foreach ($datos as $valor) {
            if (is_array($valor) && !empty($valor) && is_array($valor[0])) {
                return $valor;
            }
        }
        return [];
    }
}
