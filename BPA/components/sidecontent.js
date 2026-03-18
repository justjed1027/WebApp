// Side Content Component JavaScript (in-flow layout)
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const mobileBreakpoint = window.matchMedia('(max-width: 1024px)');
  const topNavPhoneBreakpoint = window.matchMedia('(max-width: 810px)');
  let lastScrollY = window.scrollY;
  let lastTopNavScrollY = window.scrollY;
  let currentHomePreference = 'dashboard2';
  let preferredNavigationMode = 'sidebar';
  let currentPrimaryHex = null;

  const clamp = (num, min, max) => Math.min(max, Math.max(min, num));

  const toHexColor = (value) => {
    const color = (value || '').trim();
    if (!color) return null;
    if (/^silver$/i.test(color)) return '#C0C0C0';
    if (/^#[0-9a-fA-F]{6}$/.test(color)) return color.toUpperCase();
    if (/^[0-9a-fA-F]{6}$/.test(color)) return `#${color.toUpperCase()}`;
    return null;
  };

  const adjustHex = (hex, amount) => {
    const safeHex = toHexColor(hex);
    if (!safeHex) return null;

    const r = parseInt(safeHex.slice(1, 3), 16);
    const g = parseInt(safeHex.slice(3, 5), 16);
    const b = parseInt(safeHex.slice(5, 7), 16);

    const nr = clamp(r + amount, 0, 255);
    const ng = clamp(g + amount, 0, 255);
    const nb = clamp(b + amount, 0, 255);

    return `#${nr.toString(16).padStart(2, '0')}${ng.toString(16).padStart(2, '0')}${nb.toString(16).padStart(2, '0')}`.toUpperCase();
  };

  const getLogoSrc = () => {
    const appBase = getAppBase();
    if (currentPrimaryHex) {
      if (currentPrimaryHex.toUpperCase() === '#C0C0C0') {
        return `${appBase}/images/LogoSilver.png`;
      }
      const colorKey = currentPrimaryHex.replace('#', '').toLowerCase();
      return `${appBase}/images/logo${colorKey}.png`;
    }
    return `${appBase}/images/skillswaplogotrans.png`;
  };

  const updateLogoImages = () => {
    const appBase = getAppBase();
    const newSrc = getLogoSrc();
    const fallbackSrc = `${appBase}/images/skillswaplogotrans.png`;
    document.querySelectorAll('.top-nav-brand img, .mobile-site-brand img').forEach((img) => {
      img.onerror = function () { this.src = fallbackSrc; this.onerror = null; };
      img.src = newSrc;
    });
  };

  const applyPrimaryColor = (value) => {
    const hex = toHexColor(value);
    if (!hex) return;

    currentPrimaryHex = hex;
    const hover = adjustHex(hex, -25) || hex;
    document.documentElement.style.setProperty('--primary-color', hex);
    document.documentElement.style.setProperty('--primary-hover', hover);
    document.documentElement.style.setProperty('--primary-light', `color-mix(in srgb, ${hex} 15%, transparent)`);
    // Also set on body so it overrides any body.light-mode { --primary-color } CSS declarations
    document.body.style.setProperty('--primary-color', hex);
    document.body.style.setProperty('--primary-hover', hover);
    document.body.style.setProperty('--primary-light', `color-mix(in srgb, ${hex} 15%, transparent)`);
    updateLogoImages();
  };

  const applyThemePreference = (theme) => {
    const mode = (theme || '').toLowerCase();
    if (mode === 'light') {
      document.body.classList.add('light-mode');
      return;
    }
    document.body.classList.remove('light-mode');
  };

  const getAppBase = () => {
    const path = window.location.pathname;
    const marker = '/BPA/';
    const idx = path.indexOf(marker);
    return idx >= 0 ? path.substring(0, idx + marker.length - 1) : '';
  };

  const normalizeHomePreference = (value) => {
    const normalized = (value || '').toLowerCase().trim();
    if (normalized === 'course') return 'courses';
    if (normalized === 'courses') return 'courses';
    return 'dashboard2';
  };

  const getHomeUrl = (homePreference = currentHomePreference) => {
    const appBase = getAppBase();
    const home = normalizeHomePreference(homePreference);
    return `${appBase}/${home}/${home}.php`;
  };

  const applyHomePreference = (homePreference) => {
    currentHomePreference = normalizeHomePreference(homePreference);
    const homeUrl = getHomeUrl(currentHomePreference);

    if (sidebar) {
      const dashboardLinks = sidebar.querySelectorAll('.nav-link[data-tooltip="Dashboard"]');
      dashboardLinks.forEach((link) => {
        link.setAttribute('href', homeUrl);
      });
    }

    const topBrand = document.querySelector('.top-nav-brand');
    if (topBrand) {
      topBrand.setAttribute('href', homeUrl);
    }

    const mobileBrand = document.querySelector('.mobile-site-brand');
    if (mobileBrand) {
      mobileBrand.setAttribute('href', homeUrl);
    }
  };

  const removeTopNav = () => {
    const topNav = document.getElementById('topNavShell');
    if (topNav) {
      topNav.remove();
    }

    const restoreBtn = document.getElementById('topNavRestoreBtn');
    if (restoreBtn) {
      restoreBtn.remove();
    }
  };

  const ensureTopNavRestoreButton = () => {
    let restoreBtn = document.getElementById('topNavRestoreBtn');
    if (restoreBtn) {
      return restoreBtn;
    }

    restoreBtn = document.createElement('button');
    restoreBtn.type = 'button';
    restoreBtn.id = 'topNavRestoreBtn';
    restoreBtn.className = 'top-nav-restore';
    restoreBtn.setAttribute('aria-label', 'Show top navigation');
    restoreBtn.setAttribute('title', 'Show top navigation');
    restoreBtn.innerHTML = `
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
        <path d="M8.53 4.47a.75.75 0 0 0-1.06 0L3.22 8.72a.75.75 0 0 0 1.06 1.06L8 6.06l3.72 3.72a.75.75 0 1 0 1.06-1.06z"/>
      </svg>`;
    restoreBtn.addEventListener('click', () => {
      document.body.classList.remove('top-nav-scrolled-hidden');
      lastTopNavScrollY = window.scrollY;
    });
    document.body.appendChild(restoreBtn);
    return restoreBtn;
  };

  const getLinkLabel = (link) => {
    const text = link.querySelector('span')?.textContent?.trim();
    if (text) return text;
    const tooltip = link.getAttribute('data-tooltip');
    if (tooltip) return tooltip;
    return 'Navigation';
  };

  const buildTopNav = () => {
    if (!sidebar || document.getElementById('topNavShell')) {
      return;
    }

    const appBase = getAppBase();
    const navLinks = Array.from(sidebar.querySelectorAll('.sidebar-middle .nav-link'));
    const bottomLinks = Array.from(sidebar.querySelectorAll('.sidebar-bottom .nav-link'));
    const settingsLink = bottomLinks.find((link) => {
      const tooltip = (link.getAttribute('data-tooltip') || '').toLowerCase();
      const text = (link.textContent || '').toLowerCase();
      return tooltip === 'settings' || tooltip === 'edit user' || text.includes('settings') || text.includes('edit user');
    });
    const logoutLink = bottomLinks.find((link) => (link.getAttribute('href') || '').includes('action=logout'));

    const shell = document.createElement('div');
    shell.id = 'topNavShell';
    shell.className = 'top-nav-shell';

    const left = document.createElement('div');
    left.className = 'top-nav-left';
    const topNavLogoSrc = getLogoSrc();
    const topNavFallbackSrc = `${appBase}/images/skillswaplogotrans.png`;
    left.innerHTML = `
      <a class="top-nav-brand" href="${getHomeUrl()}" aria-label="SkillSwap home">
        <img src="${topNavLogoSrc}" alt="SkillSwap logo" onerror="this.src='${topNavFallbackSrc}';this.onerror=null;">
      </a>`;

    const sidebarAvatar = sidebar.querySelector('.profile-avatar');
    const userAvatarLink = document.createElement('a');
    userAvatarLink.className = 'top-nav-user-avatar';
    userAvatarLink.href = `${appBase}/profile/profile.php`;
    userAvatarLink.setAttribute('title', 'View Profile');
    if (sidebarAvatar) {
      userAvatarLink.innerHTML = sidebarAvatar.innerHTML;
    }
    left.appendChild(userAvatarLink);

    const brandDivider = document.createElement('span');
    brandDivider.className = 'top-nav-divider';
    brandDivider.setAttribute('aria-hidden', 'true');
    left.appendChild(brandDivider);

    navLinks.forEach((link) => {
      const topLink = document.createElement('a');
      topLink.className = `top-nav-link${link.classList.contains('active') ? ' active' : ''}`;
      topLink.href = link.getAttribute('href') || '#';
      topLink.textContent = getLinkLabel(link);
      left.appendChild(topLink);
    });

    const center = document.createElement('div');
    center.className = 'top-nav-center';

    const right = document.createElement('div');
    right.className = 'top-nav-right';

    if (settingsLink) {
      const settingsBtn = document.createElement('a');
      settingsBtn.className = 'top-nav-control top-nav-settings';
      settingsBtn.href = '#';
      settingsBtn.setAttribute('title', 'Edit User');
      settingsBtn.innerHTML = settingsLink.querySelector('svg')?.outerHTML || '<span>⚙</span>';
      right.appendChild(settingsBtn);
    }

    if (logoutLink) {
      const logoutBtn = document.createElement('a');
      logoutBtn.className = 'top-nav-control';
      logoutBtn.href = logoutLink.getAttribute('href') || '#';
      logoutBtn.setAttribute('title', 'Log Out');
      logoutBtn.innerHTML = logoutLink.querySelector('svg')?.outerHTML || '<span>↪</span>';
      right.appendChild(logoutBtn);
    }

    const themeBtn = document.createElement('button');
    themeBtn.type = 'button';
    themeBtn.className = 'top-nav-control top-nav-theme';
    themeBtn.setAttribute('title', 'Toggle Theme');
    themeBtn.innerHTML = `
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
        <path d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278"/>
      </svg>`;
    right.appendChild(themeBtn);

    const hideBtn = document.createElement('button');
    hideBtn.type = 'button';
    hideBtn.className = 'top-nav-control top-nav-hide';
    hideBtn.setAttribute('title', 'Hide top navigation');
    hideBtn.setAttribute('aria-label', 'Hide top navigation');
    hideBtn.innerHTML = `
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
        <path d="M3.22 10.78a.75.75 0 0 0 1.06 0L8 7.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L8.53 5.47a.75.75 0 0 0-1.06 0L3.22 9.72a.75.75 0 0 0 0 1.06"/>
      </svg>`;
    right.appendChild(hideBtn);

    shell.appendChild(left);
    shell.appendChild(center);
    shell.appendChild(right);
    document.body.appendChild(shell);
    ensureTopNavRestoreButton();

    const settingsBtn = shell.querySelector('.top-nav-settings');
    if (settingsBtn && settingsLink) {
      settingsBtn.addEventListener('click', (event) => {
        event.preventDefault();
        settingsLink.click();
      });
    }

    themeBtn.addEventListener('click', () => {
      const nativeToggle = document.getElementById('themeToggle');
      if (nativeToggle) {
        nativeToggle.click();
        return;
      }

      document.body.classList.toggle('light-mode');
      const isLight = document.body.classList.contains('light-mode');
      localStorage.setItem('theme', isLight ? 'light' : 'dark');
    });

    hideBtn.addEventListener('click', () => {
      document.body.classList.add('top-nav-scrolled-hidden');
      lastTopNavScrollY = window.scrollY;
    });
  };

  const applyNavigationMode = (mode) => {
    const navMode = (mode || '').toLowerCase() === 'top' ? 'top' : 'sidebar';
    preferredNavigationMode = navMode;
    const isTopMode = navMode === 'top' && !topNavPhoneBreakpoint.matches;
    document.body.classList.toggle('top-nav-mode', isTopMode);

    if (isTopMode) {
      closeMobileSidebar();
      buildTopNav();
      return;
    }

    removeTopNav();
  };

  const applyLocalPreferences = () => {
    const localTheme = localStorage.getItem('theme');
    const localPrimary = localStorage.getItem('primary_color');
    const localNavigation = localStorage.getItem('navigation_mode');
    const localHomePreference = localStorage.getItem('home_preference');
    if (localTheme) {
      applyThemePreference(localTheme);
    }
    // Skip localStorage color if the server already set the correct color inline on <html>
    const serverColor = document.documentElement.style.getPropertyValue('--primary-color').trim();
    if (localPrimary && !serverColor) {
      applyPrimaryColor(localPrimary);
    } else if (serverColor) {
      applyPrimaryColor(serverColor);
    }
    if (localNavigation) {
      applyNavigationMode(localNavigation);
    }
    if (localHomePreference) {
      applyHomePreference(localHomePreference);
    }
  };

  const loadPreferencesFromDatabase = async () => {
    try {
      const response = await fetch(`${getAppBase()}/components/get_user_preferences.php`, {
        credentials: 'same-origin'
      });

      if (!response.ok) {
        return;
      }

      const data = await response.json();
      if (!data || !data.success) {
        return;
      }

      applyThemePreference(data.theme);
      applyPrimaryColor(data.primary_color_hex || data.primary_color);
      applyNavigationMode(data.navigation_mode);
      applyHomePreference(data.home_preference);

      if (data.theme) {
        localStorage.setItem('theme', data.theme);
      }
      if (data.primary_color_hex || data.primary_color) {
        localStorage.setItem('primary_color', data.primary_color_hex || data.primary_color);
      }
      if (data.navigation_mode) {
        localStorage.setItem('navigation_mode', data.navigation_mode);
      }
      if (data.home_preference) {
        localStorage.setItem('home_preference', normalizeHomePreference(data.home_preference));
      }
    } catch (error) {
      // Keep existing local preferences if DB load fails.
    }
  };

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
    if (!sidebar || !document.body.classList.contains('has-side-content') || document.body.classList.contains('top-nav-mode')) {
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
    if (!document.body.classList.contains('has-side-content') || document.body.classList.contains('top-nav-mode')) {
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
    brand.href = getHomeUrl();
    brand.setAttribute('aria-label', 'SkillSwap home');
    const mobileFallbackSrc = `${appBase}/images/skillswaplogotrans.png`;
    const mobileLogoSrc = getLogoSrc() || mobileFallbackSrc;
    brand.innerHTML = `
      <img src="${mobileLogoSrc}" alt="SkillSwap logo" onerror="this.src='${mobileFallbackSrc}';this.onerror=null;">
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
    if (!document.body.classList.contains('has-side-content') || document.body.classList.contains('top-nav-mode')) {
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

  const updateTopNavScroll = () => {
    if (!document.body.classList.contains('top-nav-mode')) {
      document.body.classList.remove('top-nav-scrolled-hidden');
      lastTopNavScrollY = window.scrollY;
      return;
    }

    const currentScrollY = window.scrollY;
    const delta = currentScrollY - lastTopNavScrollY;

    if (currentScrollY <= 20 || delta < -4) {
      document.body.classList.remove('top-nav-scrolled-hidden');
    } else if (delta > 4 && currentScrollY > 60) {
      document.body.classList.add('top-nav-scrolled-hidden');
    }

    lastTopNavScrollY = currentScrollY;
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

  applyLocalPreferences();
  loadPreferencesFromDatabase();

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
    mobileBreakpoint.addEventListener('change', () => {
      applyNavigationMode(preferredNavigationMode);
      ensureMobileSidebar();
      ensureMobileBrand();
      syncMobileSidebarState();
    });
  } else if (typeof mobileBreakpoint.addListener === 'function') {
    mobileBreakpoint.addListener(() => {
      applyNavigationMode(preferredNavigationMode);
      ensureMobileSidebar();
      ensureMobileBrand();
      syncMobileSidebarState();
    });
  }

  if (typeof topNavPhoneBreakpoint.addEventListener === 'function') {
    topNavPhoneBreakpoint.addEventListener('change', () => {
      applyNavigationMode(preferredNavigationMode);
      ensureMobileSidebar();
      ensureMobileBrand();
      syncMobileSidebarState();
    });
  } else if (typeof topNavPhoneBreakpoint.addListener === 'function') {
    topNavPhoneBreakpoint.addListener(() => {
      applyNavigationMode(preferredNavigationMode);
      ensureMobileSidebar();
      ensureMobileBrand();
      syncMobileSidebarState();
    });
  }

  window.addEventListener('scroll', updateMobileTopControls, { passive: true });
  window.addEventListener('scroll', updateTopNavScroll, { passive: true });
});
