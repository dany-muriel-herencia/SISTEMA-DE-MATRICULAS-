# 📊 TABLERO KANBAN — SISTEMA DE GESTIÓN ACADÉMICA Y MATRÍCULA (SGAU - UNJBG)

**Curso:** Base de Datos II / Ingeniería de Software  
**Fecha de Entrega:** Jueves 17 de Diciembre (21:00 hrs) | **Sustentación:** Viernes 18 de Diciembre (08:40 hrs)  
**Equipo de Proyecto:**
- 🛡️ **Dany Ronaldo Muriel Herencia** (`dany-muriel-herencia`) — **Product Owner, QA Lead & ÚNICO REVISOR AUTORIZADO DE PULL REQUESTS (PRs)**
- ⚙️ **Franklin Cristofier Anahua Cahuachia** (`franklin-anahua`) — Backend Engineer & Database Specialist
- 🎨 **Jhosep Rodrigo Arocutipa Mamani** (`jhosep-arocutipa`) — Frontend Developer & UI/UX Specialist

---

## 🚫 POLÍTICA ESTRICTA DE DESARROLLO: CERO ORM (0% ORM)
> **Directiva del Docente (Máximo 08 puntos en implementación web):**
> - Prohibido el uso de ORMs (Eloquent, TypeORM, Prisma, Hibernate, etc.).
> - Toda persistencia se realiza mediante **consultas nativas / PDO Prepared Statements** y llamadas a **Procedimientos Almacenados (Stored Procedures)**:
>   `CALL sp_registrar_matricula_completa(?, ?, ?, ?, @id, @res, @msg);`
> - Todo Pull Request que incluya dependencias de ORM será **rechazado de inmediato** en la etapa de revisión por **Dany Muriel**.

---

## 🔄 FLUJO DE TRABAJO EN EL TABLERO (WORKFLOW)

```
┌──────────────┐     ┌──────────────┐     ┌───────────────┐     ┌────────────────────────┐     ┌──────────────┐
│  1. BACKLOG  │ ──> │  2. TO DO    │ ──> │ 3. IN PROGRESS│ ──> │ 4. IN REVIEW (PR)      │ ──> │   5. DONE    │
│ (Pendientes) │     │(Sprint List) │     │ (Desarrollo)  │     │ Revisión Exclusiva por │     │ (Aprobado y  │
└──────────────┘     └──────────────┘     └───────────────┘     │    DANY MURIEL         │     │  En Main)    │
                                                                │  [Cero ORM + Tests]    │     └──────────────┘
                                                                └────────────────────────┘
```

---

## 📋 TABLERO KANBAN ACTIVO

