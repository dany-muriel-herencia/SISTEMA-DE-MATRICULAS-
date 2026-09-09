import { AppError } from './app-error.js';
import { HttpStatus } from '../constants/http-status.js';

export class DatabaseError extends AppError {
  constructor(message = 'Error en operación de base de datos', queryInfo = null) {
    super(message, HttpStatus.INTERNAL_SERVER_ERROR, queryInfo);
  }
}
