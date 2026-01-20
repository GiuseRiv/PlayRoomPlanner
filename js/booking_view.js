'use strict';

(function () {
  const BOOKING_ID = Number(window.BOOKING_ID || 0);
  const API_URL = 'api/booking_view.php?id=' + encodeURIComponent(BOOKING_ID);

  function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g, m => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[m]));
  }

  function showAlert(type, msg) {
    const box = document.getElementById('alertBox');
    if (box) {
        box.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show shadow-sm">
            ${esc(msg)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`;
    }
  }

  function badgeStato(stato) {
    const s = (stato || '').toLowerCase();
    let cls = 'bg-secondary';
    if (s === 'accettato' || s === 'confermata') cls = 'bg-success';
    if (s === 'pendente') cls = 'bg-warning text-dark';
    if (s === 'rifiutato' || s === 'annullata') cls = 'bg-danger';
    return `<span class="badge ${cls}">${esc(stato || 'N/D')}</span>`;
  }

  function badgeRuolo(ruolo) {
      const r = (ruolo || '').toLowerCase();
      let cls = 'bg-light text-dark border';
      if(r === 'docente') cls = 'bg-primary bg-opacity-10 text-primary border-primary border-opacity-25';
      if(r === 'tecnico') cls = 'bg-dark text-white';
      return `<span class="badge ${cls}">${esc(ruolo)}</span>`;
  }

  function formatDate(ymd) {
    if (!ymd) return '-';
    const [y, m, d] = ymd.split('-');
    return `${d}/${m}/${y}`;
  }

  async function load() {
    if (!BOOKING_ID) {
      document.getElementById('loadingSpinner').classList.add('d-none');
      showAlert('danger', 'ID prenotazione mancante.');
      return;
    }

    try {
        const res = await fetch(API_URL);
        const payload = await res.json().catch(() => ({}));

        if (!res.ok || payload.ok !== true) {
            throw new Error(payload.message || 'Errore caricamento dettagli');
        }

        const data = payload.data || {};
        const p = data.prenotazione || {};

        // ============================================================
        // 1. AGGIORNAMENTO BARRA PROGRESSO (PRIORITARIO)
        // ============================================================
        try {
            const capienza = parseInt(p.capienza);
            // Se capienza è 0 o NaN (errore dati), mettiamo 1 per evitare divisione per zero
            const maxCap = (capienza && capienza > 0) ? capienza : 1; 
            
            // Organizzatore (1) + Invitati Accettati
            const organizerCount = 1; 
            const acceptedInvites = parseInt(data.inviti_stats?.accettati) || 0;
            const occupied = organizerCount + acceptedInvites;
            
            let percent = (occupied / maxCap) * 100;
            
            // Larghezza visuale (minimo 5% se c'è qualcuno, per non farla sparire)
            let visualPercent = percent;
            if (visualPercent > 100) visualPercent = 100;
            if (occupied > 0 && visualPercent < 5) visualPercent = 5;

            const bar = document.getElementById('occupancyBar');
            const fullBadge = document.getElementById('fullCapacityBadge');
            const textEl = document.getElementById('occupancyText');

            if (bar) {
                bar.style.width = visualPercent + '%';
                
                // Reset classi
                bar.className = 'progress-bar progress-bar-striped transition'; 
                
                // Colori
                if (percent >= 100) {
                    bar.classList.add('bg-danger');
                    if(fullBadge) fullBadge.classList.remove('d-none');
                } else if (percent >= 50) {
                    bar.classList.add('bg-warning', 'text-dark');
                    if(fullBadge) fullBadge.classList.add('d-none');
                } else {
                    bar.classList.add('bg-success');
                    if(fullBadge) fullBadge.classList.add('d-none');
                }
            }
            if (textEl) textEl.textContent = `${occupied} su ${maxCap}`;

        } catch (barErr) {
            console.error("Errore calcolo barra:", barErr);
        }

        // ============================================================
        // 2. POPOLAMENTO DATI TESTUALI
        // ============================================================
        document.getElementById('headerTitle').textContent = p.attivita || 'Dettaglio';
        document.getElementById('attivita').textContent = p.attivita || '—';
        document.getElementById('statoBadge').innerHTML = badgeStato(p.stato);
        document.getElementById('dataDisplay').textContent = formatDate(p.data);
        document.getElementById('oraDurataDisplay').textContent = `${p.ora_inizio}:00 (${p.durata_ore} ore)`;
        document.getElementById('creato').textContent = p.data_creazione ? formatDate(p.data_creazione.split(' ')[0]) : '-';
        document.getElementById('organizzatore').innerHTML = 
            `<strong>${esc(p.organizzatore)}</strong> <span class="badge bg-light text-dark border ms-1">${esc(p.ruolo_organizzatore)}</span>`;

        document.getElementById('sala').textContent = p.nome_sala || '-';
        document.getElementById('capienza').textContent = p.capienza || '0';
        document.getElementById('settore').textContent = p.nome_settore || '-';
        document.getElementById('tipoSettore').textContent = p.tipo_settore ? `(${p.tipo_settore})` : '';

        // Dotazioni (con controllo array)
        const dotsBox = document.getElementById('dotazioni');
        const dots = Array.isArray(data.dotazioni) ? data.dotazioni : [];
        if (dots.length === 0) {
            dotsBox.innerHTML = '<span class="text-muted fst-italic">Nessuna dotazione specifica</span>';
        } else {
            dotsBox.innerHTML = dots.map(d => 
                `<span class="badge bg-info bg-opacity-10 text-dark border me-1 mb-1">${esc(d.nome)}</span>`
            ).join('');
        }

        // Breakdown testuale inviti
        const stats = data.inviti_stats || { tot:0, accettati:0, pendenti:0, rifiutati:0 };
        document.getElementById('roleBreakdown').textContent = 
            `Totale invitati: ${stats.tot} (✅ ${stats.accettati} confermati, ⏳ ${stats.pendenti} in attesa, ❌ ${stats.rifiutati} rifiutati)`;

        // ============================================================
        // 3. TABELLA VS PRIVACY
        // ============================================================
        const invitati = Array.isArray(data.invitati) ? data.invitati : [];
        const tableContainer = document.getElementById('tableContainer');
        const hiddenMsg = document.getElementById('hiddenListMessage');
        const tbody = document.getElementById('invTbody');

        if (invitati.length > 0) {
            // Mostra Tabella
            if(tableContainer) tableContainer.classList.remove('d-none');
            if(hiddenMsg) hiddenMsg.classList.add('d-none');

            if(tbody) {
                tbody.innerHTML = invitati.map(u => `
                    <tr>
                        <td><div class="fw-bold">${esc(u.cognome)} ${esc(u.nome)}</div></td>
                        <td>
                            <a href="mailto:${esc(u.email)}" class="text-decoration-none text-muted small">
                               <i class="bi bi-envelope"></i> ${esc(u.email)}
                            </a>
                        </td>
                        <td>${badgeRuolo(u.ruolo)}</td>
                        <td>${badgeStato(u.stato)}</td>
                    </tr>
                `).join('');
            }
        } else {
            // Nascondi Tabella
            if(tableContainer) tableContainer.classList.add('d-none');
            // Mostra privacy solo se ci sono invitati ma non li vediamo
            if (hiddenMsg) {
                if (stats.tot > 0) hiddenMsg.classList.remove('d-none');
                else hiddenMsg.classList.add('d-none');
            }
        }

        // Mostra tutto
        document.getElementById('loadingSpinner').classList.add('d-none');
        document.getElementById('mainContent').classList.remove('d-none');

    } catch (err) {
        console.error(err); // Log per debug
        document.getElementById('loadingSpinner').classList.add('d-none');
        showAlert('danger', 'Errore imprevisto: ' + err.message);
    }
  }

  document.addEventListener('DOMContentLoaded', load);
})();