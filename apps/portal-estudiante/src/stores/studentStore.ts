import { create } from 'zustand';

export type DiaSemana = 'Lunes' | 'Martes' | 'Miércoles' | 'Jueves' | 'Viernes';
export type Curso = {
  idCurso: number; codigo: string; nombre: string; creditos: number; ciclo: number;
  dia: DiaSemana; horaInicio: string; horaFin: string; aula: string; docente: string; vacantes: number;
};

const ofertaInicial: Curso[] = [
  { idCurso: 1, codigo: 'ESIS-501', nombre: 'Base de Datos II', creditos: 4, ciclo: 5, dia: 'Lunes', horaInicio: '08:00', horaFin: '10:00', aula: 'Lab. 204', docente: 'Ing. Patricia Ramos', vacantes: 12 },
  { idCurso: 2, codigo: 'ESIS-502', nombre: 'Ingeniería de Software I', creditos: 4, ciclo: 5, dia: 'Lunes', horaInicio: '09:00', horaFin: '11:00', aula: 'Aula 302', docente: 'Mg. Carlos Flores', vacantes: 8 },
  { idCurso: 3, codigo: 'ESIS-503', nombre: 'Sistemas Operativos', creditos: 4, ciclo: 5, dia: 'Martes', horaInicio: '10:00', horaFin: '12:00', aula: 'Lab. 201', docente: 'Ing. Marco Salas', vacantes: 16 },
  { idCurso: 4, codigo: 'ESIS-504', nombre: 'Redes y Comunicaciones', creditos: 4, ciclo: 5, dia: 'Miércoles', horaInicio: '08:00', horaFin: '11:00', aula: 'Lab. Redes', docente: 'Mg. Elena Cruz', vacantes: 10 },
  { idCurso: 5, codigo: 'ESIS-505', nombre: 'Inteligencia Artificial', creditos: 4, ciclo: 5, dia: 'Jueves', horaInicio: '14:00', horaFin: '17:00', aula: 'Aula 401', docente: 'Dr. Luis Paredes', vacantes: 6 },
  { idCurso: 6, codigo: 'ESIS-506', nombre: 'Taller de Programación Web', creditos: 3, ciclo: 5, dia: 'Viernes', horaInicio: '08:00', horaFin: '11:00', aula: 'Lab. 203', docente: 'Ing. Sofía Vega', vacantes: 14 },
];

type StudentStore = {
  oferta: Curso[]; seleccionados: Curso[]; diaActivo: DiaSemana; cargando: boolean;
  fuente: 'muestra' | 'api'; conflicto: string;
  cargarOferta: () => Promise<void>; seleccionarDia: (dia: DiaSemana) => void;
  alternarCurso: (curso: Curso) => void; confirmarMatricula: () => string;
};

const minutos = (hora: string) => { const [h, m] = hora.split(':').map(Number); return h * 60 + m; };
const buscarCruce = (nuevo: Curso, cursos: Curso[]) => cursos.find((actual) => actual.dia === nuevo.dia && minutos(nuevo.horaInicio) < minutos(actual.horaFin) && minutos(nuevo.horaFin) > minutos(actual.horaInicio));

export const useStudentStore = create<StudentStore>((set, get) => ({
  oferta: ofertaInicial, seleccionados: [], diaActivo: 'Lunes', cargando: false, fuente: 'muestra', conflicto: '',
  cargarOferta: async () => {
    set({ cargando: true });
    try {
      const resultados = await Promise.all(ofertaInicial.map(async (curso) => {
        const respuesta = await fetch(`/api/schedule/secciones/${curso.idCurso}`);
        if (!respuesta.ok) throw new Error('No disponible');
        const secciones = await respuesta.json() as { vacantesDisponibles?: number; horarios?: { dia?: string; inicio?: string; fin?: string }[] }[];
        const seccion = secciones[0];
        // El servicio actual devuelve una sección de ejemplo con horario fijo;
        // usamos la oferta local para el calendario y actualizamos solo vacantes.
        return { ...curso, vacantes: seccion?.vacantesDisponibles ?? curso.vacantes };
      }));
      set({ oferta: resultados, fuente: 'api', cargando: false });
    } catch { set({ oferta: ofertaInicial, fuente: 'muestra', cargando: false }); }
  },
  seleccionarDia: (dia) => set({ diaActivo: dia }),
  alternarCurso: (curso) => {
    const { seleccionados } = get();
    if (seleccionados.some((actual) => actual.idCurso === curso.idCurso)) {
      set({ seleccionados: seleccionados.filter((actual) => actual.idCurso !== curso.idCurso), conflicto: '' }); return;
    }
    const cruce = buscarCruce(curso, seleccionados);
    if (cruce) { set({ conflicto: `${curso.nombre} se cruza el ${curso.dia} con ${cruce.nombre} (${cruce.horaInicio}–${cruce.horaFin}).` }); return; }
    const creditos = seleccionados.reduce((total, actual) => total + actual.creditos, 0);
    if (creditos + curso.creditos > 22) { set({ conflicto: 'La selección supera el máximo permitido de 22 créditos.' }); return; }
    set({ seleccionados: [...seleccionados, curso], conflicto: '' });
  },
  confirmarMatricula: () => {
    const { seleccionados } = get();
    if (!seleccionados.length) return 'Selecciona al menos una asignatura antes de continuar.';
    return `Pre-matrícula de demostración lista: ${seleccionados.length} asignaturas seleccionadas.`;
  },
}));
