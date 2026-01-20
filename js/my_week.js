'use strict';

async function apiGet(url) {
  const res = await fetch(url, { credentials: 'same-origin' });
  const payload = await res.json();
  if (!res.ok || !payload.ok) throw new Error(payload?.message || `HTTP ${res.status}`);
  return payload.data;
}

function showAlert(msg, type = 'danger') {
  const box = document.getElementById('alertBox');
  box.innerHTML = `
    <div class="alert alert-${type} alert-dismissible fade show">
      ${msg}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`;
}

let allRows = [];

function renderRows(rows) {
  const tbody = document.getElementById('weekTbody');
  tbody.innerHTML = rows.length 
    ? rows.map(r => `
      <tr>
        <td>${r.data}</td>
        <td>${r.ora}</td>
        <td>${r.durata}</td>
        <td>${r.sala}</td>
        <td>${r.attivita}</td>
        <td>${r.organizzatore}</td>
        <td>${badgeHtml(r.stato_invito)}</td>
        <td class="text-end">
          ${r.can_cancel ? 
            `<button class="btn btn-sm btn-outline-danger cancel-btn" data-id="${r.id_prenotazione}">Annulla</button>` : 
            '—'}
        </td>
      </tr>
    `).join('')
    : '<tr><td colspan="8" class="text-muted text-center py-4">Nessun impegno</td></tr>';
}

function badgeHtml(stato) {
  const s = stato?.toLowerCase();
  const map = {
    'accettato': '✅ Parteciperò (bg-success)',
    'pendente': '⏳ In attesa (bg-warning text-dark)',
    'rifiutato': '❌ Rifiutato (bg-danger)',
    'organizzatore': '👑 Organizzatore (bg-primary)'
  };
  const [text, cls] = (map[s] || '— (bg-secondary)').split('(');
  return `<span class="badge ${cls.slice(0,-1)}">${text}</span>`;
}

function applyFilters() {
  const status = document.getElementById('statusFilter')?.value || '';
  const q = document.getElementById('textFilter')?.value?.toLowerCase() || '';
  
  const filtered = allRows.filter(r => {
    return (!status || r.stato_invito === status) &&
           (!q || `${r.sala} ${r.attivita} ${r.organizzatore}`.toLowerCase().includes(q));
  });
  renderRows(filtered);
}

async function loadWeek(day) {
  document.getElementById('weekTbody').innerHTML = '<tr><td colspan="8" class="text-center"><div class="spinner-border"></div></td></tr>';
  
  try {
    const data = await apiGet(`backend/user_week.php?day=${day}`);
    allRows = data;
    applyFilters();
    
    const wr = document.getElementById('weekRange');
    wr.textContent = `${data.week?.monday || ''} → ${data.week?.sunday || ''}`;
  } catch (e) {
    showAlert(e.message);
    renderRows([]);
  }
}

async function cancelBooking(id) {
  const res = await fetch(`backend/bookings.php?id=${id}`, { method: 'DELETE', credentials: 'same-origin' });
  const payload = await res.json();
  if (!res.ok || !payload.ok) throw new Error(payload.message || 'Errore');
  return payload.data;
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('weekForm');
  const dayInput = document.getElementById('dayInput');
  const statusFilter = document.getElementById('statusFilter');
  const textFilter = document.getElementById('textFilter');
  const tbody = document.getElementById('weekTbody');
  
  // Eventi
  form.onsubmit = e => { e.preventDefault(); loadWeek(dayInput.value); };
  document.getElementById('btnRefresh').onclick = () => loadWeek(dayInput.value);
  statusFilter.onchange = textFilter.oninput = applyFilters;
  
  // Load iniziale
  if (dayInput.value) loadWeek(dayInput.value);
  
  // Cancel buttons
  tbody.addEventListener('click', async e => {
    const btn = e.target.closest('.cancel-btn');
    if (!btn) return;
    
    if (!confirm('Annullare prenotazione?')) return;
    
    btn.disabled = true;
    try {
      await cancelBooking(btn.dataset.id);
      showAlert('Annullata!', 'success');
      loadWeek(dayInput.value);
    } catch (e) {
      showAlert(e.message);
      btn.disabled = false;
    }
  });
});
