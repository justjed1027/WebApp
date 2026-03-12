const dashboard2Grid = document.getElementById('dashboard2Grid');
const dashboard2Cards = Array.from(document.querySelectorAll('[data-card]'));
const bucketSubjects = window.d2BucketSubjects || {};

const ARROW_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" /></svg>';

function escapeHtml(str) {
  return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function renderSubjectList(card) {
  const list = card.querySelector('[data-subject-list]');
  if (!list) return;
  let subjectsByBucket = {};
  try { subjectsByBucket = JSON.parse(card.dataset.subjects || '{}'); } catch (e) {}
  const activeBox = card.querySelector('.d2-bucket-box.is-active');
  const bucketName = activeBox ? activeBox.dataset.bucket : 'My Expertise';
  const subjects = subjectsByBucket[bucketName] || [];
  if (subjects.length === 0) {
    list.innerHTML = '<p class="d2-no-subjects">No subjects in this category.</p>';
    return;
  }
  list.innerHTML = subjects.map(s =>
    `<a class="dashboard2-subject-option" href="../courses/course-detail.php?id=${s.id}"><span>${escapeHtml(s.name)}</span>${ARROW_SVG}</a>`
  ).join('');
}

function selectBucket(card, bucketName) {
  card.querySelectorAll('.d2-bucket-box').forEach(box => {
    box.classList.toggle('is-active', box.dataset.bucket === bucketName);
  });
  renderSubjectList(card);
}

// Initialise all cards using each card's profile-based default bucket
dashboard2Cards.forEach(card => {
  const defaultBucket = card.dataset.defaultBucket || 'My Expertise';
  selectBucket(card, defaultBucket);
});

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
  // Bucket box click — switch category, do not flip
  const bucketBox = event.target.closest('.d2-bucket-box');
  if (bucketBox) {
    event.stopPropagation();
    const card = bucketBox.closest('[data-card]');
    if (card) selectBucket(card, bucketBox.dataset.bucket);
    return;
  }

  if (event.target.closest('a')) {
    return;
  }

  const card = event.target.closest('[data-card]');
  if (card) {
    setCardFlipped(card, !card.classList.contains('is-flipped'));
  }
});
