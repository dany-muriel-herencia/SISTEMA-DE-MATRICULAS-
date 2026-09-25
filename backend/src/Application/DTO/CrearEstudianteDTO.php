<?php
declare(strict_types=1);
namespace App\Application\DTO;
final class CrearEstudianteDTO {
    public function __construct(public readonly int $usuarioId,public readonly string $codigo,public readonly string $dni,public readonly \DateTimeImmutable $nacimiento,public readonly \DateTimeImmutable $ingreso) {
        if($usuarioId<=0 || trim($codigo)==='' || !preg_match('/^\d{8}$/D',$dni) || $nacimiento >= $ingreso) throw new \InvalidArgumentException('Datos del estudiante inválidos.');
    }
    private static function fecha(string $value): \DateTimeImmutable {
        $d=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);
        if(!$d || $d->format('Y-m-d')!==$value) throw new \InvalidArgumentException('Fecha inválida: use AAAA-MM-DD.');
        return $d;
    }
    public static function fromArray(array $d): self {
        return new self((int)($d['usuario_id']??0),(string)($d['codigo_universitario']??''),(string)($d['dni']??''),self::fecha((string)($d['fecha_nacimiento']??'')),self::fecha((string)($d['fecha_ingreso']??'')));
    }
}
