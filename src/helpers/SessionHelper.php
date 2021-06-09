<?php

namespace Smoren\Yii2\Auth\helpers;

use Smoren\Yii2\Auth\components\Session;
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

    public static function deleteSessionFile($token)
    {
        $filePath = Session::getFilePath($token);
        if(file_exists($filePath) && is_file($filePath)) {
            error_log("removed $filePath");
            @unlink($filePath);
        }
    }
}
