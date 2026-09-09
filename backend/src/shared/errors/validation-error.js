import { AppError } from './app-error.js';
import { HttpStatus } from '../constants/http-status.js';

export class ValidationError extends AppError {
  constructor(message = 'Error de validación de datos', validationErrors = []) {
    super(message, HttpStatus.BAD_REQUEST, validationErrors);
  }
}
