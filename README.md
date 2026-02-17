# Yii2 & Docker SMS Management Case Study

Bu proje, yüksek hacimli veritabanlarında (1M+ satır) performanslı veri işleme ve zaman dilimi (timezone) yönetimi için geliştirilmiştir.

## Teknik Özellikler
- **Kuyruk Yönetimi:** Status 0 olan mesajlar, alıcının yerel saatine (09:00-23:00) göre filtrelenerek işlenir.
- **Performans:** `(status, provider, send_after)` üzerine kurulu Composite Index ile milyonluk tabloda milisaniyelik sorgu hızı.
- **Güvenlik:** Eşzamanlılık çakışmalarını (race condition) önlemek için `FOR UPDATE SKIP LOCKED` mimarisi.

## Kurulum ve Çalıştırma
1. `docker-compose up -d`
2. `docker-compose exec app php yii migrate`
3. Web üzerinden verileri yüklemek ve izlemek için: `http://localhost:8000/web/index.php?r=sms/index`
