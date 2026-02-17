<?php
$this->title = 'SMS Gönderim Paneli';
use yii\helpers\Url; 
?>
<div class="sms-index">
    <h1><?= $this->title ?></h1>

    <!-- <?php foreach (Yii::$app->session->getAllFlashes() as $key => $message): ?>
        <div class="alert alert-<?= $key ?>"><?= $message ?></div>
    <?php endforeach; ?> -->

    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-12">
            <?php if ($pendingCount == 0): ?>
                <a href="<?= Url::to(['sms/seed']) ?>" class="btn btn-primary btn-lg">
                    <i class="glyphicon glyphicon-plus"></i> İlk Test Verilerini Yükle (10.000 Kayıt)
                </a>
            <?php else: ?>
                <a href="<?= Url::to(['sms/process']) ?>" class="btn btn-success btn-lg">
                    <i class="glyphicon glyphicon-send"></i> Sıradaki 5 Mesajı İşle ve Gönder
                </a>
                <a href="<?= Url::to(['sms/seed']) ?>" class="btn btn-default">
                    Daha Fazla Veri Ekle
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="well">
                <h3>Bekleyen SMS</h3>
                <p style="font-size: 24px; font-weight: bold; color: orange;">
                    <?= number_format((int)$pendingCount) ?>
                </p>
            </div>
        </div>
    </div>

    <h3>Son Gönderilen 50 Mesaj (Status 1)</h3>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Telefon</th>
                <th>Zaman Dilimi</th>
                <th>Gönderilme Tarihi</th>
                <th>Mesaj</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sentMessages as $msg): ?>
            <tr>
                <td><?= $msg['id'] ?></td>
                <td><?= $msg['phone'] ?></td>
                <td><?= $msg['time_zone'] ?></td>
                <td><?= $msg['sent_at'] ?></td>
                <td><?= mb_strimwidth($msg['message'], 0, 50, "...") ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>