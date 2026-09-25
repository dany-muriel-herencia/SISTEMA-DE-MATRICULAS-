import { ApiClient } from './services/api.js';
import { createEnrollment } from './enrollment.js';

const $ = (selector) => document.querySelector(selector);
let user = null, selected = null, courses = [], periods = [], catalogs = { docentes: [], aulas: [] };
let listVersion = 0, detailVersion = 0;
const staff = () => ['ADMIN', 'COORDINADOR'].includes(user?.rol);
const enrollment = createEnrollment({ notice, handleError, refreshSections: loadSections });
const node = (tag, text, className) => {
  const element = document.createElement(tag);
  if (text !== undefined) element.textContent = text;
  if (className) element.className = className;
  return element;
};
function notice(message = '', error = false) {
  $('#notice').textContent = message;
  $('#notice').hidden = !message;
  $('#notice').classList.toggle('error', error);
}
function saveToken(token) {
  ApiClient.token = token;
  try { token ? sessionStorage.setItem('sgau-token', token) : sessionStorage.removeItem('sgau-token'); } catch { /* Sesión en memoria si el almacenamiento está bloqueado. */ }
}
function resetDetail() {
  detailVersion++;
  selected = null;
  $('#detail-title').textContent = 'Horarios de la sección';
  $('#detail-info').textContent = 'Selecciona una sección para ver sus horarios.';
  $('#schedules').replaceChildren();
  $('#new-schedule').hidden = true;
  $('#detail-panel').setAttribute('aria-busy', 'false');
}
function clearSession() {
  enrollment.reset();
  saveToken(''); user = null; listVersion++; resetDetail();
  courses = []; periods = []; catalogs = { docentes: [], aulas: [] };
  $('#sections').replaceChildren();
  $('#workspace').hidden = true; $('#session').hidden = true; $('#login-panel').hidden = false;
  document.querySelectorAll('dialog[open]').forEach(dialog => dialog.close());
  document.querySelectorAll('[data-staff]').forEach(element => { element.hidden = true; });
  $('#login-form').reset();
}
function handleError(error, target) {
  if (error.name === 'AbortError') return;
  if (error.status === 401 && user) {
    clearSession(); notice('Tu sesión venció. Inicia sesión nuevamente.', true); return;
  }
  if (target) { target.textContent = error.message; target.hidden = false; }
  else notice(error.message, true);
}
function options(select, rows, key, label, placeholder) {
  select.replaceChildren(new Option(placeholder, ''));
  rows.forEach(row => select.add(new Option(label(row), row[key])));
}
const courseName = id => courses.find(c => Number(c.id_curso) === Number(id))?.nombre || `Curso #${id}`;
const teacherName = id => catalogs.docentes.find(d => Number(d.id_docente) === Number(id))?.nombre || `Docente #${id}`;
const roomName = id => catalogs.aulas.find(a => Number(a.id_aula) === Number(id))?.nombre || `Aula #${id}`;

