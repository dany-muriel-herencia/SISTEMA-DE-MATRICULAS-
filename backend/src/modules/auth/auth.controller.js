import { ApiResponse } from '../../shared/utils/api-response.js';
import { HttpStatus } from '../../shared/constants/http-status.js';

export class AuthController {
  constructor(authService) {
    this.authService = authService;
  }

  login = async (req, res, next) => {
    try {
      const { email, password } = req.body;
      const result = await this.authService.login(email, password);
      return ApiResponse.success(res, {
        statusCode: HttpStatus.OK,
        message: 'Sesión iniciada correctamente',
        data: result
      });
    } catch (error) {
      next(error);
    }
  };
}
