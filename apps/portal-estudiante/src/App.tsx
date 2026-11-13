import React, { useState } from 'react';
import { Calendar, AlertCircle, CheckCircle, Clock, BookOpen, ShieldCheck } from 'lucide-react';

interface CursoItem {
  idCurso: number;
  codigo: string;
  nombre: string;
  creditos: number;
  ciclo: number;
  horario: string;
  dia: string;
  horaInicio: string;
  horaFin: string;
}

const CURSOS_DISPONIBLES: CursoItem[] = [
  { idCurso: 1, codigo: 'ESIS-501', nombre: 'Base de Datos II', creditos: 4, ciclo: 5, horario: 'Lun 08:00 - 10:00', dia: 'LUNES', horaInicio: '08:00', horaFin: '10:00' },
  { idCurso: 2, codigo: 'ESIS-502', nombre: 'Ingeniería de Software I', creditos: 4, ciclo: 5, horario: 'Lun 09:00 - 11:00', dia: 'LUNES', horaInicio: '09:00', horaFin: '11:00' }, // Provoca cruce intencional
  { idCurso: 3, codigo: 'ESIS-503', nombre: 'Sistemas Operativos', creditos: 4, ciclo: 5, horario: 'Mar 10:00 - 12:00', dia: 'MARTES', horaInicio: '10:00', horaFin: '12:00' },
  { idCurso: 4, codigo: 'ESIS-504', nombre: 'Redes y Comunicaciones', creditos: 4, ciclo: 5, horario: 'Mie 08:00 - 11:00', dia: 'MIERCOLES', horaInicio: '08:00', horaFin: '11:00' },
  { idCurso: 5, codigo: 'ESIS-505', nombre: 'Inteligencia Artificial', creditos: 4, ciclo: 5, horario: 'Jue 14:00 - 17:00', dia: 'JUEVES', horaInicio: '14:00', horaFin: '17:00' },
  { idCurso: 6, codigo: 'ESIS-506', nombre: 'Taller de Programación Web', creditos: 3, ciclo: 5, horario: 'Vie 08:00 - 11:00', dia: 'VIERNES', horaInicio: '08:00', horaFin: '11:00' }
];

