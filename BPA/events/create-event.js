// Create Event Modal

document.addEventListener('DOMContentLoaded', () => {
	const modal = document.getElementById('createEventModal');
	const modalOverlay = document.getElementById('createModalOverlay');
	const modalClose = document.getElementById('createModalClose');
	const fabButton = document.getElementById('fabCreateEvent');
	const cancelButton = document.getElementById('btnCancelCreate');
	const form = document.getElementById('createEventForm');

	// Tags multi-select element
	const tagSelect = document.getElementById('eventTags');

	// Load tags for a subject and populate the multi-select
	const loadTagsForSubject = async (subjectId) => {
		if (!tagSelect) return;
		// clear existing
		tagSelect.innerHTML = '';
		if (!subjectId) {
			const note = document.createElement('div');
			note.className = 'tag-note';
			note.textContent = 'Select a category to load tags';
			tagSelect.appendChild(note);
			return;
		}
		try {
			const res = await fetch(`get_subject_tags.php?subject_id=${encodeURIComponent(subjectId)}`);
			const json = await res.json();
			if (json && json.success && Array.isArray(json.tags)) {
				if (json.tags.length === 0) {
					const note = document.createElement('div');
					note.className = 'tag-note';
					note.textContent = 'No tags available for this category';
					tagSelect.appendChild(note);
				} else {
					json.tags.forEach(t => {
						const id = String(t.id);
						const wrapper = document.createElement('label');
						wrapper.className = 'tag-checkbox-label';
						const cb = document.createElement('input');
						cb.type = 'checkbox';
						cb.value = id;
						cb.name = 'eventTagCheckbox';
						cb.id = `tag-${id}`;
						const span = document.createElement('span');
						span.textContent = t.name;
						wrapper.appendChild(cb);
						wrapper.appendChild(span);
						tagSelect.appendChild(wrapper);
					});
				}
				// if discovery info present, log it to console for debugging
				if (json.discovery && json.discovery.length) console.debug('Tag discovery:', json.discovery);
			}
		} catch (err) {
			console.error('Failed to load tags', err);
		}
	};

	// When category changes, load tags
	const categorySelect = document.getElementById('eventCategory');
	if (categorySelect) {
		categorySelect.addEventListener('change', (e) => {
			loadTagsForSubject(e.target.value);
		});
		// load initial tags if category already selected
		if (categorySelect.value) loadTagsForSubject(categorySelect.value);
	}
	
	if (!modal || !fabButton) return;

	// Open modal
	const openModal = () => {
		modal.hidden = false;
		document.body.style.overflow = 'hidden';
	};

	// Close modal
	const closeModal = () => {
		modal.hidden = true;
		document.body.style.overflow = '';
		form.reset();
		// clear dynamic tag list so state resets between opens
		if (tagSelect) tagSelect.innerHTML = '';
	};

	// Event listeners
	fabButton.addEventListener('click', openModal);
	modalClose.addEventListener('click', closeModal);
	modalOverlay.addEventListener('click', closeModal);
	cancelButton.addEventListener('click', closeModal);

	// Lightweight confirmation popup (no external CSS needed)
	const showConfirmation = (message, onConfirm) => {
		const overlay = document.createElement('div');
		overlay.style.position = 'fixed';
		overlay.style.inset = '0';
		overlay.style.background = 'rgba(0,0,0,0.5)';
		overlay.style.display = 'flex';
		overlay.style.alignItems = 'center';
		overlay.style.justifyContent = 'center';
		overlay.style.zIndex = '10000';

		const box = document.createElement('div');
		box.style.width = 'min(92vw, 420px)';
		box.style.background = '#0f172a';
		box.style.color = '#e2e8f0';
		box.style.border = '1px solid #334155';
		box.style.borderRadius = '12px';
		box.style.boxShadow = '0 10px 30px rgba(0,0,0,0.35)';
		box.style.padding = '20px';
		box.style.textAlign = 'center';

		const title = document.createElement('div');
		title.textContent = 'Event Created';
		title.style.fontSize = '18px';
		title.style.fontWeight = '600';
		title.style.marginBottom = '8px';

		const msg = document.createElement('div');
		msg.textContent = message || 'Your event was created successfully.';
		msg.style.opacity = '0.9';
		msg.style.marginBottom = '16px';

		const actions = document.createElement('div');
		actions.style.display = 'flex';
		actions.style.gap = '10px';
		actions.style.justifyContent = 'center';

		const ok = document.createElement('button');
		ok.textContent = 'OK';
		ok.style.background = '#10b981';
		ok.style.border = 'none';
		ok.style.color = '#052e2b';
		ok.style.fontWeight = '700';
		ok.style.padding = '10px 16px';
		ok.style.borderRadius = '8px';
		ok.style.cursor = 'pointer';
		ok.addEventListener('click', () => {
			overlay.remove();
			if (typeof onConfirm === 'function') onConfirm();
		});

		box.appendChild(title);
		box.appendChild(msg);
		actions.appendChild(ok);
		box.appendChild(actions);
		overlay.appendChild(box);
		document.body.appendChild(overlay);

		// Close on Escape
		overlay.addEventListener('keydown', (e) => {
			if (e.key === 'Escape') overlay.remove();
		});
		// Focus trap start
		ok.focus();
	};
	
	// Escape key to close
	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && !modal.hidden) closeModal();
	});

	// Form submission
	form.addEventListener('submit', (e) => {
		e.preventDefault();
		
		// Collect form data
		const formData = {
			title: document.getElementById('eventTitle').value,
			category: document.getElementById('eventCategory').value,
			description: document.getElementById('eventDescription').value,
			image: document.getElementById('eventImage').value || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1200&h=600&fit=crop',
			date: document.getElementById('eventDate').value,
			startTime: document.getElementById('eventStartTime').value,
			endTime: document.getElementById('eventEndTime').value,
			location: document.getElementById('eventLocation').value,
			capacity: document.getElementById('eventCapacity').value,
			organizer: document.getElementById('eventOrganizer').value,
			tags: Array.from(document.querySelectorAll('#eventTags input[type="checkbox"]:checked')).map(i => i.value),
			visibility: document.querySelector('input[name="eventVisibility"]:checked').value,
			requireApproval: document.getElementById('eventRequireApproval').checked,
			featured: document.getElementById('eventFeatured').checked,
			registrationDeadline: document.getElementById('eventRegistrationDeadline').value,
			contactEmail: document.getElementById('eventContactEmail').value
		};

		// Validate description length
		if (formData.description.length < 50) {
			alert('Description must be at least 50 characters long.');
			return;
		}

		// Send to backend
		fetch('create_event.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(formData)
		})
		.then(async (r) => {
			let data = null;
			try {
				// Try to parse JSON regardless of header robustness
				const text = await r.text();
				data = text ? JSON.parse(text) : null;
			} catch (e) {
				console.warn('Response was not valid JSON');
			}

			if (!r.ok) {
				throw new Error((data && data.message) || `Request failed (${r.status})`);
			}
			return data;
		})
		.then((res) => {
			if (res && res.success) {
				closeModal();
				showConfirmation('Your event was created successfully.', () => {
					location.reload();
				});
			} else {
				const msg = (res && res.message) ? res.message : 'Unknown error creating event';
				alert('Error creating event: ' + msg);
			}
		})
		.catch(err => {
			console.error(err);
			alert('Network or parsing error creating event.');
		});
	});

	// Real-time character counter for description
	const description = document.getElementById('eventDescription');
	const descHint = description.nextElementSibling;
	
	description.addEventListener('input', () => {
		const length = description.value.length;
		if (length < 50) {
			descHint.textContent = `${50 - length} more characters needed`;
			descHint.style.color = '#ef4444';
		} else {
			descHint.textContent = `${length} characters`;
			descHint.style.color = '#10b981';
		}
	});
});