async function initialize() {
  const token = ApiClient.token;
  const profile = await ApiClient.request('auth/me');
  if (token !== ApiClient.token) return;
  user = profile;
  $('#identity').textContent = `${user.nombre} · ${user.rol}`;
  $('#session').hidden = false;
  // Carga completa: un fallo conserva la pantalla de acceso para permitir reintentar.
  const results = await Promise.allSettled([ApiClient.listAll('periodos'), ApiClient.listAll('cursos'), ApiClient.request('secciones/catalogos')]);
  if (token !== ApiClient.token) return;
  const failure = results.find(result => result.status === 'rejected');
  if (failure) throw failure.reason;
  [periods, courses, catalogs] = results.map(result => result.value);
  await enrollment.initialize(user, courses, periods);
  if (token !== ApiClient.token) return;
  options($('#period-filter'), periods, 'id_periodo', p => p.nombre, 'Selecciona un periodo');
  options($('#course-filter'), courses, 'id_curso', c => `${c.codigo} · ${c.nombre}`, 'Todos los cursos');
  const active = periods.find(p => p.estado === 'MATRICULA_ABIERTA') || periods[0];
  $('#period-filter').value = active?.id_periodo || '';
  $('#login-panel').hidden = true; $('#workspace').hidden = false;
  $('#new-section').hidden = !staff(); $('#read-only').hidden = staff();
  notice(); await loadSections();
}
async function loadSections() {
  const version = ++listVersion;
  resetDetail(); $('#sections').replaceChildren(); $('#count').textContent = '0';
  const period = $('#period-filter').value;
  if (!period) { $('#list-state').textContent = 'Selecciona un periodo para consultar.'; return; }
  $('#list-state').textContent = 'Cargando secciones…';
  const query = new URLSearchParams({ periodo_id: period });
  if ($('#course-filter').value) query.set('curso_id', $('#course-filter').value);
  try {
    const rows = await ApiClient.request(`secciones?${query}`);
    if (version !== listVersion || !user) return;
    $('#count').textContent = rows.length;
    $('#list-state').textContent = rows.length ? 'Selecciona Ver horarios para consultar una sección.' : 'No hay secciones para estos filtros.';
    rows.forEach(section => {
      const row = node('tr'); row.dataset.id = section.id_seccion;
      const title = node('td'); title.append(node('strong', section.codigo), node('small', courseName(section.id_curso)));
      const action = node('td'); const button = node('button', 'Ver horarios', 'secondary');
      button.type = 'button'; button.setAttribute('aria-label', `Ver horarios de ${section.codigo}`);
      button.addEventListener('click', () => loadDetail(section.id_seccion, true)); action.append(button);
      row.append(title, node('td', `${section.vacantes_disponibles} / ${section.vacantes}`), action);
      $('#sections').append(row);
    });
  } catch (error) {
    if (version !== listVersion) return;
    $('#list-state').textContent = 'No se pudieron cargar las secciones. Pulsa Actualizar para reintentar.';
    handleError(error);
  }
}
async function loadDetail(id, focus = false) {
  resetDetail(); const version = detailVersion;
  $('#detail-info').textContent = 'Cargando horarios…'; $('#detail-panel').setAttribute('aria-busy', 'true');
  try {
    const data = await ApiClient.request(`secciones/${id}`);
    if (version !== detailVersion || !user) return;
    selected = data;
    $('#detail-title').textContent = `Sección ${data.codigo}`;
    $('#detail-info').textContent = `${courseName(data.id_curso)} · ${teacherName(data.id_docente)} · ${data.vacantes_disponibles} de ${data.vacantes} vacantes disponibles`;
    document.querySelectorAll('#sections tr').forEach(row => row.classList.toggle('selected', Number(row.dataset.id) === Number(id)));
    const days = ['LUNES','MARTES','MIERCOLES','JUEVES','VIERNES','SABADO','DOMINGO'];
    if (!data.horarios.length) $('#schedules').append(node('p', 'Esta sección todavía no tiene horarios.', 'muted'));
    [...data.horarios].sort((a,b) => days.indexOf(a.dia_semana) - days.indexOf(b.dia_semana) || a.hora_inicio.localeCompare(b.hora_inicio)).forEach(h => {
      const card = node('div', undefined, 'schedule');
      card.append(node('strong', `${h.dia_semana} · ${h.hora_inicio.slice(0,5)} – ${h.hora_fin.slice(0,5)}`), node('small', `${roomName(h.id_aula)} · ${h.modalidad}`));
      $('#schedules').append(card);
    });
    $('#new-schedule').hidden = !staff();
    if (focus) $('#detail-title').focus();
  } catch (error) {
    if (version !== detailVersion) return;
    $('#detail-info').textContent = 'No se pudo cargar el detalle. Selecciona la sección para reintentar.';
    handleError(error);
  } finally { if (version === detailVersion) $('#detail-panel').setAttribute('aria-busy', 'false'); }
}

