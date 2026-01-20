'use strict';

const API_SINGLE_USER = 'api/users.php';
const API_SECTORS = 'api/sectors.php';
const API_UPDATE = 'api/users_admin_update.php';

const params = new URLSearchParams(window.location.search);
const ID_UTENTE = params.get('id');

function showAlert(type, msg) {
  const box = document.getElementById('alertBox');
  if(box) box.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show">${msg}<button class="btn-close" data-bs-dismiss="alert"></button></div>`;
}

// LOGICA TECNICO: Usa la classe CSS personalizzata
function toggleSectorVisibility() {
    const ruoloSelect = document.getElementById('fieldRuolo');
    const boxSettori = document.getElementById('boxSettori');
    const msgTecnico = document.getElementById('msgTecnico');
    const fieldSettori = document.getElementById('fieldSettori');

    if (ruoloSelect.value === 'tecnico') {
        boxSettori.classList.add('opacity-50'); // Classe definita in app.css
        fieldSettori.disabled = true;
        msgTecnico.classList.remove('d-none');
    } else {
        boxSettori.classList.remove('opacity-50');
        fieldSettori.disabled = false;
        msgTecnico.classList.add('d-none');
    }
}

async function init() {
  if (!ID_UTENTE) {
    showAlert('danger', 'ID Utente mancante.');
    const form = document.querySelector('form'); if(form) form.style.display = 'none';
    return;
  }

  try {
    const [resUser, resSectors] = await Promise.all([
      fetch(`${API_SINGLE_USER}?id=${ID_UTENTE}`),
      fetch(API_SECTORS)
    ]);

    const userJson = await resUser.json();
    const sectorsJson = await resSectors.json();

    if (!userJson.ok) throw new Error(userJson.message);
    
    const user = userJson.data;
    const sectors = Array.isArray(sectorsJson.data) ? sectorsJson.data : [];

    // Popola Sinistra
    document.getElementById('userId').value = user.id_iscritto;
    document.getElementById('displayId').textContent = user.id_iscritto;
    document.getElementById('displayNomeCompleto').textContent = `${user.nome} ${user.cognome}`;
    document.getElementById('displayEmail').textContent = user.email;
    document.getElementById('displayDataNascita').textContent = user.data_nascita || 'Non impostata';
    
    // Placeholder SVG Base64 (non richiede file esterni)
    const placeholder = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiB2aWV3Qm94PSIwIDAgMjQgMjQiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2NjYyIgc3Ryb2tlLXdpZHRoPSIxIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiPjxjaXJjbGUgY3g9IjEyIiBjeT0iMTIiIHI9IjEwIj48L2NpcmNsZT48cGF0aCBkPSJNMjAgMjF2LTIgYS00IDQtNCAwLTAgMS04LTggdi0yIj48L3BhdGg+PGNpcmNsZSBjeD0iMTIiIGN5PSI3IiByPSI0Ij48L2NpcmNsZT48L3N2Zz4=';
    document.getElementById('displayFoto').src = user.foto ? user.foto : placeholder;

    const badge = document.getElementById('displayRuoloBadge');
    badge.innerHTML = `<span class="badge bg-${user.ruolo === 'tecnico' ? 'dark' : user.ruolo === 'docente' ? 'primary' : 'secondary'}">${user.ruolo.toUpperCase()}</span>`;

    // Popola Destra
    const roleSelect = document.getElementById('fieldRuolo');
    roleSelect.value = user.ruolo;

    const sectorSelect = document.getElementById('fieldSettori');
    sectorSelect.innerHTML = sectors.map(s => `<option value="${s.id_settore}">${s.nome}</option>`).join('');

    const userSectors = user.settori_ids || [];
    Array.from(sectorSelect.options).forEach(opt => {
        if (userSectors.some(id => id == opt.value)) opt.selected = true;
    });

    // Inizializza logica tecnico
    toggleSectorVisibility();
    roleSelect.addEventListener('change', toggleSectorVisibility);

  } catch (e) {
    console.error(e);
    showAlert('danger', 'Errore: ' + e.message);
  }
}

const editForm = document.getElementById('editForm');
if(editForm) {
  editForm.addEventListener('submit', async e => {
    e.preventDefault();
    const roleSelect = document.getElementById('fieldRuolo');
    const sectorSelect = document.getElementById('fieldSettori');
    
    // Se Tecnico -> Array vuoto di settori
    let settoriFinali = [];
    if (roleSelect.value !== 'tecnico') {
        settoriFinali = Array.from(sectorSelect.selectedOptions).map(o => o.value);
    }

    const payload = {
      id_iscritto: document.getElementById('userId').value,
      ruolo: roleSelect.value,
      settori: settoriFinali
    };

    try {
      const res = await fetch(API_UPDATE, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (!res.ok || !json.ok) throw new Error(json.message || 'Errore salvataggio');

      showAlert('success', 'Profilo aggiornato! Reindirizzamento...');
      setTimeout(() => { window.location.href = 'index.php?page=users_manage'; }, 1500);

    } catch (err) {
      showAlert('danger', err.message);
    }
  });
}

init();