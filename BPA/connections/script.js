// Connections page JS placeholder
document.querySelectorAll('.btn-connect').forEach(btn => {
  btn.addEventListener('click', function() {
    btn.textContent = 'Connected';
    btn.disabled = true;
    btn.style.background = '#e5e7eb';
    btn.style.color = '#888';
    btn.style.cursor = 'default';
  });
});
document.querySelectorAll('.side-follow').forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    btn.textContent = 'Following';
    btn.style.color = '#888';
    btn.style.pointerEvents = 'none';
  });
});