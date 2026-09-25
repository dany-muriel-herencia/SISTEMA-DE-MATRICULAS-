# Relaciones del esquema sgau

usuario 1 — 0..1 estudiante / docente / administrador (misma clave id_usuario).
facultad 1 — N escuela 1 — N carrera 1 — N plan_estudio.
plan_estudio N — N curso mediante curriculum.
curso N — N curso mediante prerequisito.
curso / periodo_academico / docente 1 — N seccion.
seccion / aula 1 — N horario.
estudiante / periodo_academico 1 — N matricula.
matricula / seccion 1 — N detalle_matricula.
estudiante / concepto_pago 1 — N pago 1 — 0..1 comprobante_pago.
usuario 1 — N sesion / auditoria.

El esquema actual no asigna un plan de estudio al estudiante. Esa ampliación requiere una decisión académica y una migración específica; no se inventan relaciones al crear perfiles.
