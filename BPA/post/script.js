
// No JS rendering needed; posts are rendered by PHP

// Handle post-type buttons: redirect to create-post.php?type=<type>
document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('.post-type-btn').forEach(btn => {
		btn.addEventListener('click', function () {
			const type = btn.getAttribute('data-type');
			if (type) {
				window.location.href = `create-post.php?type=${encodeURIComponent(type)}`;
			}
		});
	});
});
