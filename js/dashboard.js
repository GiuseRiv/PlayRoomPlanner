// JS/dashboard.js
const API_STATS = 'api/dashboard_stats.php';

function esc(str) {
  return String(str ?? '').replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[m]));
}

function showAlert(type, text) {
  const box = document.getElementById('dashAlert');
  box.innerHTML = `
    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
      ${esc(text)}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
    </div>`;
}

document.addEventListener('DOMContentLoaded', async () => {
  try {
    const res = await fetch(API_STATS);
    const payload = await res.json().catch(() => ({}));

    if (!res.ok || payload.ok !== true) {
      throw new Error(payload.message || 'Errore caricamento dashboard');
    }

    const d = payload.data || {};
    document.getElementById('kpiPending').textContent = d.pending_invites ?? 0;
    document.getElementById('kpiAccepted').textContent = d.accepted_future ?? 0;

    const next = d.next_event;
    if (!next) {
      document.getElementById('nextTitle').textContent = 'Nessun impegno pianificato';
      document.getElementById('nextMeta').textContent = '—';
    } else {
      document.getElementById('nextTitle').textContent = `${next.attivita || 'Attività'} • ${next.nome_sala}`;
      document.getElementById('nextMeta').textContent = `${next.data} alle ${next.ora_inizio}:00 (${next.durata_ore}h)`;
    }
  } catch (e) {
    showAlert('warning', e.message);
  }
});
