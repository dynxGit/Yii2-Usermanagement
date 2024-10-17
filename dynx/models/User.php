<?php

/**
 * DYNX-Usermanagement
 * ----------
 * User management module for Yii2 framework
 * Version 1.0.0
 * Copyright (c) 2024
 * András Szincsák, Győr Hungary
 * MIT License
 * https://github.com/dynxGit/dynx
 */

namespace dynx\models;

use Yii;
use yii\base\InvalidCallException;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\web\IdentityInterface;
use yii\helpers\ArrayHelper;
use dynx\Module;
use dynx\components\DyActiveRecord;
use dynx\models\behaviors\UserStatusBehavior;
use dynx\models\behaviors\UserPinBehavior;


/**
 * User model
 *
 * @property integer $id
 * @property string $name
 * @property string $email
 * @property integer $email_confirmed
 * @property string $status
 * @property string $pin
 * @property string $password_hash
 * @property string $auth_key
 * @property string $token
 * @property string $lang
 * @property string $created_at
 * @property string $updated_at
 * @property string $blocked_at
 * @property string $deleted_at
 * @property string $lastlogin_at
 * @property integer $login_count
 * @property int $role [smallint(6)]
 *
 * @method touch($attribute)
 */
class User extends DyActiveRecord implements IdentityInterface
{
    //  use Captcha, Password;
    const STATUS_PENDING   = 0;   // Just registered - send code
    const STATUS_VALIDATED = 4;   // Email validated - wait password
    const STATUS_ACTIVE    = 1;   // Acount is ready (is in tryout interval or is payed)
    const STATUS_INACTIVE  = 2;   // Acount is delayed ( end of tryout or not payed)
    const STATUS_BANNED    = 98;  // Banned for some reason
    const STATUS_REMOVED   = 99;  // User deleted 


