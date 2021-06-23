<?php

namespace Smoren\Yii2\Auth\components;

use Smoren\Yii2\Auth\exceptions\SessionException;
use Yii;

/**
 * Хэлпер для работы с сессиями пользователей
 */
class SessionManager
{
    /**
     * @var Session
     */
    protected static $session;
    protected static $token;

    public static function register(Session $session)
    {
        static::$session = $session;
    }

    public static function setToken($token)
    {
        static::$token = $token;
    }

    public static function open()
    {
        if(!(static::$session instanceof Session)) {
            throw new SessionException('session is not registered', SessionException::STATUS_LOGIC_ERROR);
        }

        static::$session->start(static::$token);
    }

    public static function close()
    {
        Yii::$app->session->close();
    }

    public static function getCurrentSession(): Session
    {
        return Yii::$app->session;
    }

    public static function deleteSessionFile($token = null)
    {
        $filePath = Session::getFilePath($token ?? static::$token);
        if(file_exists($filePath) && is_file($filePath)) {
            error_log("removed $filePath");
            @unlink($filePath);
        }
    }
}
