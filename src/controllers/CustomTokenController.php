<?php

namespace Smoren\Yii2\Auth\controllers;

use Smoren\Yii2\Auth\behaviors\ConstTokenParamAuth;

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
        $encryptionParams = $this->getEncryptionParams();

        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => ConstTokenParamAuth::class,
            'token' => $this->getValidToken(),
            'except' => static::$except,
            'useEncryption' => $encryptionParams !== null,
            'encryptionParamsGetter' => function() {
                return $this->getEncryptionParams();
            },
        ];

        return $behaviors;
    }

    /**
     * Получение валидного токена
     * @return mixed
     */
    abstract protected function getValidToken(): string;

    /**
     * @return array
     */
    protected function getEncryptionParams(): ?array
    {
        return null;
    }
}
