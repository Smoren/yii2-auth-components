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
    protected static $tokenParamKey = 'token';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => ConfigParamTokenParamAuth::class,
            'except' => static::$except,
            'paramKey' => static::$tokenParamKey,
            'useEncryption' => false,
        ];

        return $behaviors;
    }
}
