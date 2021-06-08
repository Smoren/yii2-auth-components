<?php

namespace Smoren\Yii2\Auth\behaviors;

use Smoren\ExtendedExceptions\BaseException;
use Smoren\Yii2\Auth\exceptions\TokenException;
use Smoren\Yii2\Auth\helpers\AuthHelper;
use yii\web\IdentityInterface;
use yii\web\User;

/**
 * Расширенный класс для авторизации через токен
 */
class UserTokenParamAuth extends BaseTokenParamAuth
{
    /**
     * @inheritDoc
     */
    protected function getToken(array $params = []): string
    {
        return AuthHelper::getToken();
    }

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
