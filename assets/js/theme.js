(function () {
  var THEME_KEY = 'hb-theme';
  var DEFAULT_THEME = 'luxury';

  var LANG_KEY = 'hb-lang';
  var DEFAULT_LANG = 'en';

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
  }

  function applyLang(lang) {
    // Save to localStorage and set on html so JS-driven strings can be translated
    try { localStorage.setItem(LANG_KEY, lang); } catch (e) {}
    document.documentElement.setAttribute('lang', lang);
  }

  function initTheme() {
    var select = document.getElementById('theme-select');
    var saved = localStorage.getItem(THEME_KEY) || DEFAULT_THEME;
    if (saved === 'vibrant' || saved === 'minimalist') saved = 'dark';
    applyTheme(saved);
    if (select) {
      select.value = saved;
      select.addEventListener('change', function () {
        applyTheme(select.value);
        localStorage.setItem(THEME_KEY, select.value);
      });
    }
  }

  function initLang() {
    var select = document.getElementById('language-select');
    var saved = localStorage.getItem(LANG_KEY) || DEFAULT_LANG;
    applyLang(saved);
    if (select) {
      // Preselect the saved language
      select.value = saved;
      select.addEventListener('change', function () {
        var lang = select.value;
        applyLang(lang);
        // Redirect with ?lang= parameter so PHP session updates
        var url = new URL(window.location.href);
        url.searchParams.set('lang', lang);
        window.location.href = url.toString();
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    initTheme();
    initLang();
  });
})();