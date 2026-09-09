import { Router } from 'express';
import healthRoutes from './modules/health/health.routes.js';
import authRoutes from './modules/auth/auth.routes.js';
import estudiantesRoutes from './modules/estudiantes/estudiantes.routes.js';
import academicoRoutes from './modules/academico/academico.routes.js';
import periodosRoutes from './modules/periodos/periodos.routes.js';
import ofertaRoutes from './modules/oferta-academica/oferta.routes.js';
import matriculaRoutes from './modules/matricula/matricula.routes.js';

const apiRouter = Router();

// Registro de rutas por módulo según especificación REST
apiRouter.use('/', healthRoutes);
apiRouter.use('/auth', authRoutes);
apiRouter.use('/estudiantes', estudiantesRoutes);
apiRouter.use('/cursos', academicoRoutes);
apiRouter.use('/periodos', periodosRoutes);
apiRouter.use('/secciones', ofertaRoutes);
apiRouter.use('/matriculas', matriculaRoutes);

export default apiRouter;
