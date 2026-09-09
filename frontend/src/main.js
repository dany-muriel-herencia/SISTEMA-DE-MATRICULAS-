import { ApiClient } from './services/api.js';

document.addEventListener('DOMContentLoaded', async () => {
  const statusLabel = document.getElementById('status-label');
  const statusDot = document.querySelector('.status-dot');
  const healthOutput = document.getElementById('health-output');
  const dbMetric = document.getElementById('db-metric');

  try {
    const result = await ApiClient.getHealthStatus();
    healthOutput.textContent = JSON.stringify(result.data, null, 2);

    if (result.ok && result.data.success) {
      statusLabel.textContent = 'API Online';
      statusDot.classList.add('online');
      
      const dbStatus = result.data.data?.database?.status;
      if (dbStatus === 'CONNECTED') {
        dbMetric.textContent = `MySQL Conectado (${result.data.data.database.latencyMs} ms)`;
        dbMetric.style.color = '#34d399';
      } else {
        dbMetric.textContent = 'MySQL Desconectado';
        dbMetric.style.color = '#fbbf24';
      }
    } else {
      statusLabel.textContent = 'API Offline o Desconectada';
      statusDot.classList.add('offline');
      dbMetric.textContent = 'Sin conexión';
      dbMetric.style.color = '#f87171';
    }
  } catch (err) {
    statusLabel.textContent = 'Error de conexión';
    statusDot.classList.add('offline');
    healthOutput.textContent = `Error al consultar estado: ${err.message}`;
  }
});
