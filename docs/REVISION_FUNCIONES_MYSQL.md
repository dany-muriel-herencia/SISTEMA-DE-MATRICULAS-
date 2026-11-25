# 🔍 MATRIZ DE REVISIÓN TÉCNICA DE FUNCIONES Y PROCEDIMIENTOS ALMACENADOS (MYSQL 8.0)
## Sistema de Gestión Académica y Matrícula Universitaria (SGAU - UNJBG)

**Propósito:** Definir con exactitud qué integrante del equipo es responsable del diseño, qué integrante realiza la revisión técnica de parámetros y lógica SQL (Peer Review), y la aprobación final obligatoria por parte de **Dany Muriel** (Lead & QA).

---

## 1. TABLA DE FUNCIONES DETERMINÍSTICAS Y DE CÁLCULO (`FUNCTIONS`)

| Función MySQL | Propósito / Lógica de Negocio | Dev (Autor) | Revisor Técnico (Peer Review) | Revisor Final & Aprobador | Criterios Específicos de Validación |
|---|---|---|---|---|---|
| `fn_detectar_cruce_horario` | Verifica solapamiento de días, horas de inicio y fin entre secciones elegidas. | Franklin Anahua | Jhosep Arocutipa *(Integración semáforo UI)* | **Dany Muriel** | Retorno 1 si hay cruce, 0 si está libre; precisión en rangos `BETWEEN`. |
| `fn_validar_prerrequisitos` | Valida que el estudiante haya aprobado los cursos prerrequisito exigidos en la malla. | Dany Muriel | Franklin Anahua *(Verificación índices en kardex)* | **Dany Muriel (Lead)** | Búsqueda indexada en `tb_historial_academico` con `nota >= 10.5`. |
| `fn_vacantes_disponibles` | Calcula cupos libres restando matriculados a la capacidad del aula/sección. | Franklin Anahua | Dany Muriel *(Control de concurrencia)* | **Dany Muriel** | Evitar sobrecupo (`capacidad - ocupados >= 0`). |
| `fn_calcular_creditos_matricula` | Suma los créditos de los cursos inscritos en la matrícula activa. | Dany Muriel | Franklin Anahua *(Validación de tipos de dato)* | **Dany Muriel (Lead)** | Agregación correcta sobre `tb_detalle_matricula`. |
| `fn_validar_limite_creditos` | Evalúa si la carga crediticia respeta el tope base (22) o ampliado por mérito (26). | Dany Muriel | Franklin Anahua *(Verificación ponderado PPA)* | **Dany Muriel (Lead)** | Condición de promedio regular vs. tercio/décimo superior. |
| `fn_validar_curso_en_plan` | Comprueba que el curso pertenezca al plan de estudios del estudiante. | Dany Muriel | Franklin Anahua *(Mapeo plan curricular)* | **Dany Muriel (Lead)** | Integridad con `tb_curso` y `tb_plan_estudio`. |
| `fn_calcular_saldo_deuda` | Suma montos pendientes de pago del alumno (TUPA, matrícula, multas). | Franklin Anahua | Jhosep Arocutipa *(Visualización alerta deudas)* | **Dany Muriel** | Retorno 0.00 para permitir paso a matrícula. |
| `fn_creditos_adicionales` | Determina si el estudiante califica para créditos extra según su promedio. | Franklin Anahua | Dany Muriel *(Lógica académica)* | **Dany Muriel** | Validar que PPA sea mayor a 14.00 para autorizar hasta 26 créditos. |
| `fn_nota_minima` | Retorna la constante institucional de aprobación (10.50). | Franklin Anahua | Jhosep Arocutipa *(Validación visual)* | **Dany Muriel** | Constante determinística `DECIMAL(4,2) = 10.50`. |
| `fn_limite_creditos_base` | Retorna el tope ordinario de créditos semestrales (22). | Franklin Anahua | Jhosep Arocutipa *(Validación visual)* | **Dany Muriel** | Constante determinística `TINYINT = 22`. |
| `fn_promedio_merito` | Retorna el umbral de promedio para mérito académico (14.00). | Franklin Anahua | Jhosep Arocutipa *(Validación visual)* | **Dany Muriel** | Constante determinística `DECIMAL(4,2) = 14.00`. |

