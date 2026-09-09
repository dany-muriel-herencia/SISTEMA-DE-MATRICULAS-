import { AppError } from './app-error.js';
import { HttpStatus } from '../constants/http-status.js';

export class ForbiddenError extends AppError {
  constructor(message = 'Acceso denegado. No posee permisos suficientes para esta acción.') {
    super(message, HttpStatus.FORBIDDEN);
  }
}
