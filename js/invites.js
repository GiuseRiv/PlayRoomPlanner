
const API_LIST   = 'backend/invites_list.php';
const API_ACCEPT = 'backend/invite_accept.php';
const API_REJECT = 'backend/invite_reject.php';
const API_LEAVE  = 'backend/invite_leave.php';

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

async function apiPost(url, bodyObj) {
  let res;
  try {
    res = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json; charset=utf-8' },
      body: JSON.stringify(bodyObj || {})
    });
  } catch (e) {
    console.error('FETCH FAILED', url, e);
    throw new Error('Fetch fallita (controlla URL/connessione)');
  }

  let payload = {};
  try {
    payload = await res.json();
  } catch (e) {
    console.error('JSON PARSE FAILED', url, 'HTTP', res.status, e);
    throw new Error(`Risposta non JSON (HTTP ${res.status})`);
  }

  console.log('API POST', url, 'HTTP', res.status, payload);

  if (!res.ok || payload.ok !== true) {
    throw new Error((payload.message || 'Errore richiesta') + ` (HTTP ${res.status})`);
  }
  return payload.data;
}



function renderRow(inv) {
  const stato = inv.stato_invito || '';
  let badge = 'secondary';
  if (stato === 'pendente') badge = 'warning';
  if (stato === 'accettato') badge = 'success';
  if (stato === 'rifiutato') badge = 'danger';

  let actions = '';
  if (stato === 'pendente') {
    actions = `
      <button class="btn btn-sm btn-success me-1" data-action="accept" data-id="${inv.id_prenotazione}">
        Accetta
      </button>
      <button class="btn btn-sm btn-outline-danger" data-action="openReject" data-id="${inv.id_prenotazione}">
        Rifiuta
      </button>
    `;
  } else if (stato === 'accettato') {
    actions = `
      <button class="btn btn-sm btn-outline-secondary" data-action="leave" data-id="${inv.id_prenotazione}">
        Rimuoviti
      </button>
    `;
  } else {
    actions = `<span class="text-muted small">—</span>`;
  }

  return `
    <tr>
      <td>${esc(inv.data)}</td>
      <td>${esc(inv.ora_inizio)}:00</td>
      <td>${esc(inv.durata_ore)}h</td>
      <td>${esc(inv.nome_sala)}</td>
      <td>${esc(inv.attivita ?? '')}</td>
      <td><span class="badge text-bg-${badge}">${esc(stato)}</span></td>
      <td class="text-end">${actions}</td>
    </tr>
  `;
}

async function loadInvites() {
  const tbody = document.getElementById('invitesTbody');
  tbody.innerHTML = `<tr><td colspan="7" class="text-muted">Caricamento...</td></tr>`;

  const res = await fetch(API_LIST, { credentials: 'same-origin' });
  const payload = await res.json().catch(() => ({}));

  if (!res.ok || payload.ok !== true) {
    throw new Error(payload.message || 'Errore caricamento inviti');
  }

  const invites = Array.isArray(payload.data) ? payload.data : (payload.data?.invites || []);
  if (invites.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-muted">Nessun invito da mostrare.</td></tr>`;
    return;
  }

  tbody.innerHTML = invites.map(renderRow).join('');
}


document.addEventListener('DOMContentLoaded', async () => {
  const rejectModalEl = document.getElementById('rejectModal');
  const rejectModal = new bootstrap.Modal(rejectModalEl);

  // Caricamento iniziale
  try { await loadInvites(); } catch (e) { showAlert('danger', e.message); }

  document.getElementById('btnRefresh').addEventListener('click', async () => {
    try { await loadInvites(); } catch (e) { showAlert('danger', e.message); }
  });

  // Bottoni in tabella
  document.getElementById('invitesTbody').addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;

    const action = btn.dataset.action;
    const idPren = parseInt(btn.dataset.id, 10);

    try {
      if (action === 'accept') {
        await apiPost(API_ACCEPT, { id_prenotazione: idPren });
        showAlert('success', 'Invito accettato.');
        await loadInvites();
      }

      if (action === 'leave') {
        await apiPost(API_LEAVE, { id_prenotazione: idPren });
        showAlert('success', 'Rimozione confermata.');
        await loadInvites();
      }

      if (action === 'openReject') {
        document.getElementById('rejectPrenotazioneId').value = String(idPren);
        document.getElementById('rejectReason').value = '';
        rejectModal.show();
      }
    } catch (err) {
      showAlert('danger', err.message);
    }
  });

  // Submit rifiuto
  document.getElementById('rejectForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const idPren = parseInt(document.getElementById('rejectPrenotazioneId').value, 10);
    const motivazione = document.getElementById('rejectReason').value.trim();
    if (!motivazione) return;

    try {
      await apiPost(API_REJECT, { id_prenotazione: idPren, motivazione_rifiuto: motivazione });
      rejectModal.hide();
      showAlert('success', 'Invito rifiutato.');
      await loadInvites();
    } catch (err) {
      showAlert('danger', err.message);
    }
  });
});
