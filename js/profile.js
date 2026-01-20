'use strict';

/**
 * ============================================================================
 * PLAY ROOM PLANNER - GESTIONE PROFILO UTENTE
 * ============================================================================
 */

// --- FUNZIONI UTILITY DI BASE ---
function setValue(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val || '';
}

function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val || '';
}

// --- INIZIALIZZAZIONE ---
document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Verifica presenza ID Utente (Sicurezza base frontend)
    const userIdInput = document.getElementById('currentUserId');
    if (!userIdInput) return; 
    const userId = userIdInput.value;

    // 2. Avvio Moduli
    loadProfile(userId);        // Carica dati iniziali
    initProfileForm(userId);    // Gestione Anagrafica (Nome/Cognome)
    initPhotoUpload(userId);    // Gestione Foto
    initPasswordForm(userId);   // Gestione Password Sicura
});

/**
 * ----------------------------------------------------------------------------
 * 1. CARICAMENTO DATI PROFILO (GET)
 * Recupera i dati dal server e popola i campi, inclusa la logica "Responsabile".
 * ----------------------------------------------------------------------------
 */
async function loadProfile(userId) {
    try {
        const res = await fetch(`api/users.php?id=${userId}`);
        const payload = await res.json();
        
        if (!res.ok || !payload.ok) throw new Error(payload.message || 'Errore nel caricamento dati');

        const user = payload.data;

        // A. Header e Info Rapide
        setText('displayNome', `${user.nome} ${user.cognome}`);
        const ruoloCap = user.ruolo ? user.ruolo.charAt(0).toUpperCase() + user.ruolo.slice(1) : '';
        setText('displayRuolo', ruoloCap);

        // B. Immagine Profilo (con Cache Busting)
        const imgEl = document.getElementById('profilePreview');
        if (imgEl) {
            const fotoPath = (user.foto && user.foto !== 'default.png') 
                             ? `uploads/${user.foto}` : 'images/default.png';
            // Aggiungiamo timestamp per forzare il refresh dell'immagine
            imgEl.src = `${fotoPath}?t=${Date.now()}`;
        }

        // C. Campi Anagrafica Form
        const matInput = document.getElementById('fieldMatricola');
        if (matInput) matInput.value = String(user.id_iscritto).padStart(5, '0');

        setValue('fieldRuolo', ruoloCap);
        setValue('fieldNome', user.nome);
        setValue('fieldCognome', user.cognome);
        setValue('fieldEmail', user.email);
        
        const dateInput = document.getElementById('fieldDataNascita');
        if (dateInput && user.data_nascita) dateInput.value = user.data_nascita;

        // D. Sezione Responsabile di Settore (Logica Condizionale)
        const respSection = document.getElementById('respSection');
        
        if (user.nome_settore_resp) {
            if (respSection) respSection.classList.remove('d-none');
            setText('respSettoreNome', user.nome_settore_resp);
            
            // --- MODIFICA: CALCOLO DINAMICO ANNI ---
            // Invece di usare user.anni_servizio diretto, calcoliamo dalla data
            let testoAnni = '-';
            if (user.data_nomina) {
                const startYear = new Date(user.data_nomina).getFullYear();
                const currentYear = new Date().getFullYear();
                let diff = currentYear - startYear;
                
                if (diff < 0) diff = 0; // Protezione date future

                if (diff === 0) {
                    testoAnni = "< 1 anno";
                } else {
                    testoAnni = diff + (diff === 1 ? ' anno' : ' anni');
                }
            }
            
            setValue('respAnni', testoAnni);
            setValue('respData', user.data_nomina);
            // ---------------------------------------

        } else {
            // Nascondi se non è responsabile
            if (respSection) respSection.classList.add('d-none');
        }

    } catch (err) {
        console.error("Errore loadProfile:", err);
    }
}

/**
 * ----------------------------------------------------------------------------
 * 2. AGGIORNAMENTO ANAGRAFICA (PUT)
 * Modifica solo Nome e Cognome.
 * ----------------------------------------------------------------------------
 */
