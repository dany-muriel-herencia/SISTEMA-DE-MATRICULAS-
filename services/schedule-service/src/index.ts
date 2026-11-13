import express from 'express';
import { DatabaseClient } from '@sgau/database-client';

const app = express();
const PORT = process.env.PORT || 3004;

app.use(express.json());
DatabaseClient.initialize({ database: process.env.DB_NAME || 'sgau_schedule' });

app.get('/health', (req, res) => {
  res.json({ service: 'schedule-service', status: 'UP' });
});

// CU-32 Aulas y CU-35 Detección de Cruces
app.get('/secciones/:idCurso', (req, res) => {
  res.json([
    {
      idSeccion: 101,
      codigoSeccion: 'A',
      vacantesDisponibles: 12,
      aforo: 40,
      horarios: [{ dia: 'LUNES', inicio: '08:00', fin: '10:00' }]
    }
  ]);
});

app.listen(PORT, () => {
  console.log(`[SCHEDULE-SERVICE] Corriendo en puerto ${PORT}`);
});

