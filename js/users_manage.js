'use strict';

// URL API
const API_USERS_ADMIN = 'api/users_admin.php';
const API_SECTORS = 'api/sectors.php';
const API_SINGLE_USER = 'api/users.php';

let allUsers = [];
let currentSort = {
    key: 'id_iscritto', // Ordinamento default
    order: 'asc'        // 'asc' o 'desc'
};

// Funzione Helper per mostrare alert
function showAlert(type, msg) {
  const box = document.getElementById('alertBox');
  if(box) {
      box.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show">${msg}<button class="btn-close" data-bs-dismiss="alert"></button></div>`;
  }
}

// Funzione generica per chiamate API
async function apiRequest(method, url) {
  const res = await fetch(url, { method });
  if (!res.ok) throw new Error(`Errore HTTP ${res.status}`);
  const json = await res.json();
  if (!json.ok) throw new Error(json.message || 'Errore sconosciuto');
  return json.data;
}

// === FUNZIONI DI ORDINAMENTO (MANCANTI) ===
function applySortInternal() {
  const { key, order } = currentSort;
  allUsers.sort((a, b) => {
    let va = a[key] ?? '';
    let vb = b[key] ?? '';

    // Confronto numerico per ID
    if (key === 'id_iscritto') {
      va = Number(va);
      vb = Number(vb);
      return order === 'asc' ? va - vb : vb - va;
    }

    // Confronto stringhe per altri campi
    va = String(va).toLowerCase();
    vb = String(vb).toLowerCase();
    if (va < vb) return order === 'asc' ? -1 : 1;
    if (va > vb) return order === 'asc' ? 1 : -1;
    return 0;
  });
}

function sortTable(key) {
  if (currentSort.key === key) {
    currentSort.order = currentSort.order === 'asc' ? 'desc' : 'asc';
  } else {
    currentSort.key = key;
    currentSort.order = 'asc';
  }
  applySortInternal();
  drawTable();
}

function updateSortIcons() {
  // Aggiorna icone nelle intestazioni (opzionale ma bello)
  const headers = document.querySelectorAll('th[onclick]');
  headers.forEach(th => {
    const icon = th.querySelector('i');
    if (icon) {
      icon.className = 'bi bi-arrow-down-up small text-muted ms-1';
      const thKey = th.getAttribute('onclick').match(/'([^']+)'/)[1];
      if (currentSort.key === thKey) {
        icon.className = currentSort.order === 'asc' 
          ? 'bi bi-arrow-up-short small text-primary ms-1' 
          : 'bi bi-arrow-down-short small text-primary ms-1';
      }
    }
  });
}

// --- FUNZIONE CHE DISEGNA LA RIGA ---
function renderUser(user) {
  // 1. Badge colorato per il ruolo
  let badgeClass = 'bg-secondary'; // default allievo
  if (user.ruolo === 'docente') badgeClass = 'bg-primary';
  if (user.ruolo === 'tecnico') badgeClass = 'bg-dark';

  // 2. Percorso foto
  const fotoSrc = (user.foto && user.foto !== 'default.png') 
                  ? `uploads/${user.foto}` 
                  : 'images/default.png';

  // 3. RECUPERO RUOLO ATTUALE
  const myRoleInput = document.getElementById('myUserRole');
  const myRole = myRoleInput ? myRoleInput.value : '';

  // 4. DECISIONE AZIONI
  let actionsHtml = '';
  
  if (myRole === 'tecnico') {
      // TECNICO: Vede i bottoni Modifica ed Elimina
      actionsHtml = `
        <a href="index.php?page=users_edit&id=${user.id_iscritto}" class="btn btn-sm btn-outline-primary" title="Modifica">
            <i class="bi bi-pencil-square"></i>
        </a>
        <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${user.id_iscritto}" title="Elimina">
            <i class="bi bi-trash"></i>
        </button>
      `;
  } else {
      // DOCENTE / ALTRI: Niente bottoni inutili. 
      actionsHtml = `<span class="text-muted opacity-50" title="Non modificabile"><i class="bi bi-lock-fill"></i></span>`;
  }

  // 5. Ritorna HTML
  return `
    <tr>
      <td>${user.id_iscritto}</td>
      <td>
        <img src="${fotoSrc}" width="32" height="32" class="rounded-circle object-fit-cover border">
      </td>
      <td class="fw-bold">${user.nome} ${user.cognome}</td>
      <td><span class="badge ${badgeClass}">${user.ruolo.toUpperCase()}</span></td>
      <td>${user.email}</td>
      <td><small class="text-muted">${user.settori || '-'}</small></td>
      <td class="text-end">
        ${actionsHtml}
      </td>
    </tr>
  `;
}

// Caricamento Lista Utenti
async function loadUsers(params = '') {
  const tbody = document.getElementById('usersTbody');
  if(!tbody) return;

  try {
    // Spinner caricamento
    tbody.innerHTML = `<tr><td colspan="7" class="text-center p-3">Caricamento...</td></tr>`;
    
    // 1. Scarica i dati
    allUsers = await apiRequest('GET', `${API_USERS_ADMIN}?${params}`);
    
    // 2. Applica l'ordinamento corrente prima di disegnare
    applySortInternal();

    // 3. Disegna
    drawTable();

  } catch (e) {
    let msg = e.message;
    if(msg.includes('403')) msg = "Non hai i permessi per vedere questa lista.";
    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger p-3 fw-bold">${msg}</td></tr>`;
  }
}

// Funzione helper per disegnare la tabella
function drawTable() {
    const tbody = document.getElementById('usersTbody');
    if (allUsers.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center p-3 text-muted">Nessun utente trovato.</td></tr>`;
    } else {
        tbody.innerHTML = allUsers.map(renderUser).join('');
    }
    updateSortIcons();
}

// Gestione Filtri
function applyFilters() {
  const role = document.getElementById('roleFilter').value;
  const search = document.getElementById('searchInput').value;
  const sector = document.getElementById('sectorFilter').value;
  const params = new URLSearchParams({ role, search, sector });
  loadUsers(params.toString());
}

// AVVIO PAGINA
document.addEventListener('DOMContentLoaded', async () => {
  // 1. Carica Settori per la select
  try {
     const s = await apiRequest('GET', API_SECTORS);
     const sectorFilter = document.getElementById('sectorFilter');
     if(sectorFilter) {
         sectorFilter.innerHTML += s.map(x => `<option value="${x.id_settore}">${x.nome}</option>`).join('');
     }
  } catch(e) { console.error("Errore caricamento settori", e); }
  
  // 2. Carica Utenti
  loadUsers();

  // 3. Listener Tabella (per click su Elimina)
  const tbody = document.getElementById('usersTbody');
  if(tbody) {
    tbody.addEventListener('click', async e => {
      const delBtn = e.target.closest('.delete-btn');
      if (delBtn && confirm('Sei sicuro di voler eliminare definitivamente questo utente?')) {
         try {
           await apiRequest('DELETE', `${API_SINGLE_USER}?id=${delBtn.dataset.id}`);
           showAlert('success', 'Utente eliminato con successo');
           applyFilters(); // Ricarica lista
         } catch (err) {
           showAlert('danger', 'Impossibile eliminare: ' + err.message);
         }
      }
    });
  }

  // 4. Listener Filtri
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
