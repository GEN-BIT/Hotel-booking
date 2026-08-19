function showPanel(panelId) {
  var slider = document.getElementById('authSlider');
  var positions = {
    'loginPanel': '0%',
    'registerPanel': '-50%'
  };

  if (positions[panelId] !== undefined) {
    slider.style.transform = 'translateX(' + positions[panelId] + ')';
    document.querySelectorAll('.auth-panel').forEach(function(p) {
      p.classList.remove('active');
    });
    var target = document.getElementById(panelId);
    if (target) {
      target.classList.add('active');
    }
  }
}

function showForgot() {
  document.getElementById('forgotOverlay').classList.add('active');
}

function hideForgot() {
  document.getElementById('forgotOverlay').classList.remove('active');
}

document.addEventListener('DOMContentLoaded', function() {
  var params = new URLSearchParams(window.location.search);
  var mode = params.get('mode');

  if (mode === 'register') {
    showPanel('registerPanel');
  } else if (mode === 'forgot') {
    showForgot();
  } else {
    showPanel('loginPanel');
  }
});
