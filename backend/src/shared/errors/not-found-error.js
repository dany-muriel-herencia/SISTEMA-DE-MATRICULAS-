import { AppError } from './app-error.js';
import { HttpStatus } from '../constants/http-status.js';

export class NotFoundError extends AppError {
  constructor(resource = 'Recurso', identifier = '') {
    const msg = identifier
      ? `${resource} con identificador '${identifier}' no fue encontrado.`
      : `${resource} no encontrado.`;
    super(msg, HttpStatus.NOT_FOUND);
  }
}
