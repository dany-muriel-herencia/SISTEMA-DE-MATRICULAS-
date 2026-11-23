import React, { useEffect } from 'react';
import { AlertTriangle, BookOpen, CalendarDays, Check, CheckCircle2, Clock3, GraduationCap, MapPin, RefreshCw, ShieldCheck, Users } from 'lucide-react';
import { type Curso, type DiaSemana, useStudentStore } from './stores/studentStore';
import ConstanciaPanel from './ConstanciaPanel';
import './styles.css';

const dias: DiaSemana[] = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
const horas = Array.from({ length: 11 }, (_, index) => `${String(index + 8).padStart(2, '0')}:00`);
const creditosMaximos = 22;
const minutos = (hora: string) => { const [h, m] = hora.split(':').map(Number); return h * 60 + m; };

function BloqueCurso({ curso, compacto = false }: { curso: Curso; compacto?: boolean }) {
  return <div className={`schedule-course ${compacto ? 'compact' : ''}`} style={{ top: `${minutos(curso.horaInicio) - 480}px`, height: `${Math.max(minutos(curso.horaFin) - minutos(curso.horaInicio) - 4, 34)}px` }} title={`${curso.nombre} · ${curso.horaInicio}–${curso.horaFin}`}>
    <strong>{curso.codigo}</strong><span>{curso.nombre}</span><small>{curso.horaInicio}–{curso.horaFin}</small>{!compacto && <small>{curso.aula}</small>}
  </div>;
}

