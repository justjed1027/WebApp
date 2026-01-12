// Featured Events Loader - Dynamically loads personalized featured events

document.addEventListener('DOMContentLoaded', () => {
	const featuredTrack = document.querySelector('.featured-track');
	const carouselDots = document.querySelector('.carousel-dots');
	const prevBtn = document.querySelector('.carousel-btn.prev');
	const nextBtn = document.querySelector('.carousel-btn.next');
	
	let currentSlide = 0;
	let featuredEvents = [];
	let autoplayInterval = null;
	let cachedTimeSlot = null;

	// Format date helper
	const formatDate = (dateStr) => {
		const date = new Date(dateStr);
		return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
	};

	// Format time helper
	const formatTime = (timeStr) => {
		if (!timeStr) return '';
		const [hours, minutes] = timeStr.split(':');
		const hour = parseInt(hours);
		const ampm = hour >= 12 ? 'PM' : 'AM';
		const displayHour = hour % 12 || 12;
		return `${displayHour}:${minutes} ${ampm}`;
	};

	// Get time range string
	const getTimeRange = (start, end) => {
		if (!start && !end) return '';
		if (start && !end) return `Starts at ${formatTime(start)}`;
		if (start && end) return `${formatTime(start)} - ${formatTime(end)}`;
		return '';
	};

	// Create featured event card HTML
	const createFeaturedCard = (event, index, total) => {
		const tagsHtml = event.tags && event.tags.length > 0
			? event.tags.slice(0, 3).map(tag => `<span class="event-tag">#${tag}</span>`).join('')
			: '<span class="event-tag">#event</span>';

		const timeRange = getTimeRange(event.startTime, event.endTime);
		const formattedDate = formatDate(event.date);
		const attendeeText = event.capacity 
			? `${event.registrationCount}/${event.capacity} registered`
			: `${event.registrationCount} attending`;

		return `
			<article class="featured-event-card" role="group" aria-roledescription="slide" aria-label="${index + 1} of ${total}" style="min-width: 100%; flex-shrink: 0;">
				<div class="featured-event-image-container">
					<img src="${event.image}" alt="${event.title}" class="featured-event-img" loading="lazy">
				</div>
				<div class="featured-event-info">
					<div class="featured-event-header">
						<h2 class="featured-event-title">${event.title}</h2>
						<span class="featured-badge">Featured</span>
					</div>
					<p class="featured-event-desc">${event.description || 'No description available.'}</p>
					<div class="featured-event-details">
						<div class="event-detail-item">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
								<path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
							</svg>
							<span>${formattedDate}</span>
						</div>
						${timeRange ? `
						<div class="event-detail-item">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
								<path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
								<path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
							</svg>
							<span>${timeRange}</span>
						</div>
						` : ''}
						${event.location ? `
						<div class="event-detail-item">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
								<path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/>
							</svg>
							<span>${event.location}</span>
						</div>
						` : ''}
						<div class="event-detail-item">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
								<path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
							</svg>
							<span>${attendeeText}</span>
						</div>
					</div>
					<div class="featured-event-tags">
						${tagsHtml}
					</div>
					<div class="featured-event-actions">
						<button class="btn-view-details" data-event-id="${event.id}">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
								<path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
								<path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
							</svg>
							View Details
						</button>
					</div>
				</div>
			</article>
		`;
	};

	// Load featured events from backend
	const loadFeaturedEvents = async () => {
		try {
			const response = await fetch('get_featured_events.php');
			const data = await response.json();

			console.log('Featured events response:', data);
			console.log('Response status:', response.status);

			if (data.success && data.events && data.events.length > 0) {
				featuredEvents = data.events;
				cachedTimeSlot = data.timeSlot;
				console.log(`Loaded ${featuredEvents.length} featured events`);
				renderFeaturedCarousel();
			} else {
				// Show no events message
				console.log('No featured events found. Data:', data);
				featuredTrack.innerHTML = `
					<div class="no-featured-events" style="text-align: center; padding: 4rem 2rem; color: var(--text-secondary);">
						<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" viewBox="0 0 16 16" style="opacity: 0.3; margin-bottom: 1rem;">
							<path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
							<path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
						</svg>
						<h3 style="margin-bottom: 0.5rem;">No Featured Events Available</h3>
						<p>We couldn't find any events matching your interests right now. Check back later!</p>
					</div>
				`;
				if (carouselDots) carouselDots.innerHTML = '';
			}
		} catch (error) {
			console.error('Failed to load featured events:', error);
			console.error('Error details:', error.message, error.stack);
			featuredTrack.innerHTML = `
				<div class="featured-error" style="text-align: center; padding: 4rem 2rem; color: var(--text-error);">
					<p>Failed to load featured events. Please try refreshing the page.</p>
					<p style="font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.5rem;">Error: ${error.message}</p>
				</div>
			`;
		}
	};

	// Render carousel with featured events
	const renderFeaturedCarousel = () => {
		if (!featuredEvents || featuredEvents.length === 0) return;

		// Generate cards HTML
		featuredTrack.innerHTML = featuredEvents.map((event, index) => 
			createFeaturedCard(event, index, featuredEvents.length)
		).join('');

		console.log(`Rendered ${featuredEvents.length} cards in carousel`);

		// Generate dots
		if (carouselDots) {
			carouselDots.innerHTML = featuredEvents.map((_, index) => 
				`<button class="carousel-dot ${index === 0 ? 'active' : ''}" data-index="${index}" aria-label="Go to slide ${index + 1}"></button>`
			).join('');

			// Add dot click handlers
			carouselDots.querySelectorAll('.carousel-dot').forEach(dot => {
				dot.addEventListener('click', () => {
					const index = parseInt(dot.dataset.index);
					scrollToSlide(index);
				});
			});
		}

		// Attach view details handlers
		attachViewDetailsHandlers();

		// Start autoplay
		startAutoplay();
	};

	// Attach view details button handlers
	const attachViewDetailsHandlers = () => {
		document.querySelectorAll('.btn-view-details').forEach(btn => {
			btn.addEventListener('click', () => {
				const eventId = parseInt(btn.dataset.eventId);
				const event = featuredEvents.find(e => e.id === eventId);
				if (event && window.openEventModal) {
					window.openEventModal(event);
				}
			});
		});
	};

	// Update carousel display
	const updateCarousel = () => {
		if (!featuredEvents || featuredEvents.length === 0) return;

		// Update button states
		if (prevBtn) prevBtn.disabled = currentSlide === 0;
		if (nextBtn) nextBtn.disabled = currentSlide === featuredEvents.length - 1;

		// Update dots
		if (carouselDots) {
			carouselDots.querySelectorAll('.carousel-dot').forEach((dot, index) => {
				dot.classList.toggle('active', index === currentSlide);
			});
		}
	};

	// Scroll to specific slide
	const scrollToSlide = (index, scrollViewport = true) => {
		currentSlide = Math.max(0, Math.min(index, featuredEvents.length - 1));
		const cards = featuredTrack.querySelectorAll('.featured-event-card');
		if (cards[currentSlide]) {
			if (scrollViewport) {
				// Manual navigation - scroll into view
				cards[currentSlide].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
			} else {
				// Auto-play - scroll track only without scrolling page
				const cardLeft = cards[currentSlide].offsetLeft;
				featuredTrack.scrollTo({ left: cardLeft, behavior: 'smooth' });
			}
		}
		updateCarousel();
		resetAutoplay();
	};

	// Next slide
	const nextSlide = () => {
		if (currentSlide < featuredEvents.length - 1) {
			scrollToSlide(currentSlide + 1, false);
		} else {
			// Loop back to first
			scrollToSlide(0, false);
		}
	};

	// Previous slide
	const prevSlide = () => {
		if (currentSlide > 0) {
			scrollToSlide(currentSlide - 1);
		}
	};

	// Autoplay
	const startAutoplay = () => {
		stopAutoplay();
		autoplayInterval = setInterval(() => {
			nextSlide();
		}, 5000); // Change slide every 5 seconds
	};

	const stopAutoplay = () => {
		if (autoplayInterval) {
			clearInterval(autoplayInterval);
			autoplayInterval = null;
		}
	};

	const resetAutoplay = () => {
		stopAutoplay();
		startAutoplay();
	};

	// Navigation button handlers
	if (prevBtn) {
		prevBtn.addEventListener('click', () => {
			prevSlide();
		});
	}

	if (nextBtn) {
		nextBtn.addEventListener('click', () => {
			nextSlide();
		});
	}

	// Check if time slot has changed (every minute)
	const checkTimeSlotChange = () => {
		const currentHour = new Date().getHours();
		let newTimeSlot;
		if (currentHour >= 0 && currentHour < 6) {
			newTimeSlot = '00:00';
		} else if (currentHour >= 6 && currentHour < 12) {
			newTimeSlot = '06:00';
		} else if (currentHour >= 12 && currentHour < 18) {
			newTimeSlot = '12:00';
		} else {
			newTimeSlot = '18:00';
		}

		if (cachedTimeSlot && newTimeSlot !== cachedTimeSlot) {
			console.log('Time slot changed, reloading featured events...');
			loadFeaturedEvents();
		}
	};

	// Check for time slot changes every minute
	setInterval(checkTimeSlotChange, 60000); // Check every 60 seconds

	// Initial load
	loadFeaturedEvents();
});
