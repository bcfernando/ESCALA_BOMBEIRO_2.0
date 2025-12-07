// /escala_bombeiros/js/portal_usuario.js
let current = new Date();

function apiUrl(d=new Date()){
  const y = d.getFullYear();
  const m = d.getMonth() + 1;
  return `/escala_bombeiros/api/portal_events.php?year=${y}&month=${m}`;
}

function fmtMonth(d){
  return new Intl.DateTimeFormat('pt-BR', { month:'long', year:'numeric' }).format(d);
}

function ymd(dt){ return dt.toISOString().split('T')[0]; }

function safeNum(v){ const n=Number(v); return Number.isFinite(n) ? n : 0; }

// ---- Renderização ----
function renderResumo(resumo){
  const integral = safeNum(resumo.integral);
  const diurno   = safeNum(resumo.diurno);
  const noturno  = safeNum(resumo.noturno);
  const horas    = safeNum(resumo.horas);
  const valor    = safeNum(resumo.valor);

  document.getElementById('total-earnings').textContent = `R$ ${valor}`;
  document.getElementById('total-hours').textContent    = `${horas}h`;
  document.getElementById('sum-integral').textContent   = `${integral}x • R$ ${integral*250}`;
  document.getElementById('sum-diurno').textContent     = `${diurno}x • R$ ${diurno*125}`;
  document.getElementById('sum-noturno').textContent    = `${noturno}x • R$ ${noturno*125}`;
}

function dayCell(date,isCurrent,byDate){
  const cell = document.createElement('div');
  cell.className = 'day-cell' + (isCurrent ? '' : ' other-month');

  const num = document.createElement('div');
  num.className = 'day-number';
  num.textContent = date.getDate();
  cell.appendChild(num);

  const key = ymd(date);
  const eventos = byDate[key] || [];

  // destaca se é meu
  const euTenho = eventos.some(e => !!e.meu);
  if (euTenho) cell.style.outline = '2px solid #60a5fa';

  if (eventos.length){
    const ev = eventos.find(e=>e.meu) || eventos[0];
    const badge = document.createElement('div');
    badge.className = 'shift-badge ' + (
      ev.turno==='I' || ev.turno==='I_SUB' ? 'shift-integral' :
      ev.turno==='D' ? 'shift-diurno' : 'shift-noturno'
    );
    badge.textContent =
      ev.turno==='I' ? 'Integral' :
      ev.turno==='I_SUB' ? 'Substituto' :
      ev.turno==='D' ? 'Diurno' : 'Noturno';
    cell.appendChild(badge);

    const val = document.createElement('div');
    val.className='shift-value';
    val.textContent = `R$ ${safeNum(ev.valor)}`;
    cell.appendChild(val);
  }

  const today = new Date();
  if (date.toDateString() === today.toDateString()) cell.classList.add('today');

  return cell;
}

function renderCalendario(payload){
  // mês no título
  document.getElementById('month-label').textContent = fmtMonth(current);

  // índice por data
  const byDate = {};
  (payload.eventos || []).forEach(e => {
    (byDate[e.data] ||= []).push(e);
  });

  const y = current.getFullYear();
  const m = current.getMonth();
  const first = new Date(y, m, 1);
  const last  = new Date(y, m+1, 0);
  const startDow = first.getDay();

  const grid = document.getElementById('calendar-grid');
  grid.innerHTML = '';

  // preencher começo com dias do mês anterior para alinhar a semana
  for(let i=startDow-1;i>=0;i--){
    grid.appendChild(dayCell(new Date(y, m, -i), false, byDate));
  }
  // dias do mês
  for(let d=1; d<=last.getDate(); d++){
    grid.appendChild(dayCell(new Date(y, m, d), true, byDate));
  }

  renderResumo(payload.resumo || {});
}

// ---- Navegação ----
function carregar(){
  fetch(apiUrl(current))
    .then(r => r.json())
    .then(data => {
      if (!data || data.ok === false) throw new Error(data?.erro || 'Falha na API');
      renderCalendario(data);
    })
    .catch(err => {
      console.error('API erro:', err);
      // fallback visual
      document.getElementById('month-label').textContent = fmtMonth(current);
      renderResumo({});
      document.getElementById('calendar-grid').innerHTML = '';
    });
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('btn-prev').onclick  = () => { current.setMonth(current.getMonth()-1); carregar(); };
  document.getElementById('btn-next').onclick  = () => { current.setMonth(current.getMonth()+1); carregar(); };
  document.getElementById('btn-today').onclick = () => { current = new Date(); carregar(); };
  carregar();
});
