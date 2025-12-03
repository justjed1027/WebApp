// Featured events carousel
document.addEventListener('DOMContentLoaded', () => {
	const carousel = document.querySelector('.featured-carousel');
	const track = document.querySelector('.featured-track');
	if (!carousel || !track) return;

	const cards = Array.from(track.querySelectorAll('.featured-event-card'));
	const prev = carousel.querySelector('.carousel-btn.prev');
	const next = carousel.querySelector('.carousel-btn.next');
	const dotsWrap = carousel.querySelector('.carousel-dots');

	if (cards.length <= 1) {
		if (prev) prev.style.display = 'none';
		if (next) next.style.display = 'none';
		return;
	}

	// Build dots and set IDs
	dotsWrap.innerHTML = cards.map((_, i) => `<button class="dot" aria-label="Go to slide ${i + 1}"></button>`).join('');
	const dots = Array.from(dotsWrap.querySelectorAll('.dot'));
	cards.forEach((card, i) => { card.id = `featured-slide-${i}`; });

	let index = 0;

	const update = () => {
		dots.forEach((d, i) => d.classList.toggle('active', i === index));
		cards.forEach((c, i) => c.classList.toggle('active', i === index));
	};

	const goTo = (i, smooth = true) => {
		index = (i + cards.length) % cards.length;
		const target = cards[index];
		const left = target.offsetLeft - track.offsetLeft;
		track.scrollTo({ left, behavior: smooth ? 'smooth' : 'auto' });
		update();
	};

	prev.addEventListener('click', () => goTo(index - 1));
	next.addEventListener('click', () => goTo(index + 1));
	dots.forEach((d, i) => d.addEventListener('click', () => goTo(i)));

	// Sync index after manual scrolling
	let scrollTimeout;
	track.addEventListener('scroll', () => {
		clearTimeout(scrollTimeout);
		scrollTimeout = setTimeout(() => {
			const current = track.scrollLeft + track.clientWidth / 2;
			let closest = 0;
			let dist = Infinity;
			cards.forEach((card, i) => {
				const center = card.offsetLeft + card.clientWidth / 2;
				const d = Math.abs(center - current);
				if (d < dist) { dist = d; closest = i; }
			});
			index = closest;
			update();
		}, 100);
	});

	// Autoplay with pause on hover/visibility
	let timer = setInterval(() => goTo(index + 1), 3000);
	const pause = () => { clearInterval(timer); };
	const resume = () => { timer = setInterval(() => goTo(index + 1), 3000); };
	carousel.addEventListener('mouseenter', pause);
	carousel.addEventListener('mouseleave', resume);
	document.addEventListener('visibilitychange', () => {
		if (document.hidden) pause(); else resume();
	});

	// Keyboard support
	carousel.setAttribute('tabindex', '0');
	carousel.addEventListener('keydown', (e) => {
		if (e.key === 'ArrowLeft') { e.preventDefault(); goTo(index - 1); }
		if (e.key === 'ArrowRight') { e.preventDefault(); goTo(index + 1); }
	});

	// Initial state
	goTo(0, false);
});

// Upcoming events: show only first 3 on load with toggle to expand/collapse
document.addEventListener('DOMContentLoaded', () => {
	const grid = document.querySelector('.upcoming-events-grid');
	if (!grid) return;

	// Give the grid an id for aria-controls if not present
	if (!grid.id) grid.id = 'upcomingGrid';

	const cards = Array.from(grid.querySelectorAll('.event-card'));
	const wrapper = document.getElementById('upcomingToggleWrapper');
	const btn = document.getElementById('toggleUpcoming');
	if (!btn || !wrapper) return;

	const limit = 3;
	const labelSpan = btn.querySelector('.btn-label');
	const setLabel = (text) => {
		if (labelSpan) labelSpan.textContent = text;
		else btn.textContent = text;
	};
	const hideExtra = () => {
		cards.forEach((c, i) => c.classList.toggle('hidden-card', i >= limit));
		btn.setAttribute('aria-expanded', 'false');
		setLabel('Show all upcoming events');
	};
	const showAll = () => {
		cards.forEach((c) => c.classList.remove('hidden-card'));
		btn.setAttribute('aria-expanded', 'true');
		setLabel('Show fewer');
	};

	if (cards.length > limit) {
		wrapper.hidden = false;
		hideExtra();
		let expanded = false;
		btn.addEventListener('click', () => {
			expanded = !expanded;
			if (expanded) showAll();
			else hideExtra();
		});
	} else {
		wrapper.hidden = true;
	}
});

// Past events: mirror the same show 3 + toggle behavior
document.addEventListener('DOMContentLoaded', () => {
	const grid = document.querySelector('.past-events-grid');
	if (!grid) return;

	if (!grid.id) grid.id = 'pastGrid';

	const cards = Array.from(grid.querySelectorAll('.event-card'));
	const wrapper = document.getElementById('pastToggleWrapper');
	const btn = document.getElementById('togglePast');
	if (!btn || !wrapper) return;

	const limit = 3;
	const labelSpan = btn.querySelector('.btn-label');
	const setLabel = (text) => {
		if (labelSpan) labelSpan.textContent = text;
		else btn.textContent = text;
	};
	const hideExtra = () => {
		cards.forEach((c, i) => c.classList.toggle('hidden-card', i >= limit));
		btn.setAttribute('aria-expanded', 'false');
		setLabel('Show all past events');
	};
	const showAll = () => {
		cards.forEach((c) => c.classList.remove('hidden-card'));
		btn.setAttribute('aria-expanded', 'true');
		setLabel('Show fewer');
	};

	if (cards.length > limit) {
		wrapper.hidden = false;
		hideExtra();
		let expanded = false;
		btn.addEventListener('click', () => {
			expanded = !expanded;
			if (expanded) showAll();
			else hideExtra();
		});
	} else {
		wrapper.hidden = true;
	}
});