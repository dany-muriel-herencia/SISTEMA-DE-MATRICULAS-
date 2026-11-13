import express from 'express';
import crypto from 'crypto';

const app = express();
const PORT = process.env.PORT || 3007;

app.use(express.json());

app.get('/health', (req, res) => {
  res.json({ service: 'reporting-service', status: 'UP' });
});

// CU-52 Actas oficiales con hash seguro
app.get('/actas/:idSeccion', (req, res) => {
  const content = `Acta-Seccion-${req.params.idSeccion}-${Date.now()}`;
  const hash = crypto.createHash('sha256').update(content).digest('hex');
  res.json({
    idSeccion: req.params.idSeccion,
    titulo: 'Acta Oficial de Evaluación',
    hashCriptografico: hash,
    fechaEmision: new Date()
  });
});

app.listen(PORT, () => {
  console.log(`[REPORTING-SERVICE] Corriendo en puerto ${PORT}`);
});

