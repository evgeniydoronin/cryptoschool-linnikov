// browser-sync.js
const browserSync = require('browser-sync').create();
const os = require('os');
const { exec } = require('child_process');

// Функция для остановки старых процессов BrowserSync
function killOldProcesses() {
  return new Promise((resolve) => {
    exec('pkill -f "browser-sync"', (error) => {
      // Игнорируем ошибки (процессы могут не существовать)
      setTimeout(resolve, 1000); // Даём время на завершение процессов
    });
  });
}

// Путь к файлам темы WordPress
const themePath = './wp-content/themes/cryptoschool/';

// Асинхронная инициализация с очисткой старых процессов
async function startBrowserSync() {
  console.log('🧹 Останавливаю старые процессы BrowserSync...');
  await killOldProcesses();
  
  console.log('🚀 Запускаю BrowserSync...');
  browserSync.init({
    // Прокси для WordPress сайта
    proxy: "127.0.0.1:8080",
    
    // Настройки сети
    host: '0.0.0.0',
    port: 3000,
    ui: {
      port: 3001
    },
    
    // Базовые опции
    open: false,
    notify: {
      styles: {
        top: 'auto',
        bottom: '0',
        right: '0',
        fontSize: '12px',
        padding: '5px 10px',
        borderRadius: '5px 0 0 0'
      }
    },
    
    // Принудительная инжекция скрипта
    snippetOptions: {
      rule: {
        match: /<\/body>/i,
        fn: function (snippet, match) {
          return snippet + match;
        }
      }
    },
    
    // Отслеживание файлов
    files: [
      // PHP файлы темы
      `${themePath}**/*.php`,
      // PHP файлы плагинов
      './wp-content/plugins/cryptoschool/**/*.php',
      // Стили
      `${themePath}**/*.{css,scss,sass}`,
      // JavaScript
      `${themePath}**/*.js`,
      // Изображения
      `${themePath}**/*.{png,jpg,jpeg,gif,svg,webp,ico}`
    ],
    
    // Настройки логирования
    logLevel: 'info',
    logPrefix: 'CryptoSchool',
    timestamps: true,
    
    // Ghost mode - синхронизация действий между устройствами
    ghostMode: {
      clicks: true,
      forms: true,
      scroll: true
    },
    
    // Инжекция изменений без полной перезагрузки
    injectChanges: true,
    
    // Задержки для стабильности
    reloadDelay: 500,
    reloadDebounce: 1000,
    
    // Middleware для инжекции скрипта во все страницы
    middleware: [
      {
        route: "",
        handle: function(req, res, next) {
          // Добавляем заголовки для лучшей совместимости
          res.setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
          res.setHeader('Pragma', 'no-cache');
          res.setHeader('Expires', '0');
          next();
        }
      }
    ],
    
    // Кастомные колбэки для вывода информации
    callbacks: {
      ready: function(err, bs) {
        if (err) {
          console.error('❌ Ошибка запуска BrowserSync:', err);
          return;
        }
        
        // Получаем фактические порты
        const actualPort = bs.options.get('port');
        const actualUIPort = bs.options.get('ui.port');
        
        console.log('\n🚀 CryptoSchool Development Server запущен!');
        
        // Уведомление о смене порта, если он отличается от заданного
        if (actualPort !== 3000) {
          console.log(`⚠️  Порт 3000 был занят, используется порт ${actualPort}`);
        }
        if (actualUIPort !== 3001) {
          console.log(`⚠️  Порт 3001 был занят, UI панель использует порт ${actualUIPort}`);
        }
        console.log(`📱 Локальный доступ:    http://localhost:${actualPort}`);
        console.log(`🌐 Внешний доступ:     http://0.0.0.0:${actualPort}`);
        console.log(`⚙️  Панель управления: http://localhost:${actualUIPort}`);
        
        console.log('\n⚠️  ВАЖНО: Для автообновления используйте ТОЛЬКО прокси-адреса выше!');
        console.log('   Прямой доступ к http://localhost:8080 НЕ будет автоматически обновляться');
        
        console.log('\n📋 Доступ с других устройств в локальной сети:');
        
        // Получение всех сетевых интерфейсов
        const interfaces = os.networkInterfaces();
        let hasExternalIP = false;
        
        Object.keys(interfaces).forEach(function(devName) {
          const iface = interfaces[devName];
          iface.forEach(function(alias) {
            if (alias.family === 'IPv4' && alias.address !== '127.0.0.1' && !alias.internal) {
              console.log(`   📱 http://${alias.address}:${actualPort}`);
              hasExternalIP = true;
            }
          });
        });
        
        if (!hasExternalIP) {
          console.log('   ⚠️  Внешние IP-адреса не найдены');
        }
        
        console.log('\n📁 Отслеживание файлов:');
        console.log(`   • PHP темы: ${themePath}**/*.php`);
        console.log(`   • PHP плагина: ./wp-content/plugins/cryptoschool/**/*.php`);
        console.log(`   • CSS/SCSS: ${themePath}**/*.{css,scss,sass}`);
        console.log(`   • JS: ${themePath}**/*.js`);
        console.log(`   • Изображения: ${themePath}**/*.{png,jpg,gif,svg,webp,ico}`);
        
        console.log('\n✨ Готов к разработке!');
        console.log('💡 Подсказка: изменения CSS будут внедряться без перезагрузки страницы');
        console.log('🔄 Ghost Mode включен - действия синхронизируются между всеми устройствами\n');
      }
    }
  });

  // Дополнительный обработчик для отслеживания состояния сервиса
  browserSync.emitter.on('service:running', function (data) {
    console.log('🔄 BrowserSync сервис запущен на порту:', data.port);
  });
}

// Запуск BrowserSync
startBrowserSync().catch(console.error);

// Экспорт для программного использования
module.exports = browserSync;
