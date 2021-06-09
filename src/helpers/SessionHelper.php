<?php

namespace Smoren\Yii2\Auth\helpers;

use Yii;

/**
 * Хэлпер для работы с сессиями пользователей
 */
class SessionHelper
{
    public static function close()
    {
        Yii::$app->session->close();
    }

    public static function getCurrentSession()
    {
        return Yii::$app->session;
    }
}
