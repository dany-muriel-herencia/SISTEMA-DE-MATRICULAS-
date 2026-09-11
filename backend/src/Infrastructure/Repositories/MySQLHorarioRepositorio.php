<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Horario;
use App\Dominio\Repositorios\HorarioRepositorio;
use DateTimeImmutable;

final class MySQLHorarioRepositorio extends MySQLRepositorioBase implements HorarioRepositorio
{
    public function buscarPorId(int $id): ?Horario { $r = $this->one('SELECT * FROM horario WHERE id_horario = :id', [':id' => $id]); return $r ? $this->map($r) : null; }
    public function listar(): array { return array_map(fn(array $r): Horario => $this->map($r), $this->all('SELECT * FROM horario ORDER BY dia_semana, hora_inicio')); }
    public function guardar(Horario $h): void { $this->unsupported('Horario requiere id_seccion e id_aula, pero la entidad no los expone.'); }
    public function actualizar(Horario $h): void { $this->exec('UPDATE horario SET dia_semana = :dia, hora_inicio = :inicio, hora_fin = :fin, modalidad = :modalidad WHERE id_horario = :id', [':id' => $h->getIdHorario(), ':dia' => $h->getDiaSemana(), ':inicio' => $h->getHoraInicio()->format('H:i:s'), ':fin' => $h->getHoraFin()->format('H:i:s'), ':modalidad' => $h->getModalidad()]); }
    public function eliminar(int $id): void { $this->exec('DELETE FROM horario WHERE id_horario = :id', [':id' => $id]); }
    private function map(array $r): Horario { return new Horario((int) $r['id_horario'], (string) $r['dia_semana'], new DateTimeImmutable('1970-01-01 ' . $r['hora_inicio']), new DateTimeImmutable('1970-01-01 ' . $r['hora_fin']), (string) $r['modalidad']); }
}
