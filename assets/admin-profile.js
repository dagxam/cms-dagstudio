(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    var menu = document.querySelector('[data-profile-menu]');
    if (!menu) return;
    document.addEventListener('pointerdown', function (event) {
      if (menu.open && !menu.contains(event.target)) menu.open = false;
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && menu.open) {
        menu.open = false;
        var trigger = menu.querySelector('summary');
        if (trigger) trigger.focus();
      }
    });
  });
}());