| 1. BACKLOG | 2. TO DO (Sprint Activo) | 3. IN PROGRESS | 4. IN REVIEW (PR Review - Dany) | 5. DONE (Aprobado) |
|---|---|---|---|---|
| **ISSUE-11: Rectificaciones**<br>**Módulo:** M10<br>**CU:** CU-64 a CU-73<br>**Dev:** Franklin<br>**Revisor:** Dany<br>**Est:** 8 pts<br>*Simulación de horario y actas de rectificación.* | **ISSUE-07: Centro Matrícula Admin**<br>**Módulo:** M06<br>**CU:** CU-37 a CU-46<br>**Dev:** Franklin<br>**Revisor:** Dany<br>**Est:** 8 pts<br>*Monitoreo en vivo y excepciones.* | **ISSUE-01: Autenticación & JWT**<br>**Módulo:** M01<br>**CU:** CU-01 a CU-03<br>**Dev:** Franklin<br>**Revisor:** Dany<br>**Est:** 5 pts<br>*Login nativo PDO + BCrypt.* | **ISSUE-04: Malla Curricular**<br>**Módulo:** M04<br>**CU:** CU-12 a CU-18<br>**Dev:** Dany<br>**Revisor:** Dany (QA)<br>**Est:** 8 pts<br>*PR #3: Vistas y grafos SQL.* | **BD-01: Base de Datos v4.0**<br>**Módulo:** Base Datos<br>**Dev:** Dany & Franklin<br>**Estado:** Merged<br>*10 Tablas + SPs ACID sin triggers ni ORM.* |
| **ISSUE-14: Pruebas de Carga**<br>**Módulo:** QA / Rendimiento<br>**CU:** Integral M05<br>**Dev:** Dany<br>**Revisor:** Dany (QA)<br>**Est:** 5 pts<br>*Test concurrencia: 100 alumnos vs 30 vacantes.* | **ISSUE-08: Horarios y Aulas**<br>**Módulo:** M07<br>**CU:** CU-32 a CU-38<br>**Dev:** Franklin<br>**Revisor:** Dany<br>**Est:** 8 pts<br>*Matriz semanal y cruce de aulas.* | **ISSUE-02: Roles y Auditoría**<br>**Módulo:** M02<br>**CU:** CU-04 a CU-07<br>**Dev:** Dany<br>**Revisor:** Dany (QA)<br>**Est:** 5 pts<br>*RBAC nativo y bitácora.* | **ISSUE-06: Constancia QR**<br>**Módulo:** M05<br>**CU:** CU-30, CU-31<br>**Dev:** Jhosep<br>**Revisor:** Dany<br>**Est:** 5 pts<br>*PR #4: PDF + Hash SHA-256.* | **ARCH-01: Arquitectura Base**<br>**Módulo:** Infraestructura<br>**Dev:** Dany<br>**Estado:** Merged<br>*Estructura modular, apps React y Gateway.* |
| | **ISSUE-09: Pagos y Tesorería**<br>**Módulo:** M08<br>**CU:** CU-43 a CU-48<br>**Dev:** Franklin<br>**Revisor:** Dany<br>**Est:** 8 pts<br>*Caja ventanilla y boletas QR.* | **ISSUE-03: Expediente Alumno**<br>**Módulo:** M03<br>**CU:** CU-08 a CU-11<br>**Dev:** Franklin<br>**Revisor:** Dany<br>**Est:** 8 pts<br>*Kardex y código UNJBG.* | | **DOC-01: Especificación CUs**<br>**Módulo:** Documentación<br>**Dev:** Equipo<br>**Estado:** Merged<br>*Mapeo 73 Casos de Uso y Clases UML.* |
| | **ISSUE-10: Reportes y Actas**<br>**Módulo:** M09<br>**CU:** CU-49 a CU-58<br>**Dev:** Jhosep<br>**Revisor:** Dany<br>**Est:** 8 pts<br>*Padrón y actas oficiales con hash.* | **ISSUE-05: Matrícula Transaccional**<br>**Módulo:** M05<br>**CU:** CU-23 a CU-29<br>**Dev:** Dany<br>**Revisor:** Dany (QA)<br>**Est:** 13 pts<br>*`sp_registrar_matricula_completa`.* | | |
| | | **ISSUE-12: UI Portal Alumno**<br>**Módulo:** Frontend M05<br>**CU:** Mockups Alumno<br>**Dev:** Jhosep<br>**Revisor:** Dany<br>**Est:** 8 pts<br>*Horario semanal interactivo React.* | | |
| | | **ISSUE-13: UI Backoffice Admin**<br>**Módulo:** Frontend Admin<br>**CU:** Mockups Admin<br>**Dev:** Jhosep<br>**Revisor:** Dany<br>**Est:** 8 pts<br>*Paneles administrativos React.* | | |

---

## 🗂️ DETALLE DE TARJETAS POR COLUMNA

### 1. Columna: BACKLOG (Próximos Sprints)
- **[ISSUE-11] Rectificaciones y Modificaciones Académicas (M10)**
  - *Responsable:* Franklin Anahua | *Revisor:* Dany Muriel
  - *Estimación:* 8 Story Points
  - *Alcance:* Implementar `sp_solicitar_rectificacion` y `sp_aprobar_rectificacion` (altas, bajas y traslados de sección dentro del plazo reglamentario).
- **[ISSUE-14] Pruebas de Carga y Concurrencia de Matrícula (QA)**
  - *Responsable:* Dany Muriel | *Revisor:* Dany Muriel (QA)
  - *Estimación:* 5 Story Points
  - *Alcance:* Simulación concurrente de 100 estudiantes intentando matricularse en 30 vacantes. Verificación de cero sobrecupos y consistencia ACID.

---

