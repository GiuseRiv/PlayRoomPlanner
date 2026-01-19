let allRows = [];

function showAlert(msg, type = 'danger') {
  const box = document.getElementById('alertBox');
  if (!box) return;
  box.innerHTML = `
    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
      ${msg}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
    </div>`;
}

function clearAlert() {
  const box = document.getElementById('alertBox');
  if (box) box.innerHTML = '';
}

function setLoading() {
  const tb = document.getElementById('weekTbody');
  if (tb) tb.innerHTML = `<tr><td colspan="8" class="text-muted">Caricamento...</td></tr>`;
}

function badgeHtml(stato) {
  const s = (stato || '').toLowerCase();
  const cls =
    s === 'accettato' ? 'bg-success' :
    s === 'pendente' ? 'bg-warning text-dark' :
    s === 'rifiutato' ? 'bg-danger' :
    'bg-secondary';
  return `<span class="badge ${cls}">${stato || ''}</span>`;
}

function renderRows(rows) {
  const tb = document.getElementById('weekTbody');
  if (!tb) return;

  if (!rows || rows.length === 0) {
    tb.innerHTML = `<tr><td colspan="8" class="text-muted">Nessun impegno in settimana</td></tr>`;
    return;
  }

  tb.innerHTML = rows.map(r => {
    const id = r.id_prenotazione;
    const canCancel = !!r.can_cancel;

    const actionsHtml = (canCancel && id)
      ? `<button type="button" class="btn btn-sm btn-outline-danger"
                 data-action="cancel" data-id="${id}">Annulla</button>`
      : `<span class="text-muted">—</span>`;

    return `
      <tr>
        <td>${r.data ?? ''}</td>
        <td>${r.ora ?? ''}</td>
        <td>${r.durata ?? ''}</td>
        <td>${r.sala ?? ''}</td>
        <td>${r.attivita ?? ''}</td>
        <td>${r.organizzatore ?? ''}</td>
        <td>${badgeHtml(r.stato_invito)}</td>
        <td class="text-end">${actionsHtml}</td>
      </tr>
    `;
  }).join('');
}

function applyFilters() {
  const status = document.getElementById('statusFilter')?.value || '';
  const q = (document.getElementById('textFilter')?.value || '').trim().toLowerCase();

  const filtered = allRows.filter(r => {
    const okStatus = !status || (r.stato_invito === status);
    const hay = `${r.sala ?? ''} ${r.attivita ?? ''} ${r.organizzatore ?? ''}`.toLowerCase();
    const okQ = !q || hay.includes(q);
    return okStatus && okQ;
  });

  renderRows(filtered);
}

async function loadWeek(day) {
  setLoading();
  clearAlert();

  const url = `api/user_week.php?day=${encodeURIComponent(day)}`;

  try {
    const res = await fetch(url, { credentials: 'same-origin' });
    const txt = await res.text();

    let payload;
    try {
      payload = JSON.parse(txt);
    } catch (e) {
      showAlert(`Risposta non JSON (HTTP ${res.status}). Contenuto: ${txt.slice(0, 200)}...`);
      renderRows([]);
      return;
    }

    if (!res.ok || payload.ok !== true) {
      showAlert((payload.message || 'Errore richiesta') + ` (HTTP ${res.status})`);
      renderRows([]);
      return;
    }

    allRows = payload.data || [];
    applyFilters();

    const wr = document.getElementById('weekRange');
    if (wr && payload.week?.monday && payload.week?.sunday) {
      wr.textContent = `${payload.week.monday} → ${payload.week.sunday}`;
    }
  } catch (e) {
    showAlert('Errore JS: ' + (e?.message || e));
    renderRows([]);
  }
}

async function cancelBooking(id) {
  const res = await fetch(`api/bookings.php?id=${encodeURIComponent(id)}`, {
    method: 'DELETE',
    credentials: 'same-origin'
  });
  const payload = await res.json().catch(() => ({}));
  if (!res.ok || payload.ok !== true) throw new Error(payload.message || 'Errore annullamento');
  return payload.data;
}

document.addEventListener('DOMContentLoaded', () => {
  const dayInput = document.getElementById('dayInput');
  const btnRefresh = document.getElementById('btnRefresh');
  const form = document.getElementById('weekForm');
  const tbody = document.getElementById('weekTbody');

  document.getElementById('statusFilter')?.addEventListener('change', applyFilters);
  document.getElementById('textFilter')?.addEventListener('input', applyFilters);

  if (dayInput?.value) loadWeek(dayInput.value);

  btnRefresh?.addEventListener('click', () => {
    if (dayInput?.value) loadWeek(dayInput.value);
  });

  form?.addEventListener('submit', (e) => {
    e.preventDefault();
    if (dayInput?.value) loadWeek(dayInput.value);
  });

  tbody?.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-action="cancel"]');
    if (!btn) return;

    const id = btn.getAttribute('data-id');
    if (!id) return;

    if (!confirm('Vuoi annullare questa prenotazione?')) return;

    btn.disabled = true;
    try {
      await cancelBooking(id);
      showAlert('Prenotazione annullata.', 'success');
      if (dayInput?.value) loadWeek(dayInput.value);
    } catch (err) {
      showAlert(err.message || 'Errore annullamento');
      btn.disabled = false;
    }
  });
});
