<?php

namespace Smoren\Yii2\Auth\behaviors;

use Smoren\Yii2\Auth\exceptions\TokenException;

/**
 * Поведение для авторизации через кастомное сравнение с правильным токеном
 */
class ConstTokenParamAuth extends CustomTokenParamAuth
{
    /**
     * @var string token
     */
    protected $token;

    /**
     * @param string $token
     */
    public function setToken(string $token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     * @throws TokenException
     */
    protected function getValidToken(): string
    {
        if($this->token === null) {
            throw new TokenException('no token in specified', TokenException::STATUS_LOGIC_ERROR);
        }

        return $this->token;
    }
}
