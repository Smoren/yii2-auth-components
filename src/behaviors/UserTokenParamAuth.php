<?php

namespace Smoren\Yii2\Auth\behaviors;

use Smoren\Yii2\Auth\exceptions\TokenException;
use yii\web\IdentityInterface;
use yii\web\User;

/**
 * Поведение авторизации через токен пользователя
 */
class UserTokenParamAuth extends BaseTokenParamAuth
{
    /**
     * @inheritDoc
     */
    protected function getIdentity(string $token, User $user): IdentityInterface
    {
        if(($identity = $user->loginByAccessToken($token, get_class($this))) instanceof IdentityInterface) {
            return $identity;
        }

        throw new TokenException('token invalid', TokenException::STATUS_INVALID);
    }
}
