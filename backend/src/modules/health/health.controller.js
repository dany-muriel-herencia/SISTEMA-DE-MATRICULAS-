import { ApiResponse } from '../../shared/utils/api-response.js';
import { HttpStatus } from '../../shared/constants/http-status.js';

export class HealthController {
  constructor(healthService) {
    this.healthService = healthService;
  }

  getStatus = async (req, res, next) => {
    try {
      const statusData = await this.healthService.getSystemStatus();
      const isDbOk = statusData.database.status === 'CONNECTED';

      return ApiResponse.success(res, {
        statusCode: isDbOk ? HttpStatus.OK : HttpStatus.SERVICE_UNAVAILABLE,
        message: isDbOk
          ? 'Servidor y base de datos operando correctamente'
          : 'Servidor activo pero la base de datos no se encuentra conectada',
        data: statusData
      });
    } catch (error) {
      next(error);
    }
  };
}
