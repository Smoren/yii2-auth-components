<?php

namespace Smoren\Yii2\Auth\behaviors;

use Smoren\Yii2\Auth\exceptions\TokenException;
use Yii;

/**
 * Поведение для авторизации через кастомное сравнение с правильным токеном
 */
abstract class ConfigParamTokenParamAuth extends CustomTokenParamAuth
{
    /**
     * @var string ключ токена в секции params конфига
     */
    protected $paramKey = 'token';

    /**
     * Установка ключа токена в секции params конфига
     * @param string $paramKey
     * @return $this
     */
    public function setToken(string $paramKey): self
    {
        $this->paramKey = $paramKey;
        return $this;
    }

    /**
     * @return string
     * @throws TokenException
     */
    protected function getValidToken(): string
    {
        $token = Yii::$app->params[$this->paramKey] ?? null;

        if($token === null) {
            throw new TokenException('no token in params', TokenException::STATUS_LOGIC_ERROR);
        }

        return $token;
    }
}