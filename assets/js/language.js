(function () {
  var DEFAULT_LANG = 'en';

  function init() {
    var select = document.getElementById('language-select');
    if (!select) return;

    // Read current language from PHP-rendered HTML attribute or default
    var current = document.documentElement.getAttribute('lang') || DEFAULT_LANG;
    select.value = current;

    select.addEventListener('change', function () {
      var lang = select.value;
      if (lang === current) return;

      var url = new URL(window.location.href);
      url.searchParams.set('lang', lang);
      window.location.href = url.toString();
    });
  }

  document.addEventListener('DOMContentLoaded', init);
})();
