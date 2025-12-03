// Event detail modal
document.addEventListener('DOMContentLoaded', () => {
	const modal = document.getElementById('eventModal');
	const modalOverlay = document.getElementById('modalOverlay');
	const modalClose = document.getElementById('modalClose');
	const btnExpandDescription = document.getElementById('btnExpandDescription');
	
	if (!modal) return;

	// Sample event data (would come from database in production)
	const eventData = {
		'Tech Career Fair 2023': {
			image: 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=1200&h=600&fit=crop',
			date: 'October 15, 2023',
			time: '10:00 AM - 4:00 PM',
			location: 'Student Center Grand Hall',
			attending: '342 attending',
			tags: ['#career', '#networking', '#tech'],
			description: 'Join us for the biggest tech career fair of the year! Connect with leading tech companies, startups, and industry professionals. This is your chance to explore career opportunities, network with recruiters, and learn about the latest trends in technology. Whether you\'re looking for internships, full-time positions, or just want to expand your professional network, this event is perfect for you. We\'ll have representatives from over 50 companies, resume review sessions, mock interviews, and keynote speakers from industry leaders. Bring multiple copies of your resume and dress professionally. Light refreshments will be provided throughout the day.',
			category: 'Career Fair',
			organizer: 'Career Services Center',
			capacity: '500 spots',
			registration: 'Open until Oct 14',
			creator: 'Sarah Johnson',
			creatorRole: 'Career Services Director'
		},
		'Machine Learning Workshop': {
			image: 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1200&h=600&fit=crop',
			date: 'October 18, 2023',
			time: '2:00 PM - 5:00 PM',
			location: 'Technology Building Room 305',
			attending: '124 attending',
			tags: ['#workshop', '#machine learning', '#python'],
			description: 'Dive into the world of machine learning in this hands-on workshop! Learn the fundamentals of ML algorithms, build your first predictive model, and understand how AI is transforming industries. This workshop is designed for beginners with basic Python knowledge. We\'ll cover supervised learning, neural networks, and practical applications. You\'ll work with real datasets and leave with a portfolio project. Laptops required - please bring your own device with Python 3.8+ installed.',
			category: 'Workshop',
			organizer: 'AI & Data Science Club',
			capacity: '150 spots',
			registration: 'Open until Oct 17',
			creator: 'Dr. Michael Chen',
			creatorRole: 'CS Professor'
		},
		'Design Thinking Workshop': {
			image: 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=1200&h=600&fit=crop',
			date: 'October 22, 2023',
			time: '1:00 PM - 4:00 PM',
			location: 'Innovation Hub',
			attending: '89 attending',
			tags: ['#design', '#workshop', '#UX'],
			description: 'Master the art of design thinking! This interactive workshop will teach you how to approach problems creatively and develop user-centered solutions. Perfect for entrepreneurs, designers, and anyone interested in innovation. Learn the five stages: empathize, define, ideate, prototype, and test. Work in teams on real-world challenges and present your solutions.',
			category: 'Workshop',
			organizer: 'Design Innovation Lab',
			capacity: '100 spots',
			registration: 'Open until Oct 21',
			creator: 'Emily Rodriguez',
			creatorRole: 'UX Design Lead'
		}
	};

	// Open modal function
	const openModal = (eventTitle) => {
		const data = eventData[eventTitle];
		if (!data) return;

		// Populate modal with event data
		document.getElementById('modalImage').src = data.image;
		document.getElementById('modalImage').alt = eventTitle;
		document.getElementById('modalTitle').textContent = eventTitle;
		document.getElementById('modalDate').innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/></svg><span>${data.date}</span>`;
		document.getElementById('modalTime').innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/></svg><span>${data.time}</span>`;
		document.getElementById('modalLocation').innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/></svg><span>${data.location}</span>`;
		document.getElementById('modalAttending').innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/></svg><span>${data.attending}</span>`;
		document.getElementById('modalTags').innerHTML = data.tags.map(tag => `<span class="event-tag">${tag}</span>`).join('');
		document.getElementById('modalDescription').textContent = data.description;
		document.getElementById('modalCategory').textContent = data.category;
		document.getElementById('modalOrganizer').textContent = data.organizer;
		document.getElementById('modalCapacity').textContent = data.capacity;
		document.getElementById('modalRegistration').textContent = data.registration;
		document.getElementById('modalCreator').textContent = data.creator;
		document.getElementById('modalCreatorRole').textContent = data.creatorRole;

		// Show modal
		modal.hidden = false;
		document.body.style.overflow = 'hidden';
	};

	// Close modal function
	const closeModal = () => {
		modal.hidden = true;
		document.body.style.overflow = '';
		// Reset description expansion
		const descText = document.getElementById('modalDescription');
		descText.classList.remove('expanded');
		btnExpandDescription.textContent = 'Read more';
	};

	// Attach click handlers to all "View Details" buttons
	document.querySelectorAll('.btn-view-details').forEach(btn => {
		btn.addEventListener('click', (e) => {
			const card = e.target.closest('.event-card');
			const title = card.querySelector('.event-title').textContent;
			openModal(title);
		});
	});

	// Close modal handlers
	modalClose.addEventListener('click', closeModal);
	modalOverlay.addEventListener('click', closeModal);
	
	// Escape key to close
	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && !modal.hidden) closeModal();
	});

	// Expand/collapse description
	btnExpandDescription.addEventListener('click', () => {
		const descText = document.getElementById('modalDescription');
		descText.classList.toggle('expanded');
		btnExpandDescription.textContent = descText.classList.contains('expanded') ? 'Read less' : 'Read more';
	});
});
