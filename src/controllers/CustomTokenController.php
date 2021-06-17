<?php

namespace Smoren\Yii2\Auth\controllers;

use Smoren\Yii2\Auth\behaviors\ConfigParamTokenParamAuth;
use Smoren\Yii2\Auth\behaviors\ConstTokenParamAuth;
use Smoren\Yii2\Auth\behaviors\CustomTokenParamAuth;
use Smoren\Yii2\Auth\behaviors\UserTokenParamAuth;

/**
 * Базовый класс API контроллера с авторизацией
 */
abstract class CustomTokenController extends BaseController
{
    protected static $except = ['options', 'OPTIONS'];

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => ConstTokenParamAuth::class,
            'token' => $this->getValidToken(),
            'except' => static::$except,
            'useEncryption' => false,
        ];

        return $behaviors;
    }

    /**
     * Получение валидного токена
     * @return mixed
     */
    abstract protected function getValidToken();
}
