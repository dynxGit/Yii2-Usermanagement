<?php

/**
 * YII2 usermanager 
 * ----------
 * User management module for Yii2 framework
 * Version 1.0.0
 * Copyright (c) 2024
 * András Szincsák, Győr Hungary
 * MIT License
 * https://github.com/dynxGit/dinx
 */

namespace dynx;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Module as YiiModule;
use yii\base\BootstrapInterface;
use yii\console\Application as ConsoleApplication;
use yii\web\Application as WebApplication;
use yii\web\GroupUrlRule;
use yii\web\UserEvent;
use yii\helpers\ArrayHelper;
use dynx\models\User;

/**
 * user module definition class
 */
class Module extends YiiModule implements BootstrapInterface
{
    public $SUAemail = null; //TODO Clear default value
      /**
     * @var array options for app mailer
     * @link https://www.yiiframework.com/doc/api/2.0/yii-mail-basemailer
     * If you want to override the mailer views, set viewPath.
     */
    public $mailOptions = [
        'viewPath' => '@dynx/mail',
        'htmlLayout' => '@dynx/mail/layouts/html',
        'textLayout' => '@dynx/mail/layouts/text',
    ];

    public $loginAttempt = 3;
    public $loginAttemptTimeout = 10;
    /**
     * How many seconds confirmation token will be invalid
     */
    public $tokenExpired = 3600; // 1 hour

    /**
     * Format of pin (sections separated by "-")
     * Section type : last Char in section (C:Character N:Number)
     * section count: Number of the chars is the left part of the section 
     * 
     * if section count is not number the section is fix!  (XYZ-4N = XYZ-1234)
     * default: "3C-4N" result 3 character and 4 number : ABC-1234
     */
    public $pinFormat = "3C-4N";

    /**
     * LOGIN input type (email or username default:email)
     */
    public $loginInput = "email";
    /**
     * User account Tryout timeframe in day. 0 means no account limit 
     */
    public $tryout = 30; //days


    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        /*
        if (! Yii::$app->has('authManager')) {
            throw new InvalidConfigException('$app::authManager is not configured.');
        }
    */
        parent::init();
        Yii::configure($this, require __DIR__ . '/config/config.php');

        if (! isset(Yii::$app->i18n->translations['dynx/*'])) {
            Yii::$app->i18n->translations['dynx/*'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'en',
                'basePath' => '@dynx/messages/',
                'fileMap'        => [
                    'dynx/ar' => 'models.php',

                ],
            ];
        }
    }

    /**
     * @param $event  UserEvent
     */
    public static function beforeLogin($event)
    {
        /* @var $user User */
        $user = $event->identity;
        if ($user->status != User::STATUS_ACTIVE) $event->isValid = false;  // holds blocked user
        else {
            $user->touch('lastlogin_at');
            $user->updateCounters(['login_count' => 1]);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidConfigException
     */
    public function bootstrap($app)
    {
        if ($app instanceof WebApplication) {
            $app->on($app::EVENT_BEFORE_ACTION, [$this, 'beforeAction']);
        } else {
            /* @var $app ConsoleApplication */
            $app->controllerMap = ArrayHelper::merge($app->controllerMap, [
                'migrate-dynx' => [
                    'class' => '\yii\console\controllers\MigrateController',
                    'migrationPath' => null,
                    'migrationNamespaces' => [
                        'dynx\migrations'
                    ]
                ],
                //  'dynx-DB' => 'dynx\commands\DynxController'
            ]);
        }
    }


    public function getConfigField($fieldname)
    {
        return (isset($this->config[$fieldname])) ? $this->config[$fieldname] : null;
    }

    /**
     * @param $event ActionEvent
     */
    public function beforeAction($event)
    {
        $request = Yii::$app->request;
        $lang = $request->get('lang');
        if ($lang) {
            Yii::$app->session->set('language', $lang);
        } else {
            $lang = Yii::$app->session->get('language');
        }
        if (!Yii::$app->user->isGuest)
            $lang = Yii::$app->user->identity->lang;
        if ($lang) Yii::$app->language = $lang;
    }
    /**
     * Check how much attempts user has been made in X seconds
     *
     * @return bool
     */
    public function attemptValidation()
    {
        $lastAttempt = Yii::$app->session->get("dy_attempt_last");

        if ($lastAttempt) {
            $attempts = Yii::$app->session->get("dy_attempt_count", 0);

            Yii::$app->session->set("dy_attempt_count", ++$attempts);
            if (($lastAttempt + $this->loginAttemptTimeout) < time()) {
                Yii::$app->session->set("dy_attempt_last", time());
                Yii::$app->session->set("dy_attempt_count", 1);

                return true;
            } elseif ($attempts > $this->loginAttempts) {
                return false;
            }

            return true;
        }

        Yii::$app->session->set("dy_attempt_last", time());
        Yii::$app->session->set("dy_attempt_count", 1);

        return true;
    }

    public function __get($name)
    {
        if (substr($name, 0, 3) === 'cfg') {
            $attribute = lcfirst(substr($name, 3));
            return (isset($this->config[$attribute])) ? $this->config[$attribute] : null;
        }
        return parent::__get($name);
    }
}
