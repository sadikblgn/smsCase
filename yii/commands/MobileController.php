<?php

namespace app\commands;

use yii\console\Controller;
use Yii;

/**
 * SMS Case Study - Mobile Controller
 */
class MobileController extends Controller
{
    /**
     * Adım 4: Tabloyu temizler ve 1.050.000 satır rastgele veri ekler.
     * Kullanım: php yii mobile/populate-random-data
     */
    public function actionPopulateRandomData()
    {
        echo "Tablo temizleniyor (TRUNCATE)...\n";
        Yii::$app->db->createCommand()->truncateTable('logs_sms')->execute();

        $timeZones = [
            'Australia/Melbourne', 'Australia/Sydney', 'Australia/Brisbane',
            'Australia/Adelaide', 'Australia/Perth', 'Australia/Tasmania',
            'Pacific/Auckland', 'Asia/Kuala_Lumpur', 'Europe/Istanbul'
        ];

        $batchSize = 5000; // Bellek yönetimi için verileri paketler halinde ekleyeceğiz.
        
        echo "1.000.000 adet (Status 1) veri ekleniyor...\n";
        $this->generateBatchData(1000000, 1, $timeZones, $batchSize);

        echo "50.000 adet (Status 0) veri ekleniyor...\n";
        $this->generateBatchData(50000, 0, $timeZones, $batchSize);
        
        echo "\nVeri doldurma işlemi tamamlandı!\n";
    }

    private function generateBatchData($count, $status, $timeZones, $batchSize)
    {
        $rows = [];
        $columns = ['phone', 'message', 'status', 'provider', 'time_zone', 'send_after', 'created_at'];

        for ($i = 1; $i <= $count; $i++) {
            $tz = $timeZones[array_rand($timeZones)];
            
            $sendAfter = null;
            if ($status === 0) {
                // Şimdiki zamandan 2 saat öncesi ile 2 gün sonrası arası
                $timestamp = rand(time() - 7200, time() + 172800);
                $sendAfter = date('Y-m-d H:i:s', $timestamp);
            }

            $rows[] = [
                '04' . rand(10000000, 99999999),
                "Random SMS message " . Yii::$app->security->generateRandomString(120),
                $status,
                'inhousesms',
                $tz,
                $sendAfter,
                date('Y-m-d H:i:s')
            ];

            if (count($rows) >= $batchSize) {
                Yii::$app->db->createCommand()->batchInsert('logs_sms', $columns, $rows)->execute();
                $rows = [];
                if ($i % 50000 === 0) echo "."; 
            }
        }

        if (!empty($rows)) {
            Yii::$app->db->createCommand()->batchInsert('logs_sms', $columns, $rows)->execute();
        }
    }

    /**
     * Adım 5 & 6: Mesajları seç, kilitle ve gönderildi olarak işaretle.
     * Kullanım: php yii mobile/get-messages-to-send
     */
    public function actionGetMessagesToSend()
    {
        $limit = 5;
        $now = new \DateTime('now', new \DateTimeZone('Australia/Melbourne'));
        $nowStr = $now->format('Y-m-d H:i:s');

        // Eşzamanlılık (Concurrency) kontrolü için Transaction başlatıyoruz.
        $transaction = Yii::$app->db->beginTransaction();

        try {
            /**
             * OPTİMİZASYON NOTLARI (Adım 6):
             * 1. FOR UPDATE SKIP LOCKED: Aynı anda çalışan başka scriptlerin kilitlediği satırları atlar.
             * 2. Limit 100: Saat dilimi kontrolü PHP'de yapılacağı için geniş bir aday listesi çekiyoruz.
             * 3. Index Kullanımı: Migration'da eklediğimiz IDX_fetch_ready sayesinde 1M veri taranmaz.
             */
            $sql = "SELECT * FROM logs_sms 
                    WHERE status = 0 
                    AND provider = 'inhousesms' 
                    AND send_after <= :now
                    ORDER BY id ASC
                    LIMIT 100 
                    FOR UPDATE SKIP LOCKED";

            $candidates = Yii::$app->db->createCommand($sql)
                ->bindValue(':now', $nowStr)
                ->queryAll();

            $selectedIds = [];
            $results = [];

            foreach ($candidates as $row) {
                if (count($results) >= $limit) break;

                if ($this->isLocalTimeValid($row['time_zone'])) {
                    $selectedIds[] = $row['id'];
                    $results[] = $row;
                }
            }

            if (!empty($selectedIds)) {
                Yii::$app->db->createCommand()->update('logs_sms', [
                    'status' => 1,
                    'sent' => 1,
                    'sent_at' => $nowStr
                ], ['id' => $selectedIds])->execute();

                $transaction->commit();

                echo "--- Gönderilen Mesajlar ---\n";
                foreach ($results as $msg) {
                    echo "ID: {$msg['id']} | Tel: {$msg['phone']} | TZ: {$msg['time_zone']}\n";
                }
            } else {
                $transaction->rollBack();
                echo "Kriterlere uygun mesaj bulunamadı.\n";
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            echo "Hata: " . $e->getMessage() . "\n";
        }
    }

    private function isLocalTimeValid($tz)
    {
        try {
            $localTime = new \DateTime('now', new \DateTimeZone($tz));
            $hour = (int)$localTime->format('G');
            // Yerel saat 09:00 ile 23:00 (dahil) arası mı?
            return ($hour >= 9 && $hour <= 23);
        } catch (\Exception $e) {
            return false;
        }
    }
}