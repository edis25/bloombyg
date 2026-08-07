<?php
// ============================================================
// BLOOM By G — Yönetim Paneli
// Veritabanı gerektirmez: content/site.json dosyasını düzenler,
// görsel/video yüklemelerini assets/ altına kaydeder.
// ============================================================
session_start();
require __DIR__ . '/config.php';

// ---------- yardımcılar ----------
function load_content() {
    $raw = @file_get_contents(CONTENT_FILE);
    $data = $raw ? json_decode($raw, true) : null;
    return is_array($data) ? $data : [];
}
function save_content($data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return file_put_contents(CONTENT_FILE, $json . "\n", LOCK_EX) !== false;
}
function json_get($data, $path) {
    foreach (explode('.', $path) as $k) {
        if (!is_array($data) || !array_key_exists($k, $data)) return null;
        $data = $data[$k];
    }
    return $data;
}
function json_set(&$data, $path, $value) {
    $ref = &$data;
    foreach (explode('.', $path) as $k) {
        if (!is_array($ref)) $ref = [];
        $ref = &$ref[$k];
    }
    $ref = $value;
}
function flash($msg, $type = 'ok') {
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}
function handle_upload($file, $kind) {
    // $kind: 'image' | 'video' → hedef klasör ve uzantı beyaz listesi
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) { flash('Yükleme hatası (kod ' . $file['error'] . ').', 'err'); return null; }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = $kind === 'image' ? ['jpg', 'jpeg', 'png', 'webp'] : ['mp4', 'webm', 'mov'];
    $maxMb = $kind === 'image' ? MAX_IMAGE_MB : MAX_VIDEO_MB;
    if (!in_array($ext, $allowed)) { flash('İzin verilmeyen dosya türü: .' . $ext, 'err'); return null; }
    if ($file['size'] > $maxMb * 1024 * 1024) { flash('Dosya çok büyük (en fazla ' . $maxMb . ' MB).', 'err'); return null; }
    $base = pathinfo($file['name'], PATHINFO_FILENAME);
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base)));
    $slug = trim($slug, '-') ?: 'medya';
    $name = $slug . '-' . substr(uniqid(), -6) . '.' . $ext;
    $dir = $kind === 'image' ? IMG_DIR : VIDEO_DIR;
    $web = $kind === 'image' ? IMG_WEB : VIDEO_WEB;
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) { flash('Dosya kaydedilemedi — klasör yazma izni olmayabilir.', 'err'); return null; }
    return $web . $name;
}

