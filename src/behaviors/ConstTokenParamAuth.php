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
abstract class ConstTokenParamAuth extends CustomTokenParamAuth
{
    const PARAM_KEY = 'token';

    /**
     * @return string
     * @throws TokenException
     */
    protected function getValidToken(): string
    {
        $token = Yii::$app->params[static::PARAM_KEY] ?? null;

        if($token === null) {
            throw new TokenException('no token in params', TokenException::STATUS_LOGIC_ERROR);
        }

        return $token;
    }
}
