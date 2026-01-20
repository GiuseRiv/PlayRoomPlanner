'use strict';

// --- FUNZIONI HELPER GLOBALI ---
function setValue(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val || '';
}

function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val || '';
}

// --- CARICAMENTO PROFILO ---
async function loadProfile() {
    try {
        const userIdInput = document.getElementById('currentUserId');
        if (!userIdInput) return; // Sicurezza se l'input non c'è

        const userId = userIdInput.value;
        const res = await fetch(`api/users.php?id=${userId}`);
        const payload = await res.json();
        
        if (!res.ok || !payload.ok) throw new Error(payload.message || 'Errore');

        const user = payload.data;

        // --- POPOLAMENTO HEADER ---
        setText('displayNome', `${user.nome} ${user.cognome}`);
        const ruoloCap = user.ruolo ? user.ruolo.charAt(0).toUpperCase() + user.ruolo.slice(1) : '';
        setText('displayRuolo', ruoloCap);

        const imgEl = document.getElementById('profilePreview');
        if (imgEl) {
            const fotoPath = (user.foto && user.foto !== 'default.png') 
                             ? `uploads/${user.foto}` : 'images/default.png';
            imgEl.src = `${fotoPath}?t=${Date.now()}`;
        }

        // --- POPOLAMENTO CAMPI ---
        // 1. Matricola (ID) con zeri (es. 00024)
        const matInput = document.getElementById('fieldMatricola');
        if (matInput && user.id_iscritto) {
            matInput.value = String(user.id_iscritto).padStart(5, '0');
        }

        setValue('fieldRuolo', ruoloCap);
        setValue('fieldNome', user.nome);
        setValue('fieldCognome', user.cognome);
        setValue('fieldEmail', user.email);
        
        // 2. Data di nascita (formato YYYY-MM-DD per l'input date)
        const dateInput = document.getElementById('fieldDataNascita');
        if (dateInput && user.data_nascita) {
            dateInput.value = user.data_nascita;
        }

    } catch (err) {
        console.error(err);
        const alertBox = document.getElementById('alertBox');
        if (alertBox) alertBox.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
    }
}

// --- INIZIALIZZAZIONE AL CARICAMENTO DOM ---
document.addEventListener('DOMContentLoaded', () => {
    loadProfile();
    const userIdInput = document.getElementById('currentUserId');
    if (!userIdInput) return; 
    
    const userId = userIdInput.value;

    // --- FORM 1: ANAGRAFICA ---
    const formProfile = document.getElementById('profileForm');
    if (formProfile) {
        formProfile.addEventListener('submit', async (e) => {
            e.preventDefault();
            const dataToSend = {
                nome: document.getElementById('fieldNome').value,
                cognome: document.getElementById('fieldCognome').value
            };

            try {
                const res = await fetch(`api/users.php?id=${userId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dataToSend)
                });
                const result = await res.json();
                if (res.ok && result.ok) {
                    alert('Anagrafica aggiornata!');
                    loadProfile(); 
                } else {
                    alert('Errore: ' + (result.message || 'Sconosciuto'));
                }
            } catch (err) { alert("Errore connessione"); }
        });
    }

    // --- FORM 2: PASSWORD CON VALIDAZIONE LIVE ---
    const formPass = document.getElementById('passwordForm');
    const newPassInput = document.getElementById('newPass');
    const confPassInput = document.getElementById('confirmPass');
    const rulesBox = document.getElementById('passwordRules');

    // Funzione helper visiva
    function updateReq(id, isValid) {
        const el = document.querySelector(id);
        if (!el) return isValid;
        const icon = el.querySelector('i');
        
        if (isValid) {
            el.classList.add('valid');
            if(icon) {
                icon.classList.remove('bi-circle', 'bi-x-circle');
                icon.classList.add('bi-check-circle-fill');
            }
        } else {
            el.classList.remove('valid');
            if(icon) {
                icon.classList.remove('bi-check-circle-fill', 'bi-x-circle');
                icon.classList.add('bi-circle');
            }
        }
        return isValid;
    }

    if (formPass && newPassInput && confPassInput) {
        
        // Evento LIVE mentre scrivi
        function checkRules() {
            if (rulesBox) rulesBox.classList.remove('d-none');
            
            const val = newPassInput.value;
            const conf = confPassInput.value;

            // 1. Lunghezza
            updateReq('#req-length', val.length >= 8);
            
            // 2. Maiuscola
            updateReq('#req-upper', /[A-Z]/.test(val));

            // 3. Speciale
            updateReq('#req-special', /[!@#$%^&*(),.?":{}|<>]/.test(val));

            // 4. Coincidenza (solo se conferma non è vuota)
            if(conf.length > 0) {
                updateReq('#req-match', val === conf);
            } else {
                // Reset visivo se vuoto
                const el = document.querySelector('#req-match');
                if (el) {
                    el.classList.remove('valid');
                    const icon = el.querySelector('i');
                    if(icon) {
                        icon.classList.remove('bi-check-circle-fill');
                        icon.classList.add('bi-circle');
                    }
                }
            }
        }

        newPassInput.addEventListener('input', checkRules);
        newPassInput.addEventListener('focus', checkRules);
        confPassInput.addEventListener('input', checkRules);

        // SUBMIT
        formPass.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const oldP = document.getElementById('oldPass').value;
            const newP = newPassInput.value;
            const confP = confPassInput.value;

            // Validazione Finale JS
            const hasUpper = /[A-Z]/.test(newP);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(newP);

            if (newP.length < 8 || !hasUpper || !hasSpecial || newP !== confP) {
                alert("La password non rispetta tutti i requisiti di sicurezza indicati.");
                return;
            }

            const dataToSend = {
                old_password: oldP,
                new_password: newP
            };

            const btn = document.getElementById('btnSavePass');
            btn.disabled = true;
            btn.textContent = 'Aggiornamento...';

            try {
                const res = await fetch(`api/users.php?id=${userId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dataToSend)
                });
                const result = await res.json();

                if (res.ok && result.ok) {
                    alert('Password aggiornata con successo!');
                    formPass.reset(); 
                    if(rulesBox) rulesBox.classList.add('d-none');
                    // Reset icone
                    document.querySelectorAll('.req-item').forEach(el => {
                         el.classList.remove('valid');
                         const i = el.querySelector('i');
                         if(i) i.className = 'bi bi-circle';
                    });
                } else {
                    alert('Errore: ' + (result.message || 'Vecchia password errata'));
                }
            } catch (err) { 
                alert("Errore connessione"); 
            } finally {
                btn.disabled = false;
                btn.textContent = 'Aggiorna Password';
            }
        });
    }
}); // <--- QUESTA ERA LA PARTE MANCANTE!