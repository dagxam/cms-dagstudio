(function () {
  'use strict';
  var root = document.documentElement;
  var key = root.getAttribute('data-theme-storage-key') || 'dagstudio-cms-theme';
  var current = root.getAttribute('data-theme') === 'light' ? 'light' : 'dark';

  function apply(theme) {
    current = theme === 'light' ? 'light' : 'dark';
    root.setAttribute('data-theme', current);
    root.style.colorScheme = current;
    document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
      button.setAttribute('aria-label', current === 'dark' ? 'Включить светлую тему' : 'Включить тёмную тему');
      button.setAttribute('title', current === 'dark' ? 'Светлая тема' : 'Тёмная тема');
      button.setAttribute('aria-pressed', String(current === 'light'));
    });
    var favicon = document.getElementById('dag-favicon');
    if (favicon) favicon.href = current === 'light' ? '/assets/ornament-light.svg' : '/assets/ornament-dark.svg';
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) {
      var siteColor = document.body && document.body.classList.contains('site-page')
        ? getComputedStyle(document.body).getPropertyValue('--site-bg').trim() : '';
      meta.content = /^#[a-fA-F0-9]{6}$/.test(siteColor)
        ? siteColor : (current === 'light' ? '#f9f6f0' : '#101113');
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    apply(current);

    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
      button.addEventListener('click', function () {
        var input = document.getElementById(button.getAttribute('aria-controls'));
        if (!input) return;
        var visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-label', visible ? 'Скрыть пароль' : 'Показать пароль');
        button.setAttribute('aria-pressed', String(visible));
        button.textContent = visible ? '◉' : '◎';
      });
    });
    document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
      button.addEventListener('click', function () {
        var next = current === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem(key, next); } catch (ignored) {}
        apply(next);
      });
    });
  });
}());
