const API_ROOMS = 'api/room_list.php';
const API_BOOKINGS = 'api/bookings.php';
const API_INVITE_CREATE = 'api/invite_create.php';

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
    opt.dataset.settoreId = r.id_settore;
    opt.dataset.settoreNome = r.nome_settore;
    sel.appendChild(opt);
  });
}

function fillSectorsFromRooms(rooms) {
  // settori unici presi dalle sale disponibili
  const map = new Map();
  rooms.forEach(r => map.set(String(r.id_settore), r.nome_settore));
  const sel = document.getElementById('sectorSelect');
  sel.innerHTML = `<option value="">Seleziona settore...</option>`;
  [...map.entries()].sort((a,b)=>a[1].localeCompare(b[1])).forEach(([id, nome]) => {
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

document.addEventListener('DOMContentLoaded', async () => {
  const form = document.getElementById('bookingForm');
  const inviteMode = document.getElementById('inviteMode');

  inviteMode.addEventListener('change', updateInviteModeUI);
  updateInviteModeUI();

  // 1) carica sale
  try {
    const data = await apiGet(API_ROOMS);
    const rooms = data.rooms || [];
    fillRooms(rooms);
    fillSectorsFromRooms(rooms);
  } catch (e) {
    showAlert('danger', e.message);
    document.getElementById('roomSelect').innerHTML = `<option value="">Errore caricamento sale</option>`;
  }

  // 2) submit: crea prenotazione -> (opzionale) crea inviti
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const fd = new FormData(form);

    const id_sala = parseInt(fd.get('id_sala'), 10);
    const data = fd.get('data');
    const ora_inizio = parseInt(fd.get('ora_inizio'), 10);
    const durata_ore = parseInt(fd.get('durata_ore'), 10);
    const attivita = fd.get('attivita')?.toString() ?? '';

    if (!id_sala || !data || !ora_inizio || !durata_ore) {
      showAlert('warning', 'Compila tutti i campi obbligatori.');
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
      // vai alla dashboard o alla pagina inviti/gestione
      setTimeout(() => { window.location.href = 'index.php?page=dashboard'; }, 600);

    } catch (err) {
      showAlert('danger', err.message);
    }
  });
});
