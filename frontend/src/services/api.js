const API_BASE_URL = 'http://localhost:3000/api/v1';

export class ApiClient {
  /**
   * Obtiene el diagnóstico de salud de la API backend y base de datos.
   */
  static async getHealthStatus() {
    try {
      const response = await fetch(`${API_BASE_URL}/health`);
      const json = await response.json();
      return { ok: response.ok, status: response.status, data: json };
    } catch (error) {
      return {
        ok: false,
        status: 0,
        data: {
          success: false,
          message: 'No se pudo establecer conexión con el servidor API local (http://localhost:3000)',
          errors: error.message
        }
      };
    }
  }
}
