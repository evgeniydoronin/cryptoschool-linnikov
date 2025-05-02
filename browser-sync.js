// browser-sync.js
const browserSync = require('browser-sync').create();

// Путь к файлам темы
const themePath = './wp-content/themes/criptoschool/';

browserSync.init({
  proxy: "localhost:8080",
  host: '0.0.0.0', // Важно для внешнего доступа
  open: false,
  port: 3000,
  ui: {
    port: 3001
  },
  files: [
    // PHP файлы
    `${themePath}**/*.php`,
    // CSS файлы
    `${themePath}**/*.css`,
    // JavaScript файлы
    `${themePath}**/*.js`,
    // Изображения
    `${themePath}**/*.{png,jpg,gif,svg}`,
  ],
  // Уведомления в браузере
  notify: true
});