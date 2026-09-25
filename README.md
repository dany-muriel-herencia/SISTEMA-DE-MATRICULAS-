# Sistema de Matrículas UNJBG

Backend PHP 8.2+ con PDO y MySQL/MariaDB. Frontend HTML, CSS y JavaScript para consultar el estado de la API. No requiere Node.js para ejecutarse.

## Instalación local

1. Instalar dependencias: desde backend ejecutar composer install y composer dump-autoload.
2. Copiar backend/.env.example a backend/.env y configurar la conexión. El nombre predeterminado es sgau.
3. En una instalación nueva, importar database/schema/01_initial_schema.sql. No volver a importar sobre tablas existentes.
4. Opcional: importar database/seeds/001_seed_initial_data.sql. Son datos académicos de ejemplo; no incluye usuarios ni contraseñas predeterminadas.
5. En XAMPP habilitar Apache (mod_rewrite y AllowOverride) y MySQL. Abrir frontend/index.html por HTTP desde la carpeta del proyecto, no con file://.
6. La API está en backend/public/api. Para comprobarla: backend/public/api/health.

La migración 002 se aplica solo sobre la base seleccionada con USE sgau; puede ejecutarse después del esquema actual sin duplicar la columna. La migración 001 usa SOURCE con una ruta relativa a la raíz del repositorio y requiere el cliente mysql.

## Primera cuenta

No hay registro público de administradores. Desde backend, ejecutar php bin/crear_admin.php y escribir nombre, correo y contraseña cuando se soliciten. La contraseña se guarda con password_hash; usar la consola en un equipo privado.

## API y permisos

POST /api/auth/login recibe email y password y devuelve un token aleatorio asociado a una sesión persistida. Enviar Authorization: Bearer TOKEN en las rutas protegidas. POST /api/auth/logout revoca la sesión. La vigencia se configura con SESSION_EXPIRATION.

Usuarios: ADMIN. Estudiantes: ADMIN o COORDINADOR. Lectura de cursos y periodos: usuarios autenticados. Creación de cursos: ADMIN o COORDINADOR. Matrículas: personal autorizado o el propio estudiante; no se acepta un rol enviado en cabeceras.

Los contratos JSON vigentes se describen en backend/docs/openapi.yaml. Las contraseñas nunca se incluyen en las respuestas.

## Matrículas

El registro valida periodo abierto (incluye el día final), secciones, créditos, vacantes, duplicados, prerrequisitos y cruces de horario. Los prerrequisitos aprobados se consultan en detalle_matricula.estado = APROBADO de matrículas no anuladas. El sistema todavía no incluye una interfaz ni un endpoint para registrar calificaciones: no inferir aprobaciones desde el promedio.

El registro bloquea al estudiante y las secciones en MySQL dentro de una transacción. La anulación devuelve las vacantes de detalles MATRICULADO y los marca ANULADO en la misma transacción.

## Verificación

Desde backend: php tests/run_tests.php. Usa SQLite en memoria, carga las clases y rutas y prueba repositorios, sesiones, matrícula y rollback. No modifica MySQL. El bloqueo FOR UPDATE y las migraciones deben verificarse también en una base MySQL/MariaDB de pruebas antes de desplegar.

La cola de archivos reserva cada trabajo como PROCESSING con bloqueo exclusivo. Un trabajo interrumpido permanece en ese estado y requiere revisión antes de reintentarlo; no hay reintentos automáticos. El worker es una utilidad interna y no está integrado en las rutas HTTP.
