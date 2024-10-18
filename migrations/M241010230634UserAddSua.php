<?php

namespace dynx\migrations;

use yii\db\Migration;

use dynx\Module;
use dynx\models\User;

/**
 * Class M241010230634UserAddSua
 */
class M241010230634UserAddSua extends Migration
{
	public function safeUp()
	{
		$mod=Module::getInstance();
		if($mod->SUAemail){
		$user = new User();
		$user->status = User::STATUS_PENDING;
		$user->name = 'SUA';
		$user->email = isset($mod->SUAemail)?$mod->SUAemail:'sua@dynx.hu';
		$user->pin = $user->generatePin();
		$user->save();

		/* Add SUA role */
		$auth = \Yii::$app->authManager;
		$auth->removeAll();
		$admin = $auth->createRole('SUA');
        $admin->description = 'SuperUser';
        $auth->add($admin);
		$auth->assign($admin, $user->id);
		} else 
		{
			echo  "SUA email is not defined in Module config!\n";
			return false;
		}
	}

	public function safeDown()
	{
		$mod=Module::getInstance();
		$suaEmail=isset($mod->SUAemail)?$mod->SUAemail:'sua@dynx.hu';
		$user = User::findByEmail($suaEmail);
		if ($user) {
			$user->delete();
		}

		/* REMOVE roles */
		$auth = \Yii::$app->authManager;
		$admin = $auth->getRole('SUA');
		if($admin)
		$auth->remove($admin);
	}

}
