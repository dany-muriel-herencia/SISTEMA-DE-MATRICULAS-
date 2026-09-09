/**
 * Utilidades básicas de validación de campos comunes.
 */
export class ValidatorUtil {
  static isEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
  }

  static isNotEmpty(value) {
    return value !== undefined && value !== null && String(value).trim().length > 0;
  }

  static isPositiveInteger(value) {
    const num = Number(value);
    return Number.isInteger(num) && num > 0;
  }
}
