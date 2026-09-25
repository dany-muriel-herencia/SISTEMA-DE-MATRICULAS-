# Arquitectura actual

Frontend → Presentation (rutas, middleware, controladores) → Application/CasoDeUso → Dominio (entidades e interfaces) → Infrastructure (PDO).

El esquema canónico es database/schema/01_initial_schema.sql, base sgau. Los repositorios traducen snake_case de SQL a propiedades PHP. Composer mapea App al directorio backend/src y App\Dominio\Repositorios a Dominio/Repositories.

La identidad se obtiene de una sesión aleatoria persistida y del usuario actual en la base; los roles enviados por el cliente no se consideran. Las operaciones transaccionales residen en los repositorios de matrícula y programación. El frontend permite iniciar sesión, consultar secciones y horarios y registrar programación según el rol. Consulta la identidad con auth/me y los docentes y aulas con secciones/catalogos. Mantiene el token en sessionStorage y usa el servidor como autoridad de permisos.

Las entidades Usuario y Curso permiten ID 0 antes de persistir; el repositorio asigna el identificador generado. Estudiante reutiliza el ID de un usuario existente. Para contratos y pruebas consultar README y backend/docs/openapi.yaml.