// ---------- oturum ----------
if (isset($_GET['cikis'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
if (isset($_POST['login_password'])) {
    if (password_verify($_POST['login_password'], PANEL_PASSWORD_HASH)) {
        $_SESSION['bloom_auth'] = true;
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    } else {
        $login_error = 'Şifre hatalı.';
    }
}
$authed = !empty($_SESSION['bloom_auth']);

// ---------- form şeması ----------
$sections = [
    'slider' => ['label' => 'Slider', 'fields' => [
        ['path' => 'slider.slide1.kicker', 'label' => '1. Slayt — Üst yazı', 'type' => 'text'],
        ['path' => 'slider.slide1.title',  'label' => '1. Slayt — Başlık', 'type' => 'text'],
        ['path' => 'slider.slide1.video_desktop', 'label' => '1. Slayt — Video (yatay / masaüstü)', 'type' => 'video'],
        ['path' => 'slider.slide1.video_mobile',  'label' => '1. Slayt — Video (dikey / mobil)', 'type' => 'video'],
        ['path' => 'slider.slide2.kicker', 'label' => '2. Slayt — Üst yazı', 'type' => 'text'],
        ['path' => 'slider.slide2.title',  'label' => '2. Slayt — Başlık', 'type' => 'text'],
        ['path' => 'slider.slide2.image',  'label' => '2. Slayt — Görsel', 'type' => 'image'],
        ['path' => 'slider.slide3.kicker', 'label' => '3. Slayt — Üst yazı', 'type' => 'text'],
        ['path' => 'slider.slide3.title',  'label' => '3. Slayt — Başlık', 'type' => 'text'],
        ['path' => 'slider.slide3.image',  'label' => '3. Slayt — Görsel', 'type' => 'image'],
    ]],
    'hikaye' => ['label' => 'Hikaye', 'fields' => [
        ['path' => 'story.eyebrow', 'label' => 'Küçük başlık', 'type' => 'text'],
        ['path' => 'story.title',   'label' => 'Başlık', 'type' => 'text'],
        ['path' => 'story.p1',      'label' => '1. Paragraf', 'type' => 'textarea'],
        ['path' => 'story.p2',      'label' => '2. Paragraf', 'type' => 'textarea'],
        ['path' => 'story.accent',  'label' => 'Vurgu cümlesi (italik)', 'type' => 'textarea'],
        ['path' => 'story.image',   'label' => 'Görsel', 'type' => 'image'],
    ]],
    'davetler' => ['label' => 'Özel Davetler', 'fields' => [
        ['path' => 'events.eyebrow', 'label' => 'Küçük başlık', 'type' => 'text'],
        ['path' => 'events.title',   'label' => 'Başlık', 'type' => 'text'],
        ['path' => 'events.p1', 'label' => '1. Paragraf', 'type' => 'textarea'],
        ['path' => 'events.p2', 'label' => '2. Paragraf', 'type' => 'textarea'],
        ['path' => 'events.p3', 'label' => '3. Paragraf', 'type' => 'textarea'],
        ['path' => 'events.list', 'label' => 'Etkinlik listesi (her satır bir madde)', 'type' => 'list'],
        ['path' => 'events.button', 'label' => 'Buton yazısı', 'type' => 'text'],
        ['path' => 'events.video', 'label' => 'Video', 'type' => 'video'],
    ]],
    'deneyim' => ['label' => 'Bloom Deneyimi', 'fields' => [
        ['path' => 'experience.eyebrow', 'label' => 'Küçük başlık', 'type' => 'text'],
        ['path' => 'experience.title',   'label' => 'Başlık', 'type' => 'text'],
        ['path' => 'experience.p1',      'label' => 'Paragraf', 'type' => 'textarea'],
        ['path' => 'experience.accent',  'label' => 'Vurgu cümlesi (italik)', 'type' => 'textarea'],
        ['path' => 'experience.cards.0.caption', 'label' => '1. Kart — Başlık', 'type' => 'text'],
        ['path' => 'experience.cards.0.image',   'label' => '1. Kart — Görsel', 'type' => 'image'],
        ['path' => 'experience.cards.1.caption', 'label' => '2. Kart — Başlık', 'type' => 'text'],
        ['path' => 'experience.cards.1.image',   'label' => '2. Kart — Görsel', 'type' => 'image'],
        ['path' => 'experience.cards.2.caption', 'label' => '3. Kart — Başlık', 'type' => 'text'],
        ['path' => 'experience.cards.2.image',   'label' => '3. Kart — Görsel', 'type' => 'image'],
    ]],
    'galeri' => ['label' => 'Galeri', 'fields' => []],
    'iletisim' => ['label' => 'İletişim', 'fields' => [
        ['path' => 'contact.eyebrow', 'label' => 'Küçük başlık', 'type' => 'text'],
        ['path' => 'contact.title',   'label' => 'Başlık', 'type' => 'text'],
        ['path' => 'contact.p1', 'label' => '1. Paragraf', 'type' => 'textarea'],
        ['path' => 'contact.p2', 'label' => '2. Paragraf', 'type' => 'textarea'],
        ['path' => 'contact.address', 'label' => 'Adres', 'type' => 'textarea'],
        ['path' => 'contact.address_link', 'label' => 'Adres harita linki', 'type' => 'text'],
        ['path' => 'contact.phone', 'label' => 'Telefon', 'type' => 'text'],
        ['path' => 'contact.instagram', 'label' => 'Instagram kullanıcı adı (@ olmadan)', 'type' => 'text'],
        ['path' => 'contact.image', 'label' => 'Görsel', 'type' => 'image'],
        ['path' => 'footer.copyright', 'label' => 'Footer telif yazısı', 'type' => 'text'],
    ]],
];

$active = isset($_GET['bolum']) && isset($sections[$_GET['bolum']]) ? $_GET['bolum'] : 'slider';

// ---------- kayıt işlemleri ----------
if ($authed && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['login_password'])) {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        flash('Oturum doğrulaması başarısız — sayfayı yenileyip tekrar deneyin.', 'err');
        header('Location: index.php?bolum=' . urlencode($active));
        exit;
    }
    $data = load_content();

    // bölüm formu kaydet
    if (isset($_POST['save_section']) && isset($sections[$_POST['save_section']])) {
        foreach ($sections[$_POST['save_section']]['fields'] as $f) {
            $key = str_replace('.', '__', $f['path']);
            if ($f['type'] === 'text' || $f['type'] === 'textarea') {
                if (isset($_POST[$key])) json_set($data, $f['path'], trim($_POST[$key]));
            } elseif ($f['type'] === 'list') {
                if (isset($_POST[$key])) {
                    $items = array_values(array_filter(array_map('trim', explode("\n", $_POST[$key]))));
                    json_set($data, $f['path'], $items);
                }
            } elseif ($f['type'] === 'image' || $f['type'] === 'video') {
                if (isset($_FILES[$key])) {
                    $path = handle_upload($_FILES[$key], $f['type']);
                    if ($path) {
                        json_set($data, $f['path'], $path);
                        // 1. slayt videosu değişirse posteri eski kalmasın diye temizlenmez;
                        // poster otomatik üretilemediğinden mevcut poster korunur.
                    }
                }
            }
        }
        $saved = save_content($data);
        flash($saved ? 'Değişiklikler kaydedildi.' : 'Kaydedilemedi — content/site.json yazılabilir olmalı.', $saved ? 'ok' : 'err');
    }

    // galeri işlemleri
    if (isset($_POST['gallery_action'])) {
        $g = $data['gallery'] ?? [];
        $i = isset($_POST['idx']) ? (int)$_POST['idx'] : -1;
        switch ($_POST['gallery_action']) {
            case 'add':
                if (isset($_FILES['gallery_file'])) {
                    $path = handle_upload($_FILES['gallery_file'], 'image');
                    if ($path) { $g[] = $path; flash('Görsel galeriye eklendi.'); }
                }
                break;
            case 'delete':
                if (isset($g[$i])) { array_splice($g, $i, 1); flash('Görsel galeriden çıkarıldı.'); }
                break;
            case 'up':
                if ($i > 0 && isset($g[$i])) { [$g[$i-1], $g[$i]] = [$g[$i], $g[$i-1]]; }
                break;
            case 'down':
                if (isset($g[$i]) && isset($g[$i+1])) { [$g[$i+1], $g[$i]] = [$g[$i], $g[$i+1]]; }
                break;
        }
        $data['gallery'] = array_values($g);
        if (!save_content($data)) flash('Kaydedilemedi — content/site.json yazılabilir olmalı.', 'err');
    }

    header('Location: index.php?bolum=' . urlencode($_POST['bolum_ref'] ?? $active));
    exit;
}

$data = load_content();
$flashes = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Bloom — Yönetim Paneli</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root { --ink:#35302a; --gold:#a08a5f; --sage:#6f7a68; --cream:#faf7f1; --line:#e8e2d8; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Jost',sans-serif; font-weight:300; background:var(--cream); color:var(--ink); min-height:100vh; }
a { color:inherit; }
.wrap { max-width:860px; margin:0 auto; padding:34px 20px 80px; }
.brand { font-family:'Cormorant Garamond',serif; font-size:26px; letter-spacing:.3em; text-transform:uppercase; color:var(--gold); text-align:center; }
.brand small { display:block; font-family:'Jost'; font-size:10px; letter-spacing:3px; color:var(--ink); opacity:.6; margin-top:4px; }
.topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:30px; }
.topbar .out { font-size:11px; letter-spacing:2px; text-transform:uppercase; text-decoration:none; border:1px solid var(--line); padding:8px 14px; background:#fff; }
.tabs { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:26px; }
.tabs a { text-decoration:none; font-size:11px; letter-spacing:2px; text-transform:uppercase; padding:10px 16px; background:#fff; border:1px solid var(--line); }
.tabs a.on { background:var(--ink); color:#fff; border-color:var(--ink); }
.card { background:#fff; border:1px solid var(--line); padding:28px; }
.field { margin-bottom:20px; }
.field label { display:block; font-size:11px; letter-spacing:2px; text-transform:uppercase; color:var(--gold); margin-bottom:7px; }
.field input[type=text], .field textarea { width:100%; border:1px solid var(--line); padding:11px 13px; font-family:'Jost'; font-size:14px; font-weight:300; color:var(--ink); background:#fff; outline:none; }
.field input:focus, .field textarea:focus { border-color:var(--gold); }
.field textarea { min-height:84px; resize:vertical; }
.media-row { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.media-row img, .media-row video { width:130px; height:82px; object-fit:cover; border:1px solid var(--line); background:#eee; }
.media-row .cur { font-size:11px; opacity:.55; word-break:break-all; max-width:280px; }
.btn { display:inline-block; font-size:11px; letter-spacing:2px; text-transform:uppercase; background:var(--ink); color:#fff; border:none; padding:13px 26px; cursor:pointer; font-family:'Jost'; }
.btn:hover { background:var(--gold); }
.btn.ghost { background:none; color:var(--ink); border:1px solid var(--line); padding:8px 12px; }
.btn.ghost:hover { border-color:var(--gold); color:var(--gold); background:none; }
.btn.danger:hover { border-color:#b04a3a; color:#b04a3a; }
.flash { padding:12px 16px; margin-bottom:16px; font-size:13px; border:1px solid; }
.flash.ok { background:#f0f4ec; border-color:#c6d4bc; color:#4c5c43; }
.flash.err { background:#f8ecea; border-color:#e0bdb6; color:#84463a; }
.gal { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:14px; margin-bottom:24px; }
.gal .it { background:#fff; border:1px solid var(--line); padding:8px; }
.gal img { width:100%; aspect-ratio:2/3; object-fit:cover; display:block; margin-bottom:8px; }
.gal .ops { display:flex; gap:6px; justify-content:center; }
.gal .ops form { display:inline; }
.hint { font-size:12px; opacity:.6; margin:6px 0 0; }
.login { max-width:360px; margin:16vh auto 0; }
.login .card { padding:34px; }
.login .btn { width:100%; margin-top:6px; }
.preview-link { font-size:11px; letter-spacing:2px; text-transform:uppercase; text-decoration:none; color:var(--gold); }
@media (max-width:600px){ .wrap{padding:22px 12px 60px;} .card{padding:18px;} }
</style>
</head>
<body>
<div class="wrap">

<?php if (!$authed): ?>
  <div class="login">
    <div class="brand" style="margin-bottom:26px">Bloom<small>Yönetim Paneli</small></div>
    <div class="card">
      <?php if (!empty($login_error)): ?><div class="flash err"><?= h($login_error) ?></div><?php endif; ?>
      <form method="post">
        <div class="field">
          <label>Şifre</label>
          <input type="password" name="login_password" autofocus>
        </div>
        <button class="btn">Giriş Yap</button>
      </form>
    </div>
  </div>

<?php else: ?>
  <div class="topbar">
    <div class="brand">Bloom<small>Yönetim Paneli</small></div>
    <div style="display:flex; gap:14px; align-items:center">
      <a class="preview-link" href="../" target="_blank">Siteyi Gör ↗</a>
      <a class="out" href="?cikis=1">Çıkış</a>
    </div>
  </div>

  <div class="tabs">
    <?php foreach ($sections as $key => $s): ?>
      <a href="?bolum=<?= $key ?>" class="<?= $key === $active ? 'on' : '' ?>"><?= h($s['label']) ?></a>
    <?php endforeach; ?>
  </div>

  <?php foreach ($flashes as $f): ?>
    <div class="flash <?= $f['type'] ?>"><?= h($f['msg']) ?></div>
  <?php endforeach; ?>

  <?php if ($active === 'galeri'): ?>
    <div class="card">
      <div class="gal">
        <?php foreach (($data['gallery'] ?? []) as $i => $src): ?>
          <div class="it">
            <img src="../<?= h($src) ?>" alt="">
            <div class="ops">
              <form method="post"><input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>"><input type="hidden" name="bolum_ref" value="galeri"><input type="hidden" name="gallery_action" value="up"><input type="hidden" name="idx" value="<?= $i ?>"><button class="btn ghost" title="Sola taşı">←</button></form>
              <form method="post" onsubmit="return confirm('Bu görsel galeriden çıkarılsın mı?')"><input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>"><input type="hidden" name="bolum_ref" value="galeri"><input type="hidden" name="gallery_action" value="delete"><input type="hidden" name="idx" value="<?= $i ?>"><button class="btn ghost danger" title="Sil">✕</button></form>
              <form method="post"><input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>"><input type="hidden" name="bolum_ref" value="galeri"><input type="hidden" name="gallery_action" value="down"><input type="hidden" name="idx" value="<?= $i ?>"><button class="btn ghost" title="Sağa taşı">→</button></form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
        <input type="hidden" name="bolum_ref" value="galeri">
        <input type="hidden" name="gallery_action" value="add">
        <div class="field">
          <label>Yeni görsel ekle (jpg, png, webp — en fazla <?= MAX_IMAGE_MB ?> MB)</label>
          <input type="file" name="gallery_file" accept=".jpg,.jpeg,.png,.webp" required>
          <p class="hint">Dikey (2:3 oranına yakın) fotoğraflar en iyi sonucu verir.</p>
        </div>
        <button class="btn">Galeriye Ekle</button>
      </form>
    </div>

  <?php else: ?>
    <div class="card">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
        <input type="hidden" name="save_section" value="<?= h($active) ?>">
        <input type="hidden" name="bolum_ref" value="<?= h($active) ?>">
        <?php foreach ($sections[$active]['fields'] as $f):
          $key = str_replace('.', '__', $f['path']);
          $val = json_get($data, $f['path']); ?>
          <div class="field">
            <label><?= h($f['label']) ?></label>
            <?php if ($f['type'] === 'text'): ?>
              <input type="text" name="<?= $key ?>" value="<?= h($val) ?>">
            <?php elseif ($f['type'] === 'textarea'): ?>
              <textarea name="<?= $key ?>"><?= h($val) ?></textarea>
            <?php elseif ($f['type'] === 'list'): ?>
              <textarea name="<?= $key ?>" style="min-height:150px"><?= h(is_array($val) ? implode("\n", $val) : $val) ?></textarea>
            <?php elseif ($f['type'] === 'image'): ?>
              <div class="media-row">
                <?php if ($val): ?><img src="../<?= h($val) ?>" alt=""><?php endif; ?>
                <div>
                  <input type="file" name="<?= $key ?>" accept=".jpg,.jpeg,.png,.webp">
                  <div class="cur"><?= h($val) ?></div>
                  <p class="hint">Yeni dosya seçilirse mevcut görselin yerine geçer (en fazla <?= MAX_IMAGE_MB ?> MB).</p>
                </div>
              </div>
            <?php elseif ($f['type'] === 'video'): ?>
              <div class="media-row">
                <?php if ($val): ?><video src="../<?= h($val) ?>" muted></video><?php endif; ?>
                <div>
                  <input type="file" name="<?= $key ?>" accept=".mp4,.webm,.mov">
                  <div class="cur"><?= h($val) ?></div>
                  <p class="hint">MP4 önerilir; en fazla <?= MAX_VIDEO_MB ?> MB. Küçük dosya = hızlı site.</p>
                </div>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <button class="btn">Kaydet</button>
      </form>
    </div>
  <?php endif; ?>

<?php endif; ?>
</div>
</body>
</html>
