<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\DetalleMatricula;
use App\Dominio\Entidades\Matricula;
use App\Dominio\Repositorios\MatriculaRepositorio;

final class MySQLMatriculaRepositorio
    extends MySQLRepositorioBase
    implements MatriculaRepositorio
{
    public function buscarPorId(int $idMatricula): ?Matricula
    {
        $sql = "
            SELECT
                id_matricula,
                id_estudiante,
                id_periodo,
                codigo_matricula,
                fecha_matricula,
                estado,
                total_creditos
            FROM matricula
            WHERE id_matricula = :id_matricula
            LIMIT 1
        ";

        $resultado = $this->one($sql, [
            ':id_matricula' => $idMatricula
        ]);

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function buscarPorEstudiante(int $idEstudiante): array
    {
        $sql = "
            SELECT
                id_matricula,
                id_estudiante,
                id_periodo,
                codigo_matricula,
                fecha_matricula,
                estado,
                total_creditos
            FROM matricula
            WHERE id_estudiante = :id_estudiante
            ORDER BY fecha_matricula DESC
        ";

        $resultados = $this->all($sql, [
            ':id_estudiante' => $idEstudiante
        ]);

        return array_map(
            fn(array $fila): Matricula => $this->map($fila),
            $resultados
        );
    }

    public function buscarPorPeriodo(int $idPeriodo): array
    {
        $sql = "
            SELECT
                id_matricula,
                id_estudiante,
                id_periodo,
                codigo_matricula,
                fecha_matricula,
                estado,
                total_creditos
            FROM matricula
            WHERE id_periodo = :id_periodo
            ORDER BY fecha_matricula DESC
        ";

        $resultados = $this->all($sql, [
            ':id_periodo' => $idPeriodo
        ]);

        return array_map(
            fn(array $fila): Matricula => $this->map($fila),
            $resultados
        );
    }

    public function buscarPorCodigo(string $codigo): ?Matricula
    {
        $resultado = $this->one(
            'SELECT id_matricula, id_estudiante, id_periodo, codigo_matricula, fecha_matricula, estado, total_creditos
             FROM matricula WHERE codigo_matricula = :codigo LIMIT 1',
            [':codigo' => $codigo]
        );

        return $resultado ? $this->map($resultado) : null;
    }

    public function listarPorEstudiante(int $idEstudiante): array
    {
        return $this->buscarPorEstudiante($idEstudiante);
    }

    public function guardar(Matricula $matricula): void
    {
        $sql = "
            INSERT INTO matricula (
                id_estudiante,
                id_periodo,
                codigo_matricula,
                fecha_matricula,
                estado,
                total_creditos
            )
            VALUES (
                :id_estudiante,
                :id_periodo,
                :codigo_matricula,
                :fecha_matricula,
                :estado,
                :total_creditos
            )
        ";

        $this->exec($sql, [
            ':id_estudiante' => $matricula->getIdEstudiante(),
            ':id_periodo' => $matricula->getIdPeriodo(),
            ':codigo_matricula' => (string) $matricula->getCodigoMatricula(),
            ':fecha_matricula' => $matricula->getFechaMatricula()->format('Y-m-d'),
            ':estado' => $matricula->getEstado(),
            ':total_creditos' => $matricula->getTotalCreditos()
        ]);
    }

    public function actualizar(Matricula $matricula): void
    {
        $sql = "
            UPDATE matricula
            SET
                id_estudiante = :id_estudiante,
                id_periodo = :id_periodo,
                codigo_matricula = :codigo_matricula,
                fecha_matricula = :fecha_matricula,
                estado = :estado,
                total_creditos = :total_creditos
            WHERE id_matricula = :id_matricula
        ";

        $this->exec($sql, [
            ':id_matricula' => $matricula->getIdMatricula(),
            ':id_estudiante' => $matricula->getIdEstudiante(),
            ':id_periodo' => $matricula->getIdPeriodo(),
            ':codigo_matricula' => (string) $matricula->getCodigoMatricula(),
            ':fecha_matricula' => $matricula->getFechaMatricula()->format('Y-m-d'),
            ':estado' => $matricula->getEstado(),
            ':total_creditos' => $matricula->getTotalCreditos()
        ]);
    }

    /** @param DetalleMatricula[] $detalles */
    public function registrarConDetalles(Matricula $matricula, array $detalles): int
    {
        $this->db->beginTransaction();

            try {
                $this->exec(
                    "INSERT INTO matricula (id_estudiante, id_periodo, codigo_matricula, fecha_matricula, estado, total_creditos)
                     VALUES (:id_estudiante, :id_periodo, :codigo_matricula, :fecha_matricula, :estado, :total_creditos)",
                    [
                        ':id_estudiante' => $matricula->getIdEstudiante(),
                        ':id_periodo' => $matricula->getIdPeriodo(),
                        ':codigo_matricula' => (string) $matricula->getCodigoMatricula(),
                        ':fecha_matricula' => $matricula->getFechaMatricula()->format('Y-m-d'),
                        ':estado' => $matricula->getEstado(),
                        ':total_creditos' => $matricula->getTotalCreditos(),
                    ]
                );

                $matriculaId = $this->generatedId();

                foreach ($detalles as $detalle) {
                    $detalle->setIdMatricula($matriculaId);

                    $cupo = $this->one(
                        'SELECT vacantes_disponibles FROM seccion WHERE id_seccion = :id_seccion FOR UPDATE',
                        [':id_seccion' => $detalle->getIdSeccion()]
                    );
                    if (!$cupo || (int) $cupo['vacantes_disponibles'] <= 0) {
                        throw new \DomainException('No hay vacantes disponibles para una de las secciones seleccionadas.');
                    }

                    $this->exec(
                        'INSERT INTO detalle_matricula (id_matricula, id_seccion, estado)
                         VALUES (:id_matricula, :id_seccion, :estado)',
                        [
                            ':id_matricula' => $matriculaId,
                            ':id_seccion' => $detalle->getIdSeccion(),
                            ':estado' => $detalle->getEstado(),
                        ]
                    );

                    $this->exec(
                        'UPDATE seccion SET vacantes_disponibles = vacantes_disponibles - 1
                         WHERE id_seccion = :id_seccion AND vacantes_disponibles > 0',
                        [':id_seccion' => $detalle->getIdSeccion()]
                    );
                }

                $this->db->commit();
                return $matriculaId;
            } catch (\Throwable $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                throw $e;
            }
    }

    private function map(array $fila): Matricula
    {
        $matricula = new Matricula(
            (int) $fila['id_matricula'],
            (int) $fila['id_estudiante'],
            (int) $fila['id_periodo'],
            new \DateTimeImmutable($fila['fecha_matricula']),
            (string) $fila['estado'],
            (int) $fila['total_creditos']
        );

        if (!empty($fila['codigo_matricula'])) {
            $matricula->setCodigoMatricula(new \App\Dominio\ValueObjects\CodigoMatricula((string) $fila['codigo_matricula']));
        }

        return $matricula;
    }
}