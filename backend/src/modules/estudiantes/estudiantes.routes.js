import { Router } from 'express';
import { ApiResponse } from '../../shared/utils/api-response.js';

const router = Router();

// Endpoint base informativo listo para implementar en etapas posteriores
router.get('/', (req, res) => {
  return ApiResponse.success(res, {
    message: 'Módulo de Gestión de Estudiantes - Listo para implementar',
    data: []
  });
});

export default router;
