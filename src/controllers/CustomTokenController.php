<?php

namespace Smoren\Yii2\Auth\controllers;

use Smoren\Yii2\Auth\behaviors\ConstTokenParamAuth;

/**
 * Базовый класс API контроллера с авторизацией
 */
abstract class CustomTokenController extends BaseController
{
    protected static $except = ['options', 'OPTIONS'];
    protected static $useEncryption = false;

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
            'useEncryption' => static::$useEncryption,
        ];

        return $behaviors;
    }

    /**
     * Получение валидного токена
     * @return mixed
     */
    abstract protected function getValidToken(): string;
}
