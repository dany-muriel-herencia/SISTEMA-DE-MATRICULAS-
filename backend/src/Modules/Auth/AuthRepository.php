<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\Database;
use PDO;

/**
 * M01 & M02: Repositorio de Autenticación, Usuarios, Roles y Auditoría (ISSUE-01 Franklin, ISSUE-02 Dany).
 * 100% PDO Nativo - Cero ORM.
 */
final class AuthRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function buscarPorEmailOCodigo(string $identificador): ?array
    {
        $sql = <<<'SQL'
            SELECT u.id_usuario, u.codigo_usuario, u.email_institucional, u.password_hash,
                   u.nombres, u.apellidos, u.dni, u.id_rol, r.nombre_rol,
                   u.estado, u.intentos_fallidos, e.id_estudiante, e.codigo_universitario
            FROM usuarios u
            INNER JOIN roles r ON r.id_rol = u.id_rol
            LEFT JOIN estudiantes e ON e.id_usuario = u.id_usuario
            WHERE u.email_institucional = :email OR u.codigo_usuario = :codigo
            LIMIT 1
        SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':email' => $identificador,
            ':codigo' => $identificador,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function registrarIntentoFallido(int $idUsuario, int $intentosActuales): string
    {
        $nuevosIntentos = $intentosActuales + 1;
        $nuevoEstado = $nuevosIntentos >= 3 ? 'BLOQUEADO' : 'ACTIVO';

        $stmt = $this->pdo->prepare(
            'UPDATE usuarios SET intentos_fallidos = :intentos, estado = :estado WHERE id_usuario = :id'
        );
        $stmt->execute([
            ':intentos' => $nuevosIntentos,
            ':estado' => $nuevoEstado,
            ':id' => $idUsuario,
        ]);

        return $nuevoEstado;
    }

    public function registrarAccesoExitoso(int $idUsuario): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuarios SET intentos_fallidos = 0, ultimo_acceso = NOW() WHERE id_usuario = :id'
        );
        $stmt->execute([':id' => $idUsuario]);
    }

    public function registrarAuditoria(?int $idUsuario, string $modulo, string $accion, string $tabla, ?int $idRegistro, string $detalle): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle, ip_origen)
             VALUES (:id_usuario, :modulo, :accion, :tabla, :id_registro, :detalle, :ip)'
        );
        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':modulo' => $modulo,
            ':accion' => $accion,
            ':tabla' => $tabla,
            ':id_registro' => $idRegistro,
            ':detalle' => $detalle,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);
    }

    public function listarUsuarios(): array
    {
        $sql = <<<'SQL'
            SELECT u.id_usuario, u.codigo_usuario, u.email_institucional, u.nombres, u.apellidos,
                   u.dni, u.estado, u.intentos_fallidos, u.ultimo_acceso, r.id_rol, r.nombre_rol
            FROM usuarios u
            INNER JOIN roles r ON r.id_rol = u.id_rol
            ORDER BY u.id_usuario ASC
        SQL;
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function asignarRol(int $idUsuario, int $idRol, ?int $idAdmin): bool
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET id_rol = :id_rol WHERE id_usuario = :id_usuario');
        $ok = $stmt->execute([':id_rol' => $idRol, ':id_usuario' => $idUsuario]);
        if ($ok) {
            $this->registrarAuditoria($idAdmin, 'SEGURIDAD', 'ASIGNAR_ROL', 'usuarios', $idUsuario, "Cambio a id_rol={$idRol}");
        }
        return $ok;
    }

    public function listarAuditoria(int $limite = 100): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.id_auditoria, a.id_usuario, u.codigo_usuario, a.modulo, a.accion,
                    a.tabla_afectada, a.id_registro, a.detalle, a.ip_origen, a.fecha_hora
             FROM auditoria a
             LEFT JOIN usuarios u ON u.id_usuario = a.id_usuario
             ORDER BY a.id_auditoria DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
