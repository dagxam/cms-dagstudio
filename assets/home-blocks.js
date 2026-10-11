(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    var select = document.querySelector('[data-home-block-type]');
    var panel = document.querySelector('[data-home-items-panel]');
    if (!select || !panel) return;
    function update() {
      var type = select.value;
      panel.hidden = type !== 'cards' && type !== 'stats';
      panel.querySelectorAll('[data-home-item-url]').forEach(function (label) {
        label.hidden = type !== 'cards';
        var input = label.querySelector('input');
        if (input) input.disabled = type !== 'cards';
      });
      panel.querySelectorAll('input, textarea').forEach(function (input) {
        if (input.closest('[data-home-item-url]')) return;
        input.disabled = panel.hidden;
      });
    }
    select.addEventListener('change', update);
    update();
  });
}());
