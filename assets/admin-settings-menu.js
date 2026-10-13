(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    var root = document.querySelector('[data-settings-flyout]');
    if (!root) return;
    var trigger = root.querySelector('[data-settings-trigger]');
    var panel = root.querySelector('[data-settings-popover]');
    if (!trigger || !panel) return;
    document.body.appendChild(panel);
    var closeTimer = null;
    var pinned = false;
    var sidebar = document.querySelector('.sidebar');

    function cancelClose() {
      if (closeTimer !== null) {
        clearTimeout(closeTimer);
        closeTimer = null;
      }
    }
    function positionPanel() {
      if (panel.hidden) return;
      var rect = trigger.getBoundingClientRect();
      var margin = 10;
      var maxWidth = Math.max(205, window.innerWidth - 20);
      panel.style.maxWidth = Math.min(310, maxWidth) + 'px';
      var width = panel.getBoundingClientRect().width;
      var toRight = rect.right + 10;
      var toLeft = rect.left - width - 10;
      var x;
      var y;
      if (toRight + width + margin <= window.innerWidth) {
        x = toRight;
        y = rect.top;
      } else if (toLeft >= margin) {
        x = toLeft;
        y = rect.top;
      } else {
        x = Math.max(margin, Math.min(rect.left, window.innerWidth - width - margin));
        y = rect.bottom + 8;
      }
      var availableHeight = Math.max(170, window.innerHeight - 2 * margin);
      panel.style.maxHeight = availableHeight + 'px';
      var height = Math.min(panel.scrollHeight, availableHeight);
      y = Math.max(margin, Math.min(y, window.innerHeight - height - margin));
      panel.style.left = Math.round(x) + 'px';
      panel.style.top = Math.round(y) + 'px';
    }
    function openMenu(focusFirst) {
      cancelClose();
      panel.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
      root.classList.add('is-open');
      positionPanel();
      if (focusFirst) {
        var first = panel.querySelector('a:not([hidden])');
        if (first) first.focus();
      }
    }
    function closeMenu(returnFocus) {
      cancelClose();
      panel.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
      root.classList.remove('is-open');
      pinned = false;
      if (returnFocus) trigger.focus();
    }
    function queueClose() {
      cancelClose();
      if (!pinned) closeTimer = setTimeout(function () {
        if (!root.matches(':hover') && !panel.matches(':hover') &&
            !root.contains(document.activeElement) && !panel.contains(document.activeElement)) {
          closeMenu(false);
        }
      }, 170);
    }
    trigger.addEventListener('click', function (event) {
      if (!panel.hidden && pinned) {
        closeMenu(false);
      } else {
        pinned = true;
        openMenu(event.detail === 0);
      }
    });
    trigger.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        pinned = true;
        openMenu(true);
      }
    });
    root.addEventListener('pointerenter', function (event) {
      if (event.pointerType !== 'mouse') return;
      cancelClose();
      if (panel.hidden) {
        pinned = false;
        openMenu(false);
      }
    });
    root.addEventListener('pointerleave', function (event) {
      if (event.pointerType === 'mouse') queueClose();
    });
    panel.addEventListener('pointerenter', cancelClose);
    panel.addEventListener('pointerleave', queueClose);
    document.addEventListener('pointerdown', function (event) {
      if (!panel.hidden && !root.contains(event.target) && !panel.contains(event.target)) closeMenu(false);
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !panel.hidden) {
        event.preventDefault();
        closeMenu(true);
      }
    });
    document.addEventListener('focusin', function (event) {
      if (!panel.hidden && !root.contains(event.target) && !panel.contains(event.target)) closeMenu(false);
    });
    window.addEventListener('resize', positionPanel);
    window.addEventListener('scroll', positionPanel, {passive: true});
    if (sidebar) sidebar.addEventListener('scroll', positionPanel, {passive: true});
  });
}());
