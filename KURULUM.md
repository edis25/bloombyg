# Bloom By G — Site & Yönetim Paneli Kurulumu

## Yapı

- `index.html` — site (içeriğini `content/site.json`'dan okur)
- `content/site.json` — bütün düzenlenebilir içerik (metinler, görseller, videolar, galeri)
- `panel/` — yönetim paneli (PHP; veritabanı gerektirmez)
- `assets/` — stiller, scriptler, görseller, videolar

## cPanel'e kurulum

1. Tüm proje dosyalarını zip'leyin ve cPanel → **Dosya Yöneticisi** ile
   `public_html` içine yükleyip çıkartın (ya da FTP ile aktarın).
2. `content/site.json` dosyasının ve `assets/img/bloom/`, `assets/video/`
   klasörlerinin **yazılabilir** olduğundan emin olun (izin: 664 / klasörler 775;
   çoğu cPanel'de varsayılan haliyle çalışır).
3. Panel: `https://siteniz.com/panel/`
   - Varsayılan şifre: **bloom2026**
   - Şifreyi değiştirmek için `panel/config.php` içindeki açıklamayı izleyin.
4. PHP 7.4+ yeterlidir (8.x önerilir). Veritabanı gerekmez.

## Panel neleri düzenler?

- **Slider**: 3 slaytın yazıları; 1. slaytın masaüstü/mobil videoları; 2-3. slayt görselleri
- **Hikaye / Özel Davetler / Deneyim / İletişim**: tüm başlık ve paragraflar,
  görseller, davetler videosu, etkinlik listesi, adres-telefon-Instagram
- **Galeri**: görsel ekleme, silme, sıralama (ok butonları)

## Notlar

- Panelden yüklenen büyük videolar siteyi yavaşlatır; 1080p / H.264 /
  10 MB altı MP4 önerilir.
- Video yükleme sınırı hosting'in PHP ayarına da bağlıdır
  (`upload_max_filesize`, `post_max_size`); gerekirse cPanel →
  "Select PHP Version → Options" bölümünden yükseltin.
- Netlify demosu (bloombyg.netlify.app) statiktir; panel orada çalışmaz,
  yalnızca PHP destekli hosting'de çalışır.
