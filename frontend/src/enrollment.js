import { ApiClient } from './services/api.js';

// Los permisos y reglas académicas se vuelven a comprobar en el servidor.
export function createEnrollment({ notice, handleError, refreshSections }) {
  const root = document.createElement('section');
  root.id = 'enrollment'; root.hidden = true;
  root.innerHTML = `
    <div class="heading"><div><span class="eyebrow">GESTIÓN ACADÉMICA</span><h1>Matrículas</h1><p>Selecciona secciones, revisa sus horarios y consulta tus matrículas.</p></div></div>
    <form class="filters panel" id="enrollment-filters">
      <label id="student-label">Estudiante<select id="enrollment-student" required></select></label>
      <label>Periodo académico<select id="enrollment-period" required></select></label>
      <button class="secondary" type="submit">Actualizar matrícula</button>
    </form>
    <p id="enrollment-state" role="status"></p>
    <div class="columns">
      <section class="panel"><h2>Secciones disponibles</h2><p class="muted">Elige una sección por curso. El servidor verifica vacantes, prerrequisitos y cruces.</p><div id="enrollment-offer"></div></section>
      <section class="panel"><h2>Tu selección</h2><div id="enrollment-cart"></div><p id="enrollment-total" role="status"></p><button id="enrollment-submit" disabled>Revisar matrícula</button></section>
    </div>
    <section class="panel enrollment-history"><h2>Historial de matrículas</h2><div id="enrollment-history"></div></section>
    <dialog id="enrollment-confirm" aria-labelledby="enrollment-confirm-title"><form id="enrollment-confirm-form"><h2 id="enrollment-confirm-title">Confirmar matrícula</h2><div id="enrollment-review"></div><p id="enrollment-error" class="form-error" role="alert" hidden></p><div class="form-grid"><button type="button" id="enrollment-back" class="secondary">Volver</button><button id="enrollment-confirm-button" type="submit">Confirmar</button></div></form></dialog>`;
  document.querySelector('main').append(root);
  const nav = document.createElement('nav'); nav.className = 'module-nav'; nav.hidden = true;
  nav.setAttribute('aria-label','Módulos académicos');
  nav.innerHTML = '<button type="button" class="secondary" data-view="sections" aria-pressed="true">Secciones y horarios</button><button type="button" class="secondary" data-view="enrollment" aria-pressed="false">Matrículas</button>';
  document.querySelector('#workspace').before(nav);
  const $ = selector => root.querySelector(selector);
  const el = (tag, text, className) => { const e=document.createElement(tag); if(text !== undefined) e.textContent=text; if(className) e.className=className; return e; };
  let user=null, courses=[], periods=[], sections=[], history=[], students=[], cart=new Map(), generation=0, busy=false, action=null, ready=false;
  const course = id => courses.find(c=>Number(c.id_curso)===Number(id));
  const title = s => `${course(s.id_curso)?.nombre || 'Curso #'+s.id_curso} · Sección ${s.codigo}`;
  const period = () => periods.find(p=>Number(p.id_periodo)===Number($('#enrollment-period').value));
  const hasActive = () => history.some(m=>Number(m.id_periodo)===Number($('#enrollment-period').value) && m.estado==='REGISTRADA');
  const credits = () => [...cart.values()].reduce((sum,s)=>sum+Number(course(s.id_curso)?.creditos || 0),0);
  function selectOptions(select, rows, key, label, placeholder) {
    select.replaceChildren(new Option(placeholder,'')); rows.forEach(row=>select.add(new Option(label(row),row[key])));
  }
  function lock(value) {
    busy=value;
    root.querySelectorAll('button,select,input').forEach(e=>{e.disabled=value;});
    nav.querySelectorAll('button').forEach(e=>{e.disabled=value;});
    document.querySelector('#logout').disabled=value;
    if(!value) renderCart();
  }
  function renderCart() {
    $('#enrollment-cart').replaceChildren();
    if(!cart.size) $('#enrollment-cart').append(el('p','Aún no seleccionaste secciones.','muted'));
    cart.forEach(s=>{
      const row=el('div',undefined,'schedule'); row.append(el('strong',title(s)));
      const remove=el('button','Quitar','secondary'); remove.type='button'; remove.disabled=busy;
      remove.addEventListener('click',()=>{cart.delete(Number(s.id_seccion)); renderOffer(); renderCart();}); row.append(remove); $('#enrollment-cart').append(row);
    });
    $('#enrollment-total').textContent=`${cart.size} secciones · ${credits()} / 22 créditos`;
    $('#enrollment-submit').disabled=busy || !ready || !cart.size || credits()<1 || credits()>22 || period()?.estado!=='MATRICULA_ABIERTA' || hasActive();
  }
  function renderOffer() {
    const target=$('#enrollment-offer'); target.replaceChildren();
    if(!sections.length) target.append(el('p','No hay secciones para este periodo.','muted'));
    sections.forEach(s=>{
      const card=el('div',undefined,'schedule'); const label=el('label',undefined,'section-choice'); const input=el('input'); input.type='checkbox'; input.checked=cart.has(Number(s.id_seccion));
      input.disabled=busy || hasActive() || Number(s.vacantes_disponibles)<=0 || period()?.estado!=='MATRICULA_ABIERTA';
      input.addEventListener('change',()=>{
        if(input.checked) {
          const duplicate=[...cart.values()].find(other=>Number(other.id_curso)===Number(s.id_curso));
          if(duplicate) { input.checked=false; notice('Ya seleccionaste una sección de este curso. Quita la anterior para cambiarla.',true); return; }
          cart.set(Number(s.id_seccion),s);
        } else cart.delete(Number(s.id_seccion));
        renderCart();
      });
      label.append(input,el('strong',title(s))); card.append(label,el('small',`${course(s.id_curso)?.creditos || 0} créditos · ${s.vacantes_disponibles} vacantes`));
      if(!s.horarios.length) card.append(el('small','Sin horarios publicados.'));
      s.horarios.forEach(h=>card.append(el('small',`${h.dia_semana} ${h.hora_inicio.slice(0,5)}–${h.hora_fin.slice(0,5)} · Aula #${h.id_aula} · ${h.modalidad}`)));
      target.append(card);
    });
  }
  function renderHistory() {
    const target=$('#enrollment-history'); target.replaceChildren();
    if(!history.length) target.append(el('p','No hay matrículas registradas para este estudiante.','muted'));
    history.forEach(m=>{
      const card=el('article',undefined,'schedule');
      card.append(el('strong',`${m.codigo_matricula} · ${m.estado}`),el('small',`${periods.find(p=>Number(p.id_periodo)===Number(m.id_periodo))?.nombre || 'Periodo #'+m.id_periodo} · ${m.total_creditos} créditos · ${m.fecha_matricula}`));
      m.detalles.forEach(d=>{ const s=sections.find(s=>Number(s.id_seccion)===Number(d.id_seccion)); card.append(el('small',`${s?title(s):'Sección #'+d.id_seccion} · ${d.estado}`)); });
      if(m.estado==='REGISTRADA') { const button=el('button','Anular matrícula','secondary'); button.type='button'; button.disabled=busy; button.addEventListener('click',()=>confirm('cancel',m)); card.append(button); }
      target.append(card);
    });
  }
  async function load() {
    const version=++generation; ready=false; cart.clear(); sections=[]; history=[]; renderCart();
    $('#enrollment-offer').replaceChildren(); $('#enrollment-history').replaceChildren();
    const student=$('#enrollment-student').value, id=$('#enrollment-period').value;
    if(!student || !id) { $('#enrollment-state').textContent='Selecciona un estudiante y un periodo.'; return; }
    $('#enrollment-state').textContent='Cargando secciones e historial…';
    try {
      const results=await Promise.allSettled([ApiClient.request(`secciones?periodo_id=${id}`),ApiClient.request(`matriculas/estudiante/${student}`)]);
      if(version!==generation || !user) return;
      const failure=results.find(r=>r.status==='rejected'); if(failure) throw failure.reason;
      const rows=results[0].value;
      // Concurrencia acotada al consultar los horarios de la oferta.
      const details=[];
      for(let offset=0;offset<rows.length;offset+=8) {
        const batch=await Promise.allSettled(rows.slice(offset,offset+8).map(s=>ApiClient.request(`secciones/${s.id_seccion}`)));
        if(version!==generation || !user) return;
        const failed=batch.find(r=>r.status==='rejected'); if(failed) throw failed.reason;
        details.push(...batch.map(r=>r.value));
      }
      sections=details; history=results[1].value; ready=true;
      $('#enrollment-state').textContent=hasActive() ? 'Este estudiante ya tiene una matrícula activa en el periodo. Puedes consultarla en el historial.' : period()?.estado==='MATRICULA_ABIERTA' ? 'Revisa la selección antes de confirmar. La matrícula está sujeta a las fechas y validaciones académicas.' : 'Este periodo no está abierto para nuevas matrículas. Puedes consultar el historial.';
      renderOffer(); renderCart(); renderHistory();
    } catch(error) { if(version!==generation) return; $('#enrollment-state').textContent='No se pudo cargar la información. Pulsa Actualizar matrícula para reintentar.'; handleError(error); }
  }
  function confirm(kind, enrollment=null) {
    if(busy || !ready) return;
    action={kind,enrollment,student:Number($('#enrollment-student').value),period:Number($('#enrollment-period').value),ids:[...cart.keys()]};
    $('#enrollment-error').hidden=true;
    $('#enrollment-confirm-title').textContent=kind==='cancel'?'Anular matrícula':'Confirmar matrícula';
    $('#enrollment-review').replaceChildren();
    if(kind==='cancel') $('#enrollment-review').append(el('p',`¿Anular la matrícula ${enrollment.codigo_matricula}? Se liberarán sus vacantes. Esta acción no se puede deshacer desde esta pantalla.`));
    else {
      $('#enrollment-review').append(el('p',`${$('#enrollment-student').selectedOptions[0].textContent} · ${period()?.nombre}`));
      cart.forEach(s=>$('#enrollment-review').append(el('p',title(s))));
      $('#enrollment-review').append(el('strong',`Total: ${credits()} créditos`));
    }
    $('#enrollment-confirm-button').textContent=kind==='cancel'?'Confirmar anulación':'Confirmar matrícula';
    $('#enrollment-confirm').showModal();
  }
  $('#enrollment-confirm-form').addEventListener('submit',async event=>{
    event.preventDefault(); if(busy || !action) return;
    const current=action, version=generation; lock(true); $('#enrollment-error').hidden=true;
    try {
      const result=await ApiClient.request(current.kind==='cancel'?`matriculas/${current.enrollment.id_matricula}/anular`:'matriculas',{method:'POST',...(current.kind==='cancel'?{}:{body:{estudiante_id:current.student,periodo_id:current.period,secciones:current.ids}})});
      if(version!==generation || !user) return;
      $('#enrollment-confirm').close(); action=null;
      notice(current.kind==='cancel'?`Matrícula ${result.codigo_matricula} anulada. Vacantes liberadas.`:`Matrícula registrada: ${result.codigo_matricula}.`);
      await load(); await refreshSections();
    } catch(error) { if(version===generation) handleError(error,$('#enrollment-error')); }
    finally { lock(false); renderOffer(); renderHistory(); }
  });
  $('#enrollment-back').addEventListener('click',()=>{if(!busy) $('#enrollment-confirm').close();});
  $('#enrollment-confirm').addEventListener('cancel',event=>{if(busy) event.preventDefault();});
  $('#enrollment-submit').addEventListener('click',()=>confirm('register'));
  $('#enrollment-filters').addEventListener('submit',event=>{event.preventDefault(); load();});
  $('#enrollment-student').addEventListener('change',load); $('#enrollment-period').addEventListener('change',load);
  nav.addEventListener('click',event=>{
    const view=event.target.dataset.view; if(!view || busy) return;
    root.hidden=view!=='enrollment'; document.querySelector('#workspace').hidden=view!=='sections';
    nav.querySelectorAll('button').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.view===view)));
    if(view==='enrollment') load();
  });
  return {
    async initialize(profile, allCourses, allPeriods) {
      const version=++generation; user=profile; courses=allCourses; periods=allPeriods;
      if(!['ADMIN','COORDINADOR','ESTUDIANTE'].includes(user.rol)) { nav.hidden=true; return; }
      students=user.rol==='ESTUDIANTE'?[user]:await ApiClient.listAll('estudiantes');
      if(version!==generation || !user) return;
      selectOptions($('#enrollment-student'),students,'id_usuario',s=>`${s.nombre}${s.codigo_universitario?' · '+s.codigo_universitario:''}`,'Selecciona un estudiante');
      if(user.rol==='ESTUDIANTE') $('#enrollment-student').value=user.id_usuario;
      $('#student-label').hidden=user.rol==='ESTUDIANTE';
      selectOptions($('#enrollment-period'),periods,'id_periodo',p=>p.nombre,'Selecciona un periodo');
      $('#enrollment-period').value=(periods.find(p=>p.estado==='MATRICULA_ABIERTA') || periods[0])?.id_periodo || '';
      nav.hidden=false;
    },
    reset() {
      generation++; user=null; ready=false; action=null; cart.clear(); sections=[]; history=[]; students=[];
      root.hidden=true; nav.hidden=true; $('#enrollment-confirm').close();
      $('#enrollment-student').replaceChildren(); $('#enrollment-period').replaceChildren();
      $('#enrollment-offer').replaceChildren(); $('#enrollment-history').replaceChildren(); $('#enrollment-cart').replaceChildren();
      nav.querySelectorAll('button').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.view==='sections')));
    },
  };
}
