// All handlers run after DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    // Auto-resize behavior for the top textarea (.create-post-input)
    function autoResizeTextarea(el) {
        if (!el) return;
        // Reset height to compute the correct scrollHeight
        el.style.height = 'auto';
        // Add a small extra so the caret isn't right at the edge
        el.style.height = (el.scrollHeight + 2) + 'px';
    }

    document.querySelectorAll('.create-post-input').forEach(textarea => {
        // Initialize height based on current content
        autoResizeTextarea(textarea);
        // Listen for input changes
        textarea.addEventListener('input', function () {
            autoResizeTextarea(this);
        });
        // Optional: on window resize recompute (keeps width changes in mind)
        window.addEventListener('resize', function () { autoResizeTextarea(textarea); });
    });

    // File label and preview modal logic
    const fileInput = document.getElementById('avatar');
    const fileLabel = document.getElementById('fileLabel');
    const previewBtn = document.getElementById('filePreviewBtn');
    const removeBtn = document.getElementById('fileRemoveBtn');
    const modal = document.getElementById('filePreviewModal');
    const modalInner = document.getElementById('filePreviewInner') || document.getElementById('filePreviewContent');
    const modalClose = document.getElementById('modalCloseBtn');

    function resetModal() {
        if (modalInner) modalInner.innerHTML = '';
    }

    if (fileInput && fileLabel) {
        // Initialize label
        fileLabel.textContent = 'Pick a file to upload';

        fileInput.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                const f = this.files[0];
                fileLabel.textContent = f.name + ' (' + Math.round(f.size / 1024) + ' KB)';
                if (previewBtn) previewBtn.disabled = false;
                if (removeBtn) removeBtn.disabled = false;
            } else {
                fileLabel.textContent = 'Pick a file to upload';
                if (previewBtn) previewBtn.disabled = true;
                if (removeBtn) removeBtn.disabled = true;
            }
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', function () {
            if (!fileInput) return;
            fileInput.value = '';
            if (fileLabel) fileLabel.textContent = 'Pick a file to upload';
            if (previewBtn) previewBtn.disabled = true;
            removeBtn.disabled = true;
        });
        // start disabled until file selected
        removeBtn.disabled = true;
    }

    if (previewBtn) {
        // start disabled if no file selected
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) previewBtn.disabled = true;

        previewBtn.addEventListener('click', function () {
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) return;
            const f = fileInput.files[0];
            resetModal();
            const type = f.type || '';
            const reader = new FileReader();

            if (type.startsWith('image/')) {
                reader.onload = function (e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    modalInner.appendChild(img);
                    modal.classList.add('active');
                    modal.setAttribute('aria-hidden', 'false');
                };
                reader.readAsDataURL(f);
            } else if (type.startsWith('video/')) {
                reader.onload = function (e) {
                    const video = document.createElement('video');
                    video.src = e.target.result;
                    video.controls = true;
                    modalInner.appendChild(video);
                    modal.classList.add('active');
                    modal.setAttribute('aria-hidden', 'false');
                };
                reader.readAsDataURL(f);
            } else {
                // Other types: show filename and download link
                const info = document.createElement('div');
                info.textContent = 'File: ' + f.name + ' (' + Math.round(f.size / 1024) + ' KB)';
                const link = document.createElement('a');
                link.textContent = 'Download';
                link.href = URL.createObjectURL(f);
                link.download = f.name;
                link.style.display = 'inline-block';
                link.style.marginTop = '8px';
                modalInner.appendChild(info);
                modalInner.appendChild(link);
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
            }
        });
    }

    // modal close handlers
    if (modalClose) modalClose.addEventListener('click', function () {
        modal.classList.remove('active'); resetModal(); modal.setAttribute('aria-hidden', 'true');
    });
    if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) { modal.classList.remove('active'); resetModal(); modal.setAttribute('aria-hidden', 'true'); } });
    window.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal && modal.classList.contains('active')) { modal.classList.remove('active'); resetModal(); modal.setAttribute('aria-hidden', 'true'); } });
});

