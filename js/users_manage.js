'use strict';

// PERCORSI ROOT: Poiché il JS è caricato da index.php, vede le cartelle dalla radice
const API_USERS_ADMIN = 'api/users_admin.php';
const API_SECTORS = 'api/sectors.php';
const API_SINGLE_USER = 'api/users.php';

let allUsers = [];

function showAlert(type, msg) {
  const box = document.getElementById('alertBox');
  if(box) box.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show">${msg}<button class="btn-close" data-bs-dismiss="alert"></button></div>`;
}

async function apiRequest(method, url) {
  const res = await fetch(url, { method });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const json = await res.json();
  if (!json.ok) throw new Error(json.message || 'Errore');
  return json.data;
}

function renderUser(user) {
  let badgeClass = 'bg-secondary';
  if (user.ruolo === 'docente') badgeClass = 'bg-primary';
  if (user.ruolo === 'tecnico') badgeClass = 'bg-dark';

  return `
    <tr>
      <td>${user.id_iscritto}</td>
      <td>
        ${user.foto ? `<img src="${user.foto}" width="32" height="32" class="rounded-circle object-fit-cover">` : '<span class="text-muted">-</span>'}
      </td>
      <td class="fw-bold">${user.nome} ${user.cognome}</td>
      <td><span class="badge ${badgeClass}">${user.ruolo}</span></td>
      <td>${user.email}</td>
      <td><small>${user.settori || '-'}</small></td>
      <td class="text-end">
        <a href="index.php?page=users_edit&id=${user.id_iscritto}" class="btn btn-sm btn-outline-primary">Modifica</a>
        <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${user.id_iscritto}">Elimina</button>
      </td>
    </tr>
  `;
}

async function loadUsers(params = '') {
  const tbody = document.getElementById('usersTbody');
  try {
    allUsers = await apiRequest('GET', `${API_USERS_ADMIN}?${params}`);
    tbody.innerHTML = allUsers.map(renderUser).join('');
  } catch (e) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Errore: ${e.message}</td></tr>`;
  }
}

function applyFilters() {
  const role = document.getElementById('roleFilter').value;
  const search = document.getElementById('searchInput').value;
  const sector = document.getElementById('sectorFilter').value;
  const params = new URLSearchParams({ role, search, sector });
  loadUsers(params.toString());
}

document.addEventListener('DOMContentLoaded', async () => {
  try {
     const s = await apiRequest('GET', API_SECTORS);
     const sectorFilter = document.getElementById('sectorFilter');
     if(sectorFilter) sectorFilter.innerHTML += s.map(x => `<option value="${x.id_settore}">${x.nome}</option>`).join('');
  } catch(e){}
  
  loadUsers();

  const tbody = document.getElementById('usersTbody');
  if(tbody) {
    tbody.addEventListener('click', async e => {
      const delBtn = e.target.closest('.delete-btn');
      if (delBtn && confirm('Sei sicuro di voler eliminare questo utente?')) {
         try {
           await apiRequest('DELETE', `${API_SINGLE_USER}?id=${delBtn.dataset.id}`);
           showAlert('success', 'Utente eliminato');
           loadUsers();
         } catch (err) {
           showAlert('danger', err.message);
         }
      }
    });
  }

  const roleFilter = document.getElementById('roleFilter');
  if(roleFilter) roleFilter.onchange = applyFilters;
  
  const searchInput = document.getElementById('searchInput');
  if(searchInput) searchInput.oninput = applyFilters;
  
  const sectorFilter = document.getElementById('sectorFilter');
  if(sectorFilter) sectorFilter.onchange = applyFilters;
  
  const btnRefresh = document.getElementById('btnRefresh');
  if(btnRefresh) btnRefresh.onclick = () => loadUsers();
});