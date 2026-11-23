import React from 'react';

type Turno = {
  id: number;
  codigo: string;
  estudiante: string;
  hora: string;
  estado: 'En espera' | 'Atendiendo';
};

const turnosIniciales: Turno[] = [
  {
    id: 1,
    codigo: '2026-001',
    estudiante: 'Ana Pérez',
    hora: '09:15',
    estado: 'Atendiendo',
  },
  {
    id: 2,
    codigo: '2026-002',
    estudiante: 'Luis García',
    hora: '09:18',
    estado: 'En espera',
  },
];

export const View: React.FC = () => {
  const [turnos, setTurnos] = React.useState(turnosIniciales);

  const atenderSiguiente = () => {
    setTurnos((actuales) => {
      const primeroEnEspera = actuales.findIndex(
        (turno) => turno.estado === 'En espera',
      );

      if (primeroEnEspera === -1) return actuales;

      return actuales.map((turno, indice) =>
        indice === primeroEnEspera
          ? { ...turno, estado: 'Atendiendo' }
          : turno,
      );
    });
  };

  return (
    <section style={{ padding: 24, color: '#172033' }}>
      <h2>Centro de matrícula</h2>
      <p>Cola de atención de estudiantes para el proceso de matrícula.</p>

      <button
        type="button"
        onClick={atenderSiguiente}
        style={{
          padding: '10px 16px',
          border: 0,
          borderRadius: 8,
          background: '#005a9c',
          color: '#fff',
          cursor: 'pointer',
        }}
      >
        Atender siguiente
      </button>

      <div style={{ marginTop: 24, overflowX: 'auto' }}>
        <table style={{ width: '100%', borderCollapse: 'collapse' }}>
          <thead>
            <tr>
              <th style={celda}>Código</th>
              <th style={celda}>Estudiante</th>
              <th style={celda}>Hora</th>
              <th style={celda}>Estado</th>
            </tr>
          </thead>
          <tbody>
            {turnos.map((turno) => (
              <tr key={turno.id}>
                <td style={celda}>{turno.codigo}</td>
                <td style={celda}>{turno.estudiante}</td>
                <td style={celda}>{turno.hora}</td>
                <td style={celda}>{turno.estado}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  );
};

const celda: React.CSSProperties = {
  padding: 12,
  textAlign: 'left',
  borderBottom: '1px solid #e2e8f0',
};View;
