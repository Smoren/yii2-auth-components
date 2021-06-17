<?php

namespace Smoren\Yii2\Auth\behaviors;

use Smoren\Yii2\Auth\exceptions\TokenException;
use Yii;
use yii\helpers\ArrayHelper;

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
     * @param $token
     */
    public function setToken($token)
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
