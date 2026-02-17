# Yii2 & Docker SMS Management Case Study

Bu proje, yüksek hacimli veritabanlarında (1M+ satır) performanslı veri işleme ve zaman dilimi (timezone) yönetimi için geliştirilmiştir.

🚀 Kurulum ve Çalıştırma (Installation)
Bu proje Dockerize edilmiştir. Çalıştırmak için sisteminizde Docker ve Docker Compose kurulu olmalıdır.

Projeyi Klonlayın:
git clone <repo-url>
cd <proje-klasoru>

Konteynerleri Başlatın:
(Bu komut gerekli PHP sürücülerini ve Apache ayarlarını otomatik olarak yapılandıracaktır)
docker-compose up -d

Veritabanını Hazırlayın:
docker-compose exec app php yii migrate

Arayüze Erişin:
Tarayıcınızdan şu adresi açın:
http://localhost:8000/web/index.php?r=sms/index

Not: Eğer veritabanınız boşsa, arayüzdeki "İlk Test Verilerini Yükle" butonunu kullanarak sistemi anında test edebilirsiniz.

🛠 Teknik Özellikler
Kuyruk Yönetimi: Status 0 olan mesajlar, alıcının yerel saatine (09:00-23:00) göre filtrelenerek işlenir.
Performans: (status, provider, send_after) üzerine kurulu Composite Index ile milyonluk tabloda milisaniyelik sorgu hızı.
Concurrency: Eşzamanlılık çakışmalarını (race condition) önlemek için MySQL 8 FOR UPDATE SKIP LOCKED mimarisi.
Zaman Dilimi Desteği: PHP katmanında dinamik DateTimeZone kontrolü ile her mesajın alıcısına kendi yerel saatine göre gönderilmesi sağlanır.
