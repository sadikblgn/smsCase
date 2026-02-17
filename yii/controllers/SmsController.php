<?php

namespace app\controllers;

use yii\web\Controller;
use Yii;

class SmsController extends Controller
{
    public function actionIndex()
    {
        try {
            // Gönderilen son 50 mesajı çekiyoruz
            $sentMessages = Yii::$app->db->createCommand("
                SELECT * FROM logs_sms 
                WHERE status = 1 
                ORDER BY sent_at DESC 
                LIMIT 50
            ")->queryAll();

            // Bekleyen mesaj sayısını çekiyoruz
            $pendingCount = Yii::$app->db->createCommand("
                SELECT COUNT(*) FROM logs_sms WHERE status = 0
            ")->queryScalar();

            return $this->render('index', [
                'sentMessages' => $sentMessages,
                'pendingCount' => $pendingCount
            ]);
        } catch (\Exception $e) {
            // Eğer veritabanı hatası varsa ekrana yazdır ki görelim
            return "Veritabanı Hatası: " . $e->getMessage();
        }
    }

    public function actionProcess()
    {
        $nowStr = date('Y-m-d H:i:s');
        $limit = 5;

        $transaction = Yii::$app->db->beginTransaction();
        try {
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
            foreach ($candidates as $row) {
                if (count($selectedIds) >= $limit) break;
                
                // Timezone kontrolü (09:00 - 23:00 arası mı?)
                $localTime = new \DateTime('now', new \DateTimeZone($row['time_zone']));
                $hour = (int)$localTime->format('G');
                
                if ($hour >= 9 && $hour <= 23) {
                    $selectedIds[] = $row['id'];
                }
            }

            if (!empty($selectedIds)) {
                Yii::$app->db->createCommand()->update('logs_sms', [
                    'status' => 1,
                    'sent' => 1,
                    'sent_at' => $nowStr
                ], ['id' => $selectedIds])->execute();

                $transaction->commit();
                Yii::$app->session->setFlash('success', count($selectedIds) . " adet mesaj başarıyla gönderildi!");
            } else {
                $transaction->rollBack();
                Yii::$app->session->setFlash('warning', "Kriterlere uygun (saat dilimi tutan) mesaj bulunamadı.");
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', "Hata oluştu: " . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    public function actionSeed()
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300); 

        $timeZones = [
            'Australia/Melbourne', 'Australia/Sydney', 'Australia/Brisbane',
            'Australia/Adelaide', 'Australia/Perth', 'Pacific/Auckland', 
            'Asia/Kuala_Lumpur', 'Europe/Istanbul'
        ];
        
        $batchSize = 2000;
        // Web arayüzü kasmaması için 100.000 (Status 1) + 10.000 (Status 0) yapıyoruz
        $counts = [1 => 100000, 0 => 10000]; 

        try {
            foreach ($counts as $status => $total) {
                for ($i = 0; $i < ($total / $batchSize); $i++) {
                    $rows = [];
                    for ($j = 0; $j < $batchSize; $j++) {
                        $tz = $timeZones[array_rand($timeZones)];
                        $sendAfter = ($status === 0) ? date('Y-m-d H:i:s', rand(time() - 7200, time() + 86400)) : null;

                        $rows[] = [
                            '04' . rand(10000000, 99999999),
                            "Web Generated SMS: " . Yii::$app->security->generateRandomString(100),
                            $status,
                            'inhousesms',
                            $tz,
                            $sendAfter,
                            date('Y-m-d H:i:s')
                        ];
                    }
                    Yii::$app->db->createCommand()->batchInsert('logs_sms', 
                        ['phone', 'message', 'status', 'provider', 'time_zone', 'send_after', 'created_at'], 
                        $rows
                    )->execute();
                }
            }
            Yii::$app->session->setFlash('success', "Başarılı! 110.000 test verisi (Command mantığıyla) oluşturuldu.");
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', "Hata: " . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

}