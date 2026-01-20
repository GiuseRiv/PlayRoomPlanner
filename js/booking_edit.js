'use strict';

document.addEventListener('DOMContentLoaded', async () => {
    
    const bookingIdInput = document.getElementById('bookingId');
    const alertBox = document.getElementById('alertBox');
    const form = document.getElementById('editForm');
    const salaSelect = document.getElementById('fieldSala');

    
    if (!bookingIdInput || !bookingIdInput.value || bookingIdInput.value === '0') {
        showAlert('danger', 'ID prenotazione mancante.');
        return;
    }
    const bookingId = bookingIdInput.value;

    
    function showAlert(type, msg) {
        if(alertBox) {
            alertBox.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show shadow-sm">
                    ${msg}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
        } else {
            alert(msg);
        }
    }

    try {
        const res = await fetch(`backend/booking_edit.php?id=${bookingId}`);
        const json = await res.json();
        
        if(!res.ok || !json.ok) {
            throw new Error(json.message || 'Errore durante il caricamento dei dati');
        }

        const b = json.data.booking;
        const sale = json.data.sale;

        
        salaSelect.innerHTML = sale.map(s => 
            `<option value="${s.id_sala}">${s.nome} (Cap: ${s.capienza})</option>`
        ).join('');

        
        document.getElementById('fieldAttivita').value = b.attivita;
        document.getElementById('fieldData').value = b.data;
        document.getElementById('fieldOra').value = b.ora_inizio;
        document.getElementById('fieldDurata').value = b.durata_ore;
        salaSelect.value = b.id_sala;

    } catch(err) {
        console.error(err);
        showAlert('danger', err.message);
        
        const btn = form.querySelector('button[type="submit"]');
        if(btn) btn.disabled = true;
    }

   
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        
        const btn = form.querySelector('button[type="submit"]');
        const oldText = btn.textContent;
        btn.disabled = true; 
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvataggio...';
        alertBox.innerHTML = ''; 

        
        const payload = {
            id_prenotazione: bookingId,
            attivita: document.getElementById('fieldAttivita').value,
            data: document.getElementById('fieldData').value,
            id_sala: document.getElementById('fieldSala').value,
            ora_inizio: document.getElementById('fieldOra').value,
            durata_ore: document.getElementById('fieldDurata').value
        };

        try {
            const res = await fetch('backend/booking_edit.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify(payload)
            });
            const json = await res.json();

            if(!json.ok) throw new Error(json.message);

           
            alert('Prenotazione modificata con successo!');
            
            window.location.href = `index.php?page=booking_view&id=${bookingId}`;

        } catch(err) {
            showAlert('danger', 'Impossibile salvare le modifiche: ' + err.message);
            btn.disabled = false; 
            btn.textContent = oldText;
        }
    });
});