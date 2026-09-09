import { Router } from 'express';
import { HealthRepository } from './health.repository.js';
import { HealthService } from './health.service.js';
import { HealthController } from './health.controller.js';

const router = Router();

// Inyección de dependencias manual (Clean Architecture sin acoplamiento a frameworks)
const healthRepository = new HealthRepository();
const healthService = new HealthService(healthRepository);
const healthController = new HealthController(healthService);

router.get('/health', healthController.getStatus);

export default router;
