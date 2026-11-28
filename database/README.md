# 🗄️ Estructura Modular de Base de Datos (`bd_matricula_universitaria` v4.0)

Para facilitar el mantenimiento, la revisión por módulos y el estudio del código, el script monolítico [releases/bd_matricula_universitaria_v4.sql](releases/bd_matricula_universitaria_v4.sql) (**3,288 líneas**) se mantiene intacto como respaldo oficial de entrega y para `docker-compose.yml`, y se ha dividido en **12 scripts modulares autoejecutables** ordenados por responsabilidad.

---

## 📂 Árbol de Archivos y Contenido

```plaintext
database/
├── install_all.sql                                 # Instalador maestro (ejecuta los 12 scripts en orden)
├── README.md                                       # Esta guía
├── 01_schema/
│   ├── 01_tablas_ddl.sql                           # Creación de BD + 27 tablas InnoDB, FKs y CHECKs
│   ├── 02_vistas.sql                               # 3 vistas: v_cola_matricula, v_lista_espera, v_rectificaciones_pendientes
│   ├── v_usuarios_seguridad_dany.sql               # Dany (ISSUE-02): Vista M02 (usuarios, roles, última auditoría y KPIs)
│   └── v_malla_curricular_detalle_dany.sql         # Dany (ISSUE-04): Vista M04 (malla por ciclos y prerrequisitos)
├── 02_functions/
│   ├── 01_funciones_negocio.sql                    # Funciones base heredadas de v4.0
│   ├── fn_validar_prerrequisitos_dany.sql          # Dany (ISSUE-04, ISSUE-05): plan + prerrequisitos
│   ├── fn_detectar_cruce_horario_dany.sql          # Dany (ISSUE-05): solapamiento horario
│   ├── fn_creditos_permitidos_dany.sql             # Dany (ISSUE-05): límite base 22 o 26 por mérito/RD
│   └── fn_calcular_creditos_matricula_dany.sql     # Dany (ISSUE-05): suma de créditos inscritos
├── 02_triggers/
│   └── trg_usuarios_auditoria_upd_dany.sql         # Dany (ISSUE-02): Trigger AFTER UPDATE en usuarios (OLD -> NEW)
├── 03_procedures/
│   ├── 01_internos_y_base_matricula.sql            # Nota sin triggers + sp_i_* + sp_crear_matricula_pendiente + sp_confirmar_matricula
│   ├── 02_cola_turnos_matricula.sql                # TX-04: Cola de turnos de matrícula (6 SPs)
│   ├── 03_matricula_y_cursos.sql                   # TX-01: sp_registrar_matricula_completa, retiro, anulación, excepciones (5 SPs)
│   ├── 04_lista_espera_seccion.sql                 # TX-06: Lista de espera por sección llena (4 SPs)
│   ├── 05_rectificaciones.sql                      # TX-02: Solicitar, aprobar y rechazar rectificaciones (3 SPs)
│   ├── 06_pagos_y_cierre_semestre.sql              # TX-03 (sp_registrar_pago) y TX-05 (sp_cerrar_semestre_academico)
│   ├── 07_consultas_lectura.sql                    # Solo lectura: sp_consultar_matricula y sp_consultar_vacantes
│   ├── sp_guardar_usuario_seguridad_dany.sql       # Dany (ISSUE-02): CRUD usuarios, rol, switch estado y clave
│   ├── sp_guardar_estructura_academica_dany.sql    # Dany (ISSUE-04): CRUD facultades, escuelas, planes, periodos y curso+malla
│   ├── sp_registrar_matricula_completa_dany.sql    # Dany (ISSUE-05): TX-01 Maestra (alumno + manual admin)
│   ├── sp_retirar_curso_o_anular_dany.sql          # Dany (ISSUE-05): TX-06 retiro/anulación + promoción lista espera
│   ├── sp_aplicar_excepcion_matricula_dany.sql     # Dany (ISSUE-05/07): excepciones con resolución
│   └── sp_espera_gestionar_dany.sql                # Dany (ISSUE-05): Cola 2 lista de espera (inscribir/cancelar)
├── 04_seeds/
│   └── 01_datos_prueba.sql                         # Carga inicial coherente (roles, usuarios, cursos, secciones, matrículas, deudas)
├── 05_verificacion/
│   └── 01_consistencia.sql                         # Consultas de verificación de créditos y vacantes
└── releases/
    └── bd_matricula_universitaria_v4.sql           # Script monolítico completo intacto (respaldo y Docker)
```

---

## 🔗 Mapeo entre los Scripts SQL y el Backend PHP (`backend/src/Modules/`)

