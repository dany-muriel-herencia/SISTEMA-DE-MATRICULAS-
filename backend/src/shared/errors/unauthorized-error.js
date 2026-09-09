import { AppError } from './app-error.js';
import { HttpStatus } from '../constants/http-status.js';

export class UnauthorizedError extends AppError {
  constructor(message = 'Acceso no autenticado. Credenciales inválidas o token faltante.') {
    super(message, HttpStatus.UNAUTHORIZED);
  }
}
