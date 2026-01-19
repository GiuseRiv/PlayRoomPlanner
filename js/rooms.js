(function () {
  const pageNode = document.getElementById('roomsPage');
  const ROOMS_API  = pageNode?.dataset?.roomListApi   || 'api/room_list.php';
  const WEEK_API   = pageNode?.dataset?.roomWeekApi   || 'api/room_week.php';
  const DETAIL_API = pageNode?.dataset?.roomDetailApi || 'api/room_detail.php';

  const roomSelect = document.getElementById('roomSelect');
  const dayInput   = document.getElementById('day');
  const form       = document.getElementById('roomsWeekForm');

  const alertBox   = document.getElementById('roomsAlert');
  const tbody      = document.getElementById('bookingsTbody');

  const detailBox  = document.getElementById('roomDetail');
  const equipUl    = document.getElementById('roomEquip');

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
    detailBox.innerHTML = `<span class="text-muted">Seleziona una sala.</span>`;
    equipUl.innerHTML = '';
  }

  async function loadRoomsIntoSelect() {
    clearError();
    roomSelect.innerHTML = `<option value="">Caricamento sale…</option>`;

    try {
      const res = await fetch(ROOMS_API, { credentials: 'same-origin' });
      const json = await res.json();

      if (!res.ok || !json.ok) throw new Error(json.message || 'Errore nel caricamento delle sale');

      const rooms = (json.data && json.data.rooms) ? json.data.rooms : [];
      if (rooms.length === 0) {
        roomSelect.innerHTML = `<option value="">Nessuna sala trovata</option>`;
        setRoomDetailEmpty();
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

      setRoomDetailEmpty();

    } catch (e) {
      roomSelect.innerHTML = `<option value="">Errore</option>`;
      setRoomDetailEmpty();
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

    detailBox.innerHTML = `<span class="text-muted">Caricamento dettagli…</span>`;
    equipUl.innerHTML = '';

    try {
      const url = `${DETAIL_API}?id_sala=${encodeURIComponent(idSala)}`;
      const res = await fetch(url, { credentials: 'same-origin' });
      const json = await res.json();

      if (!res.ok || !json.ok) throw new Error(json.message || 'Errore nel caricamento dettagli sala');

      const room = json.data?.room;
      const eqs  = json.data?.equipments || [];

      const nomeSala = escapeHtml(room?.nome_sala);
      const settore  = escapeHtml(room?.nome_settore);
      const tipo     = room?.tipo ? ` (${escapeHtml(room.tipo)})` : '';
      const cap      = (room?.capienza != null) ? escapeHtml(room.capienza) : '-';

      detailBox.innerHTML = `
        <div><strong>${nomeSala}</strong></div>
        <div>Settore: ${settore}${tipo}</div>
        <div>Capienza: ${cap}</div>
      `;

      if (!eqs.length) {
        equipUl.innerHTML = `<li class="text-muted">Nessuna dotazione registrata.</li>`;
      } else {
        equipUl.innerHTML = eqs.map(e => `<li>${escapeHtml(e.nome)}</li>`).join('');
      }

    } catch (e) {
      detailBox.innerHTML = `<span class="text-muted">Impossibile caricare i dettagli.</span>`;
      equipUl.innerHTML = '';
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

    tbody.innerHTML = `<tr><td colspan="4" class="text-muted">Caricamento…</td></tr>`;

    try {
      const url = `${WEEK_API}?id_sala=${encodeURIComponent(idSala)}&day=${encodeURIComponent(day)}`;
      const res = await fetch(url, { credentials: 'same-origin' });
      const json = await res.json();

      if (!res.ok || !json.ok) throw new Error(json.message || 'Errore nel caricamento delle prenotazioni');

      const bookings = (json.data && json.data.bookings) ? json.data.bookings : [];
      if (bookings.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-muted">Nessuna prenotazione in questa settimana.</td></tr>`;
        return;
      }

      tbody.innerHTML = bookings.map(b => {
        const when = escapeHtml(
          b.when ??
          ((b.data || '') + (b.ora_inizio != null ? (' ' + String(b.ora_inizio).padStart(2, '0') + ':00') : ''))
        );

        const att = escapeHtml(b.attivita ?? '-');
        const org = escapeHtml(b.organizzatore ?? '-');

        const dur = escapeHtml(
          (b.durata_ore != null) ? `${b.durata_ore}h` : '-'
        );

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

  // default: oggi
  dayInput.valueAsDate = new Date();

  form.addEventListener('submit', (ev) => {
    ev.preventDefault();
    loadWeekBookings();
  });

  // quando cambio sala: aggiorno dettaglio/dotazioni
  roomSelect.addEventListener('change', () => {
    loadRoomDetail();
    // opzionale: se vuoi auto-refresh della tabella quando cambi sala
    // if (dayInput.value) loadWeekBookings();
  });

  // boot
  loadRoomsIntoSelect();
})();
