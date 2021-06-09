<?php

namespace Smoren\Yii2\Auth\behaviors;

use Smoren\Yii2\Auth\exceptions\TokenException;
use Yii;
use yii\web\IdentityInterface;
use yii\web\User;

/**
 * Поведение авторизации через токен, хранящийся в конфиге params
 */
abstract class CustomTokenParamAuth extends BaseTokenParamAuth
{
    /**
     * @return string
     */
    abstract protected function getValidToken(): string;

    /**
     * @inheritDoc
     */
    protected function processToken(string $token, User $user): ?IdentityInterface
    {
        if($token !== $this->getValidToken()) {
            throw new TokenException('token invalid', TokenException::STATUS_INVALID);
        }

        return parent::processToken($token, $user);
    }

    /**
     * @inheritDoc
     */
    protected function getIdentity(string $token, User $user): IdentityInterface
    {
        return new Yii::$app->user->identityClass;
    }
}
