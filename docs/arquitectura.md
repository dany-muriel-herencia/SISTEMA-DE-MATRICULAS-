# Arquitectura Técnica - Sistema de Matrícula UNJBG

## 1. Visión General
El **Sistema de Matrícula UNJBG** está concebido bajo una arquitectura modular y en capas desacopladas (Clean Architecture / N-Layer Architecture) con una directiva clave de persistencia: **acceso a base de datos mediante SQL puro sin ningún ORM (Object-Relational Mapping)**.

## 2. Flujo de Capas y Responsabilidades

```text
               [ Cliente / Frontend ]
                         │  (HTTP / JSON)
                         ▼
        ┌──────────────────────────────────┐
        │       CAPA DE PRESENTACIÓN       │
        │ - Rutas (Express Router)         │
        │ - Middlewares (Auth, Error, Log) │
        │ - Controllers (Manejo Req / Res) │
        └────────────────┬─────────────────┘
                         │
                         ▼
        ┌──────────────────────────────────┐
        │        CAPA DE APLICACIÓN        │
        │ - Services / Use Cases           │
        │ - Orquestación de Transacciones  │
        │ - Reglas de Negocio del Sistema  │
        └────────────────┬─────────────────┘
                         │
                         ▼
        ┌──────────────────────────────────┐
        │       CAPA DE PERSISTENCIA       │
        │ - Repositorios por Módulo        │
        │ - Sentencias SQL Parametrizadas  │
        └────────────────┬─────────────────┘
                         │
                         ▼
        ┌──────────────────────────────────┐
        │      CAPA DE INFRAESTRUCTURA     │
        │ - Pool de Conexiones (mysql2)    │
        │ - QueryRunner & TransactionRunner│
        └────────────────┬─────────────────┘
                         │
                         ▼
             [ Base de Datos MySQL Local ]
```

## 3. Principio Anti-ORM y Consultas Parametrizadas
* Todas las operaciones de lectura y mutación de datos se realizan a través de SQL estándar nativo mediante el driver `mysql2/promise`.
* Se prohíbe la concatenación directa de cadenas para prevenir vulnerabilidades de **Inyección SQL**. Siempre se utilizan marcadores de posición `?`.
* Ejemplo de consulta en repositorio:
  ```javascript
  const [rows] = await QueryRunner.query(
    'SELECT * FROM matriculas WHERE estudiante_id = ? AND periodo_id = ?',
    [estudianteId, periodoId]
  );
  ```

## 4. Estructura Modular
El sistema se organiza en módulos de dominio cohesivos:
1. `auth`: Gestión de credenciales, tokens y sesiones.
2. `estudiantes`: Datos personales, legajo y estado académico.
3. `academico`: Carreras, planes de estudio, mallas curriculares y cursos.
4. `periodos`: Apertura, configuración y cierre de periodos académicos.
5. `oferta-academica`: Secciones, docentes asignados, horarios y gestión de aulas.
6. `matricula`: Proceso de pre-matrícula, selección de secciones, control de vacantes y confirmación.
7. `health`: Diagnóstico operativo del servidor y pool de conexiones.

## 5. Gestión Transaccional
Operaciones críticas que involucran múltiples tablas (como el registro de matrícula y descuento de vacantes) se ejecutan dentro del helper transaccional:
```javascript
await QueryRunner.withTransaction(async (connection) => {
  // 1. Bloquear y verificar vacante
  const [secciones] = await connection.execute('SELECT vacantes_disponibles FROM secciones WHERE id = ? FOR UPDATE', [seccionId]);
  // 2. Insertar matrícula y detalle
  // 3. Descontar vacante
});
```
