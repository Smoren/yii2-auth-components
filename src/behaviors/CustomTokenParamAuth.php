<?php

namespace Smoren\Yii2\Auth\behaviors;

use Smoren\ExtendedExceptions\BaseException;
use Smoren\Yii2\Auth\exceptions\ApiException;
use Smoren\Yii2\Auth\exceptions\TokenException;
use Smoren\Yii2\Auth\helpers\AuthHelper;
use Smoren\Yii2\Auth\models\StatusCode;
use Yii;
use yii\web\IdentityInterface;
use yii\web\Request;
use yii\web\Response;
use yii\web\User;

/**
 * Кастомный класс для авторизации через токен
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
    protected function getToken(array $params = []): string
    {
        return AuthHelper::getToken();
    }

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
