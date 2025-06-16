// Ждём, пока DOM загрузится
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.theme-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const theme = btn.getAttribute('data-theme');
      // Сохраняем cookie на 30 дней
      document.cookie = "theme=" + theme + "; path=/; max-age=" + (60*60*24*30);
      // Перезагружаем страницу, чтобы применить новую тему
      window.location.reload();
    });
  });
});
