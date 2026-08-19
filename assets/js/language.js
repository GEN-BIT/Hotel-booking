(function () {
  var LANG_COOKIE = 'hb_lang';
  var DEFAULT_LANG = 'en';

  function setCookie(name, value, days) {
    var d = new Date();
    d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
    var expires = 'expires=' + d.toUTCString();
    document.cookie = name + '=' + value + ';' + expires + ';path=/';
  }

  function getCookie(name) {
    var cname = name + '=';
    var decoded = decodeURIComponent(document.cookie);
    var ca = decoded.split(';');
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) === ' ') c = c.substring(1);
      if (c.indexOf(cname) === 0) return c.substring(cname.length, c.length);
    }
    return '';
  }

  function init() {
    var select = document.getElementById('language-select');
    if (!select) return;

    var saved = getCookie(LANG_COOKIE) || DEFAULT_LANG;
    select.value = saved;

    select.addEventListener('change', function () {
      var lang = select.value;
      setCookie(LANG_COOKIE, lang, 365);
      location.reload();
    });
  }

  document.addEventListener('DOMContentLoaded', init);
})();
