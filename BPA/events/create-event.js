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
		.then(r => r.json())
		.then(res => {
			if (res && res.success) {
				alert('Event created successfully');
				closeModal();
				// Optionally reload to show new event
				setTimeout(() => location.reload(), 800);
			} else {
				alert('Error creating event: ' + (res.message || 'Unknown'));
			}
		})
		.catch(err => {
			console.error(err);
			alert('Network error creating event');
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
