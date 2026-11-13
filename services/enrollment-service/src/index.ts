import express from 'express';
import { DatabaseClient } from '@sgau/database-client';
import { VirtualQueueManager } from './queue/virtual-queue.manager';

const app = express();
const PORT = process.env.PORT || 3005;

app.use(express.json());
DatabaseClient.initialize({ database: process.env.DB_NAME || 'sgau_enrollment' });
const queueManager = new VirtualQueueManager();

app.get('/health', (req, res) => {
  res.json({ service: 'enrollment-service', status: 'UP' });
});

// CU-38 Cola de espera virtual
app.post('/cola/ingresar', (req, res) => {
  const { idEstudiante, idPeriodo, priorizado } = req.body;
  const ticket = queueManager.ingresarCola(idEstudiante, idPeriodo, priorizado);
  res.json(ticket);
});

// CU-23 Confirmar Matrícula
app.post('/confirmar', async (req, res) => {
  const { idMatricula } = req.body;
  const constancia = `CONST-${new Date().getFullYear()}-${String(idMatricula).padStart(6, '0')}`;
  res.json({ message: 'Matrícula confirmada', constancia });
});

app.listen(PORT, () => {
  console.log(`[ENROLLMENT-SERVICE] Corriendo en puerto ${PORT}`);
});

