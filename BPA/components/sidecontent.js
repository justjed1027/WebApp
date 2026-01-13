// Side Content Component JavaScript (in-flow layout)
document.addEventListener('DOMContentLoaded', () => {
  const sideContent = document.getElementById('sideContent');
  if (sideContent) {
    // Ensure in-flow positioning and let CSS handle responsiveness
    sideContent.style.position = 'static';
    sideContent.style.height = 'auto';
  }

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
});
