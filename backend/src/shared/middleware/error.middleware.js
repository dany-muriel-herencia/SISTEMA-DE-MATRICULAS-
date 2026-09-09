import { AppError } from '../errors/app-error.js';
import { ApiResponse } from '../utils/api-response.js';
import { HttpStatus } from '../constants/http-status.js';
import { appConfig } from '../../config/app.config.js';

/**
 * Middleware global de captura y formateo de errores.
 */
export const errorHandler = (err, req, res, next) => {
  let statusCode = HttpStatus.INTERNAL_SERVER_ERROR;
  let message = 'Ha ocurrido un error inesperado en el servidor';
  let errors = null;

  if (err instanceof AppError) {
    statusCode = err.statusCode;
    message = err.message;
    errors = err.details || null;
  } else if (err.type === 'entity.parse.failed') {
    statusCode = HttpStatus.BAD_REQUEST;
    message = 'El cuerpo de la solicitud no es un formato JSON válido';
  } else if (err.code === 'ER_DUP_ENTRY') {
    statusCode = HttpStatus.CONFLICT;
    message = 'Conflicto: Ya existe un registro con los datos suministrados';
  }

  // En entorno de desarrollo, registrar detalles en consola
  if (appConfig.nodeEnv === 'development') {
    console.error(`[Error Handler] ${req.method} ${req.url} -> [${statusCode}] ${err.message}`);
    if (err.stack && !(err instanceof AppError)) {
      console.error(err.stack);
    }
  }

  return ApiResponse.error(res, {
    statusCode,
    message,
    errors
  });
};

/**
 * Middleware para rutas no encontradas (404).
 */
export const notFoundHandler = (req, res, next) => {
  return ApiResponse.error(res, {
    statusCode: HttpStatus.NOT_FOUND,
    message: `La ruta solicitada [${req.method} ${req.originalUrl}] no existe en el servidor`
  });
};
