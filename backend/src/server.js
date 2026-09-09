import express from 'express';
import cors from 'cors';
import { appConfig } from './config/app.config.js';
import apiRouter from './routes.js';
import { errorHandler, notFoundHandler } from './shared/middleware/error.middleware.js';
import { requestLogger } from './shared/middleware/logger.middleware.js';

export const createServer = () => {
  const app = express();

  // Middlewares globales de seguridad y utilidades
  app.use(cors({
    origin: appConfig.corsOrigin,
    methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization']
  }));

  app.use(express.json({ limit: '10mb' }));
  app.use(express.urlencoded({ extended: true }));
  app.use(requestLogger);

  // Registro de rutas con prefijo API configurado (/api/v1)
  app.use(appConfig.apiPrefix, apiRouter);

  // Manejo de recursos no encontrados (404)
  app.use(notFoundHandler);

  // Middleware global de errores
  app.use(errorHandler);

  return app;
};
