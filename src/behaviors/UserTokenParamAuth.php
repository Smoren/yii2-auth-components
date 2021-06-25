<?php

namespace Smoren\Yii2\Auth\behaviors;

use Smoren\Yii2\ActiveRecordExplicit\exceptions\DbException;
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
        try {
            if(($identity = $user->loginByAccessToken($token, get_class($this))) instanceof IdentityInterface) {
                return $identity;
            }
        } catch(DbException $e) {
            throw new TokenException('token invalid', TokenException::STATUS_INVALID, $e);
        }

        throw new TokenException('token invalid', TokenException::STATUS_INVALID);
    }
}
