<?php

namespace Smoren\Yii2\Auth\controllers;

use Smoren\Yii2\Auth\behaviors\UserTokenParamAuth;

/**
 * Базовый класс API контроллера с авторизацией
 */
class UserTokenController extends BaseController
{
    protected static $except = ['options', 'OPTIONS'];

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => UserTokenParamAuth::class,
            'except' => static::$except,
        ];

        return $behaviors;
    }
}