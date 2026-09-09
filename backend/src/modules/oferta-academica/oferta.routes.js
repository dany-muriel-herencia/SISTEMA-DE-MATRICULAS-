import { Router } from 'express';
import { ApiResponse } from '../../shared/utils/api-response.js';

const router = Router();

router.get('/', (req, res) => {
  return ApiResponse.success(res, {
    message: 'Módulo de Gestión de Oferta Académica (Secciones, Docentes, Aulas, Horarios) - Listo para implementar',
    data: []
  });
});

export default router;
