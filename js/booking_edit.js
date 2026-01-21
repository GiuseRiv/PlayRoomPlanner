'use strict';

document.addEventListener('DOMContentLoaded', async () => {
    
    const bookingIdInput = document.getElementById('bookingId');
    const alertBox = document.getElementById('alertBox');
    const form = document.getElementById('editForm');
    const salaSelect = document.getElementById('fieldSala');
    
    // NUOVI ELEMENTI UI
    const modifyInvitesToggle = document.getElementById('modifyInvitesToggle');
    const inviteControlsContainer = document.getElementById('inviteControlsContainer');
    const inviteInfoBox = document.getElementById('inviteInfoBox');

    // Elementi Menu
    const inviteMode = document.getElementById('inviteMode');
    const boxSimpleRole = document.getElementById('boxSimpleRole');
    const boxSimpleSector = document.getElementById('boxSimpleSector');
    const boxCustom = document.getElementById('boxCustom');
    
    // Input specifici
    const simpleRoleSelect = document.getElementById('simpleRoleSelect');
    const simpleSectorSelect = document.getElementById('simpleSectorSelect');
    const customSectorsContainer = document.getElementById('customSectorsContainer');

    if (!bookingIdInput || !bookingIdInput.value) return;
    const bookingId = bookingIdInput.value;

    function showAlert(type, msg) {
        if(alertBox) {
            alertBox.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else alert(msg);
    }

    // --- LOGICA UI AGGIORNATA ---
    function updateUI() {
        const isModifying = modifyInvitesToggle.checked;
        
        // 1. Gestione Visibilità Container Principale e Messaggi
        if (!isModifying) {
            // Caso OFF: Non tocco nulla
            inviteControlsContainer.style.display = 'none';
            inviteInfoBox.className = 'alert alert-info border-info small mb-3';
            inviteInfoBox.innerHTML = '<i class="bi bi-info-circle-fill me-1"></i> La lista degli invitati <strong>non verrà modificata</strong>. Gli inviti precedenti restano validi.';
            return; // Esco, non serve aggiornare i sottomenu
        } else {
            // Caso ON: Modifico e sostituisco
            inviteControlsContainer.style.display = 'block';
            inviteInfoBox.className = 'alert alert-warning border-warning small mb-3';
            inviteInfoBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Attenzione:</strong> Proseguendo, la vecchia lista invitati verrà <strong>cancellata</strong> e sostituita dalla nuova selezione qui sotto.';
        }

        // 2. Gestione Sottomenu (solo se isModifying è true)
        const mode = inviteMode.value;

        // Reset display
        boxSimpleRole.style.display = 'none';
        boxSimpleSector.style.display = 'none';
        boxCustom.style.display = 'none';
        
        // Reset required
        simpleRoleSelect.required = false;
        simpleSectorSelect.required = false;

        if (mode === 'role') {
            boxSimpleRole.style.display = 'block';
            simpleRoleSelect.required = true;
        } else if (mode === 'sector') {
            boxSimpleSector.style.display = 'block';
            simpleSectorSelect.required = true;
        } else if (mode === 'custom') {
            boxCustom.style.display = 'block';
        }
    }

    // Event Listeners
    if(modifyInvitesToggle) modifyInvitesToggle.addEventListener('change', updateUI);
    if(inviteMode) inviteMode.addEventListener('change', updateUI);
    
    // Init iniziale
    updateUI(); 

    // --- CARICAMENTO DATI ---
    try {
        const res = await fetch(`backend/booking_edit.php?id=${bookingId}`);
        const json = await res.json();
        if(!json.ok) throw new Error(json.message);

        const b = json.data.booking;
        const sale = json.data.sale;
        const settori = json.data.settori || [];

        // Popola Sale
        salaSelect.innerHTML = sale.map(s => `<option value="${s.id_sala}">${s.nome} (Cap: ${s.capienza})</option>`).join('');

        // Popola Settori Semplici
        simpleSectorSelect.innerHTML = '<option value="">Seleziona...</option>' + 
            settori.map(s => `<option value="${s.id_settore}">${s.nome}</option>`).join('');

        // Popola Settori Custom
        if (customSectorsContainer) {
            if (settori.length === 0) customSectorsContainer.innerHTML = 'Nessun settore';
            else {
                customSectorsContainer.innerHTML = settori.map(s => `
                    <div class="form-check">
                        <input class="form-check-input custom-sector" type="checkbox" value="${s.id_settore}" id="cxSec_${s.id_settore}">
                        <label class="form-check-label" for="cxSec_${s.id_settore}">${s.nome}</label>
                    </div>
                `).join('');
            }
        }

        // Setta valori form
        document.getElementById('fieldAttivita').value = b.attivita;
        document.getElementById('fieldData').value = b.data;
        document.getElementById('fieldOra').value = b.ora_inizio;
        document.getElementById('fieldDurata').value = b.durata_ore;
        salaSelect.value = b.id_sala;

    } catch(err) {
        showAlert('danger', err.message);
        const fs = form.querySelector('fieldset');
        if(fs) fs.disabled = true;
    }

    // --- INVIO FORM ---
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        const oldText = btn.textContent;
        btn.disabled = true; btn.textContent = 'Salvataggio...';

        // Preparazione Dati Invito
        let inviteAll = false;
        let targetRoles = [];
        let targetSectors = [];

        // IMPORTANTE: Controllo se lo switch è attivo
        if (modifyInvitesToggle.checked) {
            const mode = inviteMode.value;

            if (mode === 'all') {
                inviteAll = true;
            } else if (mode === 'role') {
                targetRoles = [simpleRoleSelect.value];
            } else if (mode === 'sector') {
                targetSectors = [simpleSectorSelect.value];
            } else if (mode === 'custom') {
                document.querySelectorAll('.custom-role:checked').forEach(cb => targetRoles.push(cb.value));
                document.querySelectorAll('.custom-sector:checked').forEach(cb => targetSectors.push(cb.value));
            } else if (mode === 'none') {
                 // Ha attivato lo switch ma ha lasciato "Scegli chi invitare..." o un placeholder vuoto.
                 // In questo caso, potremmo voler impedire il salvataggio o considerarlo come "cancellare tutti".
                 // Per sicurezza, se non sceglie nulla nel menu, consideriamo come "non toccare nulla"
                 // O lanciamo alert. Qui ipotizziamo di richiedere una scelta.
                 if(inviteMode.value === 'none') {
                     alert("Seleziona una modalità di invito o disattiva la modifica degli invitati.");
                     btn.disabled = false; btn.textContent = oldText;
                     return;
                 }
            }
        } 
        // Se lo switch è spento (else), le variabili restano vuote/false.
        // Il backend riceverà invite_all=false, roles=[], sectors=[].
        // Il backend interpreterà questo come "Non fare nulla sugli inviti".

        const payload = {
            id_prenotazione: bookingId,
            attivita: document.getElementById('fieldAttivita').value,
            data: document.getElementById('fieldData').value,
            id_sala: document.getElementById('fieldSala').value,
            ora_inizio: document.getElementById('fieldOra').value,
            durata_ore: document.getElementById('fieldDurata').value,
            
            invite_all: inviteAll,
            target_roles: targetRoles,
            target_sectors: targetSectors
        };

        try {
            const res = await fetch('backend/booking_edit.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if(!json.ok) throw new Error(json.message);

            let msg = 'Salvato!';
            if(json.data && json.data.invites_sent > 0) msg += ` Inviati ${json.data.invites_sent} nuovi inviti.`;
            alert(msg);
            window.location.href = `index.php?page=booking_view&id=${bookingId}`;

        } catch(err) {
            showAlert('danger', err.message);
            btn.disabled = false; btn.textContent = oldText;
        }
    });
});