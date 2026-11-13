import express from 'express';
import { DatabaseClient } from '@sgau/database-client';

const app = express();
const PORT = process.env.PORT || 3001;

app.use(express.json());

// Inicializar conexión
DatabaseClient.initialize({ database: process.env.DB_NAME || 'sgau_auth' });

app.get('/health', (req, res) => {
  res.json({ service: 'auth-service', status: 'UP' });
});

// Rutas de autenticación
app.post('/login', async (req, res) => {
  const { correo, password } = req.body;
  // CU-01 Login con verificación de intentos y bloqueo a 3 intentos (RN-15)
  res.json({ message: 'Autenticación exitosa', token: 'mock-jwt-token' });
});

app.post('/logout', (req, res) => {
  // CU-02 Logout
  res.json({ message: 'Sesión cerrada correctamente' });
});

app.post('/recuperar-clave', (req, res) => {
  // CU-03 Recuperación de clave
  res.json({ message: 'Enlace de recuperación enviado' });
});

app.listen(PORT, () => {
  console.log(`[AUTH-SERVICE] Corriendo en puerto ${PORT}`);
});

