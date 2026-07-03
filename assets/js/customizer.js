/**
 * WhiteKurti — customizer.js (live preview bindings)
 */
(function ($) {
  'use strict';

  wp.customize('wk_announcement', function (value) {
    value.bind(function (newval) {
      $('.wk-promo-strip').text(newval);
    });
  });

  wp.customize('wk_footer_text', function (value) {
    value.bind(function (newval) {
      $('.wk-footer__copy').text(newval);
    });
  });

  // Live colour token updates via CSS var injection
  var tokenMap = {
    wk_color_bg:       '--bg',
    wk_color_surface:  '--surface',
    wk_color_surface2: '--surface-2',
    wk_color_ink:      '--ink',
    wk_color_inksoft:  '--ink-soft',
    wk_color_inkmute:  '--ink-mute',
    wk_color_line:     '--line',
    wk_color_accent:   '--accent',
    wk_color_sale:     '--sale',
  };

  $.each(tokenMap, function (settingId, cssVar) {
    wp.customize(settingId, function (value) {
      value.bind(function (newval) {
        var el = document.getElementById('wk-live-colors');
        if (!el) {
          el = document.createElement('style');
          el.id = 'wk-live-colors';
          document.head.appendChild(el);
        }
        // Read all current overrides and rebuild the rule
        var rules = [];
        $.each(tokenMap, function (sid, cvar) {
          var v = wp.customize(sid)();
          if (v) rules.push(cvar + ': ' + v + ';');
        });
        el.textContent = rules.length ? ':root, body { ' + rules.join(' ') + ' }' : '';
      });
    });
  });

}(jQuery));
