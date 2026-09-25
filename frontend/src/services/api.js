const API_BASE_URL = new URL('../../../backend/public/api/', import.meta.url);
export class ApiError extends Error {
  constructor(message, status) { super(message); this.status = status; }
}
export class ApiClient {
  static token = '';
  static async request(path, { method = 'GET', body, signal } = {}) {
    let response;
    try {
      response = await fetch(new URL(path, API_BASE_URL), {
        method, signal, cache: 'no-store',
        headers: { Accept: 'application/json', ...(body ? { 'Content-Type': 'application/json' } : {}),
          ...(this.token ? { Authorization: `Bearer ${this.token}` } : {}) },
        ...(body ? { body: JSON.stringify(body) } : {}),
      });
    } catch (error) {
      if (error.name === 'AbortError') throw error;
      throw new ApiError('No se pudo conectar con el servidor. Revisa la conexión e intenta nuevamente.', 0);
    }
    let result;
    try { result = await response.json(); }
    catch { throw new ApiError('El servidor devolvió una respuesta inesperada.', response.status); }
    if (!response.ok || !result.success) throw new ApiError(result.message || 'No se pudo completar la operación.', response.status);
    return result.data;
  }
  static async listAll(path) {
    const rows = [];
    for (let offset = 0; ; offset += 50) {
      const page = await this.request(`${path}?limit=50&offset=${offset}`);
      rows.push(...page);
      if (page.length < 50) return rows;
    }
  }
}
