// Edit Host Modals - Handles Edit Tags and Edit Date modals for event hosts

document.addEventListener('DOMContentLoaded', () => {
	// Edit Tags Modal Elements
	const editTagsModal = document.getElementById('editTagsModal');
	const editTagsOverlay = document.getElementById('editTagsOverlay');
	const editTagsClose = document.getElementById('editTagsClose');
	const btnCancelEditTags = document.getElementById('btnCancelEditTags');
	const editTagsForm = document.getElementById('editTagsForm');
	const editTagsSelector = document.getElementById('editTagsSelector');

	// Edit Date Modal Elements
	const editDateModal = document.getElementById('editDateModal');
	const editDateOverlay = document.getElementById('editDateOverlay');
	const editDateClose = document.getElementById('editDateClose');
	const btnCancelEditDate = document.getElementById('btnCancelEditDate');
	const editDateForm = document.getElementById('editDateForm');

	// Store current event being edited
	let currentEditEvent = null;

	// Open Edit Tags Modal
	window.openEditTagsModal = async (event) => {
		currentEditEvent = event;
		
		// Load tags for the event's subject/category
		await loadTagsForEvent(event);
		
		editTagsModal.hidden = false;
		document.body.style.overflow = 'hidden';
	};

	// Close Edit Tags Modal
	const closeEditTagsModal = () => {
		editTagsModal.hidden = true;
		document.body.style.overflow = '';
		editTagsSelector.innerHTML = '';
		currentEditEvent = null;
	};

	// Load tags for the event's subject and mark current tags as selected
	const loadTagsForEvent = async (event) => {
		if (!editTagsSelector || !event.subjectId) return;
		
		editTagsSelector.innerHTML = '<div class="tag-note">Loading tags...</div>';
		
		try {
			const res = await fetch(`get_subject_tags.php?subject_id=${encodeURIComponent(event.subjectId)}`);
			const json = await res.json();
			
			if (json && json.success && Array.isArray(json.tags)) {
				editTagsSelector.innerHTML = '';
				
				if (json.tags.length === 0) {
					editTagsSelector.innerHTML = '<div class="tag-note">No tags available for this category</div>';
				} else {
					const currentTagIds = event.tagIds || [];
					
					json.tags.forEach(t => {
						const id = String(t.id);
						const wrapper = document.createElement('label');
						wrapper.className = 'tag-checkbox-label';
						
						const cb = document.createElement('input');
						cb.type = 'checkbox';
						cb.value = id;
						cb.name = 'editTagCheckbox';
						cb.id = `edit-tag-${id}`;
						
						// Check if this tag is currently selected
						if (currentTagIds.includes(parseInt(id))) {
							cb.checked = true;
						}
						
						const span = document.createElement('span');
						span.textContent = t.name;
						
						wrapper.appendChild(cb);
						wrapper.appendChild(span);
						editTagsSelector.appendChild(wrapper);
					});
				}
			}
		} catch (err) {
			console.error('Failed to load tags', err);
			editTagsSelector.innerHTML = '<div class="tag-note" style="color: var(--text-error);">Failed to load tags</div>';
		}
	};

	// Edit Tags Form Submission
	if (editTagsForm) {
		editTagsForm.addEventListener('submit', async (e) => {
			e.preventDefault();
			
			if (!currentEditEvent) return;
			
			// Get selected tag IDs
			const checkboxes = editTagsSelector.querySelectorAll('input[name="editTagCheckbox"]:checked');
			const selectedTagIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
			
			const submitBtn = editTagsForm.querySelector('.btn-form-submit');
			submitBtn.disabled = true;
			submitBtn.textContent = 'Saving...';
			
			try {
				const response = await fetch('update_event.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({
						eventId: currentEditEvent.id,
						type: 'tags',
						tagIds: selectedTagIds
					})
				});
				
				const data = await response.json();
				if (response.ok && data.success) {
					alert('Tags updated successfully!');
					closeEditTagsModal();
					// Trigger event detail reload if needed
					if (window.reloadEventDetails) {
						await window.reloadEventDetails();
					}
				} else {
					alert('Failed to update tags: ' + (data.message || 'Unknown error'));
				}
			} catch (error) {
				console.error('Error updating tags:', error);
				alert('Network error updating tags');
			} finally {
				submitBtn.disabled = false;
				submitBtn.textContent = 'Save Tags';
			}
		});
	}

	// Edit Tags Modal Close Handlers
	if (editTagsClose) editTagsClose.addEventListener('click', closeEditTagsModal);
	if (editTagsOverlay) editTagsOverlay.addEventListener('click', closeEditTagsModal);
	if (btnCancelEditTags) btnCancelEditTags.addEventListener('click', closeEditTagsModal);

	// Open Edit Date Modal
	window.openEditDateModal = (event) => {
		currentEditEvent = event;
		
		// Populate form with current values
		document.getElementById('editEventDate').value = event.date || '';
		document.getElementById('editEventStartTime').value = event.startTime || '';
		document.getElementById('editEventEndTime').value = event.endTime || '';
		document.getElementById('editEventDeadline').value = event.deadline || '';
		
		editDateModal.hidden = false;
		document.body.style.overflow = 'hidden';
	};

	// Close Edit Date Modal
	const closeEditDateModal = () => {
		editDateModal.hidden = true;
		document.body.style.overflow = '';
		editDateForm.reset();
		currentEditEvent = null;
	};

	// Success confirmation popup for edit modal
	const showEditConfirmation = (message, onConfirm) => {
		const overlay = document.createElement('div');
		overlay.style.position = 'fixed';
		overlay.style.inset = '0';
		overlay.style.background = 'rgba(0,0,0,0.5)';
		overlay.style.display = 'flex';
		overlay.style.alignItems = 'center';
		overlay.style.justifyContent = 'center';
		overlay.style.zIndex = '10001';

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
		title.textContent = 'Event Updated';
		title.style.fontSize = '18px';
		title.style.fontWeight = '600';
		title.style.marginBottom = '8px';

		const msg = document.createElement('div');
		msg.textContent = message || 'Your event was updated successfully.';
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

		overlay.addEventListener('keydown', (e) => {
			if (e.key === 'Escape') overlay.remove();
		});
		ok.focus();
	};

	// Validation error popup for edit date modal
	const showEditValidationErrors = (errors) => {
		const overlay = document.createElement('div');
		overlay.style.position = 'fixed';
		overlay.style.inset = '0';
		overlay.style.background = 'rgba(0,0,0,0.5)';
		overlay.style.display = 'flex';
		overlay.style.alignItems = 'center';
		overlay.style.justifyContent = 'center';
		overlay.style.zIndex = '10001';

		const box = document.createElement('div');
		box.style.width = 'min(92vw, 480px)';
		box.style.maxHeight = '80vh';
		box.style.background = '#0f172a';
		box.style.color = '#e2e8f0';
		box.style.border = '1px solid #334155';
		box.style.borderRadius = '12px';
		box.style.boxShadow = '0 10px 30px rgba(0,0,0,0.35)';
		box.style.padding = '20px';
		box.style.overflowY = 'auto';

		const title = document.createElement('div');
		title.textContent = 'Validation Errors';
		title.style.fontSize = '18px';
		title.style.fontWeight = '600';
		title.style.marginBottom = '12px';
		title.style.color = '#ef4444';

		const msg = document.createElement('div');
		msg.textContent = 'Please fix the following issues before updating the event:';
		msg.style.opacity = '0.85';
		msg.style.marginBottom = '16px';
		msg.style.fontSize = '14px';

		const errorList = document.createElement('ul');
		errorList.style.textAlign = 'left';
		errorList.style.listStyle = 'none';
		errorList.style.padding = '0';
		errorList.style.margin = '0 0 20px 0';
		errors.forEach(err => {
			const li = document.createElement('li');
			li.style.padding = '8px 12px';
			li.style.marginBottom = '6px';
			li.style.background = '#1e293b';
			li.style.border = '1px solid #ef4444';
			li.style.borderRadius = '6px';
			li.style.fontSize = '14px';
			li.style.display = 'flex';
			li.style.alignItems = 'flex-start';
			li.style.gap = '8px';

			const icon = document.createElement('span');
			icon.textContent = '⚠';
			icon.style.color = '#ef4444';
			icon.style.fontSize = '16px';
			icon.style.flexShrink = '0';

			const text = document.createElement('span');
			text.textContent = err;
			text.style.flex = '1';

			li.appendChild(icon);
			li.appendChild(text);
			errorList.appendChild(li);
		});

		const actions = document.createElement('div');
		actions.style.display = 'flex';
		actions.style.justifyContent = 'center';

		const ok = document.createElement('button');
		ok.textContent = 'OK';
		ok.style.background = '#ef4444';
		ok.style.border = 'none';
		ok.style.color = '#fff';
		ok.style.fontWeight = '700';
		ok.style.padding = '10px 24px';
		ok.style.borderRadius = '8px';
		ok.style.cursor = 'pointer';
		ok.addEventListener('click', () => overlay.remove());

		box.appendChild(title);
		box.appendChild(msg);
		box.appendChild(errorList);
		actions.appendChild(ok);
		box.appendChild(actions);
		overlay.appendChild(box);
		document.body.appendChild(overlay);

		overlay.addEventListener('keydown', (e) => {
			if (e.key === 'Escape') overlay.remove();
		});
		ok.focus();
	};

	// Edit Date Form Submission
	if (editDateForm) {
		editDateForm.addEventListener('submit', async (e) => {
			e.preventDefault();
			
			if (!currentEditEvent) return;
			
			const formData = new FormData(editDateForm);
			const date = formData.get('date');
			const startTime = formData.get('startTime');
			const endTime = formData.get('endTime');
			const deadline = formData.get('deadline');

			// Validation - collect all errors
			const errors = [];

			// Date must be today or later
			if (!date) {
				errors.push('Event date is required.');
			} else {
				const today = new Date();
				const todayDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
				const evDateParts = date.split('-');
				if (evDateParts.length !== 3) {
					errors.push('Invalid event date format.');
				} else {
					const evDate = new Date(parseInt(evDateParts[0]), parseInt(evDateParts[1]) - 1, parseInt(evDateParts[2]));
					if (evDate < todayDate) {
						errors.push('Event date cannot be in the past.');
					}

					// Start and end times required and ordered
					if (!startTime || !endTime) {
						errors.push('Start and end times are required.');
					} else {
						const start = new Date(`${date}T${startTime}:00`);
						const end = new Date(`${date}T${endTime}:00`);
						if (!(start instanceof Date) || isNaN(start) || !(end instanceof Date) || isNaN(end)) {
							errors.push('Invalid start or end time format.');
						} else if (end.getTime() < start.getTime()) {
							errors.push('End time cannot be before start time.');
						} else if (end.getTime() === start.getTime()) {
							errors.push('End time cannot be the same as start time.');
						}
					}

					// Registration deadline required, after today and before event date
					if (!deadline) {
						errors.push('Registration deadline is required.');
					} else {
						const rdParts = deadline.split('-');
						if (rdParts.length !== 3) {
							errors.push('Invalid registration deadline format.');
						} else {
							const rd = new Date(parseInt(rdParts[0]), parseInt(rdParts[1]) - 1, parseInt(rdParts[2]));
							if (rd.getTime() <= todayDate.getTime()) {
								errors.push('Registration deadline must be after today.');
							}
							if (rd.getTime() >= evDate.getTime()) {
								errors.push('Registration deadline must be before the event date.');
							}
						}
					}
				}
			}

			// If there are validation errors, show them
			if (errors.length > 0) {
				showEditValidationErrors(errors);
				return;
			}
			
			const submitBtn = editDateForm.querySelector('.btn-form-submit');
			submitBtn.disabled = true;
			submitBtn.textContent = 'Saving...';
			
			try {
				const response = await fetch('update_event.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({
						eventId: currentEditEvent.id,
						type: 'date',
						date: date,
						startTime: startTime,
						endTime: endTime,
						deadline: deadline
					})
				});
				
				const data = await response.json();
				if (response.ok && data.success) {
					closeEditDateModal();
					showEditConfirmation('Date and time updated successfully.', () => {
						location.reload();
					});
				} else {
					alert('Failed to update date: ' + (data.message || 'Unknown error'));
				}
			} catch (error) {
				console.error('Error updating date:', error);
				alert('Network error updating date');
			} finally {
				submitBtn.disabled = false;
				submitBtn.textContent = 'Save Changes';
			}
		});
	}

	// Edit Date Modal Close Handlers
	if (editDateClose) editDateClose.addEventListener('click', closeEditDateModal);
	if (editDateOverlay) editDateOverlay.addEventListener('click', closeEditDateModal);
	if (btnCancelEditDate) btnCancelEditDate.addEventListener('click', closeEditDateModal);

	// Escape key to close modals
	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape') {
			if (!editTagsModal.hidden) closeEditTagsModal();
			if (!editDateModal.hidden) closeEditDateModal();
		}
	});
});