$('#login-form').addEventListener('submit', async event => {
  event.preventDefault(); const button = event.target.querySelector('button'); button.disabled = true; notice();
  try {
    // Reutiliza la sesión si solo falló la carga de catálogos.
    if (!ApiClient.token) {
      const data = await ApiClient.request('auth/login', { method: 'POST', body: Object.fromEntries(new FormData(event.target)) });
      saveToken(data.token);
    }
    await initialize(); event.target.reset();
  } catch (error) {
    if (error.status === 401) clearSession();
    handleError(error);
  } finally { button.disabled = false; }
});
$('#logout').addEventListener('click', async () => {
  $('#logout').disabled = true;
  try { await ApiClient.request('auth/logout', { method: 'POST' }); clearSession(); notice('Sesión cerrada.'); }
  catch (error) { handleError(error); }
  finally { $('#logout').disabled = false; }
});
$('#filters').addEventListener('submit', event => { event.preventDefault(); if (user) loadSections(); });
$('#period-filter').addEventListener('change', () => loadSections());
$('#course-filter').addEventListener('change', () => loadSections());

function openForm(id) {
  const dialog = $(id); dialog.querySelector('form').reset(); dialog.querySelector('.form-error').hidden = true;
  dialog.showModal();
}
$('#new-section').addEventListener('click', () => {
  if (!staff()) return;
  const form = $('#section-form');
  options(form.elements.id_periodo, periods, 'id_periodo', p => p.nombre, 'Selecciona un periodo');
  options(form.elements.id_curso, courses.filter(c => c.estado), 'id_curso', c => `${c.codigo} · ${c.nombre}`, 'Selecciona un curso');
  options(form.elements.id_docente, catalogs.docentes, 'id_docente', d => `${d.nombre} · ${d.codigo}`, 'Selecciona un docente');
  openForm('#section-dialog');
  form.elements.id_periodo.value = $('#period-filter').value;
  form.elements.id_curso.value = $('#course-filter').value;
});
$('#new-schedule').addEventListener('click', () => {
  if (!staff() || !selected) return;
  options($('#schedule-form').elements.id_aula, catalogs.aulas, 'id_aula', a => `${a.nombre} · Capacidad ${a.capacidad}`, 'Selecciona un aula');
  openForm('#schedule-dialog');
  $('#schedule-context').textContent = `Sección ${selected.codigo} · ${courseName(selected.id_curso)}`;
});
document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
document.querySelectorAll('dialog').forEach(dialog => dialog.addEventListener('cancel', event => {
  if (dialog.dataset.saving === 'true') event.preventDefault();
}));
async function submitForm(event, kind) {
  event.preventDefault(); if (!staff()) return;
  const form = event.target, dialog = form.closest('dialog'), errorBox = form.querySelector('.form-error');
  const body = Object.fromEntries(new FormData(form));
  for (const key of ['id_periodo','id_curso','id_docente','vacantes','id_aula']) if (key in body) body[key] = Number(body[key]);
  if (kind === 'horario' && body.hora_fin <= body.hora_inicio) { errorBox.textContent = 'La hora de fin debe ser posterior a la de inicio.'; errorBox.hidden = false; return; }
  const id = selected?.id_seccion;
  if (kind === 'horario' && !id) return;
  errorBox.hidden = true; dialog.dataset.saving = 'true';
  form.querySelectorAll('button').forEach(button => { button.disabled = true; });
  try {
    const result = await ApiClient.request(kind === 'seccion' ? 'secciones' : `secciones/${id}/horarios`, { method: 'POST', body });
    dialog.close(); notice(kind === 'seccion' ? 'Sección creada correctamente.' : 'Horario guardado correctamente.');
    if (kind === 'seccion') {
      $('#period-filter').value = result.id_periodo; $('#course-filter').value = '';
      await loadSections(); if (user) await loadDetail(result.id_seccion, true);
    } else await loadDetail(id, true);
  } catch (error) { handleError(error, errorBox); }
  finally { dialog.dataset.saving = 'false'; form.querySelectorAll('button').forEach(button => { button.disabled = false; }); }
}
$('#section-form').addEventListener('submit', event => submitForm(event, 'seccion'));
$('#schedule-form').addEventListener('submit', event => submitForm(event, 'horario'));
try { ApiClient.token = sessionStorage.getItem('sgau-token') || ''; } catch { /* Almacenamiento opcional. */ }
if (ApiClient.token) {
  notice('Recuperando sesión…');
  const button = $('#login-form button'); button.disabled = true;
  initialize().catch(error => { if (error.status === 401) clearSession(); handleError(error); }).finally(() => { button.disabled = false; });
}