function initProfileForm(userId) {
    const form = document.getElementById('profileForm');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const dataToSend = {
            nome: document.getElementById('fieldNome').value,
            cognome: document.getElementById('fieldCognome').value
        };

        // Feedback UI
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvataggio...';

        try {
            const res = await fetch(`api/users.php?id=${userId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dataToSend)
            });
            const result = await res.json();

            if (res.ok && result.ok) {
                alert('Dati anagrafici aggiornati!');
                loadProfile(userId); // Ricarica per aggiornare l'header
            } else {
                alert('Errore: ' + (result.message || 'Sconosciuto'));
            }
        } catch (err) {
            alert("Errore di connessione al server.");
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
}

/**
 * ----------------------------------------------------------------------------
 * 3. UPLOAD FOTO (POST Multipart)
 * Caricamento immediato al cambio file (evento change).
 * ----------------------------------------------------------------------------
 */
function initPhotoUpload(userId) {
    const input = document.getElementById('uploadFoto');
    if (!input) return;

    input.addEventListener('change', async function() {
        if (this.files && this.files[0]) {
            const formData = new FormData();
            formData.append('foto', this.files[0]);
            
            // Preview opaca durante il caricamento
            const imgEl = document.getElementById('profilePreview');
            if(imgEl) imgEl.style.opacity = '0.5';

            try {
                const res = await fetch(`api/users.php?id=${userId}`, {
                    method: 'POST',
                    body: formData // FormData imposta automaticamente l'header corretto
                });
                const result = await res.json();
                
                if (result.ok) {
                    loadProfile(userId); // Ricarica l'immagine ufficiale
                    alert('Foto aggiornata con successo!');
                } else {
                    alert('Errore upload: ' + result.message);
                }
            } catch (err) {
                alert('Errore di connessione durante l\'upload.');
            } finally {
                if(imgEl) imgEl.style.opacity = '1';
                this.value = ''; // Reset input per permettere ricaricamento stesso file
            }
        }
    });
}

/**
 * ----------------------------------------------------------------------------
 * 4. CAMBIO PASSWORD SICURO (PUT)
 * Verifica vecchia password -> Controlla Requisiti -> Aggiorna
 * ----------------------------------------------------------------------------
 */
function initPasswordForm(userId) {
    const form = document.getElementById('passwordForm');
    const newPassInput = document.getElementById('newPass');
    const confPassInput = document.getElementById('confirmPass');
    const oldPassInput = document.getElementById('oldPass');
    const rulesBox = document.getElementById('passwordRules');

    // Controllo esistenza elementi
    if (!form || !newPassInput || !confPassInput || !oldPassInput) return;

    // --- A. Helper Visuale Requisiti ---
    function updateReq(selector, isValid) {
        const el = document.querySelector(selector);
        if (!el) return;
        
        const icon = el.querySelector('i');
        if (isValid) {
            el.classList.remove('text-muted');
            el.classList.add('text-success', 'fw-bold');
            if(icon) icon.className = 'bi bi-check-circle-fill me-2';
        } else {
            el.classList.remove('text-success', 'fw-bold');
            el.classList.add('text-muted');
            if(icon) icon.className = 'bi bi-circle me-2';
        }
    }

    // --- B. Validazione LIVE (mentre scrivi) ---
    function checkRules() {
        if (rulesBox) rulesBox.classList.remove('d-none');
        
        const val = newPassInput.value;
        const conf = confPassInput.value;

        updateReq('#req-length', val.length >= 8);
        updateReq('#req-upper', /[A-Z]/.test(val));
        updateReq('#req-special', /[!@#$%^&*(),.?":{}|<>]/.test(val));
        
        // Controllo coincidenza password
        if (conf.length > 0) {
            updateReq('#req-match', val === conf);
        } else {
            // Reset visivo se campo conferma vuoto
            const matchEl = document.querySelector('#req-match');
            if (matchEl) {
                matchEl.classList.remove('text-success', 'fw-bold');
                matchEl.classList.add('text-muted');
                matchEl.querySelector('i').className = 'bi bi-circle me-2';
            }
        }
    }

    // Event Listeners Input
    newPassInput.addEventListener('input', checkRules);
    newPassInput.addEventListener('focus', checkRules);
    confPassInput.addEventListener('input', checkRules);

    // --- C. Invio Modulo (SUBMIT) ---
    form.addEventListener('submit', async (e) => {
        e.preventDefault(); // Blocca ricaricamento pagina
        
        const oldP = oldPassInput.value;
        const newP = newPassInput.value;
        const confP = confPassInput.value;

        // 1. Validazione Client
        const hasUpper = /[A-Z]/.test(newP);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(newP);

        if (newP.length < 8 || !hasUpper || !hasSpecial || newP !== confP) {
            alert("Attenzione: Controlla che tutti i requisiti della password siano verdi.");
            return;
        }

        // 2. Feedback UI
        const btn = document.getElementById('btnSavePass');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Verifica in corso...';

        const dataToSend = {
            old_password: oldP,
            new_password: newP
        };

        try {
            // 3. Chiamata al Server (Verifica Hash DB e Aggiornamento)
            const res = await fetch(`api/users.php?id=${userId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dataToSend)
            });
            const result = await res.json();

            // 4. Gestione Risultato
            if (res.ok && result.ok) {
                alert('Password aggiornata con successo!');
                
                // Reset Form e UI
                form.reset(); 
                if(rulesBox) rulesBox.classList.add('d-none');
                document.querySelectorAll('.req-item').forEach(el => {
                     el.className = 'req-item text-muted';
                     const i = el.querySelector('i');
                     if(i) i.className = 'bi bi-circle me-2';
                });

            } else {
                // Errore dal server (es: Vecchia password errata)
                alert('Errore: ' + (result.message || 'La password attuale non è corretta.'));
            }
        } catch (err) { 
            console.error(err);
            alert("Errore critico di comunicazione col server."); 
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    });
}