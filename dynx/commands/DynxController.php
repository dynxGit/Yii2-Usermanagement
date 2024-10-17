<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace dynx\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class DynxController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @return int Exit code
     */
    public function actionIndex()
    {
        $auth = Yii::$app->authManager;
        $auth->removeAll();
        $admin = $auth->createRole('SUA');
        $admin->description = 'SuperUser';
        $auth->add($admin);

        echo "Rules set\n";

        return ExitCode::OK;
    }
}
