# PLAN DE TRABAJO, HISTORIAS DE USUARIO, ISSUES Y TABLERO KANBAN
## Sistema Integrado de Matrícula Universitaria — SGAU / UNJBG
**Curso:** Base de Datos II / Ingeniería de Software  
**Equipo de Desarrollo:**
1. **Dany Ronaldo Muriel Herencia** (`dany-muriel-herencia` / `drmurielh@unjbg.edu.pe`) — **Product Owner / QA & Único Revisor Autorizado de Pull Requests (PRs)**
2. **Franklin Cristofier Anahua Cahuachia** (`franklin-anahua`) — Backend Engineer & Database Specialist
3. **Jhosep Rodrigo Arocutipa Mamani** (`jhosep-arocutipa`) — Frontend Developer & UI/UX Specialist

---

## ⚠️ REGLA DE ORO ARQUITECTURAL: PROHIBIDO EL USO DE ORM (0% ORM)
> **Directiva Académica de Evaluación (Puntaje Máximo 08 pts):**  
> Queda **estrictamente prohibido** el uso de ORMs (como Eloquent, Hibernate, Prisma, TypeORM o Doctrine).  
> **Estándar Obligatorio:**
> - Todo acceso a datos debe implementarse mediante **consultas nativas / PDO Prepared Statements** o llamadas directas a **Procedimientos Almacenados (Stored Procedures)**:
>   ```sql
>   CALL sp_registrar_matricula_completa(?, ?, ?, ?, @p_id_matricula, @p_codigo_resultado, @p_mensaje);
>   ```
> - La lógica de integridad transaccional (ACID), bloqueos pesimistas (`FOR UPDATE`), control de vacantes y auditoría reside en el motor de base de datos MySQL 8.0+.
> - Cualquier PR que incluya dependencias de ORM será **rechazado automáticamente por Dany**.

---

## 🔒 POLÍTICA DE GOBERNANZA GIT & REVISIÓN DE PULL REQUESTS (PR)

1. **Protección de Rama Principal (`main` / `develop`):**
   - Nadie realiza commits directos sobre `main` ni `develop`.
   - Se trabaja bajo GitFlow mediante ramas temáticas: `feature/ISSUE-XX-nombre`, `fix/ISSUE-XX-nombre`.
2. **Revisor Único y Autorizado:**
   - **Dany Muriel Herencia** es el **ÚNICO revisor autorizado** para aprobar y realizar el Merge de cualquier Pull Request.
   - Todo PR requiere obligatoriamente:
     1. Revisión de código (verificación de **Cero ORM** y uso estricto de SP / PDO nativo).
     2. Pruebas de integración del CU correspondiente validadas.
     3. Aprobación formal (`LGTM` / Approved) por parte de **Dany**.
3. **Archivo de Configuración `.github/CODEOWNERS`:**
   ```plaintext
   # Único responsable y aprobador de todo el repositorio:
   *       @dany-muriel-herencia
   /database/   @dany-muriel-herencia
   /apps/       @dany-muriel-herencia
   /packages/   @dany-muriel-herencia
   ```

---

## 📋 TABLA MAESTRA: HISTORIAS DE USUARIO (HU) E ISSUES DE TRABAJO

