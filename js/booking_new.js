const API_ROOMS = 'backend/rooms_bookable.php';
const API_BOOKINGS = 'backend/bookings.php';
const API_INVITE_CREATE = 'backend/invite_create.php';
const API_ROOM_BUSY = 'backend/room_day_busy.php';

function esc(str) {
  return String(str ?? '').replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[m]));
}

function showAlert(type, text) {
  const box = document.getElementById('alertBox');
  if (!box) return;
  box.innerHTML = `
    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
      ${esc(text)}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
    </div>`;
}

async function apiGet(url) {
  const res = await fetch(url, { credentials: 'same-origin' }); // cookie sessione [web:137]
  const payload = await res.json().catch(() => ({}));
  if (!res.ok || payload.ok !== true) throw new Error(payload.message || 'Errore');
  return payload.data ?? payload; 
}

async function apiPost(url, bodyObj) {
  const res = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json; charset=utf-8' },
    body: JSON.stringify(bodyObj || {})
  });
  const payload = await res.json().catch(() => ({}));
  if (!res.ok || payload.ok !== true) throw new Error(payload.message || 'Errore richiesta');
  return payload.data ?? payload;
}

function fillRooms(rooms) {
  const sel = document.getElementById('roomSelect');
  sel.innerHTML = `<option value="">Seleziona una sala...</option>`;

  rooms.forEach(r => {
    const label = `${r.nome_settore} • ${r.nome_sala} (capienza ${r.capienza})`;
    const opt = document.createElement('option');
    opt.value = String(r.id_sala);
    opt.textContent = label;

    opt.dataset.settoreId = String(r.id_settore);
    opt.dataset.settoreNome = String(r.nome_settore);

    sel.appendChild(opt);
  });
}

function fillSectorsFromRooms(rooms) {
  const map = new Map();
  rooms.forEach(r => map.set(String(r.id_settore), r.nome_settore));

  const sel = document.getElementById('sectorSelect');
  sel.innerHTML = `<option value="">Seleziona settore...</option>`;

  [...map.entries()]
    .sort((a,b)=>a[1].localeCompare(b[1]))
    .forEach(([id, nome]) => {
      const opt = document.createElement('option');
      opt.value = id;
      opt.textContent = nome;
      sel.appendChild(opt);
    });
}

function updateInviteModeUI() {
  const mode = document.getElementById('inviteMode').value;
  document.getElementById('sectorBox').style.display = (mode === 'sector') ? '' : 'none';
  document.getElementById('roleBox').style.display = (mode === 'role') ? '' : 'none';
}

function todayISO() {
  const now = new Date();
  const yyyy = now.getFullYear();
  const mm = String(now.getMonth()+1).padStart(2,'0');
  const dd = String(now.getDate()).padStart(2,'0');
  return `${yyyy}-${mm}-${dd}`;
}

function minStartHourForDay(dayStr) {
  const now = new Date();
  const t = todayISO();
  if (dayStr !== t) return 9;

  const nextHour = now.getHours() + 1;
  return Math.max(9, Math.min(23, nextHour));
}

function fillStartHoursForDay(dayStr) {
  const sel = document.getElementById('startSelect');
  const minH = minStartHourForDay(dayStr);

  sel.innerHTML = `<option value="">Seleziona...</option>`;
  for (let h = minH; h <= 22; h++) {
    const opt = document.createElement('option');
    opt.value = String(h);
    opt.textContent = `${String(h).padStart(2,'0')}:00`;
    sel.appendChild(opt);
  }
}

function fillDurations(startHour) {
  const sel = document.getElementById('durSelect');
  sel.innerHTML = `<option value="">Seleziona...</option>`;
  if (!startHour) return;

  const h = parseInt(startHour, 10);
  if (!Number.isFinite(h)) return;

  const maxDur = Math.max(1, 23 - h);
  for (let d = 1; d <= maxDur; d++) {
    const opt = document.createElement('option');
    opt.value = String(d);
    opt.textContent = `${d}h`;
    sel.appendChild(opt);
  }
}

async function loadBusyHours(idSala, dateStr) {
  if (!Number.isFinite(idSala) || idSala <= 0 || !dateStr) return [];
  const url = `${API_ROOM_BUSY}?id_sala=${encodeURIComponent(idSala)}&date=${encodeURIComponent(dateStr)}`;
  const data = await apiGet(url);
  return Array.isArray(data.busy_hours) ? data.busy_hours : [];
}

function applyBusyToStartSelect(busyHours) {
  const sel = document.getElementById('startSelect');
  const busySet = new Set((busyHours || []).map(n => String(n)));

  [...sel.options].forEach(opt => {
    if (!opt.value) return;
    if (busySet.has(opt.value)) {
      opt.disabled = true;
      if (!opt.textContent.includes('(occupata)')) {
        opt.textContent = opt.textContent + ' (occupata)';
      }
    }
  });
}

