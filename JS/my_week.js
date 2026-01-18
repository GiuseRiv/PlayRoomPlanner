// JS/my_week.js
const API_WEEK = 'api/user_week.php'; // endpoint da implementare nel backend

function esc(str) {
  return String(str ?? '').replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[m]));
}

function showAlert(type, text) {
  const box = document.getElementById('alertBox');
  box.innerHTML = `
    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
      ${esc(text)}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
    </div>`;
}

function mondaySundayOf(dateStr) {
  // calcolo ISO lun-dom in JS
  const d = new Date(dateStr + 'T00:00:00');
  let day = d.getDay(); // 0 dom..6 sab
  if (day === 0) day = 7; // 1..7 con dom=7
  const monday = new Date(d);
  monday.setDate(d.getDate() - (day - 1));
  const sunday = new Date(d);
  sunday.setDate(d.getDate() + (7 - day));

  const fmt = (x) => x.toISOString().slice(0, 10);
  return { monday: fmt(monday), sunday: fmt(sunday) };
}

function badge(stato) {
  const s = String(stato || '').toLowerCase();
  if (s === 'accettato') return `<span class="badge bg-success">accettato</span>`;
  if (s === 'rifiutato') return `<span class="badge bg-danger">rifiutato</span>`;
  if (s === 'pendente') return `<span class="badge bg-secondary">pendente</span>`;
  return `<span class="badge bg-light text-dark">${esc(stato)}</span>`;
}

async function loadWeek() {
  const day = document.getElementById('dayInput').value;
  const { monday, sunday } = mondaySundayOf(day);
  document.getElementById('weekRange').textContent = `${monday} → ${sunday}`;

  const tbody = document.getElementById('weekTbody');
  tbody.innerHTML = `<tr><td colspan="7" class="text-muted">Caricamento...</td></tr>`;

  try {
    const url = `${API_WEEK}?day=${encodeURIComponent(day)}`;
    const res = await fetch(url, { method: 'GET' });
    const data = await res.json().catch(() => ({}));

    if (!res.ok) throw new Error(data.message || 'Errore caricamento settimana');

    const events = data.events || [];
    if (events.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-muted">Nessun impegno in questa settimana.</td></tr>`;
      return;
    }

    tbody.innerHTML = events.map(ev => `
      <tr>
        <td>${esc(ev.data)}</td>
        <td>${esc(ev.ora_inizio)}:00</td>
        <td>${esc(ev.durata_ore)}h</td>
        <td>${esc(ev.nome_sala || ('Sala #' + ev.id_sala))}</td>
        <td>${esc(ev.attivita || '')}</td>
        <td>${esc(ev.organizzatore || '')}</td>
        <td>${badge(ev.stato_invito || ev.stato)}</td>
      </tr>
    `).join('');

  } catch (e) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-danger">Errore: ${esc(e.message)}</td></tr>`;
    showAlert('danger', e.message);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('weekForm').addEventListener('submit', (e) => {
    e.preventDefault();
    // aggiorno l’URL (così puoi condividere/bookmark)
    const day = document.getElementById('dayInput').value;
    window.history.replaceState({}, '', `index.php?page=my_week&day=${encodeURIComponent(day)}`);
    loadWeek();
  });

  document.getElementById('btnRefresh').addEventListener('click', loadWeek);
  loadWeek();
});
