import { createServer } from './server.js';
import { appConfig } from './config/app.config.js';
import { testDatabaseConnection, closeDatabasePool } from './database/connection.js';

const startServer = async () => {
  const app = createServer();

  console.log('====================================================');
  console.log('  SISTEMA DE MATRICULA UNJBG - BACKEND API REST');
  console.log('====================================================');
  console.log(`Entorno:      ${appConfig.nodeEnv}`);
  console.log(`Prefijo API:  ${appConfig.apiPrefix}`);

  // Diagnóstico inicial de conexión con MySQL local
  const dbStatus = await testDatabaseConnection();
  if (dbStatus.isConnected) {
    console.log(`[BD] Base de datos MySQL conectada (${dbStatus.latencyMs} ms)`);
  } else {
    console.warn(`[BD AVISO] MySQL no está accesible aún: ${dbStatus.message}`);
    console.warn('[BD AVISO] El servidor continuará ejecutándose. Inicie el servicio de MySQL cuando sea necesario.');
  }

  const server = app.listen(appConfig.port, () => {
    console.log(`[HTTP] Servidor escuchando en: http://localhost:${appConfig.port}`);
    console.log(`[HEALTH] Diagnóstico de salud en: http://localhost:${appConfig.port}${appConfig.apiPrefix}/health`);
    console.log('====================================================\n');
  });

  // Manejo de señales de cierre seguro (Graceful Shutdown)
  const shutdown = async (signal) => {
    console.log(`\n[SHUTDOWN] Señal ${signal} recibida. Cerrando servidor y conexiones...`);
    server.close(async () => {
      await closeDatabasePool();
      console.log('[SHUTDOWN] Servidor y conexiones cerrados exitosamente.');
      process.exit(0);
    });
  };

  process.on('SIGTERM', () => shutdown('SIGTERM'));
  process.on('SIGINT', () => shutdown('SIGINT'));
};

startServer().catch((err) => {
  console.error('[FATAL] Error crítico al iniciar la aplicación:', err);
  process.exit(1);
});
