<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%logs_sms}}`.
 */
class m260217_221859_create_logs_sms_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $sql = "
        CREATE TABLE logs_sms (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          parent_table ENUM('cart_order','reservation','marketing_campaign') DEFAULT NULL,
          parent_id INT UNSIGNED DEFAULT NULL,
          phone VARCHAR(100) NOT NULL,
          message MEDIUMTEXT NOT NULL,
          priority TINYINT DEFAULT 0,
          device_id VARCHAR(255) DEFAULT NULL,
          cost FLOAT NOT NULL DEFAULT 0,
          sent TINYINT UNSIGNED DEFAULT 0,
          delivered TINYINT UNSIGNED DEFAULT 0,
          error TEXT DEFAULT NULL,
          provider ENUM('inhousesms','wholesalesms','prowebsms','onverify','inhousesms-nz','inhousesms-my','inhousesms-au','inhousesms-au-marketing','inhousesms-nz-marketing') NOT NULL,
          status TINYINT NOT NULL DEFAULT 0,
          fetched_at TIMESTAMP NULL DEFAULT NULL,
          sent_at TIMESTAMP NULL DEFAULT NULL,
          delivered_at TIMESTAMP NULL DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          send_after TIMESTAMP NULL DEFAULT NULL,
          time_zone VARCHAR(55) DEFAULT NULL,
          PRIMARY KEY (id),
          INDEX IDX_logs_sms(provider, status, priority, id)
        )
        ENGINE = INNODB,
        AUTO_INCREMENT = 4448314,
        AVG_ROW_LENGTH = 269,
        CHARACTER SET utf8mb4,
        COLLATE utf8mb4_unicode_ci;
        ";

        $this->execute($sql);
        $this->createIndex('IDX_cart_created_at', 'logs_sms', 'created_at');
        $this->createIndex('IDX_logs_sms_order_id', 'logs_sms', ['parent_table', 'parent_id']);
        $this->createIndex('IDX_fetch_ready', 'logs_sms', ['status', 'provider', 'send_after']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('logs_sms');
    }
}
