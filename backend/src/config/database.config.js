import dotenv from 'dotenv';
dotenv.config();

export const databaseConfig = {
  host: process.env.DB_HOST || '127.0.0.1',
  port: parseInt(process.env.DB_PORT || '3306', 10),
  database: process.env.DB_NAME || 'db_matricula_unjbg',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  waitForConnections: process.env.DB_WAIT_FOR_CONNECTIONS === 'true' || true,
  connectionLimit: parseInt(process.env.DB_CONNECTION_LIMIT || '10', 10),
  queueLimit: parseInt(process.env.DB_QUEUE_LIMIT || '0', 10),
  timezone: 'Z',
  dateStrings: true
};
