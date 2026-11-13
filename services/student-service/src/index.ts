import express from 'express';
import { DatabaseClient } from '@sgau/database-client';

const app = express();
const PORT = process.env.PORT || 3002;

app.use(express.json());
DatabaseClient.initialize({ database: process.env.DB_NAME || 'sgau_student' });

app.get('/health', (req, res) => {
  res.json({ service: 'student-service', status: 'UP' });
});

// CU-08 Expediente y CU-11 Kardex
app.get('/:idEstudiante/kardex', (req, res) => {
  res.json({
    idEstudiante: req.params.idEstudiante,
    promedioPonderado: 14.85,
    creditosAprobados: 66,
    condicion: 'REGULAR'
  });
});

app.listen(PORT, () => {
  console.log(`[STUDENT-SERVICE] Corriendo en puerto ${PORT}`);
});