const App: React.FC = () => {
  const oferta = useStudentStore((state) => state.oferta);
  const seleccionados = useStudentStore((state) => state.seleccionados);
  const diaActivo = useStudentStore((state) => state.diaActivo);
  const cargarOferta = useStudentStore((state) => state.cargarOferta);
  const seleccionarDia = useStudentStore((state) => state.seleccionarDia);
  const alternarCurso = useStudentStore((state) => state.alternarCurso);
  const confirmarMatricula = useStudentStore((state) => state.confirmarMatricula);
  const cargando = useStudentStore((state) => state.cargando);
  const fuente = useStudentStore((state) => state.fuente);
  const conflicto = useStudentStore((state) => state.conflicto);
  const totalCreditos = seleccionados.reduce((total, curso) => total + curso.creditos, 0);

  useEffect(() => { void cargarOferta(); }, [cargarOferta]);

  const semaforo = conflicto ? 'rojo' : seleccionados.length ? 'verde' : 'ambar';
  const estadoSemaforo = conflicto ? 'Cruce detectado' : seleccionados.length ? 'Horario compatible' : 'Pendiente de selección';

  return <div className="student-page min-h-screen bg-slate-50 text-slate-800">
    <header className="student-header">
      <div className="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-5 py-5 lg:px-8">
        <div className="flex items-center gap-3">
          <div className="brand-mark"><GraduationCap size={24} /></div>
          <div><p className="text-xs font-semibold uppercase tracking-[.16em] text-sky-200">SGAU · UNJBG</p><h1 className="text-lg font-bold text-white sm:text-xl">Portal del estudiante</h1></div>
        </div>
        <div className="student-profile"><div className="profile-avatar">AP</div><div className="hidden sm:block"><strong>Ana Pérez</strong><span>2026-001 · ESIS</span></div></div>
      </div>
    </header>

    <main className="mx-auto grid w-full max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
      <section className="welcome-row">
        <div><p className="section-kicker">Matrícula · Periodo 2026-I</p><h2>Planifica tu semana</h2><p>Elige tus asignaturas y verifica que tus horarios sean compatibles.</p></div>
        <span className="regular-badge"><ShieldCheck size={15} /> Estudiante regular</span>
      </section>

      <section className="summary-grid" aria-label="Resumen de matrícula">
        <article className="summary-card"><div className="summary-icon blue"><BookOpen size={19} /></div><div><span>Asignaturas elegidas</span><strong>{seleccionados.length}</strong></div></article>
        <article className="summary-card"><div className="summary-icon violet"><GraduationCap size={19} /></div><div><span>Créditos seleccionados</span><strong className={totalCreditos > creditosMaximos ? 'text-rose-600' : ''}>{totalCreditos}<small> / {creditosMaximos}</small></strong></div></article>
        <article className={`summary-card traffic-card ${semaforo}`}><div className={`traffic-light ${semaforo}`}><i /><i /><i /></div><div><span>Semáforo de cruces</span><strong>{estadoSemaforo}</strong></div></article>
      </section>

      {conflicto && <div className="conflict-alert" role="alert"><AlertTriangle size={19} /><div><strong>No se puede agregar esa asignatura</strong><span>{conflicto}</span></div></div>}

      <section className="schedule-panel">
        <div className="panel-heading"><div><div className="panel-title-icon"><CalendarDays size={18} /></div><div><h3>Horario semanal</h3><p>Selecciona un día para consultar sus bloques.</p></div></div><span className={`data-source ${fuente}`}>{fuente === 'api' ? <><CheckCircle2 size={14} /> Secciones actualizadas</> : <><Clock3 size={14} /> Datos de demostración</>}</span></div>
        <div className="day-selector" role="tablist" aria-label="Días de la semana">{dias.map((dia) => <button key={dia} type="button" role="tab" aria-selected={diaActivo === dia} className={diaActivo === dia ? 'selected' : ''} onClick={() => seleccionarDia(dia)}>{dia}</button>)}</div>
        <div className="schedule-scroll"><div className="schedule-grid">
          <div className="time-column"><div className="schedule-day-head spacer" />{horas.map((hora) => <div key={hora} className="time-label">{hora}</div>)}</div>
          {dias.map((dia) => <div key={dia} className={`schedule-day ${diaActivo === dia ? 'active-day' : ''}`}>
            <div className="schedule-day-head"><strong>{dia}</strong><span>{seleccionados.filter((curso) => curso.dia === dia).length || '—'}</span></div>
            <div className="schedule-track">{horas.map((hora) => <div key={hora} className="schedule-hour-line" />)}{seleccionados.filter((curso) => curso.dia === dia).map((curso) => <BloqueCurso key={curso.idCurso} curso={curso} />)}</div>
          </div>)}
        </div></div>
        <div className="schedule-legend"><span><i className="legend-dot selected-dot" /> Asignatura seleccionada</span><span><i className="legend-dot" /> Espacio disponible</span></div>
      </section>

      <section className="courses-panel">
        <div className="panel-heading"><div><div className="panel-title-icon green"><Users size={18} /></div><div><h3>Selector de asignaturas</h3><p>Oferta de secciones para tu ciclo · máximo 22 créditos.</p></div></div><button className="refresh-button" type="button" onClick={() => void cargarOferta()} disabled={cargando}><RefreshCw size={15} className={cargando ? 'spin' : ''} />{cargando ? 'Actualizando' : 'Actualizar oferta'}</button></div>
        <div className="course-list">{oferta.map((curso) => {
          const elegido = seleccionados.some((item) => item.idCurso === curso.idCurso);
          const crucePotencial = !elegido && seleccionados.find((item) => item.dia === curso.dia && minutos(curso.horaInicio) < minutos(item.horaFin) && minutos(curso.horaFin) > minutos(item.horaInicio));
          return <article key={curso.idCurso} className={`course-card ${elegido ? 'chosen' : ''} ${crucePotencial ? 'has-conflict' : ''}`}>
            <div className="course-main"><div className="course-code">{curso.codigo}</div><div className="course-info"><h4>{curso.nombre}</h4><div className="course-meta"><span><Clock3 size={13} /> {curso.dia}, {curso.horaInicio}–{curso.horaFin}</span><span><MapPin size={13} /> {curso.aula}</span><span><GraduationCap size={13} /> {curso.creditos} créditos</span></div><p className="teacher-name">{curso.docente} · {curso.vacantes} vacantes</p>
              {crucePotencial && <p className="course-conflict"><AlertTriangle size={14} /> Se cruza con {crucePotencial.nombre}</p>}
            </div></div>
            <button type="button" className={`course-button ${elegido ? 'remove' : ''}`} onClick={() => alternarCurso(curso)}>{elegido ? <><Check size={15} /> Seleccionada</> : 'Agregar'}</button>
          </article>;
        })}</div>
        <footer className="enrollment-footer"><div><strong>{totalCreditos} créditos</strong><span>{seleccionados.length} asignaturas en tu horario</span></div><button type="button" className="confirm-button" disabled={!seleccionados.length} onClick={() => window.alert(confirmarMatricula())}>Continuar pre-matrícula</button></footer>
      </section>
      <ConstanciaPanel />
      <p className="demo-note">Prototipo de frontend · {fuente === 'api' ? 'La oferta consultó el servicio de horarios.' : 'Se muestran datos de demostración porque el servicio de horarios no está disponible.'} La confirmación final se habilitará al conectar el servicio de matrícula.</p>
    </main>
  </div>;
};

export default App;