    public $password;
    public $flags = [];
    public $roles = []; // role *names*

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ],
            'status'=>UserStatusBehavior::class,
            'pin'=>UserPinBehavior::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $mod = Module::getInstance();

        $r = ArrayHelper::merge([
            ['name', 'trim'],
            ['name', 'required'],
            ['name', 'string', 'min' => 2, 'max' => 60],

            ['email', 'trim'],
            ['email', 'required', 'except' => 'delete'],
            ['email', 'email'],
            ['email', 'string', 'max' => 128],
            [
                'email',
                'unique',
                'targetClass' => '\dynx\models\User',
                'message' => self::t('arUser', 'This email address has already been taken')
            ],

            ['password', 'required', 'on' => ['create']],
            ['password', 'encryptPassword', 'on' => ['create', 'update']],
            ['password', 'validatePassword', 'on' => ['settings', 'delete']],

            ['status', 'default', 'value' => self::STATUS_PENDING],
            ['status', 'in', 'range' => [self::STATUS_PENDING, self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_VALIDATED, self::STATUS_BANNED, self::STATUS_REMOVED]],
            ['status', 'required', 'on' => ['create', 'update']],

            [['singleRole', 'roles','password_hash'], 'safe']
        ], 
     //   $this->captchaRules(), 
    //    $this->passwordRules()
    []
    );
        return $r;
    }


    /**
     * {@inheritdoc}
     * IdentityInterface
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     * IdentityInterface
     * @throws NotSupportedException
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return 
        static::find(['token' => $token])
        ->andWhere(["<",'status',self::STATUS_BANNED])
        ->one();
    }
    /**
     * @param $email
     * @param int $status
     * @return User|null
     */
    public static function findByEmail($email, $status = self::STATUS_BANNED)
    {
        return 
        static::find(['email' => $email])
        ->andWhere(["<=",'status',$status])
        ->one();
    }

    /**
     * {@inheritdoc}
     * IdentityInterface
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     * IdentityInterface
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     * IdentityInterface
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Inline validation.
     * @param string $attribute the attribute currently being validated
     * @param array
     */
    public function validatePassword($attribute, $params)
    {
        if (! $this->isPasswordValid($this->$attribute)) {
            $this->addError($attribute, self::t('model','Incorrect password'));
        }
    }

    /**
     * Inline validation, sets password hash.
     * @param string $attribute the attribute currently being validated
     * @param array
     * @throws \yii\base\Exception
     */
    public function encryptPassword($attribute, $params)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($this->$attribute);
    }





    /**
     * @param string $action
     * @param string $token
     * @param string $status
     * @return static|null
     */
    public static function findByToken($token, $status = self::STATUS_ACTIVE)
    {
        if (empty($token)) return null;

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        if ($timestamp < time()) return null;   // token expired

        return static::findOne([
            'token' => $token,
            'status' => $status,
        ]);
    }

    /**
     * Generates new token with expire interval from tokenExpired 
     */
    public function generateToken()
    {
        $expired = time() + Yii::$app->controller->module->tokenExpired;
        $this->token = Yii::$app->security->generateRandomString() . '_' . $expired;
    }


    /**
     */
    public function removeToken()
    {
        $this->token = null;
    }

    /**
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function isPasswordValid($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * @throws \yii\base\Exception
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * @return mixed|string
     */
    public function getSingleRole()
    {
        if (count($this->roles) > 1) return '<multiple>';
        return empty($this->roles) ? '' : current($this->roles);
    }

    /**
     * @param $role string
     */
    public function setSingleRole($role)
    {
        $this->roles = empty($role) ? [] : [$role];
    }

    /**
     * @param $subject
     * @param $view
     * @param array $options
     * @return bool
     */
    public function sendEmail($subject, $view, $options = [])
    {
        $mailView = [
            'html' => "$view-html",
            'text' => "$view-text",
        ];
        $from = Yii::$app->params['supportEmail'] ?? Yii::$app->params['adminEmail'];
        $options['user'] = $this;

        $mailer = Yii::$app->mailer;
        foreach (Yii::$app->controller->module->mailOptions as $key => $value) {
            $mailer->$key = $value;
        }
        return $mailer
            ->compose($mailView, $options)
            ->setFrom([$from => Yii::$app->name . ' robot'])
            ->setTo($this->email)
            ->setSubject($subject)
            ->send();
    }

    /**
     * @param $subject
     * @param $view
     * @param array $options
     * @param $linkAction string - the action part in the link; if null, $view is taken
     * @return bool
     * @throws \yii\base\Exception
     */
    public function sendTokenEmail($subject, $view, $options = [], $linkAction = null)
    {
        if (is_null($this->token)) {
            throw new InvalidCallException('User token is not set.');
        }
        if (is_null($linkAction)) $linkAction = $view;
        $module = Yii::$app->controller->module;

        $options['link'] = Yii::$app->urlManager->createAbsoluteUrl([$module->id . '/default/' . $linkAction, 'token' => $this->token]);

        return $this->sendEmail($subject, $view, $options);
    }

    /**
     * User is never removed from database.
     * Instead the status is set to STATUS_DELETED, name is changed into a generic name, and other attributes are cleared.
     * Thus dangling foreign pointers (i.e. created_by) are avoided.
     * Profile record, if it exists, is deleted completely.
     * @return false|int
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     */
    public function delete()
    {
        if (!$this->beforeDelete()) {
            return false;
        }
        $this->scenario = 'delete';
        $this->status = self::STATUS_REMOVED;
        $unique = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT); // six digits
        $this->name = 'nn-' . $unique;
        $this->auth_key = null;
        $this->password_hash = null;
        $this->pin=null;

        /*  Some SQL servers, like MS SQL Server, are not compatible with ANSI standards, and don't allow multiple NULL-values
            in UNIQUE records. Therefore email gets a random value, conforming to the email-format.
            With mySQL, email could simply be set to null.
            Thanks to duocreator and Ross Addison.
            @link https://stackoverflow.com/a/767702/4270194
        */
        $this->email = "$unique@$unique.com";
        $this->deleted_at = new Expression('NOW()');
        $r = $this->save();

        $auth = Yii::$app->authManager;
        $auth->revokeAll($this->id);    // revoke all roles

        $prClass = null;;//Module::getInstance()->profileClass;
        if ($prClass) {    // delete profile, if any
            /* @var $prClass yii\db\BaseActiveRecord */
            /* @var $profile yii\db\BaseActiveRecord */
            $profile = $prClass::findOne($this->id);
            if ($profile) $profile->delete();
        }

        if ($r) $this->afterDelete();
        return $r ? 1 : false;   // the number of Users effected | false
    }

    /**
     * @param bool $insert
     * @return bool
     * @throws \yii\base\Exception
     */
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->generateAuthKey();
            if (!$this->lang) {
                $this->lang = Yii::$app->language;
            }
        }
        if ($this->status == self::STATUS_REMOVED && $this->isAttributeChanged('status')) {
            $this->deleted_at = new Expression('NOW()');
        }
        return parent::beforeSave($insert);
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     * @throws \Exception
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
        }
        parent::afterSave($insert, $changedAttributes);
