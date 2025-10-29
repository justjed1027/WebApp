// No JS rendering needed; posts are rendered by PHP
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
});
