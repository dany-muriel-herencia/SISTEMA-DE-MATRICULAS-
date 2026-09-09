import { testDatabaseConnection, closeDatabasePool } from './connection.js';
import { databaseConfig } from '../config/database.config.js';

async function main() {
  console.log('====================================================');
  console.log('  TEST DE CONEXIÓN A BASE DE DATOS LOCAL (SIN ORM)');
  console.log('====================================================');
  console.log(`Host:     ${databaseConfig.host}:${databaseConfig.port}`);
  console.log(`Database: ${databaseConfig.database}`);
  console.log(`User:     ${databaseConfig.user}`);
  console.log('----------------------------------------------------');

  const result = await testDatabaseConnection();

  if (result.isConnected) {
    console.log(`[EXITO] ${result.message}`);
    console.log(`Latencia: ${result.latencyMs} ms`);
  } else {
    console.log(`[AVISO] ${result.message}`);
    console.log('\nSugerencia: Asegúrate de que MySQL esté iniciado (ej. en Laragon) y que la base de datos exista.');
  }

  await closeDatabasePool();
  process.exit(result.isConnected ? 0 : 1);
}

main();
