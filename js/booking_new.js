'use strict';

// NON serve più API_SECTORS, usiamo solo questa (che ora restituisce rooms + sectors):
const API_ROOMS = 'backend/rooms_bookable.php'; 
const API_BOOKINGS = 'backend/bookings.php';
const API_INVITE_CREATE = 'backend/invite_create.php';
const API_ROOM_BUSY = 'backend/room_day_busy.php';

// --- UTILS ---
function esc(str) { return String(str ?? '').replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

function showAlert(type, text) { 
    const box = document.getElementById('alertBox'); 
    if(box) { 
        box.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show">${esc(text)}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`; 
        box.scrollIntoView({ behavior: 'smooth', block: 'center' }); 
    } 
}

async function apiGet(url) { 
    const res = await fetch(url, {credentials:'same-origin'}); 
    const p = await res.json().catch(()=>({})); 
    if(!res.ok||!p.ok) throw new Error(p.message||'Err'); 
    return p.data??p; 
}

async function apiPost(url, body) { 
    const res = await fetch(url, {
        method:'POST',
        credentials:'same-origin',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify(body)
    }); 
    const p = await res.json().catch(()=>({})); 
    if(!res.ok||!p.ok) throw new Error(p.message||'Err'); 
    return p.data??p; 
}

// --- LOGICA UI INVITI ---
function updateInviteUI() {
    const inviteMode = document.getElementById('inviteMode');
    if(!inviteMode) return;

    const mode = inviteMode.value;
    const boxSimpleRole = document.getElementById('boxSimpleRole');
    const boxSimpleSector = document.getElementById('boxSimpleSector');
    const boxCustom = document.getElementById('boxCustom');
    const simpleRoleSelect = document.getElementById('simpleRoleSelect');
    const simpleSectorSelect = document.getElementById('simpleSectorSelect');

    // Reset
    if(boxSimpleRole) boxSimpleRole.style.display = 'none';
    if(boxSimpleSector) boxSimpleSector.style.display = 'none';
    if(boxCustom) boxCustom.style.display = 'none';
    
    if(simpleRoleSelect) simpleRoleSelect.required = false;
    if(simpleSectorSelect) simpleSectorSelect.required = false;

    if (mode === 'role') {
        if(boxSimpleRole) boxSimpleRole.style.display = 'block';
        if(simpleRoleSelect) simpleRoleSelect.required = true;
    } else if (mode === 'sector') {
        if(boxSimpleSector) boxSimpleSector.style.display = 'block';
        if(simpleSectorSelect) simpleSectorSelect.required = true;
    } else if (mode === 'custom') {
        if(boxCustom) boxCustom.style.display = 'block';
    }
}

// --- POPOLAMENTO DATI ---
function fillRooms(rooms) {
    const roomSelect = document.getElementById('roomSelect');
    if(!roomSelect) return;
    roomSelect.innerHTML = `<option value="">Seleziona una sala...</option>`;
    rooms.forEach(r => {
        const opt = document.createElement('option');
        opt.value = String(r.id_sala);
        opt.textContent = `${r.nome_settore} • ${r.nome_sala} (Cap: ${r.capienza})`;
        roomSelect.appendChild(opt);
    });
}

function fillSectors(settori) {
    const simpleSectorSelect = document.getElementById('simpleSectorSelect');
    const customSectorsContainer = document.getElementById('customSectorsContainer');

    if(simpleSectorSelect) {
        simpleSectorSelect.innerHTML = `<option value="">Scegli settore...</option>`;
        settori.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id_settore;
            opt.textContent = s.nome;
            simpleSectorSelect.appendChild(opt);
        });
    }

    if(customSectorsContainer) {
        if (settori.length === 0) {
            customSectorsContainer.innerHTML = '<em class="text-muted">Nessun settore disponibile</em>';
        } else {
            customSectorsContainer.innerHTML = settori.map(s => `
                <div class="form-check">
                    <input class="form-check-input custom-sector" type="checkbox" value="${s.id_settore}" id="cxNewSec_${s.id_settore}">
                    <label class="form-check-label" for="cxNewSec_${s.id_settore}">${s.nome}</label>
                </div>
            `).join('');
        }
    }
}

// --- DATE & TIME UTILS ---
function todayISO() { const now = new Date(); return now.toISOString().split('T')[0]; }

function minStartHourForDay(dayStr) {
    const now = new Date(); const t = todayISO();
    if (dayStr !== t) return 9;
    const nextHour = now.getHours() + 1;
    return Math.max(9, Math.min(23, nextHour));
}

// *** MODIFICA QUI: Nascondiamo le ore passate ***
function fillStartHoursForDay(dayStr) {
    const sel = document.getElementById('startSelect');
    if(!sel) return;
    
    const minH = minStartHourForDay(dayStr);
    
    sel.innerHTML = `<option value="">Seleziona...</option>`;
    
    for (let h = 9; h <= 22; h++) {
        // Se l'ora è inferiore al minimo consentito (passata), la saltiamo completamente
        if (h < minH) continue; 

        const opt = document.createElement('option');
        opt.value = String(h);
        opt.textContent = `${String(h).padStart(2,'0')}:00`;
        
        sel.appendChild(opt);
    }
}

