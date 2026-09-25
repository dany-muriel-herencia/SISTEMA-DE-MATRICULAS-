-- Actualización compatible con instalaciones antiguas y con el esquema actual.
USE sgau;
SET @existe_codigo = (SELECT COUNT(*) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='matricula' AND COLUMN_NAME='codigo_matricula');
SET @ddl = IF(@existe_codigo=0,
 'ALTER TABLE matricula ADD COLUMN codigo_matricula VARCHAR(40) NULL AFTER id_periodo', 'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE matricula SET codigo_matricula=CONCAT('MAT-',id_periodo,'-',id_estudiante,'-',id_matricula)
 WHERE codigo_matricula IS NULL;
ALTER TABLE matricula MODIFY codigo_matricula VARCHAR(40) NOT NULL;

SET @existe_indice = (SELECT COUNT(*) FROM (
 SELECT INDEX_NAME FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='matricula' AND NON_UNIQUE=0
 GROUP BY INDEX_NAME HAVING COUNT(*)=1 AND MAX(COLUMN_NAME)='codigo_matricula'
) AS indices_codigo);
SET @ddl=IF(@existe_indice=0,
 'ALTER TABLE matricula ADD CONSTRAINT uk_matricula_codigo UNIQUE (codigo_matricula)', 'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