async function refreshBusyUI() {
  const roomSelectEl = document.getElementById('roomSelect');
  const dateInputEl = document.getElementById('dateInput');
  const startSelectEl = document.getElementById('startSelect');
  const durSelectEl = document.getElementById('durSelect');

  const idSala = parseInt(roomSelectEl.value, 10);
  const dateStr = dateInputEl.value;

  if (!Number.isFinite(idSala) || idSala <= 0 || !dateStr) {
    
    fillStartHoursForDay(dateStr || todayISO());
    fillDurations('');
    durSelectEl.value = '';
    return;
  }

  const prevStart = startSelectEl.value;

  fillStartHoursForDay(dateStr);

  try {
    const busy = await loadBusyHours(idSala, dateStr);
    applyBusyToStartSelect(busy);
  } catch (e) {
    showAlert('warning', 'Non riesco a verificare le ore occupate (continua comunque).');
  }

  const opt = [...startSelectEl.options].find(o => o.value === prevStart);
  startSelectEl.value = (opt && !opt.disabled) ? prevStart : '';

  fillDurations(startSelectEl.value);
  durSelectEl.value = '';
}

document.addEventListener('DOMContentLoaded', async () => {
  const form = document.getElementById('bookingForm');
  const inviteMode = document.getElementById('inviteMode');
  const dateInput = document.getElementById('dateInput');
  const startSelect = document.getElementById('startSelect');
  const durSelect = document.getElementById('durSelect');

  inviteMode.addEventListener('change', updateInviteModeUI);
  updateInviteModeUI();

  const t = todayISO();
  dateInput.min = t;
  if (!dateInput.value) dateInput.value = t;

  fillStartHoursForDay(dateInput.value);
  fillDurations(null);

  dateInput.addEventListener('change', async () => {
    const prevStart = startSelect.value;
    fillStartHoursForDay(dateInput.value);

    const stillExists = [...startSelect.options].some(o => o.value === prevStart);
    startSelect.value = stillExists ? prevStart : '';
    fillDurations(startSelect.value);
    durSelect.value = '';

    await refreshBusyUI();
  });

  startSelect.addEventListener('change', () => {
    fillDurations(startSelect.value);
    durSelect.value = '';
  });

  
  try {
    const data = await apiGet(API_ROOMS);
    const rooms = data.rooms || [];

    const roomSelectEl = document.getElementById('roomSelect');

    if (!rooms.length) {
      showAlert('warning', 'Nessuna sala prenotabile disponibile per il tuo account.');
      roomSelectEl.innerHTML = `<option value="">Nessuna sala disponibile</option>`;
    } else {
      fillRooms(rooms);
      fillSectorsFromRooms(rooms);

      // seleziona prima sala automaticamente per evitare id_sala vuoto
      roomSelectEl.value = String(rooms[0].id_sala);

      await refreshBusyUI();
    }
  } catch (e) {
    console.error(e);
    showAlert('danger', e.message);
    document.getElementById('roomSelect').innerHTML = `<option value="">Errore caricamento sale</option>`;
  }

  
  document.getElementById('roomSelect').addEventListener('change', refreshBusyUI);

  
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const fd = new FormData(form);

    const id_sala = parseInt(fd.get('id_sala'), 10);
    const data = String(fd.get('data') || '');
    const ora_inizio = parseInt(fd.get('ora_inizio'), 10);
    const durata_ore = parseInt(fd.get('durata_ore'), 10);
    const attivita = fd.get('attivita')?.toString() ?? '';

    if (!id_sala || !data || !ora_inizio || !durata_ore) {
      showAlert('warning', 'Compila tutti i campi obbligatori.');
      return;
    }

    const minH = minStartHourForDay(data);
    if (ora_inizio < minH) {
      showAlert('warning', `Per la data selezionata l'ora minima prenotabile è ${String(minH).padStart(2,'0')}:00.`);
      return;
    }

    try {
      const created = await apiPost(API_BOOKINGS, { id_sala, data, ora_inizio, durata_ore, attivita });
      const id_prenotazione = created.id_prenotazione;

      const mode = fd.get('invite_mode');

      if (mode && mode !== 'none') {
        const invitePayload = { id_prenotazione, mode };

        if (mode === 'sector') {
          const id_settore = parseInt(document.getElementById('sectorSelect').value, 10);
          if (!id_settore) {
            showAlert('warning', 'Seleziona un settore per gli inviti.');
            return;
          }
          invitePayload.id_settore = id_settore;
        }

        if (mode === 'role') {
          const ruolo = document.getElementById('roleSelect').value;
          invitePayload.ruolo = ruolo;
        }

        await apiPost(API_INVITE_CREATE, invitePayload);
      }

      showAlert('success', 'Prenotazione creata con successo.');
      setTimeout(() => { window.location.href = 'index.php?page=dashboard'; }, 600);

    } catch (err) {
      showAlert('danger', err.message || 'Errore');
    }
  });
});
