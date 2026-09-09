import { HttpStatus } from '../constants/http-status.js';

export class AppError extends Error {
  constructor(message, statusCode = HttpStatus.INTERNAL_SERVER_ERROR, details = null) {
    super(message);
    this.name = this.constructor.name;
    this.statusCode = statusCode;
    this.isOperational = true;
    this.details = details;
    Error.captureStackTrace(this, this.constructor);
  }
}
