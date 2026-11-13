import express from 'express';
import { DatabaseClient } from '@sgau/database-client';

const app = express();
const PORT = process.env.PORT || 3006;

app.use(express.json());
DatabaseClient.initialize({ database: process.env.DB_NAME || 'sgau_finance' });

app.get('/health', (req, res) => {
  res.json({ service: 'finance-service', status: 'UP' });
});

// CU-45 Cobro en ventanilla y CU-47 Emisión Boleta con QR
app.post('/pagos/ventanilla', (req, res) => {
  const { idDeuda, idEstudiante, monto } = req.body;
  const correlativo = Math.floor(Math.random() * 900000) + 100000;
  res.json({
    message: 'Pago registrado con éxito',
    comprobante: {
      serie: 'B001',
      correlativo,
      monto,
      qr: 'DATA-QR-HASH-AUTHENTICATED'
    }
  });
});

app.listen(PORT, () => {
  console.log(`[FINANCE-SERVICE] Corriendo en puerto ${PORT}`);
});

