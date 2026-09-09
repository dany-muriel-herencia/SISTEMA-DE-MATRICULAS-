import { testDatabaseConnection } from '../../database/connection.js';

export class HealthRepository {
  /**
   * Ejecuta diagnóstico de conexión directa sobre el pool de base de datos.
   */
  async checkDatabaseHealth() {
    return await testDatabaseConnection();
  }
}
