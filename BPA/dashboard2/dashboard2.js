const dashboard2Grid = document.getElementById('dashboard2Grid');
const dashboard2Cards = Array.from(document.querySelectorAll('[data-card]'));

function setCardFlipped(card, flipped) {
  card.classList.toggle('is-flipped', flipped);
}

// Pop front content in after flip-back animation completes
dashboard2Cards.forEach(card => {
  const inner = card.querySelector('.dashboard2-card-inner');
  let hasBeenFlipped = false;

  inner.addEventListener('animationstart', (e) => {
    if (e.animationName === 'd2-flip-to-back') hasBeenFlipped = true;
  });

  inner.addEventListener('animationend', (e) => {
    if (e.animationName !== 'd2-flip-to-front' || !hasBeenFlipped) return;
    const frontEls = card.querySelectorAll('.dashboard2-card-topline, .dashboard2-front-copy');
    frontEls.forEach(el => {
      el.classList.remove('is-entering');
      void el.offsetWidth; // force reflow so animation restarts
      el.classList.add('is-entering');
    });
  });
});

dashboard2Grid.addEventListener('click', (event) => {
  if (event.target.closest('a')) {
    return;
  }

  const flipButton = event.target.closest('[data-flip]');
  const card = event.target.closest('[data-card]');
  if (card) {
    if (flipButton) {
      event.preventDefault();
    }
    setCardFlipped(card, !card.classList.contains('is-flipped'));
  }
});