### 2. Columna: TO DO (Sprint Activo - Pendientes Inmediatos)
- **[ISSUE-07] Centro de Control de Matrícula en Vivo (M06)**
  - *Responsable:* Franklin Anahua | *Revisor:* Dany Muriel | *Est:* 8 pts
  - *Alcance:* Cola de espera en tiempo real, priorización y bypass de matrícula manual por excepción.
- **[ISSUE-08] Gestión de Horarios, Aulas y Detección de Cruces (M07)**
  - *Responsable:* Franklin Anahua | *Revisor:* Dany Muriel | *Est:* 8 pts
  - *Alcance:* Matriz de ocupabilidad de aulas físicas/labs y validación de colisiones docentes y de aula.
- **[ISSUE-09] Módulo de Pagos, Caja y Tesorería (M08)**
  - *Responsable:* Franklin Anahua | *Revisor:* Dany Muriel | *Est:* 8 pts
  - *Alcance:* Catálogo TUPA, cobro en ventanilla y boletas QR mediante `sp_registrar_pago`.
- **[ISSUE-10] Centro de Analítica, Nóminas y Actas con Hash (M09)**
  - *Responsable:* Jhosep Arocutipa | *Revisor:* Dany Muriel | *Est:* 8 pts
  - *Alcance:* Nómina oficial por sección y actas de evaluación firmadas digitalmente con hash SHA-256.

---

### 3. Columna: IN PROGRESS (En Desarrollo Activo)
- **[ISSUE-01] Autenticación Institucional y JWT Seguro (M01)**
  - *Responsable:* Franklin Anahua | *Revisor:* Dany Muriel | *Est:* 5 pts
  - *Estado:* En codificación de `sp_validar_login` con PDO nativo y BCrypt (0% ORM).
- **[ISSUE-02] Seguridad, Roles (RBAC) y Bitácora de Auditoría (M02)**
  - *Responsable:* Dany Muriel | *Revisor:* Dany Muriel (QA) | *Est:* 5 pts
  - *Estado:* Implementación de permisos por rol y tabla inmutable `tb_auditoria`.
- **[ISSUE-03] Expediente de Estudiante, Código Universitario y Kardex (M03)**
  - *Responsable:* Franklin Anahua | *Revisor:* Dany Muriel | *Est:* 8 pts
  - *Estado:* Creación del algoritmo `sp_generar_codigo_estudiante` y cálculo de PPA.
- **[ISSUE-05] Motor Transaccional de Matrícula Alumno (M05)**
  - *Responsable:* Dany Muriel | *Revisor:* Dany Muriel (QA) | *Est:* 13 pts
  - *Estado:* Programación del SP transaccional `sp_registrar_matricula_completa` con bloqueos pesimistas.
- **[ISSUE-12] Frontend SPA: Portal Estudiante en React (M05)**
  - *Responsable:* Jhosep Arocutipa | *Revisor:* Dany Muriel | *Est:* 8 pts
  - *Estado:* Horario interactivo semanal, semáforo de cruces y selector de cursos.
- **[ISSUE-13] Frontend SPA: Backoffice Administrativo en React**
  - *Responsable:* Jhosep Arocutipa | *Revisor:* Dany Muriel | *Est:* 8 pts
  - *Estado:* Dashboards modulares para administración, tesorería y gestión de aulas.

---

### 4. Columna: IN REVIEW / PR APPROVAL (Revisión Exclusiva por Dany Muriel)
- **[ISSUE-04] Gestión Curricular y Prerrequisitos (M04)**
  - *Autor:* Dany Muriel | *Revisor:* Dany Muriel (Lead) | *Est:* 8 pts
  - *PR #3:* Vista `vw_malla_curricular` y validación de prerrequisitos sin ciclos infinitos.
- **[ISSUE-06] Emisión de Constancia de Matrícula en PDF con QR (M05)**
  - *Autor:* Jhosep Arocutipa | *Revisor:* Dany Muriel | *Est:* 5 pts
  - *PR #4:* Generación de constancia con código QR y Hash SHA-256 verificado en base de datos.
  > 🔍 **Criterios de Aprobación de PR por Dany:**
  > 1. Verificación obligatoria: **CERO librerías ORM**.
  > 2. Uso correcto de `PDO::prepare` o llamadas `CALL sp_*`.
  > 3. Sin cruces de horario ni fallas de integridad referencial.