| ID Issue | ID HU | Módulo | Casos de Uso (Doc) | Descripción de la Tarea / Historia | Criterios de Aceptación (DoD) & Reglas Sin ORM | Estimación (SP) | Responsable Asignado | Revisor de PR |
|---|---|---|---|---|---|---|---|---|
| **ISSUE-01** | `HU-01` | **M01: Autenticación** | CU-01, CU-02, CU-03 | Login seguro institucional con control de 3 intentos fallidos, bloqueo de cuenta y recuperación de clave vía token hash. | PDO nativo llamando a `sp_validar_login`; hashing BCrypt en backend; token JWT emitido sin ORM. | 5 pts | Franklin Anahua | **Dany Muriel** |
| **ISSUE-02** | `HU-02` | **M02: Seguridad y Roles** | CU-04, CU-05, CU-06, CU-07 | Gestión de usuarios, asignación de roles (Estudiante, Docente, Coordinador, Tesorería, Admin) y bitácora de auditoría inmutable. | Consultas preparadas SQL nativas; inserción en `tb_auditoria` ante cada cambio de rol y estado. | 5 pts | Dany Muriel | **Dany Muriel (QA)** |
| **ISSUE-03** | `HU-03` | **M03: Estudiantes** | CU-08, CU-09, CU-10, CU-11 | Expediente de estudiante, generador de código unjbg (`YYYY-NNNNNN`), asignación de plan y cálculo automático de Kardex/PPA. | SP `sp_generar_codigo_estudiante`; cálculo matemático ponderado en SQL sin librerías intermedias. | 8 pts | Franklin Anahua | **Dany Muriel** |
| **ISSUE-04** | `HU-04` | **M04: Académico y Malla** | CU-12 a CU-16, CU-18 | Gestión curricular: facultades, escuelas, planes de estudio, cursos, matriz de ciclos y grafos de prerrequisitos sin ciclos infinitos. | Vistas SQL indexadas `vw_malla_curricular`; validación de dependencias por consulta recursiva o SP. | 8 pts | Dany Muriel | **Dany Muriel (QA)** |
| **ISSUE-05** | `HU-05` | **M05: Matrícula Alumno** | CU-23, CU-24, CU-27, CU-29 | Portal Estudiante: selección de cursos en malla, verificación interactiva de cruces de horario, límite de 26 créditos y validación de deudas. | Bloqueo transaccional `CALL sp_registrar_matricula_completa` con rollback automático ante cruces o falta de vacantes. | 13 pts | Dany Muriel | **Dany Muriel (QA)** |
| **ISSUE-06** | `HU-05` | **M05: Constancia Matrícula** | CU-30, CU-31 | Generación de Constancia Oficial de Matrícula en PDF con código QR y Hash SHA-256 de verificación pública. | Extracción de datos con PDO nativo; renderizado con biblioteca PDF y código QR verificado contra base de datos. | 5 pts | Jhosep Arocutipa | **Dany Muriel** |
| **ISSUE-07** | `HU-06` | **M06: Matrícula Admin** | CU-37 a CU-40, CU-42 a CU-46 | Centro de control de matrícula: monitor de cola en vivo, asignación prioritaria, ampliación de vacantes y matrícula manual por excepción. | Transacciones concurrentes mediante `sp_matricular_por_excepcion`; actualización de estados con control de concurrencia. | 8 pts | Franklin Anahua | **Dany Muriel** |
| **ISSUE-08** | `HU-07` | **M07: Horarios y Aulas** | CU-32 a CU-38 | Gestión de aulas físicas/labs, programación semanal por sección (Lun-Sáb) y motor detector de colisiones de aula y docente. | Función SQL / SP `sp_validar_cruce_horario`; consulta directa `vw_horarios_semana` optimizada con índices. | 8 pts | Franklin Anahua | **Dany Muriel** |
| **ISSUE-09** | `HU-08` | **M08: Pagos y Tesorería** | CU-43 a CU-48 | Catálogo TUPA, emisión de cuentas por cobrar, cobranza en ventanilla, registro de pago bancario y emisión de boletas digitales. | Procedimiento `sp_registrar_pago` con transacción ACID; actualización atómica de `tb_deuda_estudiante`. Cero ORM. | 8 pts | Franklin Anahua | **Dany Muriel** |
| **ISSUE-10** | `HU-09` | **M09: Reportes y Actas** | CU-49 a CU-58 | Centro de analítica: padrón general de matriculados, nómina oficial por sección y actas de evaluación firmadas digitalmente con hash. | Vistas de alto rendimiento `vw_nomina_matriculados`; generación de reportes impresos y exportación Excel/PDF. | 8 pts | Jhosep Arocutipa | **Dany Muriel** |
| **ISSUE-11** | `HU-10` | **M10: Rectificación** | CU-64 a CU-73 | Proceso formal de rectificación: solicitud dentro de plazo, simulación de horario (altas/bajas/cambio de sección) y emisión de acta de rectificación. | Procedimiento almacenado `sp_solicitar_rectificacion` y `sp_aprobar_rectificacion` garantizando integridad de vacantes. | 8 pts | Franklin Anahua | **Dany Muriel** |
| **ISSUE-12** | `HU-UI1` | **Frontend: Portal Alumno** | Mockup Portal Alumno | Interfaz gráfica React + Tailwind del portal del estudiante: Horario semanal interactivo, semáforo de cruces, selector de asignaturas. | Interfaz reactiva conectada al backend vía fetch/axios con Axios Client; consumo de endpoints REST puros. | 8 pts | Jhosep Arocutipa | **Dany Muriel** |
| **ISSUE-13** | `HU-UI2` | **Frontend: Portal Admin** | Mockup Backoffice General | Dashboard administrativo React: panel de gestión de aulas, cola de espera en tiempo real, interfaz de cobros y carga de resoluciones. | Vistas modulares responsivas con componentes reutilizables y manejo de estado centralizado (Zustand). | 8 pts | Jhosep Arocutipa | **Dany Muriel** |
| **ISSUE-14** | `HU-QA` | **Auditoría & Pruebas** | Integral M01-M10 | Batería de pruebas de carga de concurrencia en matrícula (simulación de 100 estudiantes compitiendo por 30 vacantes sin sobrecupo). | Scripts de prueba SQL y JMeter/k6 validando cero Deadlocks y exactitud de vacantes en MySQL. | 5 pts | Dany Muriel | **Dany Muriel (QA)** |

