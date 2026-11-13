import express from 'express';
import { DatabaseClient } from '@sgau/database-client';

const app = express();
const PORT = process.env.PORT || 3003;

app.use(express.json());
DatabaseClient.initialize({ database: process.env.DB_NAME || 'sgau_academic' });

app.get('/health', (req, res) => {
  res.json({ service: 'academic-service', status: 'UP' });
});

// Listar facultades y planes
app.get('/facultades', (req, res) => {
  res.json([{ idFacultad: 1, codigo: 'FAIN', nombre: 'Facultad de Ingeniería', decano: 'Decano FAIN' }]);
});

app.get('/planes/:idPlan/malla', (req, res) => {
  res.json({ idPlan: req.params.idPlan, ciclo: 1, cursos: [] });
});

app.listen(PORT, () => {
  console.log(`[ACADEMIC-SERVICE] Corriendo en puerto ${PORT}`);
});