---

### 5. Columna: DONE (Completado y Fusionado en Rama Principal)
- **[BD-01] Esquema y Procedimientos Almacenados de Base de Datos v4.0**
  - Script SQL final: `database/releases/bd_matricula_universitaria_v4.sql`.
  - DDL de 10 tablas relacionales, índices, vistas y SPs transaccionales sin triggers.
- **[ARCH-01] Arquitectura de Monorepo y Microservicios Modulares**
  - Apps React (Portal Estudiante y Portal Admin), Gateway y Database Client configurados.
- **[DOC-01] Especificación Completa de los 73 Casos de Uso y Diagrama de Clases UML**
  - Trazabilidad documental de CU-01 a CU-73 y documentación de clases de dominio.

---

## 👥 MATRIZ DE DISTRIBUCIÓN DE REVISIONES Y APROBACIÓN DE PULL REQUESTS (PR)

En el flujo de integración continua, cada Pull Request pasa por una **Revisión Técnica Especializada (Peer Review)** y concluye obligatoriamente con la **Aprobación Final y Merge** a cargo de **Dany Muriel**.

| Integrante | Rol en el Proyecto | Tipo de Revisión que Ejecuta | Componentes y Módulos Específicos que Revisa | Criterios de Aceptación a Validar |
|---|---|---|---|---|
| **Dany Ronaldo Muriel Herencia**<br>`@dany-muriel-herencia` | **Tech Lead, Product Owner & QA General** | **Aprobación Final Obligatoria (Gatekeeper) & Merge** | • **Todos los PRs del Proyecto (100%)**<br>• M02: Seguridad, Roles y Auditoría<br>• M04: Malla y Prerrequisitos<br>• M05: Motor Transaccional de Matrícula<br>• Arquitectura Monorepo y Gateway | • **Cero ORM estricto** (Rechazo automático si hay ORM).<br>• Transacciones ACID y bloqueos pesimistas (`FOR UPDATE`).<br>• Manejo de concurrencia y control de vacantes.<br>• Integridad referencial y cumplimiento de la rúbrica docente. |
| **Franklin Cristofier Anahua Cahuachia**<br>`@franklin-anahua` | **Backend & Database Specialist** | **Revisión Técnica de Backend y Base de Datos (Peer Review)** | • Procedimientos Almacenados (`sp_*`)<br>• Vistas e Índices SQL en MySQL 8.0<br>• Scripts DDL y DML de base de datos<br>• M01: Autenticación nativa y JWT<br>• M03: Kardex y generación de código<br>• M07: Detección de cruces de horario<br>• M08: Transacciones de caja y tesorería<br>• M10: Procedimientos de rectificación | • Correcta sintaxis SQL y optimización de índices.<br>• Integración limpia con sentencias preparadas PDO nativas.<br>• Ausencia de llamadas directas que omitan los SPs.<br>• Parámetros de entrada y salida (`IN / OUT`) consistentes.<br>• Manejo de errores SQL (`SQLEXCEPTION`, Rollback). |
| **Jhosep Rodrigo Arocutipa Mamani**<br>`@jhosep-arocutipa` | **Frontend Developer & UI/UX Specialist** | **Revisión Técnica de Interfaces y Reportes (Peer Review)** | • Componentes React 18 y Vite<br>• Estilos Tailwind CSS y diseño responsivo<br>• Fidelidad visual con los 10 Mockups oficiales<br>• M05: Horario semanal interactivo del alumno<br>• M06: Centro de control y cola en vivo Admin<br>• M09: Generador de constancias PDF y QR<br>• M09: Actas y reportes con hash SHA-256 | • Fidelidad estética con los mockups del sistema universitario.<br>• Reactividad y feedback visual (semáforos de cruce, modales).<br>• Integración correcta de llamadas API REST hacia el backend.<br>• Renderizado correcto de PDFs y lectura válida de códigos QR.<br>• Limpieza de código frontend y estado global (Zustand). |

---

### 🔀 Matriz Cruzada de Revisión por Tarea e Issue

