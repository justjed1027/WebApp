document.addEventListener('DOMContentLoaded', () => {
  const step = document.body.getAttribute('data-step');

  // Utilities
  const setAccent = (hex) => {
    if (!hex) return;
    document.documentElement.style.setProperty('--accent', hex);
  };

  // PAGE 1: Basic Info
  if (step === '1') {
    const form = document.getElementById('basicInfoForm');
    const username = document.getElementById('username');
    const err = document.getElementById('usernameError');
    const file = document.getElementById('avatar');
    const preview = document.getElementById('avatarPreview');

    if (file && preview) {
      file.addEventListener('change', () => {
        const f = file.files && file.files[0];
        if (!f) return;
        const reader = new FileReader();
        reader.onload = (e) => {
          preview.style.background = `center/cover no-repeat url('${e.target.result}')`;
          preview.textContent = '';
        };
        reader.readAsDataURL(f);
      });
    }

    if (form) {
      form.addEventListener('submit', (e) => {
        if (!username.value.trim()) {
          e.preventDefault();
          err.hidden = false;
          username.focus();
        } else {
          err.hidden = true;
          // Persist basics (can be replaced with PHP later)
          localStorage.setItem('setup_username', username.value.trim());
        }
      });
    }
  }

  // PAGE 2: Courses/Interests
  if (step === '2') {
    const categoriesRoot = document.getElementById('categories');
    const search = document.getElementById('courseSearch');
    const topic = document.getElementById('topicFilter');
    const sort = document.getElementById('sortBy');
    const panel = document.getElementById('coursesPanel');

    // Expand panel on click focus
    if (panel) {
      panel.addEventListener('click', () => panel.classList.add('expanded'));
      panel.addEventListener('mouseleave', () => panel.classList.remove('expanded'));
    }

    // Make category titles collapsible
    if (categoriesRoot) {
      categoriesRoot.querySelectorAll('.category h3').forEach(h => {
        h.addEventListener('click', () => {
          const cat = h.closest('.category');
          cat.classList.toggle('collapsed');
        });
      });
    }

    // Toggle select on course click
    document.querySelectorAll('.course').forEach(el => {
      el.addEventListener('click', (e) => {
        if (e.target.tagName !== 'INPUT') {
          const chk = el.querySelector('input[type="checkbox"]');
          if (chk) chk.checked = !chk.checked;
        }
        el.classList.toggle('selected');
        const name = el.getAttribute('data-name');
        const selected = JSON.parse(localStorage.getItem('setup_courses') || '[]');
        if (el.classList.contains('selected')) {
          if (!selected.includes(name)) selected.push(name);
        } else {
          const idx = selected.indexOf(name);
          if (idx >= 0) selected.splice(idx, 1);
        }
        localStorage.setItem('setup_courses', JSON.stringify(selected));
      });
    });

    const applyFilters = () => {
      const q = (search?.value || '').trim().toLowerCase();
      const t = topic?.value || 'all';
      const cats = Array.from(document.querySelectorAll('.category'));

      cats.forEach(cat => {
        const topicOk = t === 'all' || cat.getAttribute('data-topic') === t;
        let anyVisible = false;
        cat.querySelectorAll('.course').forEach(c => {
          const name = (c.getAttribute('data-name') || '').toLowerCase();
          const show = topicOk && (q === '' || name.includes(q));
          c.style.display = show ? '' : 'none';
          if (show) anyVisible = true;
        });
        cat.style.display = anyVisible ? '' : 'none';
      });

      // Sort categories by title when alpha selected
      if (sort && sort.value === 'alpha') {
        const grid = document.getElementById('categories');
        const sorted = cats.slice().sort((a,b)=>a.querySelector('h3').textContent.localeCompare(b.querySelector('h3').textContent));
        sorted.forEach(el => grid.appendChild(el));
      }
      if (sort && sort.value === 'new') {
        // naive newest = reverse order for now
        const grid = document.getElementById('categories');
        const reversed = cats.slice().reverse();
        reversed.forEach(el => grid.appendChild(el));
      }
    };

    search && search.addEventListener('input', applyFilters);
    topic && topic.addEventListener('change', applyFilters);
    sort && sort.addEventListener('change', applyFilters);
    applyFilters();
  }

  // PAGE 3: Colors & Theme
  if (step === '3') {
    const swatches = document.querySelectorAll('.swatch');
    const cards = document.querySelectorAll('.theme-card');
    const finish = document.getElementById('finish');

    const savedColor = localStorage.getItem('setup_color');
    const savedTheme = localStorage.getItem('setup_theme') || 'mixed';
    if (savedColor) setAccent(savedColor);
    cards.forEach(c => c.classList.toggle('selected', c.getAttribute('data-theme') === savedTheme));
    if (savedColor) {
      swatches.forEach(s => s.classList.toggle('selected', s.getAttribute('data-color') === savedColor));
    }

    swatches.forEach(s => s.addEventListener('click', () => {
      swatches.forEach(x => x.classList.remove('selected'));
      s.classList.add('selected');
      const hex = s.getAttribute('data-color');
      localStorage.setItem('setup_color', hex);
      setAccent(hex);
    }));

    cards.forEach(card => card.addEventListener('click', () => {
      cards.forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      localStorage.setItem('setup_theme', card.getAttribute('data-theme'));
    }));

    finish && finish.addEventListener('click', () => {
      // Here you can POST to PHP later; for now we redirect
      window.location.href = '../landing/landing.php';
    });
  }
});
 