/*
        $auth = Yii::$app->authManager;
        $auth->revokeAll($this->id);    // revoke old roles
        foreach ($this->roles as $roleName) {
            $role = $auth->getRole($roleName);
            $auth->assign($role, $this->id);    // assign new roles
        }

        if ($insert) {
            $this->createProfile();
        }
  */
        }

         /**
     * @param $subject
     * @param $view
     * @param array $options
     * @return bool
     */
    public function sendSysEmail( $view, $options = [])
    {
       $subject=""; 
        $mailView = [
            'html' => "$view-html",
            'text' => "$view-text",
        ];
        $from = Yii::$app->params['supportEmail'] ?? Yii::$app->params['adminEmail'];
        $options['user'] = $this;

        $mailer = Yii::$app->mailer;
        foreach (Yii::$app->controller->module->mailOptions as $key => $value)  {
            $mailer->$key = $value;
        }
        return $mailer
            ->compose($mailView, $options)
            ->setFrom([$from => Yii::$app->name . ' robot'])
            ->setTo($this->email)
            ->setSubject($subject)
            ->send();
    }   

    /**
     *
     */
    public function afterFind()
    {
        parent::afterFind();
        $this->pin=$this->generatePin();
        /* @var $auth yii\rbac\BaseManager */
        $auth = Yii::$app->authManager;
        $userRoles = $auth->getRolesByUser($this->id);  // yii\rbacRole[]
        $defaultRoles = $auth->getDefaultRoles();   // string[]
        $this->roles = array_diff(array_keys($userRoles), $defaultRoles);
    }

    /**
     * @return string
     */
    public function getStatusText()
    {
        $stats = [
            self::STATUS_PENDING => self::t('dynx', 'Pending'),
            self::STATUS_VALIDATED => self::t('dynx', 'Validated'),
            self::STATUS_ACTIVE => self::t('dynx', 'Active'),
            self::STATUS_INACTIVE => self::t('dynx', 'Inactive'),
            self::STATUS_REMOVED => self::t('dynx', 'Deleted'),
            self::STATUS_BANNED => self::t('dynx', 'Blocked'),
        ];
        return $stats[$this->status];
    }

    /**
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function createProfile()
    {
        $prClass = Module::getInstance()->profileClass;
        if ($prClass) {
            if (is_string($prClass)) $prClass = ['class' => $prClass];
            $cls = ArrayHelper::remove($prClass, 'class');
            $profile = Yii::createObject($cls, $prClass);
            if ($profile) {
                $pk = current($profile->primaryKey());       // name of the primary key, not the value
                $profile->{$pk} = $this->id;
                if ($profile->hasAttribute('name')) {
                    $profile->name = $this->name;
                }
                return $profile->save(false);
            }
        }
        return true;
    }

    /**
     * @return \yii\db\BaseActiveRecord|null
     */
    public function getProfile()
    {
        /* @var $prClass yii\db\BaseActiveRecord */
        $prClass = Module::getInstance()->profileClass;
        if (! $prClass) return null;
        return $prClass::findOne($this->id);
    }
}
