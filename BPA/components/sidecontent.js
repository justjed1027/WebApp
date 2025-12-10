// Side Content Component JavaScript (in-flow layout)
document.addEventListener('DOMContentLoaded', () => {
  const sideContent = document.getElementById('sideContent');
  if (!sideContent) return;

  // Ensure in-flow positioning and let CSS handle responsiveness
  sideContent.style.position = 'static';
  sideContent.style.height = 'auto';
});
