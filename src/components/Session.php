<?php

namespace Smoren\Yii2\Auth\components;

use DateTime;
use Smoren\ExtendedExceptions\BadDataException;
use Smoren\ExtendedExceptions\BaseException;
use Smoren\Yii2\Auth\exceptions\SessionException;
use Smoren\Yii2\Auth\exceptions\TokenException;
use Smoren\Yii2\Auth\helpers\AuthHelper;
use Smoren\Yii2\Auth\models\UserSessionInterface;
use Yii;
use yii\helpers\FileHelper;
use yii\web\DbSession;

/**
 * Реализация сессии
 */
abstract class Session extends DbSession
{
    /**
     * @var UserSessionInterface Класс AR модели сессии пользователя
     */
    protected static $dbSessionClass;
    /**
     * @var bool Нужно ли обновлять время последней активности пользователя
     */
    public $needUpdateLastActivity = true;
    /**
     * @var bool Логировать ли данные?
     */
    public $logging = !YII_DEBUG;
    /**
     * @var bool Флаг открытия сессии
     */
    protected $beenOpened = false;
    /**
     * @var UserSessionInterface AR модель сессии пользователя
     */
    protected $dbSession;
    /**
     * @var resource Указатель на файл сессии
     */
    protected $fileHandler;
    /**
     * @var string Токен пользователя
     */
    protected $token;

    public function start($token)
    {
        if($this->beenOpened) {
            return null;
        }
        $this->beenOpened = true;

        $this->token = $token;

        if(!$this->token) {
            parent::open();
            return null;
        }

        $this->fileHandler = fopen(static::getFilePath($this->token), 'wb');
        flock($this->fileHandler, LOCK_EX);

        if($this->token) {
            try {
                $this->dbSession = static::$dbSessionClass::getByToken($this->token);
                $this->setId($this->dbSession->token);
            } catch(BadDataException $e) {
            }
        }

        parent::open();
    }

    /**
     * Запрещаем повторное открытие сессии во время работы скрипта
     * @return void|null
     */
    public function open()
    {
        return null;
    }

    /**
     * Вернет путь до файла сессии
     * @param string $token
     * @return string
     * @throws \yii\base\Exception
     */
    public static function getFilePath(string $token): string
    {
        $path = ini_get('session.save_path');
        if(!$path) {
            $path = Yii::getAlias('@runtime/sessions');
            FileHelper::createDirectory($path);
        }

        return $path . DIRECTORY_SEPARATOR . "phpsessid_{$token}";
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->writeCallback = function() {
            return [
                'user_id' => Yii::$app->user->getId(),
            ];
        };

        SessionManager::register($this);
    }

    /**
     * Запись сессии в БД
     * @param string $id
     * @param string $data
     * @return bool
     * @throws SessionException
     */
    public function writeSession($id, $data)
    {
        if(!$this->dbSession) {
            return true;
        }

        $this->dbSession->setData($data);

        if(YII_ENV !== 'test') {
            try {
                $this->dbSession->save();
            } catch(BadDataException $e) {
                throw new SessionException('cannot save session', SessionException::STATUS_LOGIC_ERROR);
            }
        }

        return true;
    }

    /**
     * Чтение из сессии пользователя
     * @param string $id
     * @return string
     */
    public function readSession($id)
    {
        if(!$this->dbSession) {
            return '';
        }

        return $this->dbSession->getData() ?: '';
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function closeSession()
    {
        if($this->dbSession) {

            if($this->needUpdateLastActivity) {
                $lastActivity = (new DateTime())->getTimestamp();
                $this->dbSession->setLastActivity($lastActivity);
            }

            if(YII_ENV !== 'test') {
                try {
                    $this->dbSession->save();
                } catch(BadDataException $e) {
                    throw new SessionException($e->getMessage(), SessionException::STATUS_LOGIC_ERROR, $e, ['error' => 'Ошибка при сохранении сессии'], $e->getDebugData());
                }
            }
        }

        if($this->fileHandler !== null) {
            flock($this->fileHandler, LOCK_UN);
            fclose($this->fileHandler);
        }

        if($this->logging) {
            error_log('closed');
        }

        return parent::closeSession();
    }

    /**
     * Отменяем перегенерацию ID сессии
     * @inheritdoc
     */
    public function regenerateID($deleteOldSession = false)
    {

    }

    /**
     * @inheritDoc
     */
    public function gcSession($maxLifetime)
    {
        static::$dbSessionClass::clearOldSessions();

        return true;
    }
}