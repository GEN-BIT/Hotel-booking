(function () {
  var STORAGE_KEY = 'hb-theme';
  var DEFAULT_THEME = 'luxury';

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
  }

  function init() {
    var select = document.getElementById('theme-select');
    var saved = localStorage.getItem(STORAGE_KEY) || DEFAULT_THEME;
    applyTheme(saved);
    if (select) {
      select.value = saved;
      select.addEventListener('change', function () {
        applyTheme(select.value);
        localStorage.setItem(STORAGE_KEY, select.value);
      });
    }
  }

  document.addEventListener('DOMContentLoaded', init);
})();
