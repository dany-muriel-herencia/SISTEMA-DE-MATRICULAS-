import { appConfig } from '../../config/app.config.js';
import { databaseConfig } from '../../config/database.config.js';

export class HealthService {
  constructor(healthRepository) {
    this.healthRepository = healthRepository;
  }

  async getSystemStatus() {
    const dbStatus = await this.healthRepository.checkDatabaseHealth();

    return {
      name: 'Sistema de Matrícula UNJBG - API Backend',
      version: '1.0.0',
      status: 'UP',
      timestamp: new Date().toISOString(),
      environment: appConfig.nodeEnv,
      database: {
        engine: 'MySQL (Nativo sin ORM)',
        host: databaseConfig.host,
        port: databaseConfig.port,
        databaseName: databaseConfig.database,
        status: dbStatus.isConnected ? 'CONNECTED' : 'DISCONNECTED',
        latencyMs: dbStatus.latencyMs,
        details: dbStatus.message
      }
    };
  }
}