| Script Modular SQL | Módulo Backend PHP Asociado | Objetos Principales que Define |
|---|---|---|
| `01_schema/01_tablas_ddl.sql` | Todos (`Database.php`) | 27 tablas (`usuarios`, `estudiantes`, `matriculas`, `secciones`, `deudas_estudiante`, `rectificaciones`, etc.) |
| `01_schema/02_vistas.sql` | `Matricula`, `Rectificacion` | `v_cola_matricula`, `v_lista_espera`, `v_rectificaciones_pendientes` |
| `01_schema/v_*_dany.sql` (2 archivos) | `Auth`, `Academico` (**Dany — Bloque 13/13 completo**) | `v_usuarios_seguridad`, `v_malla_curricular_detalle` |
| `02_functions/01_funciones_negocio.sql` | `Academico`, `Horarios`, `Finanzas`, `Estudiantes` | `fn_detectar_cruce_horario`, `fn_validar_prerrequisitos`, `fn_vacantes_disponibles`, `fn_calcular_saldo_deuda`, `fn_validar_limite_creditos`, etc. (11 funciones) |
| `02_functions/fn_*_dany.sql` (4 archivos) | `Academico`, `Matricula` (**Dany**) | `fn_validar_prerrequisitos`, `fn_detectar_cruce_horario`, `fn_creditos_permitidos`, `fn_calcular_creditos_matricula` (1 archivo por función) |
| `02_triggers/trg_usuarios_auditoria_upd_dany.sql` | `Auth` (**Dany**) | `trg_usuarios_auditoria_upd` (`AFTER UPDATE ON usuarios`, auditoría `OLD -> NEW` de rol/estado) |
| `03_procedures/01_internos_y_base_matricula.sql` | `Matricula` | `sp_i_inscribir_curso`, `sp_i_espera_promover`, `sp_i_retirar_curso`, `sp_crear_matricula_pendiente`, `sp_confirmar_matricula` |
| `03_procedures/02_cola_turnos_matricula.sql` | `Matricula` (`M06`) | `sp_ingresar_cola_matricula`, `sp_atender_siguiente_cola`, `sp_finalizar_turno_cola`, `sp_abandonar_cola`, `sp_expirar_turnos_cola`, `sp_consultar_posicion_cola` |
| `03_procedures/03_matricula_y_cursos.sql` | `Matricula` (`M05`, `M06`) | `sp_registrar_matricula_completa`, `sp_inscribir_curso_matricula`, `sp_retirar_curso`, `sp_anular_matricula`, `sp_aplicar_excepcion` |
| `03_procedures/04_lista_espera_seccion.sql` | `Matricula` (`M05`) | `sp_espera_inscribir`, `sp_espera_cancelar`, `sp_espera_consultar`, `sp_espera_promover` |
| `03_procedures/05_rectificaciones.sql` | `Rectificacion` (`M10`) | `sp_solicitar_rectificacion`, `sp_aprobar_rectificacion`, `sp_rechazar_rectificacion` |
| `03_procedures/06_pagos_y_cierre_semestre.sql` | `Finanzas` (`M08`), `Reportes` (`M09`) | `sp_registrar_pago`, `sp_cerrar_semestre_academico` |
| `03_procedures/07_consultas_lectura.sql` | `Matricula`, `Horarios` | `sp_consultar_matricula`, `sp_consultar_vacantes` |
| `03_procedures/sp_*_dany.sql` (6 archivos) | `Auth`, `Academico`, `Matricula` (**Dany**) | `sp_guardar_usuario_seguridad`, `sp_guardar_estructura_academica`, `sp_registrar_matricula_completa`, `sp_retirar_curso_o_anular`, `sp_aplicar_excepcion_matricula`, `sp_espera_gestionar` |
| `04_seeds/01_datos_prueba.sql` | Todos | Datos de prueba de administradores, docentes, 10 estudiantes, cursos ESIS y matrículas históricas |
| `05_verificacion/01_consistencia.sql` | QA / Auditoría | Validación de consistencia de `total_creditos` y `vacantes_ocupadas` |

---

## ▶️ Cómo Ejecutarlo en XAMPP (Windows)

### Opción 1: Instalación completa automática desde consola (Recomendada)
Con **MySQL iniciado en XAMPP**, ejecuta desde PowerShell ubicándote en `database/`:

```powershell
cd "c:\xampp\htdocs\BASE DE DATOS SOFTWARE\SISTEMA-DE-MATRICULAS-\database"
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < install_all.sql
```

### Opción 2: Importación manual ordenada en phpMyAdmin
Si deseas cargar o probar archivos por separado en phpMyAdmin, impórtalos **estrictamente en el orden numérico**:
1. `01_schema/01_tablas_ddl.sql` ➔ `01_schema/02_vistas.sql`
2. `02_functions/01_funciones_negocio.sql`
3. `03_procedures/01_...` hasta `03_procedures/07_...`
4. `04_seeds/01_datos_prueba.sql`
5. `05_verificacion/01_consistencia.sql`

> Cada archivo desde `01_schema/02_vistas.sql` en adelante incluye `USE bd_matricula_universitaria;` y sus bloques `DELIMITER $$` / `DELIMITER ;` listos para ejecutarse de forma independiente una vez creada la estructura base.
