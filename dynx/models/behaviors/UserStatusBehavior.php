<?php

namespace dynx\models\behaviors;

use yii\db\ActiveRecord;
use yii\base\Behavior;
use dynx\Module;
use dynx\models\User;

class UserStatusBehavior extends Behavior
{
    public $statusEmailView = null;
    public $statusEmailResonse = null;

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT    => 'initStatus',
            ActiveRecord::EVENT_AFTER_FIND      => 'checkStatus',
            ActiveRecord::EVENT_AFTER_UPDATE    => 'setStatus',
        ];
    }

    public function initStatus($event) {}
    public function checkStatus($event) {
        $mod = Module::getInstance();

    }

    public function setStatus($event)
    {
        if (isset($event->changedAttributes['status'])) {
            switch ($this->owner->status) {
                    /* NEW USER */
                case User::STATUS_PENDING:
                    $this->statusEmailView = "user_create";
                    break;
                    /* email validated */
                case User::STATUS_VALIDATED:
                    $this->statusEmailView = "user_validated";

                    break;
                case User::STATUS_ACTIVE:
                    $this->statusEmailView = "user_active";

                    break;
                case User::STATUS_INACTIVE:

                    break;
            }
        }
        echo $this->owner->status;
    }
}
