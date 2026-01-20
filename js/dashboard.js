'use strict';

async function apiGet(url) {
    const res = await fetch(url);
    let payload = null;
    try { payload = await res.json(); } catch (_) {}

    if (!res.ok) throw new Error(payload?.message || `HTTP ${res.status}`);
    if (!payload || payload.ok !== true) throw new Error(payload?.message || 'Errore richiesta');
    
    return payload.data;
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = String(value);
}

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const stats = await apiGet('backend/dashboard_stats.php');

        
        setText('kpiPending', stats.pending_invites ?? 0);
        setText('kpiPlannedWeek', stats.planned_week ?? 0);

        
        const btn = document.getElementById('nextDetailsBtn');

        if (stats.next_event) {
            
            setText('nextTitle', `${stats.next_event.attivita} • ${stats.next_event.nome_sala}`);
            
            const d = new Date(stats.next_event.data);
            const dataStr = d.toLocaleDateString('it-IT');
            
            setText('nextWhen', `${dataStr} ore ${stats.next_event.ora_inizio}:00 (${stats.next_event.durata_ore}h)`);
            
            if (btn) {
                btn.href = `index.php?page=booking_view&id=${stats.next_event.id_prenotazione}`;
                btn.classList.remove('disabled');
                btn.classList.add('btn-warning'); 
                btn.classList.remove('btn-outline-warning');
            }
        } else {
            setText('nextTitle', 'Nessun impegno imminente');
            setText('nextWhen', 'Tutto libero!');
            if (btn) {
                btn.classList.add('disabled');
                btn.href = '#';
            }
        }

    } catch (err) {
        console.error("Dashboard error:", err);
        setText('kpiPending', '-');
        setText('kpiPlannedWeek', '-');
    }
});