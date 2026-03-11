const dashboard2Grid = document.getElementById('dashboard2Grid');
const dashboard2Cards = Array.from(document.querySelectorAll('[data-card]'));

function setCardFlipped(card, flipped) {
  if (flipped) {
    card.classList.remove('is-flipping-back');
    card.classList.add('is-flipped');
  } else {
    card.classList.remove('is-flipped');
    card.classList.add('is-flipping-back');
    const inner = card.querySelector('.dashboard2-card-inner');
    inner.addEventListener('animationend', () => {
      card.classList.remove('is-flipping-back');
      // Pop front content
      const frontEls = card.querySelectorAll('.dashboard2-card-topline, .dashboard2-front-copy');
      frontEls.forEach(el => {
        el.classList.remove('is-entering');
        void el.offsetWidth;
        el.classList.add('is-entering');
      });
    }, { once: true });
  }
}

dashboard2Grid.addEventListener('click', (event) => {
  if (event.target.closest('a')) {
    return;
  }

  const card = event.target.closest('[data-card]');
  if (card) {
    setCardFlipped(card, !card.classList.contains('is-flipped'));
  }
});
