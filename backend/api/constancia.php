<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function responder(int $codigo, array $contenido): never
{
    http_response_code($codigo);
    echo json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function obtenerConexion(): PDO
{
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3307';
    $base = getenv('DB_NAME') ?: 'bd_matricula_universitaria';
    $usuario = getenv('DB_USER') ?: 'root';
    $clave = getenv('DB_PASSWORD') ?: '';
    $dsn = "mysql:host={$host};port={$port};dbname={$base};charset=utf8mb4";

    return new PDO($dsn, $usuario, $clave, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function cargarConstancia(PDO $pdo, string $codigo, string $periodo): array
{
    $consulta = $pdo->prepare(
        'SELECT m.id_matricula, m.nro_constancia, m.estado, m.total_creditos,
                m.fecha_matricula, p.codigo_periodo, e.codigo_universitario,
                CONCAT(u.nombres, " ", u.apellidos) AS estudiante,
                ep.nombre AS escuela
           FROM matriculas m
           JOIN estudiantes e ON e.id_estudiante = m.id_estudiante
           JOIN usuarios u ON u.id_usuario = e.id_usuario
           JOIN escuelas_profesionales ep ON ep.id_escuela = e.id_escuela
           JOIN periodos_academicos p ON p.id_periodo = m.id_periodo
          WHERE e.codigo_universitario = :codigo
            AND (p.id_periodo = :periodo_id OR p.codigo_periodo = :periodo_codigo)
            AND m.estado = "CONFIRMADA"
          LIMIT 1'
    );
    $consulta->execute(['codigo' => $codigo, 'periodo_id' => $periodo, 'periodo_codigo' => $periodo]);
    $matricula = $consulta->fetch();
    if (!$matricula) {
        responder(404, ['error' => 'No existe una matrícula confirmada para ese estudiante y periodo.']);
    }

    $detalle = $pdo->prepare(
        'SELECT c.codigo_curso, c.nombre AS curso, c.creditos,
                s.codigo_seccion, a.codigo_aula, dm.nota_final
           FROM detalle_matricula dm
           JOIN secciones s ON s.id_seccion = dm.id_seccion
           JOIN cursos c ON c.id_curso = s.id_curso
           JOIN aulas a ON a.id_aula = s.id_aula
          WHERE dm.id_matricula = :matricula
            AND dm.estado IN ("INSCRITO", "CONVALIDADO")
          ORDER BY c.codigo_curso'
    );
    $detalle->execute(['matricula' => (int)$matricula['id_matricula']]);
    $cursos = array_map(static function (array $fila): array {
        return [
            'codigo' => (string)$fila['codigo_curso'],
            'curso' => (string)$fila['curso'],
            'creditos' => (int)$fila['creditos'],
            'seccion' => (string)$fila['codigo_seccion'],
            'aula' => (string)$fila['codigo_aula'],
            'notaFinal' => $fila['nota_final'] === null ? null : (string)$fila['nota_final'],
        ];
    }, $detalle->fetchAll());

    return [
        'numero' => (string)$matricula['nro_constancia'],
        'idMatricula' => (int)$matricula['id_matricula'],
        'estudiante' => (string)$matricula['estudiante'],
        'codigoEstudiante' => (string)$matricula['codigo_universitario'],
        'escuela' => (string)$matricula['escuela'],
        'periodo' => (string)$matricula['codigo_periodo'],
        'estado' => (string)$matricula['estado'],
        'creditos' => (int)$matricula['total_creditos'],
        'fechaEmision' => (string)$matricula['fecha_matricula'],
        'cursos' => $cursos,
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responder(405, ['error' => 'Método no permitido.']);
}

$codigo = trim((string)($_GET['codigo'] ?? ''));
$periodo = trim((string)($_GET['periodo'] ?? ''));
if ($codigo === '' || $periodo === '') {
    responder(400, ['error' => 'Indica el código del estudiante y el periodo.']);
}

try {
    $pdo = obtenerConexion();
    $datos = cargarConstancia($pdo, $codigo, $periodo);
    $contenidoCanonico = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $hash = hash('sha256', $contenidoCanonico);

    if (isset($_GET['verificar'])) {
        $hashRecibido = strtolower(trim((string)($_GET['hash'] ?? '')));
        $valido = preg_match('/^[a-f0-9]{64}$/', $hashRecibido) === 1 && hash_equals($hash, $hashRecibido);
        responder($valido ? 200 : 409, [
            'verificable' => $valido,
            'numero' => $datos['numero'],
            'hashSha256' => $hash,
            'mensaje' => $valido ? 'Constancia íntegra y coincidente con la matrícula actual.' : 'El hash no coincide con los datos actuales de la matrícula.',
        ]);
    }

    responder(200, [
        'algoritmo' => 'SHA-256',
        'hashSha256' => $hash,
        'urlVerificacion' => sprintf(
            '%s://%s%s?verificar=1&codigo=%s&periodo=%s&hash=%s',
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
            $_SERVER['HTTP_HOST'] ?? 'localhost',
            strtok($_SERVER['REQUEST_URI'], '?'),
            rawurlencode($codigo), rawurlencode($periodo), $hash
        ),
        'constancia' => $datos,
    ]);
} catch (Throwable $error) {
    error_log('[CONSTANCIA] ' . $error->getMessage());
    responder(503, ['error' => 'No se pudo consultar la constancia. Revisa la conexión PDO y la base de datos.']);
}
