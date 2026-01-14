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

	// Fetch events from backend (supports optional search query, category, status, and page)
	const fetchEvents = async (filter = 'all', q = '', category = '', status = '') => {
		try {
			let url = `get_events.php?filter=${encodeURIComponent(filter)}`;
			if (q) url += `&q=${encodeURIComponent(q)}`;
			if (category) url += `&category=${encodeURIComponent(category)}`;
			if (status) url += `&status=${encodeURIComponent(status)}`;
			const response = await fetch(url);
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

	// Render helper to populate a grid (containerElement) with event cards
	const renderEvents = (containerElement, eventsList, limit = 6) => {
		if (!containerElement) return;
		// Clear existing dynamic cards
		const existingCards = containerElement.querySelectorAll('.event-card');
		existingCards.forEach(card => card.remove());

		if (!eventsList || eventsList.length === 0) return;

		eventsList.slice(0, limit).forEach(event => {
			const html = createEventCard(event);
			containerElement.insertAdjacentHTML('beforeend', html);
		});
	};

	// Update card UI for a specific event id: registration badge and participant count
	const updateCardRegistration = (eventId, isRegistered, deltaCount = 0) => {
		const selector = `.event-card[data-event-id="${eventId}"]`;
		const card = document.querySelector(selector);
		if (!card) return;
		// Update badge
		let badge = card.querySelector('.card-registration-badge');
		if (!badge) {
			// create badge placeholder
			badge = document.createElement('div');
			badge.className = 'card-registration-badge';
			card.querySelector('.event-info').insertBefore(badge, card.querySelector('.event-info').firstChild);
		}
		if (isRegistered) {
			badge.textContent = 'Registered';
			badge.classList.add('registered');
		} else {
			badge.textContent = '';
			badge.classList.remove('registered');
		}

		// Update participant count display on card
		const countEl = card.querySelector('.participants-count span');
		if (countEl && deltaCount !== 0) {
			// find first numeric match and replace
			const text = countEl.textContent || '';
			const match = text.match(/(\d+)/);
			let count = match ? parseInt(match[1], 10) : 0;
			count = Math.max(0, count + deltaCount);
			// Check if there's a capacity (X/Y format)
			const capacityMatch = text.match(/\d+\/(\d+)/);
			const capacity = capacityMatch ? capacityMatch[1] : null;
			const countDisplay = capacity ? `${count}/${capacity}` : count;
			countEl.textContent = `${countDisplay} ${count === 1 ? 'participant' : 'participants'}`;
		}
	};

	// Simple debounce helper
	const debounce = (fn, wait) => {
		let t;
		return function (...args) {
			clearTimeout(t);
			t = setTimeout(() => fn.apply(this, args), wait);
		};
	};

	// Show a small custom confirm dialog (returns Promise<boolean>)
	const showConfirm = (message) => {
		return new Promise((resolve) => {
			const overlay = document.createElement('div');
			overlay.className = 'confirm-overlay';
			const isUnregister = message.toLowerCase().includes('unregister');
			const confirmText = isUnregister ? 'Unregister' : 'Confirm';
			overlay.innerHTML = `
				<div class="confirm-dialog">
					<p>${message}</p>
					<div class="confirm-actions">
						<button class="btn-yes">${confirmText}</button>
						<button class="btn-no">Cancel</button>
					</div>
				</div>`;
			document.body.appendChild(overlay);

			const yes = overlay.querySelector('.btn-yes');
			const no = overlay.querySelector('.btn-no');

			const cleanup = () => {
				overlay.remove();
			};

			yes.focus();

			yes.addEventListener('click', () => {
				cleanup();
				resolve(true);
			});

			no.addEventListener('click', () => {
				cleanup();
				resolve(false);
			});

			overlay.addEventListener('click', (ev) => {
				if (ev.target === overlay) {
					cleanup();
					resolve(false);
				}
			});
		});
	};

	// Wire up search input and category select
	const searchInput = document.querySelector('.events-search');
	const categorySelect = document.querySelector('.events-category');
	const pastGrid = document.querySelector('.past-events-grid');

	const applyFilters = async () => {
		const q = searchInput ? searchInput.value.trim() : '';
		const cat = (categorySelect && categorySelect.value && categorySelect.value !== 'All Categories') ? categorySelect.value : '';
		// Upcoming events (status=upcoming)
		const upcomingEvents = await fetchEvents('all', q, cat, 'upcoming');
		renderEvents(upcomingGrid, upcomingEvents, 6);
		// Past events (status=past)
		const pastEvents = await fetchEvents('all', q, cat, 'past');
		renderEvents(pastGrid, pastEvents, 6);
	};
	
	if (searchInput) {
		searchInput.addEventListener('input', debounce(() => applyFilters(), 300));
	}

	if (categorySelect) {
		categorySelect.addEventListener('change', () => applyFilters());
	}

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
					<div class="event-detail-item participants-count">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
							<path d="M8 16a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM7 6.5a1 1 0 1 1 2 0 1 1 0 0 1-2 0zm1.5 4.5c0 .5 0 1-.5 1s-1-.5-1-1 .5-1 1-1 1 .5 1 1zm3-3.5a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM2.5 8a1 1 0 1 1 2 0 1 1 0 0 1-2 0z"/>
						</svg>
							<span>${event.capacity ? `${event.registrationCount}/${event.capacity}` : event.registrationCount} ${event.registrationCount === 1 ? 'participant' : 'participants'}</span>
					</div>
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

	

	// Setup host controls handlers
	const setupHostControls = (event) => {
		const btnEditTags = document.getElementById('btnEditTags');
		const btnEditDate = document.getElementById('btnEditDate');
		const btnCloseRegistration = document.getElementById('btnCloseRegistration');
		const btnDeleteEvent = document.getElementById('btnDeleteEvent');

		// Edit Tags Handler
		if (btnEditTags) {
			btnEditTags.onclick = async () => {
				// Open styled modal instead of prompt
				if (window.openEditTagsModal) {
					window.openEditTagsModal(event);
				} else {
					alert('Edit tags modal not loaded');
				}
			};
		}

		// Edit Date Handler
		if (btnEditDate) {
			btnEditDate.onclick = async () => {
				// Open styled modal instead of prompts
				if (window.openEditDateModal) {
					window.openEditDateModal(event);
				} else {
					alert('Edit date modal not loaded');
				}
			};
		}

		// Close Registration Handler
		if (btnCloseRegistration) {
			btnCloseRegistration.onclick = async () => {
				const confirmed = await showConfirm('Close registration for this event? This cannot be undone.');
				if (!confirmed) return;
				
				btnCloseRegistration.disabled = true;
				btnCloseRegistration.textContent = 'Closing...';
				
				try {
					const response = await fetch('update_event.php', {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({
							eventId: event.id,
							type: 'close_registration'
						})
					});
					
					const data = await response.json();
					if (response.ok && data.success) {
						alert('Registration closed!');
						const modalRegistration = document.getElementById('modalRegistration');
						if (modalRegistration) {
							modalRegistration.textContent = 'Registration closed';
						}
					} else {
						alert('Failed to close registration: ' + (data.message || 'Unknown error'));
					}
				} catch (error) {
					console.error('Error closing registration:', error);
					alert('Network error closing registration');
				} finally {
					btnCloseRegistration.disabled = false;
					btnCloseRegistration.textContent = 'Close Registration';
				}
			};
		}

		// Delete Event Handler
		if (btnDeleteEvent) {
			btnDeleteEvent.onclick = async () => {
				const confirmed = await showConfirm('Delete this event permanently? All registrations will be lost. This cannot be undone.');
				if (!confirmed) return;
				
				btnDeleteEvent.disabled = true;
				btnDeleteEvent.textContent = 'Deleting...';
				
				try {
					const response = await fetch('delete_event.php', {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({
							eventId: event.id
						})
					});
					
					const data = await response.json();
					if (response.ok && data.success) {
						alert('Event deleted!');
						closeModal();
						// Refresh the event list
						await applyFilters();
					} else {
						alert('Failed to delete event: ' + (data.message || 'Unknown error'));
						btnDeleteEvent.disabled = false;
						btnDeleteEvent.textContent = 'Delete Event';
					}
				} catch (error) {
					console.error('Error deleting event:', error);
					alert('Network error deleting event');
					btnDeleteEvent.disabled = false;
					btnDeleteEvent.textContent = 'Delete Event';
				}
			};
		}
	};

	// Get current user ID from PHP session (passed via window.CURRENT_USER_ID)
	const getCurrentUserId = () => {
		return window.CURRENT_USER_ID || null;
	};

	// Open event detail modal
	const openEventModal = async (event) => {
		if (!modal) return;

		// Add subjectId for edit modals (use first subject if multiple)
		event.subjectId = event.subjectIds && event.subjectIds.length > 0 ? event.subjectIds[0] : null;

		const timeRange = getTimeRange(event.startTime, event.endTime);
		const formattedDate = formatDate(event.date);
		const tagsHtml = event.tags && event.tags.length
			? event.tags.map(tag => `<span class="event-tag">#${tag}</span>`).join('')
			: '<span class="event-tag">#event</span>';

		// Check if current user is the host
		const currentUserId = getCurrentUserId();
		const isHost = currentUserId && event.hostUserId && currentUserId === event.hostUserId;

		// Show/hide host controls
		const hostControls = document.getElementById('modalHostControls');
		if (hostControls) {
			if (isHost) {
				hostControls.hidden = false;
				setupHostControls(event);
			} else {
				hostControls.hidden = true;
			}
		}

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

		const modalParticipants = document.getElementById('modalParticipants');
		if (modalParticipants) {
			const countDisplay = event.capacity ? `${event.registrationCount}/${event.capacity}` : event.registrationCount;
			modalParticipants.innerHTML = `
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
					<path d="M8 16a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM7 6.5a1 1 0 1 1 2 0 1 1 0 0 1-2 0zm1.5 4.5c0 .5 0 1-.5 1s-1-.5-1-1 .5-1 1-1 1 .5 1 1zm3-3.5a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM2.5 8a1 1 0 1 1 2 0 1 1 0 0 1-2 0z"/>
				</svg><span>${countDisplay} ${event.registrationCount === 1 ? 'participant' : 'participants'}</span>
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

		// Update Event Host section with actual user profile
		const modalCreatorAvatar = document.getElementById('modalCreatorAvatar');
		const modalCreator = document.getElementById('modalCreator');
		const modalCreatorRole = document.getElementById('modalCreatorRole');
		
		if (modalCreator) {
			// Build display name from first and last name, fallback to username
			const hostName = (event.hostFirstName || event.hostLastName) 
				? `${event.hostFirstName || ''} ${event.hostLastName || ''}`.trim()
				: event.hostUsername || 'Event Host';
			
			modalCreator.textContent = hostName;
			// Make the creator name clickable to navigate to their profile
			modalCreator.href = `../profile/profile.php?user_id=${event.hostUserId}`;
			modalCreator.style.cursor = 'pointer';
			modalCreator.style.color = '#10b981';
		}
		
		// Handle avatar: show profile picture if available, otherwise show initials
		if (modalCreatorAvatar) {
			if (event.hostProfilePicture) {
				// Show profile picture
				modalCreatorAvatar.src = event.hostProfilePicture;
				modalCreatorAvatar.style.display = 'block';
			} else {
				// Show initials fallback
				const initial = (event.hostUsername || 'U').charAt(0).toUpperCase();
				const avatarDiv = document.createElement('div');
				avatarDiv.style.cssText = 'width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.5rem; color: white; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);';
				avatarDiv.textContent = initial;
				modalCreatorAvatar.style.display = 'none';
				modalCreatorAvatar.parentNode.insertBefore(avatarDiv, modalCreatorAvatar);
			}
		}
		
		if (modalCreatorRole) {
			modalCreatorRole.textContent = event.hostUsername || 'Host';
		}

		// Setup modal register button: replace hard-coded button inside modal
		const modalRegisterBtn = document.querySelector('.btn-modal-register');
		if (modalRegisterBtn) {
			// clear previous handlers/state
			modalRegisterBtn.disabled = false;
			modalRegisterBtn.onclick = null;
			modalRegisterBtn.onmouseenter = null;
			modalRegisterBtn.onmouseleave = null;

			const updateModalParticipantCount = (delta) => {
				const modalParticipants = document.getElementById('modalParticipants');
				if (!modalParticipants) return;
				const text = modalParticipants.textContent || '';
				const match = text.match(/(\d+)/);
				let count = match ? parseInt(match[1], 10) : 0;
				count = Math.max(0, count + delta);
				const countDisplay = event.capacity ? `${count}/${event.capacity}` : count;
				modalParticipants.innerHTML = `\n\t\t\t\t\t<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">\n\t\t\t\t\t\t<path d="M8 16a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM7 6.5a1 1 0 1 1 2 0 1 1 0 0 1-2 0zm1.5 4.5c0 .5 0 1-.5 1s-1-.5-1-1 .5-1 1-1 1 .5 1 1zm3-3.5a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM2.5 8a1 1 0 1 1 2 0 1 1 0 0 1-2 0z"/>\n\t\t\t\t\t</svg><span>${countDisplay} ${count === 1 ? 'participant' : 'participants'}</span>`;
			};

			if (event.isRegistered) {
				modalRegisterBtn.textContent = 'Registered';
				modalRegisterBtn.classList.add('registered');
				modalRegisterBtn.setAttribute('data-event-id', event.id);
				// keep button enabled so user may unregister
				modalRegisterBtn.disabled = false;

				// hover to indicate unregister action
				modalRegisterBtn.onmouseenter = () => {
					modalRegisterBtn.textContent = 'Unregister?';
					modalRegisterBtn.classList.add('danger-hover');
				};
				modalRegisterBtn.onmouseleave = () => {
					modalRegisterBtn.textContent = 'Registered';
					modalRegisterBtn.classList.remove('danger-hover');
				};

					// click to unregister (use custom confirm dialog)
					modalRegisterBtn.onclick = async () => {
						const confirmed = await showConfirm('Unregister from this event?');
						if (!confirmed) return;
						modalRegisterBtn.disabled = true;
					const original = modalRegisterBtn.textContent;
					modalRegisterBtn.textContent = 'Unregistering...';
					try {
						const res = await fetch('unregister_event.php', {
							method: 'POST',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify({ eventId: event.id })
						});
						const data = await res.json();
						if (res.ok && data.success) {
							modalRegisterBtn.textContent = 'Register Now';
							modalRegisterBtn.classList.remove('registered');
							modalRegisterBtn.disabled = false;						// Refresh event list so full events that now have space appear
						await applyFilters();							// rebind to register action
							modalRegisterBtn.onclick = async () => {
								modalRegisterBtn.disabled = true;
								const orig = modalRegisterBtn.textContent;
								modalRegisterBtn.textContent = 'Registering...';
								try {
									const r = await fetch('register_event.php', {
										method: 'POST',
										headers: { 'Content-Type': 'application/json' },
										body: JSON.stringify({ eventId: event.id })
									});
									const d = await r.json();
									if (r.ok && d.success) {
										modalRegisterBtn.textContent = 'Registered';
										modalRegisterBtn.classList.add('registered');
										modalRegisterBtn.disabled = false;
										updateModalParticipantCount(1);
										updateCardRegistration(event.id, true, 1);
									} else {
										modalRegisterBtn.textContent = orig;
										modalRegisterBtn.disabled = false;
										alert('Registration failed: ' + (d.message || 'Unknown'));
									}
								} catch (err) {
									console.error(err);
									modalRegisterBtn.textContent = orig;
									modalRegisterBtn.disabled = false;
									alert('Network error registering for event');
								}
							};
							updateModalParticipantCount(-1);
										updateCardRegistration(event.id, false, -1);
						} else {
							console.error('Unregister failed', data);
							modalRegisterBtn.textContent = original;
							modalRegisterBtn.disabled = false;
							alert('Unregister failed: ' + (data.message || 'Unknown'));
						}
					} catch (err) {
						console.error('Unregister error', err);
						modalRegisterBtn.textContent = original;
						modalRegisterBtn.disabled = false;
						alert('Network error unregistering');
					}
				};
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
							modalRegisterBtn.disabled = false;
							updateModalParticipantCount(1);
							updateCardRegistration(event.id, true, 1);
						} else if (res.status === 409) {
							modalRegisterBtn.textContent = 'Registered';
							modalRegisterBtn.classList.add('registered');
							modalRegisterBtn.disabled = false;
							updateModalParticipantCount(1);
							updateCardRegistration(event.id, true, 1);
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
					const q = searchInput ? searchInput.value.trim() : '';
					const cat = (categorySelect && categorySelect.value && categorySelect.value !== 'All Categories') ? categorySelect.value : '';
					// Try upcoming first
					let events = await fetchEvents('all', q, cat, 'upcoming');
					let found = events.find(e => e.id === parseInt(id));
					if (found) return found;
					// Then try past
					events = await fetchEvents('all', q, cat, 'past');
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

	// Initial load using any active filters (search/category)
	await applyFilters();
});
