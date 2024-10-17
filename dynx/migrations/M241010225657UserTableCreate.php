<?php

namespace dynx\migrations;

use yii\db\Migration;

/**
 * Class M241010225657UserTableCreate
 */
class M241010225657UserTableCreate extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        $this->createTable('user', [
            'id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(60)->notNull(),
            'email' => $this->string(128)->null()->unique(),
            'email_confirmed' => $this->smallInteger(1)->notNull()->defaultValue(0),
            'status' => $this->smallInteger(1)->notNull()->defaultValue(0),
            'pin' => $this->string(128)->null()->unique(),
            'password_hash' => $this->string(128)->null(),
            'auth_key' => $this->string(32)->null(),
            'token' => $this->string(48)->null()->unique(),
            'lang' => $this->string(5)->notNull()->defaultValue('en'),
            'created_at' => $this->timestamp()->null(),
            'updated_at' => $this->timestamp()->null(),
            'blocked_at' => $this->timestamp()->null(),
            'deleted_at' => $this->timestamp()->null(),
            'lastlogin_at' => $this->timestamp()->null(),
            'login_count' => $this->integer()->unsigned()->defaultValue(0)
        ], $tableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('user');
    }


}
