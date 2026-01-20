(function () {
  'use strict';

  
  const pageNode = document.getElementById('roomsPage');
  if (!pageNode) return;

  const ROOMS_API  = pageNode.dataset.roomListApi   || 'backend/room_list.php';
  const WEEK_API   = pageNode.dataset.roomWeekApi   || 'backend/room_week.php';
  const DETAIL_API = pageNode.dataset.roomDetailApi || 'backend/room_detail.php';

  const roomSelect = document.getElementById('roomSelect');
  const dayInput   = document.getElementById('day');
  const form       = document.getElementById('roomsWeekForm');

  const alertBox   = document.getElementById('roomsAlert');
  const tbody      = document.getElementById('bookingsTbody');

  
  const infoSalaNome      = document.getElementById('infoSalaNome');
  const infoSalaSettore   = document.getElementById('infoSalaSettore');
  const infoSalaCapienza  = document.getElementById('infoSalaCapienza');
  const infoSalaDotazioni = document.getElementById('infoSalaDotazioni');

  if (
    !roomSelect || !dayInput || !form || !alertBox || !tbody ||
    !infoSalaNome || !infoSalaSettore || !infoSalaCapienza || !infoSalaDotazioni
  ) {
    
    return;
  }

  
  function showError(msg) {
    alertBox.textContent = msg || 'Errore imprevisto';
    alertBox.classList.remove('d-none');
  }

  function clearError() {
    alertBox.classList.add('d-none');
    alertBox.textContent = '';
  }

  function escapeHtml(s) {
    return String(s ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function setRoomDetailEmpty() {
    infoSalaNome.textContent = '-';
    infoSalaSettore.textContent = '-';
    infoSalaCapienza.textContent = '-';
    infoSalaDotazioni.textContent = '-';

    infoSalaNome.classList.add('text-muted');
    infoSalaSettore.classList.add('text-muted');
    infoSalaCapienza.classList.add('text-muted');
    infoSalaDotazioni.classList.add('text-muted');
  }

  function setRoomDetailLoading() {
    infoSalaNome.textContent = 'Caricamento…';
    infoSalaSettore.textContent = 'Caricamento…';
    infoSalaCapienza.textContent = 'Caricamento…';
    infoSalaDotazioni.textContent = 'Caricamento…';

    infoSalaNome.classList.add('text-muted');
    infoSalaSettore.classList.add('text-muted');
    infoSalaCapienza.classList.add('text-muted');
    infoSalaDotazioni.classList.add('text-muted');
  }

  function setBookingsEmpty(msg) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-muted">${escapeHtml(msg)}</td></tr>`;
  }

  
  async function fetchJson(url) {
    const res = await fetch(url, { credentials: 'same-origin' });

    const contentType = (res.headers.get('content-type') || '').toLowerCase();
    if (!contentType.includes('application/json')) {
      const text = await res.text();
      const preview = text.slice(0, 180).replace(/\s+/g, ' ').trim();
      throw new Error(`Risposta non JSON (HTTP ${res.status}). Anteprima: ${preview}`);
    }

    const json = await res.json();
    if (!res.ok || !json || json.ok !== true) {
      throw new Error(json?.message || `Errore richiesta (HTTP ${res.status})`);
    }
    return json;
  }

  
  async function loadRoomsIntoSelect() {
    clearError();
    roomSelect.innerHTML = `<option value="">Caricamento sale…</option>`;
    setRoomDetailEmpty();
    setBookingsEmpty('Seleziona una sala e una settimana.');

    try {
      const json = await fetchJson(ROOMS_API);

      const rooms = json?.data?.rooms ?? [];
      if (!Array.isArray(rooms) || rooms.length === 0) {
        roomSelect.innerHTML = `<option value="">Nessuna sala trovata</option>`;
        return;
      }

      roomSelect.innerHTML =
        `<option value="">Seleziona una sala…</option>` +
        rooms.map(r => {
          const id = escapeHtml(r.id_sala);
          const name = escapeHtml(r.nome_sala);
          const settore = escapeHtml(r.nome_settore || '');
          const tipo = r.tipo ? ` (${escapeHtml(r.tipo)})` : '';
          const cap = (r.capienza != null) ? ` - capienza ${escapeHtml(r.capienza)}` : '';
          return `<option value="${id}">${name} — ${settore}${tipo}${cap}</option>`;
        }).join('');

    } catch (e) {
      roomSelect.innerHTML = `<option value="">Errore</option>`;
      showError(e.message);
    }
  }

  
  async function loadRoomDetail() {
    clearError();

    const idSala = roomSelect.value;
    if (!idSala) {
      setRoomDetailEmpty();
      return;
    }

    setRoomDetailLoading();

    try {
      const url = `${DETAIL_API}?id_sala=${encodeURIComponent(idSala)}`;
      const json = await fetchJson(url);

      const room = json?.data?.room ?? null;
      const eqs  = json?.data?.equipments ?? [];

      const nomeSala = room?.nome_sala ?? '-';
      const settore = (room?.nome_settore ?? '-') + (room?.tipo ? ` (${room.tipo})` : '');
      const cap = (room?.capienza != null) ? String(room.capienza) : '-';
      const dotazioni = (Array.isArray(eqs) && eqs.length) ? eqs.map(e => e.nome).join(', ') : 'Nessuna';

      infoSalaNome.textContent = nomeSala;
      infoSalaSettore.textContent = settore;
      infoSalaCapienza.textContent = cap;
      infoSalaDotazioni.textContent = dotazioni;

      [infoSalaNome, infoSalaSettore, infoSalaCapienza, infoSalaDotazioni].forEach(n => {
        n.classList.remove('text-muted');
      });

    } catch (e) {
      setRoomDetailEmpty();
      showError(e.message);
    }
  }

  
  async function loadWeekBookings() {
    clearError();

    const idSala = roomSelect.value;
    const day = dayInput.value;

    if (!idSala) {
      showError('Seleziona una sala.');
      return;
    }
    if (!day) {
      showError('Seleziona un giorno.');
      return;
    }

    setBookingsEmpty('Caricamento…');

    try {
      const url = `${WEEK_API}?id_sala=${encodeURIComponent(idSala)}&day=${encodeURIComponent(day)}`;
      const json = await fetchJson(url);

      const bookings = json?.data?.bookings ?? [];
      if (!Array.isArray(bookings) || bookings.length === 0) {
        setBookingsEmpty('Nessuna prenotazione in questa settimana.');
        return;
      }

      tbody.innerHTML = bookings.map(b => {
        const when = escapeHtml(
          b.when ??
          (b.data
            ? (b.data + (b.ora_inizio != null ? (' ' + String(b.ora_inizio).padStart(2, '0') + ':00') : ''))
            : '-')
        );

        const att = escapeHtml(b.attivita ?? '-');
        const org = escapeHtml(b.organizzatore ?? '-');
        const dur = escapeHtml((b.durata_ore != null) ? `${b.durata_ore}h` : '-');

        return `
          <tr>
            <td>${when}</td>
            <td>${att}</td>
            <td>${org}</td>
            <td class="text-end">${dur}</td>
          </tr>
        `;
      }).join('');

    } catch (e) {
      tbody.innerHTML = '';
      showError(e.message);
    }
  }

  
  dayInput.valueAsDate = new Date();
  setRoomDetailEmpty();

  form.addEventListener('submit', (ev) => {
    ev.preventDefault();
    loadWeekBookings();
  });

  roomSelect.addEventListener('change', () => {
    loadRoomDetail();
    });
  loadRoomsIntoSelect();
})();