---

## 2. TABLA DE PROCEDIMIENTOS TRANSACCIONALES DE MATRÍCULA (`STORED PROCEDURES`)

| Procedimiento Almacenado | Propósito / Transacción ACID | Dev (Autor) | Revisor Técnico (Peer Review) | Revisor Final & Aprobador | Criterios Específicos de Validación |
|---|---|---|---|---|---|
| `sp_registrar_matricula_completa` | **TRANSACCIÓN MAESTRA:** Valida deudas, créditos, prerrequisitos, vacantes y confirma la matrícula. | Dany Muriel | Franklin Anahua *(Revisión de bloqueos `FOR UPDATE`)* | **Dany Muriel (Lead)** | `START TRANSACTION`, rollback integral ante cualquier falla, 0% ORM. |
| `sp_crear_matricula_pendiente` | Crea el registro en estado `PENDIENTE` para reservar turno y temporizador. | Franklin Anahua | Jhosep Arocutipa *(Flujo de pre-matrícula UI)* | **Dany Muriel** | Control de unicidad de matrícula por período y estudiante. |
| `sp_confirmar_matricula` | Pasa la matrícula de `PENDIENTE` a `REGISTRADA` descontando vacantes definitivas. | Dany Muriel | Franklin Anahua *(Atomicidad de vacantes)* | **Dany Muriel (Lead)** | Comprobación de integridad previa a emisión de constancia. |
| `sp_inscribir_curso_matricula` | Asigna una sección individual verificando cruce de horario y cupos. | Franklin Anahua | Dany Muriel *(Validación de cruce de horario)* | **Dany Muriel** | Manejo de excepción y retorno de mensaje claro si hay cruce. |
| `sp_retirar_curso` | Da de baja una asignatura y libera la vacante automáticamente. | Franklin Anahua | Jhosep Arocutipa *(Refresco dinámico en UI)* | **Dany Muriel** | Liberación de cupo y actualización de créditos matriculados. |
| `sp_anular_matricula` | Anulación total de la matrícula por solicitud justificada o resolución. | Dany Muriel | Franklin Anahua *(Liberación masiva de vacantes)* | **Dany Muriel (Lead)** | Liberación de todas las secciones asociadas y log de auditoría. |
| `sp_aplicar_excepcion` | Bypass administrativo para matricular alumnos autorizados por Decanato/Comisión. | Dany Muriel | Franklin Anahua *(Verificación de resolución formal)* | **Dany Muriel (Lead)** | Registro del usuario administrador que autorizó la excepción. |

---

## 3. TABLA DE PROCEDIMIENTOS DE COLA EN VIVO Y LISTA DE ESPERA