---

## 📌 TABLERO KANBAN DEL PROYECTO

### Flujo de Estados del Tablero
```
[ 1. BACKLOG ] ──> [ 2. TO DO ] ──> [ 3. IN PROGRESS ] ──> [ 4. IN REVIEW (PR) ] ──> [ 5. DONE ]
                                                                      │
                                                        Revisión Exclusiva por
                                                          DANY MURIEL (Lead)
                                                                      │
                                                            ¿Cumple Cero ORM & SP?
                                                              ├── Sí ──> [ 5. DONE ]
                                                              └── No ──> Devuelve a [ 3. IN PROGRESS ]
```

---

### Distribución de Tareas en el Tablero Kanban

| 1. BACKLOG (Por Iniciar) | 2. TO DO (Sprint Activo) | 3. IN PROGRESS (En Desarrollo) | 4. IN REVIEW (Revisión PR por DANY) | 5. DONE (Terminado y Aprobado) |
|---|---|---|---|---|
| **ISSUE-11: Rectificaciones (M10)**<br>• Asignado: Franklin<br>• CU-64 a CU-73<br>• Estimación: 8 pts | **ISSUE-07: Centro Control Matrícula Admin (M06)**<br>• Asignado: Franklin<br>• Monitoreo cola en vivo y excepciones<br>• Estimación: 8 pts | **ISSUE-01: Autenticación & JWT (M01)**<br>• Asignado: Franklin<br>• Login nativo PDO + BCrypt<br>• Estimación: 5 pts | **ISSUE-04: Gestión Curricular y Malla (M04)**<br>• Asignado: Dany<br>• Vistas y SP de prerrequisitos<br>• *Revisor: Dany Muriel* | **BD-01: Script de Base de Datos v4.0**<br>• DDL + 10 Tablas principales<br>• SPs transaccionales sin triggers<br>• Cero dependencias ORM |
| **ISSUE-14: Pruebas de Carga Concurrente**<br>• Asignado: Dany<br>• Test de 100 alumnos vs 30 vacantes<br>• Estimación: 5 pts | **ISSUE-08: Horarios y Aulas (M07)**<br>• Asignado: Franklin<br>• Detección de cruces semanal<br>• Estimación: 8 pts | **ISSUE-02: Seguridad, Roles & Auditoría (M02)**<br>• Asignado: Dany<br>• Control de acceso RBAC y bitácora<br>• Estimación: 5 pts | **ISSUE-06: Constancia de Matrícula QR (M05)**<br>• Asignado: Jhosep<br>• Emisión de PDF con hash SHA-256<br>• *Revisor: Dany Muriel* | **ARCH-01: Estructura Monorepo y Microservicios**<br>• Arquitectura modular desacoplada<br>• Portal Estudiante + Portal Admin<br>• Gateway y Database Client |
| | **ISSUE-09: Pagos y Tesorería (M08)**<br>• Asignado: Franklin<br>• Cobranza y boletas QR sin ORM<br>• Estimación: 8 pts | **ISSUE-03: Expediente Alumno & Kardex (M03)**<br>• Asignado: Franklin<br>• Generador de código universitario<br>• Estimación: 8 pts | | **SPEC-01: Mapeo de 73 Casos de Uso**<br>• Trazabilidad completa con mockups<br>• Diagrama de clases de dominio UML |
| | **ISSUE-10: Reportes y Actas con Hash (M09)**<br>• Asignado: Jhosep<br>• Padrón y actas digitales<br>• Estimación: 8 pts | **ISSUE-05: Motor de Matrícula Transaccional (M05)**<br>• Asignado: Dany<br>• `sp_registrar_matricula_completa`<br>• Estimación: 13 pts | | |
| | | **ISSUE-12: UI Portal Alumno en React (M05)**<br>• Asignado: Jhosep<br>• Horario semanal interactivo<br>• Estimación: 8 pts | | |
| | | **ISSUE-13: UI Backoffice Admin en React**<br>• Asignado: Jhosep<br>• Paneles de control y tablas en vivo<br>• Estimación: 8 pts | | |