function fillDurations(startHour) {
    const sel = document.getElementById('durSelect');
    if(!sel) return;
    sel.innerHTML = `<option value="">Seleziona...</option>`;
    if (!startHour) return;
    const h = parseInt(startHour, 10);
    if (!Number.isFinite(h)) return;
    const maxDur = Math.max(1, 23 - h);
    for (let d = 1; d <= maxDur; d++) sel.add(new Option(d+'h', d));
}

async function loadBusyHours(idSala, dateStr) {
    if (!Number.isFinite(idSala) || idSala <= 0 || !dateStr) return [];
    const url = `${API_ROOM_BUSY}?id_sala=${encodeURIComponent(idSala)}&date=${encodeURIComponent(dateStr)}`;
    const data = await apiGet(url);
    return Array.isArray(data.busy_hours) ? data.busy_hours : [];
}

function applyBusyToStartSelect(busyHours) {
    const sel = document.getElementById('startSelect');
    if(!sel) return;
    const busySet = new Set((busyHours || []).map(n => String(n)));
    [...sel.options].forEach(opt => {
        if (!opt.value) return;
        if (busySet.has(opt.value)) {
            opt.disabled = true;
            if (!opt.textContent.includes('(occupata)')) opt.textContent += ' (occupata)';
        }
    });
}

async function refreshBusyUI() {
    const roomSelectEl = document.getElementById('roomSelect');
    const dateInputEl = document.getElementById('dateInput');
    const startSelectEl = document.getElementById('startSelect');
    
    if(!roomSelectEl || !dateInputEl || !startSelectEl) return;

    const idSala = parseInt(roomSelectEl.value, 10);
    const dateStr = dateInputEl.value;

    if (!Number.isFinite(idSala) || idSala <= 0 || !dateStr) {
        fillStartHoursForDay(dateStr || todayISO()); 
        fillDurations(''); 
        return;
    }

    const prevStart = startSelectEl.value;
    fillStartHoursForDay(dateStr);
    
    try {
        const busy = await loadBusyHours(idSala, dateStr);
        applyBusyToStartSelect(busy);
    } catch (e) { console.warn(e); }
    
    // Se l'ora precedentemente selezionata è ora nascosta o disabilitata, resetta
    const opt = [...startSelectEl.options].find(o => o.value === prevStart);
    if (opt && !opt.disabled) { 
        startSelectEl.value = prevStart; 
        fillDurations(prevStart); 
    } else { 
        startSelectEl.value = ''; 
        fillDurations(null); 
    }
}

// --- INIT ---
document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('bookingForm');
    const inviteMode = document.getElementById('inviteMode');
    const dateInput = document.getElementById('dateInput');
    const startSelect = document.getElementById('startSelect');
    const roomSelect = document.getElementById('roomSelect');
    
    if(inviteMode) { inviteMode.addEventListener('change', updateInviteUI); updateInviteUI(); }

    const t = todayISO();
    if(dateInput) { 
        dateInput.min = t; 
        if (!dateInput.value) dateInput.value = t; 
        fillStartHoursForDay(dateInput.value); 
        dateInput.addEventListener('change', refreshBusyUI); 
    }
    if(roomSelect) roomSelect.addEventListener('change', refreshBusyUI);
    if(startSelect) startSelect.addEventListener('change', () => fillDurations(startSelect.value));

    // CARICAMENTO SALE + SETTORI
    try {
        const data = await apiGet(API_ROOMS);
        const rooms = data.rooms || [];
        const sectors = data.sectors || [];

        if (!rooms.length) showAlert('warning', 'Nessuna sala disponibile.');
        else {
            fillRooms(rooms);
            if(roomSelect) roomSelect.value = String(rooms[0].id_sala);
            await refreshBusyUI();
        }
        
        fillSectors(sectors);

    } catch (e) {
        showAlert('danger', 'Errore caricamento dati: ' + e.message);
    }

    // SUBMIT
    if(form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(form);

            const bookingPayload = {
                id_sala: fd.get('id_sala'),
                data: fd.get('data'),
                ora_inizio: fd.get('ora_inizio'),
                durata_ore: fd.get('durata_ore'),
                attivita: fd.get('attivita')
            };

            try {
                const created = await apiPost(API_BOOKINGS, bookingPayload);
                const id_prenotazione = created.id_prenotazione;

                if(inviteMode && inviteMode.value !== 'none') {
                    const mode = inviteMode.value;
                    let inviteAll = (mode === 'all');
                    let targetRoles = [];
                    let targetSectors = [];

                    if (mode === 'role') { 
                        const r = document.getElementById('simpleRoleSelect').value; 
                        if(r) targetRoles.push(r); 
                    }
                    else if (mode === 'sector') { 
                        const s = document.getElementById('simpleSectorSelect').value; 
                        if(s) targetSectors.push(s); 
                    }
                    else if (mode === 'custom') { 
                        document.querySelectorAll('.custom-role:checked').forEach(cb => targetRoles.push(cb.value)); 
                        document.querySelectorAll('.custom-sector:checked').forEach(cb => targetSectors.push(cb.value)); 
                    }

                    await apiPost(API_INVITE_CREATE, {
                        id_prenotazione: id_prenotazione,
                        invite_all: inviteAll,
                        target_roles: targetRoles,
                        target_sectors: targetSectors
                    });
                }
                showAlert('success', 'Prenotazione creata con successo!');
                setTimeout(() => { window.location.href = 'index.php?page=dashboard'; }, 800);
            } catch (err) { showAlert('danger', err.message); }
        });
    }
});