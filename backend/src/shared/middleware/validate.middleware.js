import { ValidationError } from '../errors/validation-error.js';

/**
 * Middleware fábrica para validar datos del request contra una función de validación personalizada.
 * @param {Function} validatorFn - Función que recibe req.body/params/query y retorna { isValid, errors }
 */
export const validateRequest = (validatorFn) => {
  return (req, res, next) => {
    const result = validatorFn(req);
    if (!result.isValid) {
      return next(new ValidationError('Datos de solicitud inválidos', result.errors));
    }
    next();
  };
};
