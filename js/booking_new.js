const API_ROOMS = 'api/rooms_bookable.php';
const API_BOOKINGS = 'api/bookings.php';
const API_INVITE_CREATE = 'api/invite_create.php';
const API_ROOM_BUSY = 'api/room_day_busy.php';

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

async function apiGet(url) {
  const res = await fetch(url, { credentials: 'same-origin' });
  const payload = await res.json().catch(() => ({}));
  if (!res.ok || payload.ok !== true) throw new Error(payload.message || 'Errore');
  return payload.data;
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
  return payload.data;
}

function fillRooms(rooms) {
  const sel = document.getElementById('roomSelect');
  sel.innerHTML = `<option value="">Seleziona una sala...</option>`;
  rooms.forEach(r => {
    const label = `${r.nome_settore} • ${r.nome_sala} (capienza ${r.capienza})`;
    const opt = document.createElement('option');
    opt.value = r.id_sala;
    opt.textContent = label;

    // utili per UI/inviti
    opt.dataset.settoreId = r.id_settore;
    opt.dataset.settoreNome = r.nome_settore;
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
  // Regola: non prenotare "prima di adesso".
  // Se oggi: solo dalla prossima ora intera.
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
  for (let h = minH; h <= 23; h++) {
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

  // Deve finire entro le 23 => maxDur = 23 - h
  const maxDur = Math.max(1, 23 - h);
  for (let d = 1; d <= maxDur; d++) {
    const opt = document.createElement('option');
    opt.value = String(d);
    opt.textContent = `${d}h`;
    sel.appendChild(opt);
  }
}

async function loadBusyHours(idSala, dateStr) {
  if (!idSala || !dateStr) return [];
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
      opt.textContent = opt.textContent + ' (occupata)';
    }
  });
}


document.addEventListener('DOMContentLoaded', async () => {
  const form = document.getElementById('bookingForm');
  const inviteMode = document.getElementById('inviteMode');
  const dateInput = document.getElementById('dateInput');
  const startSelect = document.getElementById('startSelect');
  const durSelect = document.getElementById('durSelect');

  inviteMode.addEventListener('change', updateInviteModeUI);
  updateInviteModeUI();

  // data min = oggi
  const t = todayISO();
  dateInput.min = t;
  if (!dateInput.value) dateInput.value = t;

  // ore/durate iniziali
  fillStartHoursForDay(dateInput.value);
  fillDurations(null);

  dateInput.addEventListener('change', () => {
    // cambio giorno => aggiorno ore minime e reset durata
    const prevStart = startSelect.value;
    fillStartHoursForDay(dateInput.value);

    // se l'ora precedente non è più valida, reset
    const stillExists = [...startSelect.options].some(o => o.value === prevStart);
    startSelect.value = stillExists ? prevStart : '';
    fillDurations(startSelect.value);
    durSelect.value = '';
  });

  startSelect.addEventListener('change', () => {
    fillDurations(startSelect.value);
    durSelect.value = '';
  });

  // 1) carica sale prenotabili
  try {
    const data = await apiGet(API_ROOMS);
    const rooms = data.rooms || [];
    if (!rooms.length) {
      showAlert('warning', 'Nessuna sala prenotabile disponibile per il tuo account.');
      document.getElementById('roomSelect').innerHTML = `<option value="">Nessuna sala disponibile</option>`;
    } else {
      fillRooms(rooms);
      fillSectorsFromRooms(rooms);
      await refreshBusyUI();
    }
  } catch (e) {
    showAlert('danger', e.message);
    document.getElementById('roomSelect').innerHTML = `<option value="">Errore caricamento sale</option>`;
  }

  const roomSelect = document.getElementById('roomSelect');

async function refreshBusyUI() {
  const idSala = parseInt(roomSelect.value, 10);
  const dateStr = dateInput.value;

  // rigenera ore base (minime per giorno)
  const prevStart = startSelect.value;
  fillStartHoursForDay(dateStr);

  // applica ore occupate
  try {
    const busy = await loadBusyHours(idSala, dateStr);
    applyBusyToStartSelect(busy);
  } catch (e) {
    // non blocco la UI se fallisce, ma avviso
    showAlert('warning', 'Non riesco a verificare le ore occupate (continua comunque).');
  }

  // ripristina selezione se ancora valida e non disabilitata
  const opt = [...startSelect.options].find(o => o.value === prevStart);
  startSelect.value = (opt && !opt.disabled) ? prevStart : '';

  fillDurations(startSelect.value);
  durSelect.value = '';
}

roomSelect.addEventListener('change', refreshBusyUI);
dateInput.addEventListener('change', refreshBusyUI);

  // 2) submit: crea prenotazione -> (opzionale) crea inviti
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

    // extra check client: oggi non prima della prossima ora
    const minH = minStartHourForDay(data);
    if (ora_inizio < minH) {
      showAlert('warning', `Per la data selezionata l'ora minima prenotabile è ${String(minH).padStart(2,'0')}:00.`);
      return;
    }

    try {
      // crea prenotazione
      const created = await apiPost(API_BOOKINGS, { id_sala, data, ora_inizio, durata_ore, attivita });
      const id_prenotazione = created.id_prenotazione;

      // inviti opzionali
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
      showAlert('danger', err.message);
    }
  });
});
