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
  const firstName = document.getElementById('firstName');
  const lastName = document.getElementById('lastName');
    const file = document.getElementById('avatar');
    const preview = document.getElementById('avatarPreview');
  const shell = document.querySelector('.setup-shell');



    if (preview) {
      const existingAvatar = preview.dataset.existingAvatar;
      if (existingAvatar) {
        preview.style.background = `center/cover no-repeat url('${existingAvatar}')`;
        preview.textContent = '';
      }
    }

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
      form.addEventListener('submit', () => {
        // Persist if provided (optional fields)
        const fn = firstName?.value.trim();
        const ln = lastName?.value.trim();
        if (fn) localStorage.setItem('setup_first_name', fn); else localStorage.removeItem('setup_first_name');
        if (ln) localStorage.setItem('setup_last_name', ln); else localStorage.removeItem('setup_last_name');
      });
    }
  }

  // PAGE 2: What You Already Know
  if (step === '2') {
    const categoriesRoot = document.getElementById('knownCategories');
    const search = document.getElementById('knownCourseSearch');
    const topic = document.getElementById('knownTopicFilter');
    const sort = document.getElementById('knownSortBy');
    const panel = document.getElementById('knownCoursesPanel');
    const nextBtn = document.getElementById('skillsNext');

    const updateNextState = () => {
      const anyChecked = !!document.querySelector('.course input[type="checkbox"]:checked');
      if (nextBtn) nextBtn.disabled = !anyChecked;
    };

    document.querySelectorAll('.course input[type="checkbox"]:checked').forEach(inp => {
      const label = inp.closest('.course');
      if (label) label.classList.add('selected');
    });

    document.querySelectorAll('.course input[type="checkbox"]').forEach(inp => {
      inp.addEventListener('change', () => {
        const label = inp.closest('.course');
        if (label) label.classList.toggle('selected', inp.checked);
        updateNextState();
      });
    });

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
        const selected = JSON.parse(localStorage.getItem('setup_known_courses') || '[]');
        if (el.classList.contains('selected')) {
          if (!selected.includes(name)) selected.push(name);
        } else {
          const idx = selected.indexOf(name);
          if (idx >= 0) selected.splice(idx, 1);
        }
        localStorage.setItem('setup_known_courses', JSON.stringify(selected));
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
        const grid = document.getElementById('knownCategories');
        const sorted = cats.slice().sort((a,b)=>a.querySelector('h3').textContent.localeCompare(b.querySelector('h3').textContent));
        sorted.forEach(el => grid.appendChild(el));
      }
      if (sort && sort.value === 'new') {
        // naive newest = reverse order for now
        const grid = document.getElementById('knownCategories');
        const reversed = cats.slice().reverse();
        reversed.forEach(el => grid.appendChild(el));
      }
    };

    search && search.addEventListener('input', applyFilters);
    topic && topic.addEventListener('change', applyFilters);
    sort && sort.addEventListener('change', applyFilters);
    applyFilters();
    updateNextState();
  }

  // PAGE 3: What You Want to Learn
  if (step === '3') {
    const categoriesRoot = document.getElementById('categories');
    const search = document.getElementById('courseSearch');
    const topic = document.getElementById('topicFilter');
    const sort = document.getElementById('sortBy');
    const panel = document.getElementById('coursesPanel');
    const nextBtn = document.getElementById('interestsNext');

    const updateNextState = () => {
      const anyChecked = !!document.querySelector('.course input[type="checkbox"]:checked');
      if (nextBtn) nextBtn.disabled = !anyChecked;
    };

    document.querySelectorAll('.course input[type="checkbox"]:checked').forEach(inp => {
      const label = inp.closest('.course');
      if (label) label.classList.add('selected');
    });

    document.querySelectorAll('.course input[type="checkbox"]').forEach(inp => {
      inp.addEventListener('change', () => {
        const label = inp.closest('.course');
        if (label) label.classList.toggle('selected', inp.checked);
        updateNextState();
      });
    });

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
    updateNextState();
  }

  // PAGE 4: Colors & Theme
  if (step === '4') {
    const swatches = document.querySelectorAll('.swatch');
    const cards = document.querySelectorAll('.theme-card');
    const navModeCards = document.querySelectorAll('.nav-mode-card');
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
      if (card.classList.contains('nav-mode-card')) {
        navModeCards.forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        localStorage.setItem('setup_navigation_mode', card.getAttribute('data-navigation'));
        return;
      }

      cards.forEach(c => {
        if (!c.classList.contains('nav-mode-card')) {
          c.classList.remove('selected');
        }
      });
      card.classList.add('selected');
      localStorage.setItem('setup_theme', card.getAttribute('data-theme'));
    }));

    finish && finish.addEventListener('click', async () => {
      const selectedSwatch = document.querySelector('.swatch.selected');
      const selectedThemeCard = document.querySelector('.theme-card.selected');
      const selectedNavCard = document.querySelector('.nav-mode-card.selected');
      const primaryColor = selectedSwatch ? selectedSwatch.getAttribute('data-color') : '#00D97E';
      const theme = selectedThemeCard && !selectedThemeCard.classList.contains('nav-mode-card')
        ? selectedThemeCard.getAttribute('data-theme')
        : 'mixed';
      const navigationMode = selectedNavCard ? selectedNavCard.getAttribute('data-navigation') : 'sidebar';

      finish.disabled = true;
      finish.textContent = 'Saving...';

      try {
        const response = await fetch('save_preferences.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            theme,
            primary_color: primaryColor,
            navigation_mode: navigationMode
          })
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Failed to save preferences.');
        }

        localStorage.setItem('theme', data.theme);
        localStorage.setItem('primary_color', data.primary_color_hex || primaryColor);
        localStorage.setItem('navigation_mode', data.navigation_mode || navigationMode);
        localStorage.setItem('setup_theme', data.theme);
        localStorage.setItem('setup_color', data.primary_color || primaryColor);
        localStorage.setItem('setup_navigation_mode', data.navigation_mode || navigationMode);

        window.location.href = '../dashboard2/dashboard2.php';
      } catch (error) {
        alert(error.message || 'Unable to save preferences right now. Please try again.');
        finish.disabled = false;
        finish.textContent = 'Finish';
      }
    });
  }
});
 