| Procedimiento Almacenado | Propósito / Control de Flujo | Dev (Autor) | Revisor Técnico (Peer Review) | Revisor Final & Aprobador | Criterios Específicos de Validación |
|---|---|---|---|---|---|
| `sp_ingresar_cola_matricula` | Asigna número de turno correlativo ponderado según orden de mérito. | Franklin Anahua | Jhosep Arocutipa *(Visualización de turno en UI)* | **Dany Muriel** | Algoritmo FIFO priorizado por PPA de mayor a menor. |
| `sp_atender_siguiente_cola` | Cambia estado a `ATENDIENDO` y activa temporizador de 15 minutos. | Franklin Anahua | Dany Muriel *(Gestión de concurrencia)* | **Dany Muriel** | Verificación de concurrencia máxima permitida (ej. 30 simultáneos). |
| `sp_finalizar_turno_cola` | Cierra la sesión en cola al culminar la matrícula exitosa. | Franklin Anahua | Jhosep Arocutipa *(Redirección a constancia)* | **Dany Muriel** | Limpieza de sesión y pase al siguiente estudiante en cola. |
| `sp_abandonar_cola` | Libera el turno si el estudiante cancela voluntariamente. | Franklin Anahua | Jhosep Arocutipa *(Botón cancelar en UI)* | **Dany Muriel** | Marca de estado `ABANDONADO` sin bloquear la cola. |
| `sp_expirar_turnos_cola` | Proceso batch que marca `EXPIRADO` a quienes superaron el tiempo límite. | Franklin Anahua | Dany Muriel *(Optimización de ejecución periódica)* | **Dany Muriel** | Consulta indexada `WHERE fecha_limite < NOW()`. |
| `sp_espera_inscribir` | Agrega al estudiante a la lista de espera de una sección sin cupo. | Franklin Anahua | Jhosep Arocutipa *(Mensaje visual de lista espera)* | **Dany Muriel** | Generación del orden correlativo en `tb_lista_espera`. |
| `sp_espera_promover` | Si se libera una vacante, promueve automáticamente al primer en lista. | Franklin Anahua | Dany Muriel *(Transacción ACID de ascenso)* | **Dany Muriel** | Transición automática de `LISTA_ESPERA` a `MATRICULADO`. |
| `sp_espera_cancelar` | Retiro voluntario del alumno de la lista de espera. | Franklin Anahua | Jhosep Arocutipa *(Acción en portal alumno)* | **Dany Muriel** | Reordenamiento de las posiciones posteriores. |

---

## 4. TABLA DE PROCEDIMIENTOS DE RECTIFICACIÓN, PAGOS Y CIERRE

| Procedimiento Almacenado | Propósito / Dominio | Dev (Autor) | Revisor Técnico (Peer Review) | Revisor Final & Aprobador | Criterios Específicos de Validación |
|---|---|---|---|---|---|
| `sp_solicitar_rectificacion` | Registra solicitud formal de alta, baja o cambio de sección dentro de fecha. | Franklin Anahua | Jhosep Arocutipa *(Formulario de rectificación UI)* | **Dany Muriel** | Comprobación de ventana de fechas del calendario académico. |
| `sp_aprobar_rectificacion` | Aplica formalmente las modificaciones y emite el acta de rectificación. | Dany Muriel | Franklin Anahua *(Transaccionalidad múltiple)* | **Dany Muriel (Lead)** | Validación de vacantes en destino antes de aplicar la baja. |
| `sp_rechazar_rectificacion` | Desestima la solicitud registrando motivo técnico o administrativo. | Franklin Anahua | Dany Muriel *(Auditoría y estados)* | **Dany Muriel** | Notificación y log en bitácora sin alterar matrícula previa. |
| `sp_registrar_pago` | Registra comprobante, cancela deuda y autoriza financieramente la matrícula. | Franklin Anahua | Jhosep Arocutipa *(Visualización de boleta QR)* | **Dany Muriel** | Descuento exacto de saldo en `tb_deuda_estudiante`. |
| `sp_cerrar_semestre_academico` | Consolida notas finales, actualiza PPA en Kardex y cierra actas con hash. | Dany Muriel | Franklin Anahua *(Cálculo masivo y rendimiento)* | **Dany Muriel (Lead)** | Bloqueo de modificaciones a actas y recálculo general de PPA. |
| `sp_consultar_matricula` | Consulta detalle completo de cursos matriculados, créditos y estado. | Franklin Anahua | Jhosep Arocutipa *(Consumo directo en React)* | **Dany Muriel** | Salida estructurada limpia para serialización JSON. |
| `sp_consultar_vacantes` | Devuelve listado de secciones con cupos disponibles y horarios en vivo. | Franklin Anahua | Jhosep Arocutipa *(Matriz horaria en React)* | **Dany Muriel** | Rendimiento y velocidad de respuesta para alta concurrencia. |
