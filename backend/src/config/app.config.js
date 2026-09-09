import dotenv from 'dotenv';
dotenv.config();

export const appConfig = {
  port: parseInt(process.env.PORT || '3000', 10),
  nodeEnv: process.env.NODE_ENV || 'development',
  apiPrefix: process.env.API_PREFIX || '/api/v1',
  corsOrigin: process.env.CORS_ORIGIN || '*',
  jwt: {
    secret: process.env.JWT_SECRET || 'default_jwt_secret_dev_key',
    expiresIn: process.env.JWT_EXPIRES_IN || '8h'
  }
};
