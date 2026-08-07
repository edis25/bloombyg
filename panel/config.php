<?php
// ============================================================
// BLOOM Yönetim Paneli — ayarlar
// Şifreyi değiştirmek için: yeni şifreyi aşağıdaki komutla
// hash'leyip PANEL_PASSWORD_HASH değerini güncelleyin:
//   php -r "echo password_hash('yeni-sifre', PASSWORD_DEFAULT);"
// ============================================================

// Varsayılan şifre: bloom2026
define('PANEL_PASSWORD_HASH', '$2y$12$G2Imf51cTJQam0wFHswe9OhK1c.fI9D8hjcW8Yg3gi0whRf8Qqx6G');

// İçerik dosyası ve medya klasörleri (panel/ klasörüne göre)
define('CONTENT_FILE', __DIR__ . '/../content/site.json');
define('IMG_DIR', __DIR__ . '/../assets/img/bloom/');
define('VIDEO_DIR', __DIR__ . '/../assets/video/');
define('IMG_WEB', 'assets/img/bloom/');
define('VIDEO_WEB', 'assets/video/');

// Yükleme sınırları
define('MAX_IMAGE_MB', 15);
define('MAX_VIDEO_MB', 100);
