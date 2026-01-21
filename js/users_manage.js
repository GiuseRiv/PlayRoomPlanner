'use strict';

// --- CONFIGURAZIONE API ---
// Puntiamo al file unificato che gestisce sia la lista che la cancellazione
const API_USERS_ADMIN = 'backend/users_admin.php'; 
const API_SECTORS = 'backend/sectors.php'; // Serve solo per popolare la tendina filtri

// Stato locale
let allUsers = [];
let currentSort = {
    key: 'id_iscritto', 
    order: 'asc'
};

// --- HELPER FUNCTIONS ---

// Escaping per prevenire XSS
function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
}

// Mostra alert in alto
function showAlert(type, msg) {
    const box = document.getElementById('alertBox');
    if(box) {
        box.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show">${msg}<button class="btn-close" data-bs-dismiss="alert"></button></div>`;
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

/**
 * Gestisce le chiamate API (GET, DELETE, etc.)
 * Gestisce parsing JSON ed errori HTTP in modo centralizzato
 */
async function apiRequest(method, url, body = null) {
    const options = { method: method };
    
    if (body) {
        options.headers = { 'Content-Type': 'application/json' };
        options.body = JSON.stringify(body);
    }

    const res = await fetch(url, options);
    
    // Tenta di leggere il JSON. Se fallisce (es. errore fatale PHP HTML), restituisce null
    const json = await res.json().catch(() => null);

    // Gestione Errori HTTP
    if (!res.ok) {
        if (json && json.message) {
            throw new Error(json.message);
        }
        throw new Error(`Errore HTTP ${res.status}`);
    }

    // Gestione Errori Logici (ok: false)
    if (json && json.ok === false) {
        throw new Error(json.message || 'Errore sconosciuto');
    }

    return json ? json.data : null;
}

// --- LOGICA ORDINAMENTO TABELLA ---

function applySortInternal() {
    const { key, order } = currentSort;
    allUsers.sort((a, b) => {
        let va = a[key] ?? '';
        let vb = b[key] ?? '';

        // Ordinamento numerico per ID
        if (key === 'id_iscritto') {
            va = Number(va);
            vb = Number(vb);
            return order === 'asc' ? va - vb : vb - va;
        }

        // Ordinamento stringa per gli altri campi
        va = String(va).toLowerCase();
        vb = String(vb).toLowerCase();
        if (va < vb) return order === 'asc' ? -1 : 1;
        if (va > vb) return order === 'asc' ? 1 : -1;
        return 0;
    });
}

// Esposta globalmente per l'onclick nell'HTML
window.sortTable = function(key) {
    if (currentSort.key === key) {
        currentSort.order = currentSort.order === 'asc' ? 'desc' : 'asc';
    } else {
        currentSort.key = key;
        currentSort.order = 'asc';
    }
    applySortInternal();
    drawTable();
    updateSortIcons();
};

function updateSortIcons() {
    const headers = document.querySelectorAll('th[onclick]');
    headers.forEach(th => {
        const icon = th.querySelector('i');
        if (icon) {
            icon.className = 'bi bi-arrow-down-up small text-muted ms-1';
            const match = th.getAttribute('onclick').match(/'([^']+)'/);
            if (match) {
                const thKey = match[1];
                if (currentSort.key === thKey) {
                    icon.className = currentSort.order === 'asc' 
                        ? 'bi bi-arrow-up-short small text-primary ms-1' 
                        : 'bi bi-arrow-down-short small text-primary ms-1';
                }
            }
        }
    });
}

// --- RENDERIZZAZIONE ---

function renderUser(user) {
    let badgeClass = 'bg-secondary'; 
    if (user.ruolo === 'docente') badgeClass = 'bg-primary';
    if (user.ruolo === 'tecnico') badgeClass = 'bg-dark';

    const fotoSrc = (user.foto && user.foto !== 'default.png') 
                    ? `uploads/${esc(user.foto)}` 
                    : 'images/default.png';

    // Recupera il ruolo dell'utente loggato dall'input hidden
    const myRoleInput = document.getElementById('myUserRole');
    const myRole = myRoleInput ? myRoleInput.value : '';

    let actionsHtml = '';
    
    // Mostra azioni solo se sono Tecnico
    if (myRole === 'tecnico') {
        actionsHtml = `
            <a href="index.php?page=users_edit&id=${user.id_iscritto}" class="btn btn-sm btn-outline-primary" title="Modifica">
                <i class="bi bi-pencil-square"></i>
            </a>
            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${user.id_iscritto}" title="Elimina">
                <i class="bi bi-trash"></i>
            </button>
        `;
    } else {
        actionsHtml = `<span class="text-muted opacity-50" title="Solo lettura"><i class="bi bi-lock-fill"></i></span>`;
    }

    return `
      <tr>
        <td>${user.id_iscritto}</td>
        <td>
          <img src="${fotoSrc}" width="32" height="32" class="rounded-circle object-fit-cover border" alt="foto">
        </td>
        <td class="fw-bold">${esc(user.nome)} ${esc(user.cognome)}</td>
        <td><span class="badge ${badgeClass}">${esc(user.ruolo).toUpperCase()}</span></td>
        <td>${esc(user.email)}</td>
        <td><small class="text-muted">${esc(user.settori || '-')}</small></td>
        <td class="text-end">
          ${actionsHtml}
        </td>
      </tr>
    `;
}

function drawTable() {
    const tbody = document.getElementById('usersTbody');
    if (!tbody) return;

    if (allUsers.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center p-3 text-muted">Nessun utente trovato.</td></tr>`;
    } else {
        tbody.innerHTML = allUsers.map(renderUser).join('');
    }
    updateSortIcons();
}

// --- CARICAMENTO DATI ---

async function loadUsers(params = '') {
    const tbody = document.getElementById('usersTbody');
    if(!tbody) return;

    try {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center p-3">Caricamento...</td></tr>`;
        
        allUsers = await apiRequest('GET', `${API_USERS_ADMIN}?${params}`);
        
        applySortInternal();
        drawTable();

    } catch (e) {
        let msg = e.message;
        if(msg.includes('403')) msg = "Non hai i permessi per vedere questa lista.";
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger p-3 fw-bold">${esc(msg)}</td></tr>`;
    }
}

function applyFilters() {
    const role = document.getElementById('roleFilter').value;
    const search = document.getElementById('searchInput').value;
    const sector = document.getElementById('sectorFilter').value;
    
    const params = new URLSearchParams({ role, search, sector });
    loadUsers(params.toString());
}

// --- INIT ---

document.addEventListener('DOMContentLoaded', async () => {
    
    // 1. Carica Settori per il filtro
    try {
         const s = await apiRequest('GET', API_SECTORS); 
         const sectorFilter = document.getElementById('sectorFilter');
         if(sectorFilter && Array.isArray(s)) {
             sectorFilter.innerHTML += s.map(x => `<option value="${x.id_settore}">${esc(x.nome)}</option>`).join('');
         }
    } catch(e) { console.warn("Filtro settori non caricato:", e); }
    
    // 2. Carica Lista Utenti
    loadUsers();

    // 3. Gestione Click Tabella (Event Delegation per Delete)
    const tbody = document.getElementById('usersTbody');
    if(tbody) {
        tbody.addEventListener('click', async e => {
            const delBtn = e.target.closest('.delete-btn');
            
            if (delBtn) {
                const userId = delBtn.dataset.id;
                
                // MESSAGGIO DI CONFERMA AGGIORNATO (Distruttivo)
                const msg = '⚠️ ATTENZIONE: STAI PER ELIMINARE UN UTENTE.\n\n' +
                            'Questa operazione cancellerà anche:\n' +
                            '• Tutte le prenotazioni organizzate dall\'utente\n' +
                            '• Tutti gli inviti inviati o ricevuti\n' +
                            '• Eventuali ruoli di responsabilità nei settori\n\n' +
                            'Sei sicuro di voler procedere?';

                if (confirm(msg)) {
                    try {
                        // Chiamata DELETE
                        await apiRequest('DELETE', API_USERS_ADMIN, { id: userId });
                        
                        showAlert('success', 'Utente e dati collegati eliminati con successo.');
                        applyFilters(); // Ricarica tabella
                    } catch (err) {
                        showAlert('danger', err.message);
                    }
                }
            }
        });
    }

    // 4. Listeners Filtri
    const roleFilter = document.getElementById('roleFilter');
    if(roleFilter) roleFilter.onchange = applyFilters;
    
    const searchInput = document.getElementById('searchInput');
    if(searchInput) searchInput.oninput = applyFilters;
    
    const sectorFilter = document.getElementById('sectorFilter');
    if(sectorFilter) sectorFilter.onchange = applyFilters;
    
    const btnRefresh = document.getElementById('btnRefresh');
    if(btnRefresh) btnRefresh.onclick = () => {
        document.getElementById('searchInput').value = '';
        document.getElementById('roleFilter').value = '';
        document.getElementById('sectorFilter').value = '';
        loadUsers();
    };
});