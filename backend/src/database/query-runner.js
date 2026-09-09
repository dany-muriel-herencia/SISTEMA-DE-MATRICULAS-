import { getDatabasePool } from './connection.js';
import { DatabaseError } from '../shared/errors/database-error.js';

/**
 * Ejecutor de consultas SQL directas y transacciones parametrizadas sin ORM.
 */
export class QueryRunner {
  /**
   * Ejecuta una consulta SQL simple o parametrizada contra el pool de conexiones.
   * @param {string} sql - Sentencia SQL con placeholders `?`
   * @param {Array<any>} [params=[]] - Parámetros para prevenir inyección SQL
   * @returns {Promise<[any[], any]>} Resultados de la consulta
   */
  static async query(sql, params = []) {
    try {
      const pool = getDatabasePool();
      return await pool.execute(sql, params);
    } catch (error) {
      throw new DatabaseError(`Error al ejecutar consulta SQL: ${error.message}`, {
        sql,
        originalCode: error.code
      });
    }
  }

  /**
   * Ejecuta una serie de operaciones dentro de una transacción explícita.
   * Si ocurre un error, ejecuta ROLLBACK automáticamente.
   * @param {Function} callback - Función que recibe la conexión de transacción
   * @returns {Promise<any>}
   */
  static async withTransaction(callback) {
    const pool = getDatabasePool();
    const connection = await pool.getConnection();
    try {
      await connection.beginTransaction();
      const result = await callback(connection);
      await connection.commit();
      return result;
    } catch (error) {
      await connection.rollback();
      throw error;
    } finally {
      connection.release();
    }
  }
}
