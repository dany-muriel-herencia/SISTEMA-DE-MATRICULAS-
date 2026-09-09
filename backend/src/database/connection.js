import mysql from 'mysql2/promise';
import { databaseConfig } from '../config/database.config.js';

let pool = null;

/**
 * Obtiene la instancia activa del Pool de conexiones MySQL.
 * Si no existe, la inicializa con la configuración centralizada.
 * @returns {mysql.Pool}
 */
export const getDatabasePool = () => {
  if (!pool) {
    pool = mysql.createPool({
      host: databaseConfig.host,
      port: databaseConfig.port,
      user: databaseConfig.user,
      password: databaseConfig.password,
      database: databaseConfig.database,
      waitForConnections: databaseConfig.waitForConnections,
      connectionLimit: databaseConfig.connectionLimit,
      queueLimit: databaseConfig.queueLimit,
      dateStrings: databaseConfig.dateStrings,
      timezone: databaseConfig.timezone
    });
  }
  return pool;
};

/**
 * Verifica la conexión con el servidor MySQL ejecutando un PING (SELECT 1).
 * @returns {Promise<{ isConnected: boolean, latencyMs?: number, message: string }>}
 */
export const testDatabaseConnection = async () => {
  const startTime = Date.now();
  try {
    const currentPool = getDatabasePool();
    const connection = await currentPool.getConnection();
    try {
      await connection.query('SELECT 1 AS health_check');
      const latencyMs = Date.now() - startTime;
      return {
        isConnected: true,
        latencyMs,
        message: 'Conexión exitosa a la base de datos MySQL local'
      };
    } finally {
      connection.release();
    }
  } catch (error) {
    return {
      isConnected: false,
      latencyMs: Date.now() - startTime,
      message: `Error de conexión a la base de datos: ${error.message}`
    };
  }
};

/**
 * Cierra ordenadamente el pool de conexiones.
 */
export const closeDatabasePool = async () => {
  if (pool) {
    await pool.end();
    pool = null;
  }
};
