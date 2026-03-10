// Side Content Component JavaScript (in-flow layout)
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const mobileBreakpoint = window.matchMedia('(max-width: 1024px)');
  let lastScrollY = window.scrollY;

  const closeMobileSidebar = () => {
    if (!sidebar) return;

    sidebar.classList.remove('mobile-open');
    document.body.classList.remove('mobile-sidebar-open');

    const toggle = document.querySelector('.mobile-sidebar-toggle');
    if (toggle) {
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Open navigation menu');
    }
  };

  const ensureMobileSidebar = () => {
    if (!sidebar || !document.body.classList.contains('has-side-content')) {
      return;
    }

    let toggle = document.querySelector('.mobile-sidebar-toggle');
    if (!toggle) {
      toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'mobile-sidebar-toggle';
      toggle.setAttribute('aria-label', 'Open navigation menu');
      toggle.setAttribute('aria-controls', 'sidebar');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16" aria-hidden="true">
          <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
        </svg>`;
      document.body.appendChild(toggle);
    }

    let overlay = document.querySelector('.mobile-sidebar-overlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.className = 'mobile-sidebar-overlay';
      overlay.setAttribute('aria-hidden', 'true');
      document.body.appendChild(overlay);
    }

    let closeButton = sidebar.querySelector('.mobile-sidebar-close');
    if (!closeButton) {
      closeButton = document.createElement('button');
      closeButton.type = 'button';
      closeButton.className = 'mobile-sidebar-close';
      closeButton.setAttribute('aria-label', 'Close navigation menu');
      closeButton.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
          <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
        </svg>`;
      sidebar.appendChild(closeButton);
    }

    if (!toggle.dataset.mobileSidebarBound) {
      toggle.addEventListener('click', () => {
        const isOpen = sidebar.classList.toggle('mobile-open');
        document.body.classList.toggle('mobile-sidebar-open', isOpen);
        toggle.setAttribute('aria-expanded', String(isOpen));
        toggle.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
      });
      toggle.dataset.mobileSidebarBound = 'true';
    }

    if (!overlay.dataset.mobileSidebarBound) {
      overlay.addEventListener('click', closeMobileSidebar);
      overlay.dataset.mobileSidebarBound = 'true';
    }

    if (!closeButton.dataset.mobileSidebarBound) {
      closeButton.addEventListener('click', closeMobileSidebar);
      closeButton.dataset.mobileSidebarBound = 'true';
    }

    const navLinks = Array.from(sidebar.querySelectorAll('.nav-link'));
    navLinks.forEach((link) => {
      if (link.dataset.mobileSidebarBound) {
        return;
      }

      link.addEventListener('click', () => {
        if (mobileBreakpoint.matches) {
          closeMobileSidebar();
        }
      });
      link.dataset.mobileSidebarBound = 'true';
    });

    document.body.classList.add('mobile-sidebar-enabled');
  };

  const ensureMobileBrand = () => {
    if (!document.body.classList.contains('has-side-content')) {
      return;
    }

    let brand = document.querySelector('.mobile-site-brand');
    if (brand) {
      return;
    }

    const path = window.location.pathname;
    const marker = '/BPA/';
    const idx = path.indexOf(marker);
    const appBase = idx >= 0 ? path.substring(0, idx + marker.length - 1) : '';

    brand = document.createElement('a');
    brand.className = 'mobile-site-brand';
    brand.href = `${appBase}/courses/courses.php`;
    brand.setAttribute('aria-label', 'SkillSwap home');
    brand.innerHTML = `
      <img src="${appBase}/images/skillswaplogotrans.png" alt="SkillSwap logo">
      <span>SkillSwap</span>`;

    document.body.appendChild(brand);
  };

  const syncMobileSidebarState = () => {
    if (!sidebar) {
      return;
    }

    if (!mobileBreakpoint.matches) {
      closeMobileSidebar();
    }

    const toggle = document.querySelector('.mobile-sidebar-toggle');
    if (toggle) {
      const isOpen = sidebar.classList.contains('mobile-open') && mobileBreakpoint.matches;
      toggle.setAttribute('aria-expanded', String(isOpen));
      toggle.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
    }
  };

  const updateMobileTopControls = () => {
    if (!document.body.classList.contains('has-side-content')) {
      return;
    }

    if (!mobileBreakpoint.matches || document.body.classList.contains('mobile-sidebar-open')) {
      document.body.classList.remove('mobile-top-controls-hidden');
      lastScrollY = window.scrollY;
      return;
    }

    const currentScrollY = window.scrollY;
    const delta = currentScrollY - lastScrollY;

    if (currentScrollY <= 20 || delta < -4) {
      document.body.classList.remove('mobile-top-controls-hidden');
    } else if (delta > 4 && currentScrollY > 60) {
      document.body.classList.add('mobile-top-controls-hidden');
    }

    lastScrollY = currentScrollY;
  };

  // Settings confirmation modal wiring (shared across pages with the sidebar)
  const ensureSettingsModal = () => {
    let modal = document.getElementById('settingsConfirmModal');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'settingsConfirmModal';
    modal.className = 'settings-modal-backdrop hidden';
    modal.innerHTML = `
      <div class="settings-modal" role="dialog" aria-modal="true" aria-labelledby="settings-modal-title">
        <div class="settings-modal-header">
          <h3 id="settings-modal-title">Edit your account?</h3>
          <button class="settings-close" type="button" aria-label="Close">&times;</button>
        </div>
        <p class="settings-modal-body">You will be redirected to account setup to review and update your details, interests, and skills.</p>
        <div class="settings-modal-actions">
          <button type="button" class="btn-secondary" data-action="cancel-settings">Cancel</button>
          <button type="button" class="btn-primary" data-action="confirm-settings">Yes, edit account</button>
        </div>
      </div>`;
    document.body.appendChild(modal);
    return modal;
  };

  const getSetupUrl = () => {
    const path = window.location.pathname;
    const marker = '/BPA/';
    const idx = path.indexOf(marker);
    const base = idx >= 0 ? path.substring(0, idx + marker.length - 1) : '';
    return `${base}/setup/page1.php`;
  };

  const openSettingsModal = (event) => {
    event.preventDefault();
    const modal = ensureSettingsModal();
    modal.classList.remove('hidden');
  };

  const bindModalEvents = () => {
    const modal = ensureSettingsModal();
    const close = () => modal.classList.add('hidden');
    const confirmBtn = modal.querySelector('[data-action="confirm-settings"]');
    const cancelBtn = modal.querySelector('[data-action="cancel-settings"]');
    const closeBtn = modal.querySelector('.settings-close');

    if (confirmBtn) {
      confirmBtn.addEventListener('click', () => {
        window.location.href = getSetupUrl();
      });
    }
    if (cancelBtn) cancelBtn.addEventListener('click', close);
    if (closeBtn) closeBtn.addEventListener('click', close);
    modal.addEventListener('click', (e) => {
      if (e.target === modal) close();
    });
  };

  const wireSettingsLinks = () => {
    const links = Array.from(document.querySelectorAll('.nav-link'));
    links.forEach((link) => {
      const tooltip = (link.getAttribute('data-tooltip') || '').toLowerCase();
      const text = (link.textContent || '').toLowerCase();
      if (tooltip === 'settings' || tooltip === 'edit user' || text.includes('settings') || text.includes('edit user')) {
        link.addEventListener('click', openSettingsModal);
        link.setAttribute('role', 'button');
        link.setAttribute('href', '#');
      }
    });
  };

  bindModalEvents();
  wireSettingsLinks();
  ensureMobileSidebar();
  ensureMobileBrand();
  syncMobileSidebarState();
  updateMobileTopControls();

  window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeMobileSidebar();
    }
  });

  if (typeof mobileBreakpoint.addEventListener === 'function') {
    mobileBreakpoint.addEventListener('change', syncMobileSidebarState);
  } else if (typeof mobileBreakpoint.addListener === 'function') {
    mobileBreakpoint.addListener(syncMobileSidebarState);
  }

  window.addEventListener('scroll', updateMobileTopControls, { passive: true });
});
