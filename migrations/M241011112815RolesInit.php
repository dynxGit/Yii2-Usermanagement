<?php

namespace dynx\migrations;

use yii\db\Migration;

/**
 * Class M241011112815RolesInit
 */
class M241011112815RolesInit extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $roles = [
            '0_SYS_ADM' => "Sytem administrator",
            '1_SITE_ADM' => "Site administrator",
            '2_EDITOR' => "Site editor",

        ];
        $auth = \Yii::$app->authManager;
        foreach ($roles as $name => $description) {
            $role = $auth->createRole($name);
            $role->description = $description;
            $auth->add($role);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = \Yii::$app->authManager;
        $auth->removeAll();
    }
}
