import mysql, { Pool, PoolConnection } from 'mysql2/promise';
import dotenv from 'dotenv';

dotenv.config();

export interface DatabaseConfig {
  host?: string;
  port?: number;
  user?: string;
  password?: string;
  database?: string;
  connectionLimit?: number;
}

export class DatabaseClient {
  private static pool: Pool | null = null;

  public static initialize(config?: DatabaseConfig): Pool {
    if (!this.pool) {
      this.pool = mysql.createPool({
        host: config?.host || process.env.DB_HOST || '127.0.0.1',
        port: config?.port || Number(process.env.DB_PORT) || 3306,
        user: config?.user || process.env.DB_USER || 'root',
        password: config?.password ?? process.env.DB_PASSWORD ?? '',
        database: config?.database || process.env.DB_NAME || 'sgau_db',
        waitForConnections: true,
        connectionLimit: config?.connectionLimit || 20,
        queueLimit: 0,
        enableKeepAlive: true,
        keepAliveInitialDelay: 0
      });
    }
    return this.pool;
  }

  public static getPool(): Pool {
    if (!this.pool) {
      return this.initialize();
    }
    return this.pool;
  }

  public static async transaction<T>(callback: (conn: PoolConnection) => Promise<T>): Promise<T> {
    const pool = this.getPool();
    const conn = await pool.getConnection();
    try {
      await conn.beginTransaction();
      const result = await callback(conn);
      await conn.commit();
      return result;
    } catch (error) {
      await conn.rollback();
      throw error;
    } finally {
      conn.release();
    }
  }
}
