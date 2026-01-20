'use strict';

const API_REPORTS = 'api/reports.php';

async function loadReports() {
  try {
    const res = await fetch(API_REPORTS);
    const json = await res.json();

    if (!res.ok || !json.ok) throw new Error(json.message || 'Errore caricamento dati');

    const data = json.data;
    
    // 1. Render Grafico Aule
    renderChart(data.rooms);

    // 2. Render Tabella Utenti
    renderUsersTable(data.users);

  } catch (err) {
    console.error(err);
    document.getElementById('alertBox').innerHTML = 
      `<div class="alert alert-danger">Impossibile caricare i report: ${err.message}</div>`;
  }
}

function renderChart(roomsData) {
  const ctx = document.getElementById('roomsChart');
  
  // Preparazione dati per Chart.js
  // Estraiamo i nomi delle aule (Etichette) e i totali (Numeri)
  const labels = roomsData.map(r => r.nome);
  const counts = roomsData.map(r => r.totale);

  new Chart(ctx, {
    type: 'bar', // Tipo di grafico: 'bar', 'pie', 'line', 'doughnut'
    data: {
      labels: labels,
      datasets: [{
        label: 'Numero Prenotazioni',
        data: counts,
        backgroundColor: 'rgba(54, 162, 235, 0.6)', // Colore azzurro semi-trasparente
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1,
        borderRadius: 5
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1 // Evita numeri decimali sull'asse Y
          }
        }
      },
      plugins: {
        legend: { display: false } // Nasconde la legenda inutile per un solo dataset
      }
    }
  });
}

function renderUsersTable(usersData) {
  const tbody = document.getElementById('usersTbody');
  
  if (usersData.length === 0) {
    tbody.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-muted">Nessuna prenotazione trovata.</td></tr>';
    return;
  }

  tbody.innerHTML = usersData.map((u, index) => {
    // Icona medaglia per i primi 3
    let icon = `<span class="badge bg-secondary rounded-pill">${index + 1}</span>`;
    if (index === 0) icon = '🥇';
    if (index === 1) icon = '🥈';
    if (index === 2) icon = '🥉';

    return `
      <tr>
        <td class="ps-4">
            <span class="me-2 fs-5">${icon}</span> 
            <strong>${u.nome} ${u.cognome}</strong>
        </td>
        <td class="text-end pe-4 fw-bold fs-5 text-primary">
            ${u.totale}
        </td>
      </tr>
    `;
  }).join('');
}

// Avvia
document.addEventListener('DOMContentLoaded', loadReports);