import { HttpStatus } from '../constants/http-status.js';

export class ApiResponse {
  /**
   * Envía una respuesta de éxito estandarizada.
   * @param {import('express').Response} res
   * @param {object} options
   */
  static success(res, { statusCode = HttpStatus.OK, message = 'Operación exitosa', data = null, meta = null }) {
    const responseBody = {
      success: true,
      statusCode,
      message,
      data
    };

    if (meta) {
      responseBody.meta = meta;
    }

    return res.status(statusCode).json(responseBody);
  }

  /**
   * Envía una respuesta de error estandarizada.
   * @param {import('express').Response} res
   * @param {object} options
   */
  static error(res, { statusCode = HttpStatus.INTERNAL_SERVER_ERROR, message = 'Error interno del servidor', errors = null }) {
    const responseBody = {
      success: false,
      statusCode,
      message,
      errors
    };

    return res.status(statusCode).json(responseBody);
  }
}
