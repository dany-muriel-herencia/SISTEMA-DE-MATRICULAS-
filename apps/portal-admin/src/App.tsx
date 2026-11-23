import React, { useState } from 'react';
import { View as CentroMatricula } from './views/centro-matricula';
import {
  GraduationCap,
  Layers,
  Clock,
  Building2,
  CreditCard,
  BarChart3,
  FileEdit,
  ShieldCheck,
} from 'lucide-react';

const App: React.FC = () => {
  const [moduloActivo, setModuloActivo] = useState('seguridad');

  const menuItems = [
    { id: 'seguridad', label: '1. Seguridad y Cuentas (M1, M2)', icon: <ShieldCheck size={18} /> },
    { id: 'estudiantes', label: '2. Directorio Alumnos y Kardex (M3)', icon: <GraduationCap size={18} /> },
    { id: 'academico', label: '3. Planes y Mallas Curriculares (M4)', icon: <Layers size={18} /> },
    { id: 'centro-matricula', label: '4. Cola en Vivo y Matrícula (M6)', icon: <Clock size={18} /> },
    { id: 'horarios-aulas', label: '5. Matriz de Aulas y Horarios (M7)', icon: <Building2 size={18} /> },
    { id: 'tesoreria', label: '6. Caja y Boletas QR (M8)', icon: <CreditCard size={18} /> },
    { id: 'analitica', label: '7. Analítica y Actas Oficiales (M9)', icon: <BarChart3 size={18} /> },
    { id: 'rectificaciones', label: '8. Panel de Rectificaciones (M10)', icon: <FileEdit size={18} /> },
  ];

  const moduloSeleccionado = menuItems.find(
    (modulo) => modulo.id === moduloActivo,
  );

  return (
    <div
      style={{
        display: 'flex',
        minHeight: '100vh',
        fontFamily: 'Segoe UI, sans-serif',
      }}
    >
      <aside
        style={{
          width: 280,
          backgroundColor: '#001E36',
          color: 'white',
          padding: '24px 16px',
        }}
      >
        <div
          style={{
            paddingBottom: 16,
            borderBottom: '1px solid #1a3a5c',
            marginBottom: 20,
          }}
        >
          <h2 style={{ fontSize: 18, margin: 0 }}>SGAU UNJBG</h2>
          <span style={{ fontSize: 12, color: '#90cdf4' }}>
            Backoffice Administrativo ESIS
          </span>
        </div>

        <nav style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
          {menuItems.map((item) => (
            <button
              key={item.id}
              type="button"
              onClick={() => setModuloActivo(item.id)}
              style={{
                display: 'flex',
                alignItems: 'center',
                gap: 12,
                width: '100%',
                padding: '10px 14px',
                textAlign: 'left',
                border: 0,
                borderRadius: 6,
                cursor: 'pointer',
                fontSize: 13,
                backgroundColor:
                  moduloActivo === item.id ? '#005A9C' : 'transparent',
                color: moduloActivo === item.id ? 'white' : '#cbd5e1',
                fontWeight: moduloActivo === item.id ? 600 : 400,
              }}
            >
              {item.icon}
              {item.label}
            </button>
          ))}
        </nav>
      </aside>

      <main
        style={{
          flex: 1,
          padding: 32,
          backgroundColor: '#f8fafc',
        }}
      >
        <header
          style={{
            borderBottom: '1px solid #e2e8f0',
            paddingBottom: 16,
            marginBottom: 24,
          }}
        >
          <h1 style={{ fontSize: 22, margin: 0, color: '#0f172a' }}>
            Centro de Control: {moduloSeleccionado?.label}
          </h1>
          <p style={{ margin: '4px 0 0', color: '#64748b', fontSize: 14 }}>
            Servicio conectado al API Gateway en puerto 8000
          </p>
        </header>

        <div
          style={{
            backgroundColor: 'white',
            borderRadius: 10,
            padding: 24,
            border: '1px solid #e2e8f0',
          }}
        >
          {moduloActivo === 'centro-matricula' ? (
            <CentroMatricula />
          ) : (
            <>
              <h3 style={{ marginTop: 0, color: '#1e293b' }}>
                Panel Operativo Activo
              </h3>
              <p style={{ color: '#475569', fontSize: 14 }}>
                Selecciona “Cola en Vivo y Matrícula” en el menú para abrir el
                módulo de matrícula.
              </p>
            </>
          )}
        </div>
      </main>
    </div>
  );
};

export default App;