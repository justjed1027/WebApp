// Dynamic event loading and rendering
document.addEventListener('DOMContentLoaded', async () => {
	const upcomingGrid = document.querySelector('.upcoming-events-grid');
	const modal = document.getElementById('eventModal');
	const modalOverlay = document.getElementById('modalOverlay');
	const modalClose = document.getElementById('modalClose');
	const btnExpandDescription = document.getElementById('btnExpandDescription');

	if (!upcomingGrid) {
		console.warn('Upcoming events grid container not found');
		return;
	}

	// Format time helper - handles TIME format (HH:MM:SS)
	const formatTime = (timeStr) => {
		if (!timeStr) return '';
		// Check if it's just a time (HH:MM:SS)
		if (timeStr.includes(':') && !timeStr.includes('-')) {
			const [hours, minutes] = timeStr.split(':');
			const hour = parseInt(hours);
			const minute = parseInt(minutes);
			const ampm = hour >= 12 ? 'PM' : 'AM';
			const displayHour = hour % 12 || 12;
			return `${displayHour}:${minute.toString().padStart(2, '0')} ${ampm}`;
		}
		// Otherwise treat as datetime
		const date = new Date(timeStr);
		if (isNaN(date.getTime())) return '';
		return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
	};

	// Format date helper - handles DATE format and timezone offset
	const formatDate = (dateStr) => {
		if (!dateStr) return '';
		// Split date at 'T' or space to get just the date part
		const datePart = dateStr.split('T')[0] || dateStr.split(' ')[0];
		const [year, month, day] = datePart.split('-');
		const date = new Date(year, month - 1, day);
		return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
	};

	// Get time range from start and end times
	const getTimeRange = (startTime, endTime) => {
		if (!startTime && !endTime) return '';
		if (startTime && endTime) {
			const start = formatTime(startTime);
			const end = formatTime(endTime);
			return `${start} - ${end}`;
		}
		return formatTime(startTime || endTime);
	};

	// Fetch events from backend
	const fetchEvents = async (filter = 'all') => {
		try {
			const response = await fetch(`get_events.php?filter=${encodeURIComponent(filter)}`);
			const data = await response.json();
			if (data.success) {
				return data.events;
			} else {
				console.error('Failed to fetch events:', data.message);
				return [];
			}
		} catch (error) {
			console.error('Error fetching events:', error);
			return [];
		}
	};

	// Create event card HTML
	const createEventCard = (event) => {
		const tagsHtml = event.tags && event.tags.length
			? event.tags.map(tag => `<span class="event-tag">#${tag}</span>`).join('')
			: '<span class="event-tag">#event</span>';

		const timeRange = getTimeRange(event.startTime, event.endTime);

		return `
			<div class="event-card" data-event-id="${event.id}">
				<div class="event-image-container">
					<img src="${event.image || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400&h=250&fit=crop'}" alt="${event.title}" class="event-img" loading="lazy">
				</div>
				<div class="event-info">
					<h3 class="event-title">${event.title}</h3>
					
					<div class="event-details-list">
						${event.date ? `
							<div class="event-detail-item">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
									<path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
								</svg>
								<span>${formatDate(event.date)}</span>
							</div>
						` : ''}
						${timeRange ? `
							<div class="event-detail-item">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
									<path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
									<path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
								</svg>
								<span>${timeRange}</span>
							</div>
						` : ''}
						${event.location ? `
							<div class="event-detail-item">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
									<path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/>
								</svg>
								<span>${event.location}</span>
							</div>
						` : ''}
					</div>

					<div class="event-tags">
						${tagsHtml}
					</div>

					<div class="featured-event-actions">
						
						<button class="btn-view-details" data-event-id="${event.id}">View Details</button>
					</div>
				</div>
			</div>
		`;
	};

	

	// Open event detail modal
	const openEventModal = (event) => {
		if (!modal) return;

		const timeRange = getTimeRange(event.startTime, event.endTime);
		const formattedDate = formatDate(event.date);
		const tagsHtml = event.tags && event.tags.length
			? event.tags.map(tag => `<span class="event-tag">#${tag}</span>`).join('')
			: '<span class="event-tag">#event</span>';

		// Populate modal
		const modalImage = document.getElementById('modalImage');
		if (modalImage) {
			modalImage.src = event.image || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1200&h=600&fit=crop';
			modalImage.alt = event.title;
		}

		const modalTitle = document.getElementById('modalTitle');
		if (modalTitle) modalTitle.textContent = event.title;

		const modalDate = document.getElementById('modalDate');
		if (modalDate) {
			modalDate.innerHTML = `
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
					<path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
				</svg><span>${formattedDate}</span>
			`;
		}

		const modalTime = document.getElementById('modalTime');
		if (modalTime && timeRange) {
			modalTime.innerHTML = `
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
					<path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
					<path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
				</svg><span>${timeRange}</span>
			`;
		}

		const modalLocation = document.getElementById('modalLocation');
		if (modalLocation && event.location) {
			modalLocation.innerHTML = `
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
					<path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/>
				</svg><span>${event.location}</span>
			`;
		}

		const modalTags = document.getElementById('modalTags');
		if (modalTags) modalTags.innerHTML = tagsHtml;

		const modalDescription = document.getElementById('modalDescription');
		if (modalDescription) {
			modalDescription.textContent = event.description || 'No description available.';
			modalDescription.classList.remove('expanded');
		}

		const modalCategory = document.getElementById('modalCategory');
		if (modalCategory) {
			modalCategory.textContent = (event.subjects && event.subjects.length)
				? event.subjects.join(', ')
				: 'General Event';
		}

		const modalOrganizer = document.getElementById('modalOrganizer');
		if (modalOrganizer) modalOrganizer.textContent = event.organization || 'N/A';

		const modalCapacity = document.getElementById('modalCapacity');
		if (modalCapacity) modalCapacity.textContent = event.capacity ? `${event.capacity} spots` : 'N/A';

		const modalRegistration = document.getElementById('modalRegistration');
		if (modalRegistration && event.deadline) {
			modalRegistration.textContent = `Open until ${formatDate(event.deadline)}`;
		}

		const modalCreator = document.getElementById('modalCreator');
		if (modalCreator) modalCreator.textContent = event.organization || 'Event Organizer';

		// Setup modal register button: replace hard-coded button inside modal
		const modalRegisterBtn = document.querySelector('.btn-modal-register');
		if (modalRegisterBtn) {
			// reset state and handlers
			modalRegisterBtn.disabled = false;
			modalRegisterBtn.onclick = null;
			if (event.isRegistered) {
				modalRegisterBtn.textContent = 'Registered';
				modalRegisterBtn.disabled = true;
				modalRegisterBtn.classList.add('registered');
			} else {
				modalRegisterBtn.textContent = 'Register Now';
				modalRegisterBtn.classList.remove('registered');
				modalRegisterBtn.setAttribute('data-event-id', event.id);
				modalRegisterBtn.onclick = async () => {
					modalRegisterBtn.disabled = true;
					const original = modalRegisterBtn.textContent;
					modalRegisterBtn.textContent = 'Registering...';
					try {
						const res = await fetch('register_event.php', {
							method: 'POST',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify({ eventId: event.id })
						});
						const data = await res.json();
						if (res.ok && data.success) {
							modalRegisterBtn.textContent = 'Registered';
							modalRegisterBtn.classList.add('registered');
							modalRegisterBtn.disabled = true;
						} else if (res.status === 409) {
							modalRegisterBtn.textContent = 'Registered';
							modalRegisterBtn.classList.add('registered');
							modalRegisterBtn.disabled = true;
						} else {
							console.error('Registration failed', data);
							modalRegisterBtn.disabled = false;
							modalRegisterBtn.textContent = original;
							alert('Registration failed: ' + (data.message || 'Unknown'));
						}
					} catch (err) {
						console.error('Registration error', err);
						modalRegisterBtn.disabled = false;
						modalRegisterBtn.textContent = original;
						alert('Network error registering for event');
					}
				};
			}
		}

		// Show modal
		if (modal) {
			modal.hidden = false;
			document.body.style.overflow = 'hidden';
		}
	};

	// Close modal
	const closeModal = () => {
		if (modal) {
			modal.hidden = true;
			document.body.style.overflow = '';
			const descText = document.getElementById('modalDescription');
			if (descText) {
				descText.classList.remove('expanded');
			}
			if (btnExpandDescription) {
				btnExpandDescription.textContent = 'Read more';
			}
		}
	};

	// Event delegation for View Details buttons
	document.addEventListener('click', (e) => {
		if (e.target.classList.contains('btn-view-details')) {
			const eventId = e.target.getAttribute('data-event-id');
			const card = e.target.closest('[data-event-id]');
			if (card) {
				// Re-fetch and find the event
				const findEventById = async (id) => {
					const events = await fetchEvents('all');
					return events.find(e => e.id === parseInt(id));
				};
				findEventById(eventId).then(event => {
					if (event) openEventModal(event);
				});
			}
		}
	});

	// Modal close handlers
	if (modalClose) modalClose.addEventListener('click', closeModal);
	if (modalOverlay) modalOverlay.addEventListener('click', closeModal);
	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && modal && !modal.hidden) closeModal();
	});

	// Load and render events on page load
	const events = await fetchEvents('all');
	if (events.length > 0) {
		// Clear existing hardcoded cards
		const existingCards = upcomingGrid.querySelectorAll('.event-card');
		existingCards.forEach(card => card.remove());

		// Render dynamic event cards
		events.slice(0, 6).forEach(event => {
			const html = createEventCard(event);
			upcomingGrid.insertAdjacentHTML('beforeend', html);
		});
	}
});
