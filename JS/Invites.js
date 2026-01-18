// JS/invites.js

const API = {
  list:   'api/invites_list.php',
  accept: 'api/invite_accept.php',
  reject: 'api/invite_reject.php',
  leave:  'api/invite_leave.php'
};

function esc(str) {
  return String(str ?? '').replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[m]));
}

function badgeForStatus(stato) {
  const s = String(stato || '').toLowerCase();
  if (s === 'accettato') return `<span class="badge bg-success">accettato</span>`;
  if (s === 'rifiutato') return `<span class="badge bg-danger">rifiutato</span>`;
  return `<span class="badge bg-secondary">pendente</span>`;
}

function showAlert(type, text) {
  const alertBox = document.getElementById('alertBox');
  alertBox.innerHTML = `
    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
      ${esc(text)}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
    </div>`;
}

async function apiPost(url, payload) {
  const res = await fetch(url, {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(payload)
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg = data.message || 'Errore richiesta';
    throw new Error(msg);
  }
  return data;
}

async function loadInvites() {
  const tbody = document.getElementById('invitesTbody');
  tbody.innerHTML = `<tr><td colspan="7" class="text-muted">Caricamento...</td></tr>`;

  try {
    const res = await fetch(API.list, { method: 'GET' });
    const data = await res.json();

    if (!res.ok) {
      throw new Error(data.message || 'Errore caricamento inviti');
    }

    const invites = data.invites || [];
    if (invites.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-muted">Nessun invito trovato.</td></tr>`;
      return;
    }

    tbody.innerHTML = invites.map(inv => {
      const id = inv.id_prenotazione;
      const stato = String(inv.stato || 'pendente');

      const acceptBtn = (stato === 'pendente')
        ? `<button class="btn btn-sm btn-success me-1" data-action="accept" data-id="${id}">Accetta</button>`
        : '';

      const rejectBtn = (stato === 'pendente')
        ? `<button class="btn btn-sm btn-outline-danger me-1" data-action="reject" data-id="${id}">Rifiuta</button>`
        : '';

      const leaveBtn = (stato === 'accettato')
        ? `<button class="btn btn-sm btn-warning" data-action="leave" data-id="${id}">Rimuoviti</button>`
        : '';

      return `
        <tr>
          <td>${esc(inv.data)}</td>
          <td>${esc(inv.ora_inizio)}:00</td>
          <td>${esc(inv.durata_ore)}h</td>
          <td>${esc(inv.nome_sala || ('Sala #' + inv.id_sala))}</td>
          <td>${esc(inv.attivita || '')}</td>
          <td>${badgeForStatus(inv.stato)}</td>
          <td class="text-end">
            ${acceptBtn}${rejectBtn}${leaveBtn}
          </td>
        </tr>`;
    }).join('');

  } catch (e) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-danger">Errore: ${esc(e.message)}</td></tr>`;
  }
}

function openRejectModal(idPrenotazione) {
  document.getElementById('rejectPrenotazioneId').value = idPrenotazione;
  document.getElementById('rejectReason').value = '';
  const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
  modal.show();
}

async function onTableClick(e) {
  const btn = e.target.closest('button[data-action]');
  if (!btn) return;

  const action = btn.dataset.action;
  const id = parseInt(btn.dataset.id, 10);

  try {
    if (action === 'accept') {
      btn.disabled = true;
      await apiPost(API.accept, { id_prenotazione: id });
      showAlert('success', 'Invito accettato.');
      await loadInvites();
    }

    if (action === 'reject') {
      openRejectModal(id);
    }

    if (action === 'leave') {
      if (!confirm('Vuoi davvero rimuovere la tua disponibilità?')) return;
      btn.disabled = true;
      await apiPost(API.leave, { id_prenotazione: id });
      showAlert('success', 'Disponibilità rimossa.');
      await loadInvites();
    }
  } catch (e2) {
    showAlert('danger', e2.message);
    btn.disabled = false;
  }
}

async function onRejectSubmit(e) {
  e.preventDefault();
  const id = parseInt(document.getElementById('rejectPrenotazioneId').value, 10);
  const reason = document.getElementById('rejectReason').value.trim();

  if (!reason) {
    showAlert('warning', 'Inserisci una motivazione per il rifiuto.');
    return;
  }

  try {
    await apiPost(API.reject, { id_prenotazione: id, motivazione: reason });
    showAlert('success', 'Invito rifiutato.');
    bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
    await loadInvites();
  } catch (e2) {
    showAlert('danger', e2.message);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('btnRefresh').addEventListener('click', loadInvites);
  document.getElementById('invitesTbody').addEventListener('click', onTableClick);
  document.getElementById('rejectForm').addEventListener('submit', onRejectSubmit);
  loadInvites();
});
