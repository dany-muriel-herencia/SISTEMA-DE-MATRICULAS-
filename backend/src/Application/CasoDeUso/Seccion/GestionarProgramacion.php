<?php
declare(strict_types=1);
namespace App\Application\CasoDeUso\Seccion;

use App\Dominio\Repositorios\ProgramacionRepositorio;
use InvalidArgumentException;

final class GestionarProgramacion
{
    public function __construct(private ProgramacionRepositorio $repositorio) {}
    public function catalogos(): array { return $this->repositorio->catalogos(); }

    public static function entero(mixed $valor, string $campo): int
    {
        if ((!is_int($valor) && !is_string($valor)) ||
            filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) === false) {
            throw new InvalidArgumentException("$campo debe ser un entero positivo.");
        }
        return (int)$valor;
    }

    public function listar(array $filtros): array
    {
        return $this->repositorio->listar(self::entero($filtros['periodo_id'] ?? null, 'periodo_id'),
            isset($filtros['curso_id']) ? self::entero($filtros['curso_id'], 'curso_id') : null);
    }

    public function consultar(mixed $id): ?array
    {
        return $this->repositorio->consultar(self::entero($id, 'id_seccion'));
    }

    public function crearSeccion(array $datos): array
    {
        $validados = [];
        foreach (['id_curso', 'id_periodo', 'id_docente', 'vacantes'] as $campo) {
            $validados[$campo] = self::entero($datos[$campo] ?? null, $campo);
        }
        $codigo = $datos['codigo'] ?? null;
        if (!is_string($codigo) || trim($codigo) === '' || preg_match('/^.{1,30}$/usD', trim($codigo)) !== 1) {
            throw new InvalidArgumentException('codigo debe tener entre 1 y 30 caracteres.');
        }
        $validados['codigo'] = trim($codigo);
        return $this->repositorio->crearSeccion($validados);
    }

    public function crearHorario(mixed $id, array $datos): array
    {
        $id = self::entero($id, 'id_seccion');
        $validados = ['id_aula' => self::entero($datos['id_aula'] ?? null, 'id_aula')];
        foreach (['dia_semana' => ['LUNES','MARTES','MIERCOLES','JUEVES','VIERNES','SABADO','DOMINGO'],
            'modalidad' => ['PRESENCIAL','VIRTUAL','HIBRIDA']] as $campo => $permitidos) {
            if (!in_array($datos[$campo] ?? null, $permitidos, true)) {
                throw new InvalidArgumentException("$campo no es válido.");
            }
            $validados[$campo] = $datos[$campo];
        }
        foreach (['hora_inicio', 'hora_fin'] as $campo) {
            if (!is_string($datos[$campo] ?? null) || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9](?::[0-5][0-9])?$/D', $datos[$campo])) {
                throw new InvalidArgumentException("$campo debe usar HH:MM o HH:MM:SS.");
            }
            $validados[$campo] = strlen($datos[$campo]) === 5 ? $datos[$campo].':00' : $datos[$campo];
        }
        if ($validados['hora_inicio'] >= $validados['hora_fin']) {
            throw new InvalidArgumentException('La hora de fin debe ser posterior a la de inicio.');
        }
        return $this->repositorio->crearHorario($id, $validados);
    }
}