| Issue / Tarea | Desarrollador (Autor) | Primer Revisor (Revisión Técnica / Peer Review) | Revisor Final y Aprobador Autorizado (Merge) |
|---|---|---|---|
| **ISSUE-01: Autenticación & JWT (M01)** | Franklin Anahua | Jhosep Arocutipa *(Valida integración con UI Login)* | **Dany Muriel** *(Valida seguridad, cero ORM y tokens)* |
| **ISSUE-02: Roles y Auditoría (M02)** | Dany Muriel | Franklin Anahua *(Valida auditoría e integridad SQL)* | **Dany Muriel (QA Lead)** *(Aprobación de arquitectura)* |
| **ISSUE-03: Expediente Alumno & Kardex (M03)** | Franklin Anahua | Dany Muriel *(Valida fórmulas de PPA y créditos)* | **Dany Muriel** *(Aprobación final)* |
| **ISSUE-04: Malla Curricular & Grafo (M04)** | Dany Muriel | Franklin Anahua *(Valida vistas y no circularidad)* | **Dany Muriel (QA Lead)** *(Aprobación de arquitectura)* |
| **ISSUE-05: Matrícula Transaccional (M05)** | Dany Muriel | Franklin Anahua *(Valida concurrencia y SPs ACID)* | **Dany Muriel (QA Lead)** *(Aprobación crítica)* |
| **ISSUE-06: Constancia Matrícula QR (M05)** | Jhosep Arocutipa | Franklin Anahua *(Valida querys nativas de datos)* | **Dany Muriel** *(Valida hash SHA-256 y firma digital)* |
| **ISSUE-07: Matrícula Admin en Vivo (M06)** | Franklin Anahua | Jhosep Arocutipa *(Valida eventos y visualización)* | **Dany Muriel** *(Valida bypass de excepciones)* |
| **ISSUE-08: Horarios y Cruces de Aula (M07)** | Franklin Anahua | Dany Muriel *(Valida algoritmo detector de cruces)* | **Dany Muriel** *(Aprobación final)* |
| **ISSUE-09: Pagos y Tesorería (M08)** | Franklin Anahua | Dany Muriel *(Valida atomicidad en caja y deudas)* | **Dany Muriel** *(Aprobación final)* |
| **ISSUE-10: Reportes y Actas con Hash (M09)** | Jhosep Arocutipa | Franklin Anahua *(Valida consultas SQL de nómina)* | **Dany Muriel** *(Valida hash criptográfico y actas)* |
| **ISSUE-11: Rectificaciones (M10)** | Franklin Anahua | Dany Muriel *(Valida SPs de altas/bajas de cursos)* | **Dany Muriel** *(Aprobación final)* |
| **ISSUE-12: UI Portal Alumno en React** | Jhosep Arocutipa | Dany Muriel *(Valida experiencia y semáforo cruces)* | **Dany Muriel** *(Aprobación final)* |
| **ISSUE-13: UI Backoffice Admin en React** | Jhosep Arocutipa | Franklin Anahua *(Valida formularios y endpoints)* | **Dany Muriel** *(Aprobación final)* |
| **ISSUE-14: Pruebas de Carga Concurrente (QA)** | Dany Muriel | Franklin Anahua & Jhosep Arocutipa *(Revisión conjunta)* | **Dany Muriel (QA Lead)** *(Certificación final)* |

---

## 📈 RESUMEN DE CARGA DE TRABAJO (STORY POINTS)

| Integrante | Rol Principal | Tareas Asignadas | Story Points (SP) | Rol en Pull Requests |
|---|---|---|---|---|
| **Dany Muriel** | Product Owner, Lead DB & Matrícula Core | M02, M04, M05, QA | **31 pts** | **Único Revisor y Aprobador Autorizado** |
| **Franklin Anahua** | Backend & Database Specialist | M01, M03, M06, M07, M08, M10 | **45 pts** | Desarrollador (Envía PRs a Dany) |
| **Jhosep Arocutipa** | Frontend & UI/UX Specialist | UI Portal, UI Admin, M05-QR, M09 | **29 pts** | Desarrollador (Envía PRs a Dany) |
| **TOTAL PROYECTO** | — | **14 Issues / 10 Módulos** | **105 pts** | **100% Sin ORM (PDO / SPs Nativos)** |
