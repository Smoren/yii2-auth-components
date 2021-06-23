<?php

namespace Smoren\Yii2\Auth\components;

use Smoren\Yii2\Auth\exceptions\SessionException;
use Yii;
use yii\base\Exception;

/**
 * Хэлпер для работы с сессиями пользователей
 */
class SessionManager
{
    /**
     * @var Session
     */
    protected static $session;
    /**
     * @var string
     */
    protected static $token;

    /**
     * @param Session $session
     */
    public static function register(Session $session)
    {
        static::$session = $session;
    }

    /**
     * @param $token
     */
    public static function setToken($token)
    {
        static::$token = $token;
    }

    /**
     * @throws SessionException
     * @throws Exception
     */
    public static function open()
    {
        if(!(static::$session instanceof Session)) {
            throw new SessionException('session is not registered', SessionException::STATUS_LOGIC_ERROR);
        }

        static::$session->start(static::$token);
    }

    /**
     * Close session
     */
    public static function close()
    {
        Yii::$app->session->close();
    }

    /**
     * Remove db session row
     */
    public static function delete()
    {
        static::close();
        static::getCurrentSession()->getDbSession()->delete();
    }

    /**
     * @return Session
     */
    public static function getCurrentSession(): Session
    {
        return Yii::$app->session;
    }

    /**
     * @param null $token
     * @throws Exception
     */
    public static function deleteSessionFile($token = null)
    {
        $filePath = Session::getFilePath($token ?? static::$token);
        if(file_exists($filePath) && is_file($filePath)) {
            error_log("removed $filePath");
            @unlink($filePath);
        }
    }
}