export const App: React.FC = () => {
  const [seleccionados, setSeleccionados] = useState<CursoItem[]>([]);
  const LIMITE_CREDITOS = 22;

  const totalCreditos = seleccionados.reduce((sum, c) => sum + c.creditos, 0);

  // Detección de colisiones/cruces horarios (CU-27 / RN-04)
  const detectarCruce = (nuevoCurso: CursoItem): CursoItem | null => {
    for (const c of seleccionados) {
      if (c.dia === nuevoCurso.dia) {
        if (nuevoCurso.horaInicio < c.horaFin && nuevoCurso.horaFin > c.horaInicio) {
          return c;
        }
      }
    }
    return null;
  };

  const toggleCurso = (curso: CursoItem) => {
    if (seleccionados.some(c => c.idCurso === curso.idCurso)) {
      setSeleccionados(seleccionados.filter(c => c.idCurso !== curso.idCurso));
      return;
    }

    if (totalCreditos + curso.creditos > LIMITE_CREDITOS) {
      alert(`Supera el límite de ${LIMITE_CREDITOS} créditos académicos permitidos.`);
      return;
    }

    const cruce = detectarCruce(curso);
    if (cruce) {
      alert(`¡Cruce de horario detectado con "${cruce.nombre}" (${cruce.horario})!`);
      return;
    }

    setSeleccionados([...seleccionados, curso]);
  };

  return (
    <div style={{ fontFamily: 'Segoe UI, Roboto, sans-serif', maxWidth: '1100px', margin: '0 auto', padding: '24px' }}>
      {/* Header Institucional */}
      <header style={{ backgroundColor: '#002B49', color: 'white', padding: '20px 28px', borderRadius: '12px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div>
          <h1 style={{ margin: 0, fontSize: '22px' }}>SGAU / SIGA — Portal del Estudiante UNJBG</h1>
          <p style={{ margin: '6px 0 0 0', opacity: 0.85, fontSize: '14px' }}>
            Escuela Profesional de Ingeniería en Informática y Sistemas (ESIS) | Semestre 2026-I
          </p>
        </div>
        <div style={{ textAlign: 'right' }}>
          <span style={{ backgroundColor: '#005A9C', padding: '6px 12px', borderRadius: '20px', fontSize: '13px', display: 'inline-flex', alignItems: 'center', gap: '6px' }}>
            <ShieldCheck size={16} /> Estudiante Regular
          </span>
        </div>
      </header>

      {/* Resumen de Créditos y Semáforo */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '16px', marginTop: '20px' }}>
        <div style={cardMetricStyle}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <BookOpen color="#005A9C" />
            <span style={{ fontSize: '14px', color: '#666' }}>Cursos Inscritos</span>
          </div>
          <strong style={{ fontSize: '24px', marginTop: '6px', display: 'block' }}>{seleccionados.length}</strong>
        </div>

        <div style={cardMetricStyle}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <Clock color="#005A9C" />
            <span style={{ fontSize: '14px', color: '#666' }}>Total Créditos</span>
          </div>
          <strong style={{ fontSize: '24px', marginTop: '6px', display: 'block', color: totalCreditos > LIMITE_CREDITOS ? '#dc2626' : '#16a34a' }}>
            {totalCreditos} / {LIMITE_CREDITOS}
          </strong>
        </div>

        <div style={cardMetricStyle}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <Calendar color="#005A9C" />
            <span style={{ fontSize: '14px', color: '#666' }}>Semáforo de Cruces</span>
          </div>
          <span style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', marginTop: '8px', color: '#16a34a', fontWeight: 'bold' }}>
            <CheckCircle size={18} /> Sin solapamientos
          </span>
        </div>
      </div>

      {/* Catálogo de Asignaturas Disponibles */}
      <section style={{ marginTop: '28px' }}>
        <h2 style={{ fontSize: '18px', color: '#1e293b', marginBottom: '14px' }}>Oferta de Secciones y Cursos Disponibles</h2>
        <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
          {CURSOS_DISPONIBLES.map(curso => {
            const isInscrito = seleccionados.some(c => c.idCurso === curso.idCurso);
            const crucePotencial = !isInscrito && detectarCruce(curso);

            return (
              <div
                key={curso.idCurso}
                style={{
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  padding: '14px 18px',
                  borderRadius: '8px',
                  border: isInscrito ? '2px solid #005A9C' : '1px solid #e2e8f0',
                  backgroundColor: isInscrito ? '#f0f7ff' : 'white'
                }}
              >
                <div>
                  <strong style={{ fontSize: '15px', color: '#0f172a' }}>{curso.codigo} — {curso.nombre}</strong>
                  <div style={{ display: 'flex', gap: '16px', fontSize: '13px', color: '#64748b', marginTop: '4px' }}>
                    <span>Créditos: <strong>{curso.creditos}</strong></span>
                    <span>Horario: <strong>{curso.horario}</strong></span>
                  </div>
                  {crucePotencial && (
                    <div style={{ color: '#dc2626', fontSize: '12px', marginTop: '4px', display: 'flex', alignItems: 'center', gap: '4px' }}>
                      <AlertCircle size={14} /> Cruce con: {crucePotencial.nombre}
                    </div>
                  )}
                </div>

                <button
                  onClick={() => toggleCurso(curso)}
                  style={{
                    backgroundColor: isInscrito ? '#dc2626' : '#005A9C',
                    color: 'white',
                    border: 'none',
                    padding: '8px 16px',
                    borderRadius: '6px',
                    cursor: 'pointer',
                    fontWeight: 600
                  }}
                >
                  {isInscrito ? 'Retirar' : 'Inscribir'}
                </button>
              </div>
            );
          })}
        </div>
      </section>

      {/* Botón de Confirmación Transaccional */}
      <footer style={{ marginTop: '28px', textAlign: 'right' }}>
        <button
          disabled={seleccionados.length === 0}
          onClick={() => alert(`Matrícula confirmada con éxito por ${totalCreditos} créditos. Emitiendo constancia CONST-2026-000123...`)}
          style={{
            backgroundColor: seleccionados.length > 0 ? '#16a34a' : '#cbd5e1',
            color: 'white',
            padding: '12px 24px',
            borderRadius: '8px',
            border: 'none',
            fontSize: '15px',
            fontWeight: 'bold',
            cursor: seleccionados.length > 0 ? 'pointer' : 'not-allowed'
          }}
        >
          Confirmar Pre-Matrícula ({totalCreditos} Créditos)
        </button>
      </footer>
    </div>
  );
};

const cardMetricStyle: React.CSSProperties = {
  backgroundColor: 'white',
  padding: '16px 20px',
  borderRadius: '10px',
  border: '1px solid #e2e8f0',
  boxShadow: '0 1px 2px rgba(0,0,0,0.04)'
};

export default App;
