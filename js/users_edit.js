'use strict';

const userId = new URLSearchParams(window.location.search).get('id');
const API_USER = `backend/users_admin.php?id=${userId}`; 
const API_SECTORS_INFO = 'backend/users_admin.php?info_settori=1'; 
const API_SAVE = `backend/users_admin.php?id=${userId}`;

let allSectorsData = []; 
let currentUserId = parseInt(userId);

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const [userRes, sectorsRes] = await Promise.all([fetch(API_USER), fetch(API_SECTORS_INFO)]);
        
        const userJson = await userRes.json();
        const sectorsJson = await sectorsRes.json();

        if (!userJson.ok) throw new Error(userJson.message || "Errore dati utente");
        
        const user = userJson.data;
        allSectorsData = sectorsJson.data || [];

        
        document.getElementById('displayNomeCompleto').textContent = `${user.nome} ${user.cognome}`;
        
        const badge = document.getElementById('displayRuoloBadge');
        badge.className = `badge ${user.ruolo === 'docente' ? 'bg-primary' : (user.ruolo === 'tecnico' ? 'bg-dark' : 'bg-secondary')}`;
        badge.textContent = (user.ruolo || '').toUpperCase();

        
        const displayResp = document.getElementById('displayResponsabilita');
        if (user.is_responsabile && user.responsabile_id_settore) {
            
            const nomeSettore = allSectorsData.find(s => s.id_settore == user.responsabile_id_settore)?.nome || 'Sconosciuto';
            
            displayResp.innerHTML = `
                <div class="alert alert-warning py-1 px-2 mb-3 small fw-bold border-warning text-dark">
                    <i class="bi bi-star-fill text-warning"></i> Resp. ${nomeSettore}
                </div>
            `;
            displayResp.classList.remove('d-none');
        } else {
            displayResp.classList.add('d-none');
        }

        document.getElementById('displayEmail').textContent = user.email || '-';
        document.getElementById('displayDataNascita').textContent = user.data_nascita || '-';
        document.getElementById('displayId').textContent = '#' + String(user.id_iscritto).padStart(5,'0');

        const img = document.getElementById('displayFoto');
        img.src = (user.foto && user.foto !== 'default.png') ? `Images/${user.foto}` : 'Images/default.png';

        const displaySettori = document.getElementById('displaySettori');
        if (user.settori_nomi) {
            displaySettori.innerHTML = user.settori_nomi.split(', ').map(s => 
                `<span class="badge bg-light text-dark me-1 mb-1 border">${s}</span>`
            ).join('');
        } else {
            displaySettori.textContent = 'Nessun settore assegnato';
        }

        
        if (user.data_nomina) {
            const startYear = new Date(user.data_nomina).getFullYear();
            const currentYear = new Date().getFullYear();
            const years = currentYear - startYear;
            
            document.getElementById('displayAnniAttivita').textContent = (years === 0) ? "< 1 anno" : `${years} anni`;
            document.getElementById('displayDataIncarico').textContent = user.data_nomina; 
        } else {
            document.getElementById('displayAnniAttivita').textContent = '-';
            document.getElementById('displayDataIncarico').textContent = '-';
        }

        
        const roleSelect = document.getElementById('fieldRuolo');
        const sectorsSelect = document.getElementById('fieldSettori');
        const respSectorSelect = document.getElementById('selectSettoreResp');
        
        roleSelect.value = user.ruolo;
        sectorsSelect.innerHTML = '';
        respSectorSelect.innerHTML = '';

        allSectorsData.forEach(sec => {
            const opt = document.createElement('option');
            opt.value = sec.id_settore;
            opt.textContent = sec.nome;
            sectorsSelect.appendChild(opt);
            
            const optResp = document.createElement('option');
            optResp.value = sec.id_settore;
            optResp.textContent = sec.nome; 
            respSectorSelect.appendChild(optResp);
        });

        if (user.settori_ids) {
            const ids = String(user.settori_ids).split(',').map(s => s.trim());
            Array.from(sectorsSelect.options).forEach(opt => {
                if (ids.includes(opt.value)) opt.selected = true;
            });
        }

        
        const respCheckbox = document.getElementById('checkResponsabile');
        const respContainer = document.getElementById('containerResponsabile');
        const msgTecnico = document.getElementById('msgTecnico');
        const boxSettori = document.getElementById('boxSettori');

        if (user.is_responsabile) {
            respCheckbox.checked = true;
            respSectorSelect.value = user.responsabile_id_settore;
        }

        function updateUI() {
            const role = roleSelect.value;
            
            if (role === 'tecnico') {
                boxSettori.classList.add('disabled-area');
                Array.from(sectorsSelect.options).forEach(opt => opt.selected = true);
                respContainer.style.display = 'none';
                msgTecnico.classList.remove('d-none');
            } else if (role === 'docente') {
                boxSettori.classList.remove('disabled-area');
                respContainer.style.display = 'block';
                msgTecnico.classList.add('d-none');
            } else {
                boxSettori.classList.remove('disabled-area');
                respContainer.style.display = 'none';
                msgTecnico.classList.add('d-none');
            }

            document.getElementById('dettagliResponsabile').style.display = respCheckbox.checked ? 'block' : 'none';
        }

        roleSelect.addEventListener('change', updateUI);
        respCheckbox.addEventListener('change', updateUI);

        respSectorSelect.addEventListener('change', () => {
             const selectedId = parseInt(respSectorSelect.value);
             const sectorInfo = allSectorsData.find(s => s.id_settore == selectedId);
             const warnBox = document.getElementById('msgResponsabileOccupato');
             
             if (sectorInfo && sectorInfo.id_responsabile && parseInt(sectorInfo.id_responsabile) !== currentUserId) {
                 warnBox.innerHTML = `⚠️ Attuale responsabile: <b>${sectorInfo.nome_responsabile}</b>. Verrà sostituito.`;
                 warnBox.classList.remove('d-none');
             } else {
                 warnBox.classList.add('d-none');
             }
        });

        updateUI();

        
        document.getElementById('editForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const payload = {
                ruolo: roleSelect.value,
                settori: Array.from(sectorsSelect.selectedOptions).map(o => o.value),
                is_responsabile: respCheckbox.checked,
                id_settore_responsabilita: respSectorSelect.value
            };

            try {
                const res = await fetch(API_SAVE, {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if(!json.ok) throw new Error(json.message);
                
                alert("Salvataggio riuscito!");
                window.location.reload();
            } catch(err) {
                alert("Errore: " + err.message);
            }
        });

    } catch (err) {
        console.error(err);
        document.getElementById('alertBox').innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
    }
});