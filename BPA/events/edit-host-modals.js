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

	// Edit Date Form Submission
	if (editDateForm) {
		editDateForm.addEventListener('submit', async (e) => {
			e.preventDefault();
			
			if (!currentEditEvent) return;
			
			const formData = new FormData(editDateForm);
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
						date: formData.get('date'),
						startTime: formData.get('startTime'),
						endTime: formData.get('endTime') || null,
						deadline: formData.get('deadline') || null
					})
				});
				
				const data = await response.json();
				if (response.ok && data.success) {
					alert('Date and time updated successfully!');
					closeEditDateModal();
					// Trigger event detail reload if needed
					if (window.reloadEventDetails) {
						await window.reloadEventDetails();
					}
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
