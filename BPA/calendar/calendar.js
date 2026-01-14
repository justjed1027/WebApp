// Sidebar hover functionality is handled by CSS
// No JavaScript needed for basic hover expand/collapse

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

  // ===== REGISTERED EVENTS DATA =====
  let registeredEvents = []; // Will hold actual user's registered events

  // ===== SAMPLE EVENTS DATA (stub) - keeping for backwards compatibility but will be replaced =====
  const events = [];

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
      
      // Check for registered events on this date
      const dayEvents = registeredEvents.filter(ev => {
        const eventDate = ev.events_date ? ev.events_date.split(' ')[0] : '';
        return eventDate === fmt(cellDate);
      });
      
      if (dayEvents.length > 0) {
        td.classList.add('has-events');
        const dot = document.createElement('div');
        dot.className = 'indicator-dot';
        if (dayEvents.length > 1) {
          dot.setAttribute('data-count', dayEvents.length);
        }
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
    
    // Get events for this specific day
    const dayEvents = registeredEvents.filter(ev => {
      const eventDate = ev.events_date ? ev.events_date.split(' ')[0] : '';
      return eventDate === fmt(d);
    });
    
    for (let h = 0; h < 24; h++) {
      const slot = document.createElement('div');
      slot.className = 'time-slot';
      const time = document.createElement('div');
      time.className = 'slot-time';
      time.textContent = (h < 10 ? '0' + h : h) + ':00';
      slot.appendChild(time);
      
      // Find events that start in this hour
      dayEvents.forEach(ev => {
        if (!ev.events_start) return;
        const startTime = ev.events_start.split(':');
        const startHour = parseInt(startTime[0], 10);
        
        if (startHour === h) {
          const block = document.createElement('div');
          block.className = 'event-block event';
          
          // Format time display
          const formatEventTime = (timeStr) => {
            if (!timeStr) return '';
            const [hours, minutes] = timeStr.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minutes} ${ampm}`;
          };
          
          const startTimeFormatted = formatEventTime(ev.events_start);
          const endTimeFormatted = ev.events_end ? formatEventTime(ev.events_end) : '';
          const timeDisplay = endTimeFormatted ? `${startTimeFormatted} - ${endTimeFormatted}` : startTimeFormatted;
          
          // Get primary subject for badge
          const subjects = ev.subjects ? ev.subjects.split(',') : [];
          const primarySubject = subjects[0] ? subjects[0].trim() : 'Event';
          
          block.innerHTML = `<strong>${ev.events_title}</strong><br>${timeDisplay}<span class="event-duration">${primarySubject}</span>`;
          block.tabIndex = 0;
          block.setAttribute('data-event-id', ev.events_id);
          
          const showTooltip = () => {
            let tip = block.querySelector('.event-tooltip');
            if (!tip) {
              tip = document.createElement('div');
              tip.className = 'event-tooltip';
              tip.innerHTML = `
                <h4>${ev.events_title}</h4>
                <div class='event-meta'>${d.toDateString()}<br>${timeDisplay}</div>
                <span class='event-type-badge event'>${primarySubject}</span>
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
            requestAnimationFrame(() => tip.classList.add('visible'));
          };
          
          const hideTooltip = () => {
            const tip = block.querySelector('.event-tooltip');
            if (tip) tip.classList.remove('visible');
          };
          
          // Click to view event details
          block.addEventListener('click', () => {
            openEventModal(ev.events_id);
          });
          
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

  // ===== UPCOMING EVENTS LOADING =====
  const upcomingEventsContainer = document.getElementById('calendarUpcomingEvents');
  const modal = document.getElementById('eventModal');
  const modalOverlay = document.getElementById('modalOverlay');
  const modalClose = document.getElementById('modalClose');
  const btnExpandDescription = document.getElementById('btnExpandDescription');
  let currentEventId = null; // Track current event for unregister

  if (!modal) {
    console.error('Event modal not found');
  }

  // Format time helper - handles TIME format (HH:MM:SS)
  const formatTime = (timeStr) => {
    if (!timeStr) return '';
    if (timeStr.includes(':') && !timeStr.includes('-')) {
      const [hours, minutes] = timeStr.split(':');
      const hour = parseInt(hours);
      const minute = parseInt(minutes);
      const ampm = hour >= 12 ? 'PM' : 'AM';
      const displayHour = hour % 12 || 12;
      return `${displayHour}:${minute.toString().padStart(2, '0')} ${ampm}`;
    }
    const date = new Date(timeStr);
    if (isNaN(date.getTime())) return '';
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
  };

  // Format date helper
  const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const datePart = dateStr.split('T')[0] || dateStr.split(' ')[0];
    const [year, month, day] = datePart.split('-');
    const date = new Date(year, month - 1, day);
    const dayName = date.toLocaleDateString('en-US', { weekday: 'long' });
    const monthName = date.toLocaleDateString('en-US', { month: 'long' });
    return `${dayName}, ${monthName} ${parseInt(day)}`;
  };

  // Get time range
  const getTimeRange = (startTime, endTime) => {
    if (!startTime && !endTime) return '';
    if (startTime && endTime) {
      const start = formatTime(startTime);
      const end = formatTime(endTime);
      return `${start} - ${end}`;
    }
    return formatTime(startTime || endTime);
  };

  // Get default event image
  const getEventImage = (event) => {
    if (event.events_img) return event.events_img;
    // Default images based on subjects
    const subject = (event.subjects || '').toLowerCase();
    if (subject.includes('computer') || subject.includes('programming')) {
      return 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=1000&h=280&fit=crop';
    } else if (subject.includes('data') || subject.includes('science')) {
      return 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1000&h=280&fit=crop';
    } else if (subject.includes('design') || subject.includes('ui') || subject.includes('ux')) {
      return 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=1000&h=280&fit=crop';
    }
    return 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1000&h=280&fit=crop';
  };

  // Get primary subject tag
  const getPrimarySubject = (subjects) => {
    if (!subjects) return 'General';
    const subjectList = subjects.split(',');
    return subjectList[0].trim();
  };

  // Create event card HTML
  const createEventCard = (event) => {
    const dateStr = formatDate(event.events_date);
    const timeRange = getTimeRange(event.events_start, event.events_end);
    const imageUrl = getEventImage(event);
    const primarySubject = getPrimarySubject(event.subjects);
    
    return `
      <div class="calendar-event-card" data-event-id="${event.events_id}">
        <div class="calendar-event-info">
          <h4 class="calendar-event-title">${event.events_title}</h4>
          <div class="calendar-event-details">
            <div class="calendar-event-detail-item">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
              </svg>
              <span>${dateStr}${timeRange ? ' • ' + timeRange : ''}</span>
            </div>
            ${event.events_location ? `
            <div class="calendar-event-detail-item">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                <path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A31.493 31.493 0 0 1 8 14.58a31.481 31.481 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94zM8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10z"/>
                <path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
              </svg>
              <span>${event.events_location}</span>
            </div>
            ` : ''}
          </div>
          <div class="calendar-event-tags">
            <span class="calendar-event-tag subject">${primarySubject}</span>
          </div>
          <button class="calendar-btn-view-details" data-event-id="${event.events_id}">View Details</button>
        </div>
        <div class="calendar-event-image-side">
          <img src="${imageUrl}" alt="${event.events_title}" class="calendar-event-side-img">
        </div>
      </div>
    `;
  };

  // Fetch and render upcoming events
  const loadUpcomingEvents = async () => {
    try {
      const response = await fetch('get_calendar_events.php');
      const data = await response.json();
      
      if (data.success && data.events && data.events.length > 0) {
        // Store events globally for calendar views
        registeredEvents = data.events;
        
        // Render events in the sidebar
        upcomingEventsContainer.innerHTML = data.events.map(event => createEventCard(event)).join('');
        // Attach event listeners to View Details buttons
        attachViewDetailsListeners();
        
        // Re-render the current calendar view to show event indicators
        if (state.view === 'month') {
          renderMonth();
        } else if (state.view === 'day') {
          renderDay();
        } else if (state.view === 'year') {
          renderYear();
        }
      } else {
        registeredEvents = [];
        upcomingEventsContainer.innerHTML = '<p style="color: var(--text-secondary); text-align: center; padding: 20px;">No upcoming events. Register for events to see them here!</p>';
      }
    } catch (error) {
      console.error('Error loading upcoming events:', error);
      registeredEvents = [];
      upcomingEventsContainer.innerHTML = '<p style="color: var(--error); text-align: center; padding: 20px;">Failed to load upcoming events.</p>';
    }
  };

  // Helper function to populate modal with event data
  const populateModal = (event) => {
    console.log('Populating modal with event:', event);
    
    // Store the current event ID for unregister functionality
    currentEventId = event.events_id;
    
    // Populate modal
    const imageUrl = getEventImage(event);
    document.getElementById('modalImage').src = imageUrl;
    document.getElementById('modalImage').alt = event.events_title;
    document.getElementById('modalTitle').textContent = event.events_title;
    
    // Format date as "Jan 15, 2026"
    const eventDate = new Date(event.events_date + 'T00:00:00');
    const dateStr = eventDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    const timeRange = getTimeRange(event.events_start, event.events_end);
    
    document.getElementById('modalDate').innerHTML = `
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
        <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
      </svg>
      <span>${dateStr}</span>
    `;
    
    document.getElementById('modalTime').innerHTML = timeRange ? `
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
      </svg>
      <span>${timeRange}</span>
    ` : '';
    
    document.getElementById('modalLocation').innerHTML = event.events_location ? `
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/>
      </svg>
      <span>${event.events_location}</span>
    ` : `
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/>
      </svg>
      <span>0</span>
    `;
    
    // Show participant count with capacity in format "1/500 participant" or "10/500 participants"
    const capacity = event.events_capacity || 0;
    const count = event.registration_count || 0;
    const participantWord = count === 1 ? 'participant' : 'participants';
    const participantText = capacity > 0 ? `${count}/${capacity} ${participantWord}` : `${count} ${participantWord}`;
    
    document.getElementById('modalParticipants').innerHTML = `
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
      </svg>
      <span>${participantText}</span>
    `;
    
    // Tags - show subjects as hashtags
    const subjects = event.subjects ? event.subjects.split(',').map(s => s.trim()) : [];
    document.getElementById('modalTags').innerHTML = subjects.map(subject => 
      `<span class="event-tag">#${subject.toLowerCase().replace(/\s+/g, '')}</span>`
    ).join('');
    
    document.getElementById('modalDescription').textContent = event.events_description || 'No description available.';
    
    // Category - use first subject
    document.getElementById('modalCategory').textContent = subjects[0] || 'General';

    // Host profile (actual event creator)
    const modalCreatorAvatar = document.getElementById('modalCreatorAvatar');
    const modalCreator = document.getElementById('modalCreator');
    const modalCreatorRole = document.getElementById('modalCreatorRole');

    if (modalCreator) {
      const hostName = (event.user_firstname || event.user_lastname)
        ? `${event.user_firstname || ''} ${event.user_lastname || ''}`.trim()
        : (event.user_username || 'Event Host');
      modalCreator.textContent = hostName;
      modalCreator.href = `../profile/profile.php?user_id=${event.host_user_id}`;
      modalCreator.style.cursor = 'pointer';
    }
    if (modalCreatorAvatar && event.profile_filepath) {
      modalCreatorAvatar.src = event.profile_filepath;
    }
    if (modalCreatorRole) {
      modalCreatorRole.textContent = event.user_username || '';
    }
    
    // Capacity in format "500 spots"
    const capacityText = event.events_capacity ? `${event.events_capacity} spots` : 'Unlimited';
    document.getElementById('modalCapacity').textContent = capacityText;
    
    // Registration deadline in format "Open until Jan 10, 2026"
    let deadlineStr = 'Open';
    if (event.events_deadline) {
      const deadlineDate = new Date(event.events_deadline + 'T00:00:00');
      const deadlineDateStr = deadlineDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
      deadlineStr = `Open until ${deadlineDateStr}`;
    }
    document.getElementById('modalRegistration').textContent = deadlineStr;
  };

  // Open modal with event details
  const openEventModal = async (eventId) => {
    console.log('openEventModal called with eventId:', eventId);
    console.log('Modal element exists:', !!modal);
    
    if (!modal) {
      console.error('Modal element not found');
      return;
    }

    try {
      // First try to fetch from calendar events (the events the user is registered for)
      console.log('Fetching from get_calendar_events.php');
      const calResponse = await fetch('get_calendar_events.php');
      const calData = await calResponse.json();
      console.log('Calendar events response:', calData);
      
      if (calData.success && calData.events) {
        const calEvent = calData.events.find(e => e.events_id == eventId);
        if (calEvent) {
          console.log('Found event in calendar data:', calEvent);
          populateModal(calEvent);
          modal.removeAttribute('hidden');
          document.body.style.overflow = 'hidden';
          return;
        }
      }
      
      // If not found in calendar events, try all events
      console.log('Event not in calendar, fetching from ../events/get_events.php');
      const response = await fetch(`../events/get_events.php?filter=all`);
      const data = await response.json();
      console.log('All events response:', data);
      
      if (data.success && data.events) {
        const event = data.events.find(e => e.events_id == eventId);
        if (event) {
          console.log('Found event in all events:', event);
          populateModal(event);
          modal.removeAttribute('hidden');
          document.body.style.overflow = 'hidden';
          return;
        }
      }
      
      console.error('Event not found in either source. Event ID:', eventId);
    } catch (error) {
      console.error('Error loading event details:', error);
    }
  };

  // Close modal
  const closeModal = () => {
    if (!modal) return;
    modal.setAttribute('hidden', '');
    document.body.style.overflow = '';
    const descText = document.getElementById('modalDescription');
    if (descText) {
      descText.classList.remove('expanded');
    }
    if (btnExpandDescription) {
      btnExpandDescription.textContent = 'Read more';
    }
  };

  // Attach listeners to View Details buttons
  const attachViewDetailsListeners = () => {
    const buttons = document.querySelectorAll('.calendar-btn-view-details');
    console.log('Found', buttons.length, 'View Details buttons');
    buttons.forEach(btn => {
      btn.addEventListener('click', (e) => {
        const eventId = e.target.getAttribute('data-event-id');
        console.log('View Details clicked for event ID:', eventId);
        if (eventId) {
          openEventModal(eventId);
        }
      });
    });
  };

  // Modal controls
  if (modalClose) {
    modalClose.addEventListener('click', closeModal);
  }
  
  if (modalOverlay) {
    modalOverlay.addEventListener('click', closeModal);
  }

  if (btnExpandDescription) {
    btnExpandDescription.addEventListener('click', () => {
      const descText = document.getElementById('modalDescription');
      descText.classList.toggle('expanded');
      btnExpandDescription.textContent = descText.classList.contains('expanded') ? 'Read less' : 'Read more';
    });
  }

  // Unregister button
  const btnUnregister = document.getElementById('btnUnregister');

  // Confirmation modal elements
  const confirmationModal = document.getElementById('confirmationModal');
  const confirmationOverlay = document.getElementById('confirmationOverlay');
  const confirmationTitle = document.getElementById('confirmationTitle');
  const confirmationMessage = document.getElementById('confirmationMessage');
  const btnConfirmCancel = document.getElementById('btnConfirmCancel');
  const btnConfirmOk = document.getElementById('btnConfirmOk');

  // Show confirmation dialog
  const showConfirmation = (title, message) => {
    return new Promise((resolve) => {
      confirmationTitle.textContent = title;
      confirmationMessage.textContent = message;
      confirmationModal.removeAttribute('hidden');
      document.body.style.overflow = 'hidden';

      const handleConfirm = () => {
        cleanup();
        resolve(true);
      };

      const handleCancel = () => {
        cleanup();
        resolve(false);
      };

      const cleanup = () => {
        confirmationModal.setAttribute('hidden', '');
        document.body.style.overflow = '';
        btnConfirmOk.removeEventListener('click', handleConfirm);
        btnConfirmCancel.removeEventListener('click', handleCancel);
        confirmationOverlay.removeEventListener('click', handleCancel);
      };

      btnConfirmOk.addEventListener('click', handleConfirm);
      btnConfirmCancel.addEventListener('click', handleCancel);
      confirmationOverlay.addEventListener('click', handleCancel);
    });
  };

  // Show success/error message
  const showMessage = (title, message) => {
    return new Promise((resolve) => {
      confirmationTitle.textContent = title;
      confirmationMessage.textContent = message;
      confirmationModal.removeAttribute('hidden');
      document.body.style.overflow = 'hidden';
      
      // Hide cancel button, change confirm to "OK"
      btnConfirmCancel.style.display = 'none';
      btnConfirmOk.textContent = 'OK';

      const handleOk = () => {
        cleanup();
        resolve();
      };

      const cleanup = () => {
        confirmationModal.setAttribute('hidden', '');
        document.body.style.overflow = '';
        btnConfirmCancel.style.display = '';
        btnConfirmOk.textContent = 'Confirm';
        btnConfirmOk.removeEventListener('click', handleOk);
        confirmationOverlay.removeEventListener('click', handleOk);
      };

      btnConfirmOk.addEventListener('click', handleOk);
      confirmationOverlay.addEventListener('click', handleOk);
    });
  };

  if (btnUnregister) {
    btnUnregister.addEventListener('click', async () => {
      if (!currentEventId) {
        console.error('No event ID set');
        return;
      }

      const confirmed = await showConfirmation(
        'Unregister from Event',
        'Are you sure you want to unregister from this event? This action cannot be undone.'
      );

      if (!confirmed) {
        return;
      }

      try {
        const response = await fetch('../events/unregister_event.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ eventId: currentEventId })
        });

        const data = await response.json();

        if (data.success) {
          await showMessage('Success', 'You have successfully unregistered from the event!');
          closeModal();
          // Reload the events list
          loadUpcomingEvents();
        } else {
          await showMessage('Error', 'Failed to unregister: ' + (data.message || 'Unknown error'));
        }
      } catch (error) {
        console.error('Error unregistering:', error);
        await showMessage('Error', 'An error occurred while unregistering. Please try again.');
      }
    });
  }

  // Load upcoming events on page load
  loadUpcomingEvents();
});
