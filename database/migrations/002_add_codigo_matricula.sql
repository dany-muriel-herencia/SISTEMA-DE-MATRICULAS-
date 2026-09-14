ALTER TABLE matricula
    ADD COLUMN codigo_matricula VARCHAR(40) NULL AFTER id_periodo;

UPDATE matricula
SET codigo_matricula = CONCAT('MAT-', id_periodo, '-', id_estudiante, '-', id_matricula)
WHERE codigo_matricula IS NULL;

ALTER TABLE matricula
    MODIFY codigo_matricula VARCHAR(40) NOT NULL,
    ADD CONSTRAINT uk_matricula_codigo UNIQUE (codigo_matricula);