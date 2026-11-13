import express from 'express';
import { DatabaseClient } from '@sgau/database-client';

const app = express();
const PORT = process.env.PORT || 3008;

app.use(express.json());
DatabaseClient.initialize({ database: process.env.DB_NAME || 'sgau_enrollment' });

app.get('/health', (req, res) => {
  res.json({ service: 'rectification-service', status: 'UP' });
});

// CU-66 Simulación de carga y CU-72 Aplicación con Acta REC-2026-XXXX
app.post('/rectificar', (req, res) => {
  const { idMatricula, movimientos } = req.body;
  const nroActa = `REC-${new Date().getFullYear()}-${Math.floor(Math.random() * 9000) + 1000}`;
  res.json({
    message: 'Rectificación procesada con éxito',
    nroActa,
    movimientosProcesados: movimientos?.length || 0
  });
});

app.listen(PORT, () => {
  console.log(`[RECTIFICATION-SERVICE] Corriendo en puerto ${PORT}`);
});

