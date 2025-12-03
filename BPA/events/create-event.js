// Create Event Modal
document.addEventListener('DOMContentLoaded', () => {
	const modal = document.getElementById('createEventModal');
	const modalOverlay = document.getElementById('createModalOverlay');
	const modalClose = document.getElementById('createModalClose');
	const fabButton = document.getElementById('fabCreateEvent');
	const cancelButton = document.getElementById('btnCancelCreate');
	const form = document.getElementById('createEventForm');
	
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
			tags: document.getElementById('eventTags').value,
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

		// Log form data (in production, this would be sent to backend)
		console.log('Event Created:', formData);
		
		// Show success message
		alert('Event created successfully! (This is a demo - no backend connected yet)');
		
		// Close modal
		closeModal();
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
