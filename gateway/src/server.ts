import express from 'express';
import cors from 'cors';
import { createProxyMiddleware } from 'http-proxy-middleware';
import { serviceRoutes } from './config/routes';
import { authMiddleware } from './middleware/auth';

const app = express();
const PORT = process.env.PORT || 8000;

app.use(cors());
app.use(express.json());

// Health Check
app.get('/health', (req, res) => {
  res.json({ status: 'UP', service: 'API Gateway SGAU UNJBG', timestamp: new Date() });
});

// Middleware de seguridad
app.use(authMiddleware);

// Rutas Proxies hacia microservicios
app.use('/api/v1/auth', createProxyMiddleware({ target: serviceRoutes.auth, changeOrigin: true }));
app.use('/api/v1/students', createProxyMiddleware({ target: serviceRoutes.student, changeOrigin: true }));
app.use('/api/v1/academic', createProxyMiddleware({ target: serviceRoutes.academic, changeOrigin: true }));
app.use('/api/v1/schedules', createProxyMiddleware({ target: serviceRoutes.schedule, changeOrigin: true }));
app.use('/api/v1/enrollment', createProxyMiddleware({ target: serviceRoutes.enrollment, changeOrigin: true }));
app.use('/api/v1/finance', createProxyMiddleware({ target: serviceRoutes.finance, changeOrigin: true }));
app.use('/api/v1/reporting', createProxyMiddleware({ target: serviceRoutes.reporting, changeOrigin: true }));
app.use('/api/v1/rectification', createProxyMiddleware({ target: serviceRoutes.rectification, changeOrigin: true }));

app.listen(PORT, () => {
  console.log(`[API GATEWAY] SGAU-UNJBG corriendo en puerto ${PORT}`);
});