---

## 👥 RESUMEN DE RESPONSABILIDADES POR INTEGRANTE

### 1. Dany Ronaldo Muriel Herencia (Product Owner & QA Lead)
- **Rol en Git:** **Único Revisor y Aprobador Oficial de Pull Requests**.
- **Módulos Core a su Cargo:**
  - `M02: Seguridad, Roles y Auditoría` (RBAC nativo).
  - `M04: Gestión Curricular` (Mallas y control de prerrequisitos).
  - `M05: Motor de Matrícula Transaccional` (Lógica crítica ACID, vacantes y concurrencia).
  - `QA & Auditoría Integral` (Validación de cumplimiento estricto: **Cero ORM**).

### 2. Franklin Cristofier Anahua Cahuachia (Backend & Database Engineer)
- **Especialidad:** Lógica en Base de Datos, Procedimientos Almacenados y APIs nativas en PDO.
- **Módulos a su Cargo:**
  - `M01: Autenticación y Cuentas` (Endpoints de autenticación y tokens).
  - `M03: Expediente de Estudiantes y Kardex` (Cálculo de promedios ponderados y créditos).
  - `M06: Centro de Control de Matrícula Admin` (Cola de espera y excepciones).
  - `M07: Horarios y Aulas` (Matriz de ocupación y cruces de aula).
  - `M08: Gestión de Pagos y Tesorería` (Deudas y caja).
  - `M10: Rectificación de Matrícula` (Altas, bajas y sustituciones).

### 3. Jhosep Rodrigo Arocutipa Mamani (Frontend Developer & UI/UX Specialist)
- **Especialidad:** Interfaces de usuario en React, Tailwind CSS, Componentes Interactivos y Reportes.
- **Módulos y Tareas a su Cargo:**
  - `Portal del Alumno (React SPA)`: Horario semanal interactivo, semáforo de cruces, selector de cursos.
  - `Portal Administrativo (React SPA)`: Dashboards interactivos, tablas de búsqueda en tiempo real.
  - `M05 / M09: Generador de Constancias y Reportes`: Exportación a PDF con código QR y Hash SHA-256 de seguridad.
  - `M09: Centro de Inteligencia y Actas`: Vistas analíticas y reportería oficial.
