// Initialize Vanta Fog background
let vantaEffect = null;


if (typeof VANTA !== 'undefined' && document.getElementById('vanta-bg')) {
  vantaEffect = VANTA.FOG({
    el: "#vanta-bg",
    mouseControls: true,
    touchControls: true,
    gyroControls: false,
    minHeight: 200.00,
    minWidth: 200.00,
    highlightColor: 0x828282,
    midtoneColor: 0xF5F5F5,
    lowlightColor: 0xDAD7EB,
    baseColor: 0xFFFFFF,
    blurFactor: 0.19,
    speed: 0.00,
    zoom: 0.60
  });
}


// destroy on unload to avoid memory leaks
window.addEventListener('beforeunload', () => {
  if (vantaEffect && typeof vantaEffect.destroy === 'function') {
    vantaEffect.destroy();
    vantaEffect = null;
  }
});
