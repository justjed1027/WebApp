// Sidebar collapse on double-click
document.addEventListener('dblclick', function() {
  const sidebar = document.getElementById('sidebar');
  if (sidebar) {
    sidebar.classList.toggle('collapsed');
  }
});

// Prevent text selection on double-click
document.addEventListener('mousedown', function(e) {
  if (e.detail > 1) {
    e.preventDefault();
  }
});

document.addEventListener('DOMContentLoaded', () => {
  // ===== THEME TOGGLE =====
  const themeToggle = document.getElementById('themeToggle');
  const body = document.body;
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'light') body.classList.add('light-mode');
  themeToggle.addEventListener('click', () => {
    body.classList.toggle('light-mode');
    localStorage.setItem('theme', body.classList.contains('light-mode') ? 'light' : 'dark');
  });

  // ===== CALENDAR STATE =====
  const state = {
    view: 'month', // 'year' | 'month' | 'day'
    currentDate: new Date(), // drives month/year
    selectedDate: new Date() // used for day view
  };

  // ===== DOM ELEMENTS =====
  const monthLabel = document.getElementById('monthLabel');
  const yearLabel = document.getElementById('yearLabel');
  const monthGrid = document.getElementById('monthGrid');
  const yearView = document.getElementById('yearView');
  const monthView = document.getElementById('monthView');
  const dayView = document.getElementById('dayView');
  const dayFullLabel = document.getElementById('dayFullLabel');
  const dayTimeline = document.getElementById('dayTimeline');
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');
  const todayBtn = document.getElementById('todayBtn');
  const calendarCard = document.getElementById('calendarViews');

  // ===== SAMPLE EVENTS DATA (stub) =====
  const events = [
    { title: 'Data Structures Final Exam', type: 'exam', date: '2025-10-14', start: '10:00', end: '12:00' },
    { title: 'Group Project Meeting', type: 'meeting', date: '2025-10-11', start: '15:00', end: '16:30' },
    { title: 'Research Paper Deadline', type: 'assignment', date: '2025-10-19', start: '00:00', end: '23:59' },
    { title: 'Web Development Workshop', type: 'workshop', date: '2025-10-17', start: '14:00', end: '17:00' }
  ];

  // Utility: format YYYY-MM-DD
  function fmt(date) { return date.toISOString().split('T')[0]; }

  // ===== RENDER YEAR VIEW (Full Mini Calendars) =====
  function renderYear() {
    const year = state.currentDate.getFullYear();
    yearView.hidden = false; monthView.hidden = true; dayView.hidden = true;
    monthLabel.textContent = ''; yearLabel.textContent = year;
    calendarCard.classList.add('year-mode');
    const yearGrid = document.getElementById('yearGrid');
    yearGrid.innerHTML = '';
    for (let m = 0; m < 12; m++) {
      const monthDate = new Date(year, m, 1);
      const div = document.createElement('div');
      div.className = 'year-month';
      // Month header with event count badge
      const nameSpan = document.createElement('span');
      nameSpan.className = 'year-month-name';
      const monthName = monthDate.toLocaleString('default', { month: 'long' });
      nameSpan.textContent = monthName;
      const monthEvents = events.filter(ev => {
        const evDate = new Date(ev.date);
        return evDate.getMonth() === m && evDate.getFullYear() === year;
      });
      if (monthEvents.length) {
        const badge = document.createElement('span');
        badge.className = 'count-badge';
        badge.textContent = monthEvents.length;
        nameSpan.appendChild(badge);
      }
      div.appendChild(nameSpan);

      // Weekday headers (S M T W T F S)
      const weekdays = document.createElement('div');
      weekdays.className = 'mini-weekdays';
      ['S','M','T','W','T','F','S'].forEach(w => {
        const wSpan = document.createElement('span');
        wSpan.textContent = w;
        weekdays.appendChild(wSpan);
      });
      div.appendChild(weekdays);

      // Mini calendar grid (6 weeks x 7 days)
      const mini = document.createElement('div');
      mini.className = 'mini-grid';
      const startWeekday = new Date(year, m, 1).getDay();
      const daysInMonth = new Date(year, m + 1, 0).getDate();
      const prevMonthDays = new Date(year, m, 0).getDate();
      const totalCells = 42;
      let dayNum = 1;
      let nextMonthDay = 1;
      for (let i = 0; i < totalCells; i++) {
        const span = document.createElement('span');
        span.className = 'mini-day';
        let displayNum;
        if (i < startWeekday) { // previous month spill-in
          displayNum = prevMonthDays - startWeekday + 1 + i;
          span.classList.add('other');
        } else if (dayNum <= daysInMonth) { // current month
          displayNum = dayNum;
          const cellDate = new Date(year, m, dayNum);
          if (fmt(cellDate) === fmt(new Date())) span.classList.add('today');
          if (monthEvents.some(ev => ev.date === fmt(cellDate))) span.classList.add('event');
          dayNum++;
        } else { // next month spill-out
          displayNum = nextMonthDay++;
          span.classList.add('other');
        }
        span.textContent = displayNum;
        mini.appendChild(span);
      }
      div.appendChild(mini);
      if (m === new Date().getMonth() && year === new Date().getFullYear()) {
        div.classList.add('current-month');
      }
      div.setAttribute('role','button');
      div.setAttribute('tabindex','0');
      div.setAttribute('aria-label', `View ${monthName} ${year}`);
      const activateMonth = () => {
        state.currentDate = new Date(year, m, 1);
        state.view = 'month';
        renderMonth();
      };
      div.addEventListener('click', activateMonth);
      div.addEventListener('keydown', (e)=>{ if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activateMonth(); }});
      yearGrid.appendChild(div);
    }
  }

  // ===== RENDER MONTH VIEW =====
  function renderMonth() {
    const year = state.currentDate.getFullYear();
    const month = state.currentDate.getMonth();
    yearView.hidden = true; monthView.hidden = false; dayView.hidden = true;
    calendarCard.classList.remove('year-mode');
    monthLabel.textContent = state.currentDate.toLocaleString('default', { month: 'long' });
    yearLabel.textContent = year;
    monthGrid.innerHTML = '';
    const firstDay = new Date(year, month, 1);
    const startWeekday = firstDay.getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const prevMonthDays = new Date(year, month, 0).getDate();
    let row = document.createElement('tr');
    for (let i = 0; i < startWeekday; i++) {
      const td = document.createElement('td');
      td.className = 'day-cell other-month';
      td.textContent = prevMonthDays - startWeekday + 1 + i;
      row.appendChild(td);
    }
    for (let d = 1; d <= daysInMonth; d++) {
      if (row.children.length === 7) { monthGrid.appendChild(row); row = document.createElement('tr'); }
      const td = document.createElement('td');
      td.className = 'day-cell';
      const span = document.createElement('span');
      span.className = 'day-number';
      span.textContent = d;
      const cellDate = new Date(year, month, d);
      if (fmt(cellDate) === fmt(new Date())) td.classList.add('today');
      if (events.some(ev => ev.date === fmt(cellDate))) {
        const dot = document.createElement('div');
        dot.className = 'indicator-dot';
        td.appendChild(dot);
      }
      td.appendChild(span);
      td.setAttribute('role','button');
      td.setAttribute('tabindex','0');
      td.setAttribute('aria-label', cellDate.toDateString());
      const activateDay = () => {
        state.selectedDate = cellDate;
        state.view = 'day';
        renderDay();
      };
      td.addEventListener('click', activateDay);
      td.addEventListener('keydown', (e)=>{ if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activateDay(); }});
      row.appendChild(td);
    }
    let nextDay = 1;
    while (row.children.length < 7) {
      const td = document.createElement('td');
      td.className = 'day-cell other-month';
      td.textContent = nextDay++;
      row.appendChild(td);
    }
    monthGrid.appendChild(row);
  }

  // ===== RENDER DAY VIEW =====
  function renderDay() {
    yearView.hidden = true; monthView.hidden = true; dayView.hidden = false;
    calendarCard.classList.remove('year-mode');
    const d = state.selectedDate;
    dayFullLabel.textContent = d.toLocaleString('default', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    monthLabel.textContent = d.toLocaleString('default', { month: 'long' });
    yearLabel.textContent = d.getFullYear();
    dayTimeline.innerHTML = '';
    for (let h = 0; h < 24; h++) {
      const slot = document.createElement('div');
      slot.className = 'time-slot';
      const time = document.createElement('div');
      time.className = 'slot-time';
      time.textContent = (h < 10 ? '0' + h : h) + ':00';
      slot.appendChild(time);
      events.filter(ev => ev.date === fmt(d)).forEach(ev => {
        const startHour = parseInt(ev.start.split(':')[0], 10);
        if (startHour === h) {
          const block = document.createElement('div');
          block.className = 'event-block ' + ev.type;
          block.innerHTML = `<strong>${ev.title}</strong><br>${ev.start} - ${ev.end}<span class="event-duration">${ev.type}</span>`;
          block.tabIndex = 0;
          const showTooltip = () => {
            let tip = block.querySelector('.event-tooltip');
            if (!tip) {
              tip = document.createElement('div');
              tip.className = 'event-tooltip';
              tip.innerHTML = `
                <h4>${ev.title}</h4>
                <div class='event-meta'>${d.toDateString()}<br>${ev.start} - ${ev.end}</div>
                <span class='event-type-badge ${ev.type}'>${ev.type.toUpperCase()}</span>
              `;
              block.appendChild(tip);
              const rect = block.getBoundingClientRect();
              if (rect.right + 200 > window.innerWidth) {
                tip.style.left = 'auto';
                tip.style.right = '100%';
                tip.style.marginLeft = '0';
                tip.style.marginRight = '10px';
              }
            }
            requestAnimationFrame(()=> tip.classList.add('visible'));
          };
          const hideTooltip = () => {
            const tip = block.querySelector('.event-tooltip');
            if (tip) tip.classList.remove('visible');
          };
          block.addEventListener('mouseenter', showTooltip);
          block.addEventListener('mouseleave', hideTooltip);
          block.addEventListener('focus', showTooltip);
          block.addEventListener('blur', hideTooltip);
          slot.appendChild(block);
        }
      });
      dayTimeline.appendChild(slot);
    }
  }

  // ===== NAVIGATION HANDLERS =====
  prevBtn.addEventListener('click', () => {
    if (state.view === 'year') {
      state.currentDate = new Date(state.currentDate.getFullYear() - 1, 0, 1);
      renderYear();
    } else if (state.view === 'month') {
      state.currentDate = new Date(state.currentDate.getFullYear(), state.currentDate.getMonth() - 1, 1);
      renderMonth();
    } else if (state.view === 'day') {
      state.selectedDate = new Date(state.selectedDate.getFullYear(), state.selectedDate.getMonth(), state.selectedDate.getDate() - 1);
      renderDay();
    }
  });

  nextBtn.addEventListener('click', () => {
    if (state.view === 'year') {
      state.currentDate = new Date(state.currentDate.getFullYear() + 1, 0, 1);
      renderYear();
    } else if (state.view === 'month') {
      state.currentDate = new Date(state.currentDate.getFullYear(), state.currentDate.getMonth() + 1, 1);
      renderMonth();
    } else if (state.view === 'day') {
      state.selectedDate = new Date(state.selectedDate.getFullYear(), state.selectedDate.getMonth(), state.selectedDate.getDate() + 1);
      renderDay();
    }
  });

  todayBtn.addEventListener('click', () => {
    const now = new Date();
    if (state.view === 'day') {
      state.selectedDate = now;
      renderDay();
    } else {
      state.currentDate = new Date(now.getFullYear(), now.getMonth(), 1);
      state.view = 'month';
      renderMonth();
    }
  });

  // Month label: only acts when in day view (go back to month)
  monthLabel.addEventListener('click', () => {
    if (state.view === 'day') {
      state.view = 'month';
      state.currentDate = new Date(state.selectedDate.getFullYear(), state.selectedDate.getMonth(), 1);
      renderMonth();
    }
  });
  // Year label: always switches to year view (from month or day)
  yearLabel.addEventListener('click', () => {
    if (state.view === 'day') {
      // ensure currentDate aligns with selected day's month before year view
      state.currentDate = new Date(state.selectedDate.getFullYear(), state.selectedDate.getMonth(), 1);
    }
    state.view = 'year';
    renderYear();
  });
  dayFullLabel.addEventListener('click', () => {
    if (state.view === 'day') { state.view = 'month'; state.currentDate = new Date(state.selectedDate.getFullYear(), state.selectedDate.getMonth(), 1); renderMonth(); }
  });

  // Initial render
  renderMonth();
});
