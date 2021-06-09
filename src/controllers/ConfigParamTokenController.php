<?php

namespace Smoren\Yii2\Auth\controllers;

use Smoren\Yii2\Auth\behaviors\ConfigParamTokenParamAuth;
use Smoren\Yii2\Auth\behaviors\UserTokenParamAuth;

/**
 * Базовый класс API контроллера с авторизацией
 */
abstract class ConfigParamTokenController extends BaseController
{
    protected static $except = ['options', 'OPTIONS'];

    /**
     * @return string
     */
    abstract protected static function getToken(): string;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => ConfigParamTokenParamAuth::class,
            'except' => static::$except,
            'token' => static::getToken(),
        ];

        return $behaviors;
    }
}
