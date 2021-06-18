<?php

namespace Smoren\Yii2\Auth\components;

use DateTime;
use Exception;
use Smoren\ExtendedExceptions\BadDataException;
use Smoren\ExtendedExceptions\BaseException;
use Smoren\Yii2\Auth\exceptions\SessionException;
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
     * @var bool Нужно ли обновлять время последней активности пользователя
     */
    protected $needUpdateLastActivity = true;

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
     * @param $token
     * @throws \yii\base\Exception
     */
    public function start($token)
    {
        if($this->beenOpened) {
            return;
        }
        $this->beenOpened = true;

        if(!$token) {
            parent::open();
            return;
        }

        $this->lock($token);

        try {
            $this->dbSession = static::$dbSessionClass::getByToken($token);
            $this->setId($this->dbSession->token);
        } catch(BadDataException $e) {
        }

        parent::open();
    }

    /**
     * @return $this
     */
    public function disableUpdateLastActivity(): self
    {
        $this->needUpdateLastActivity = false;
        return $this;
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
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
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

        $this->setData($data);
        $this->save();

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
     * @throws Exception
     */
    public function closeSession()
    {
        $this->save();
        $this->unlock();

        return parent::closeSession();
    }

    /**
     * Отменяем перегенерацию ID сессии
     * @inheritdoc
     */
    public function regenerateID($deleteOldSession = false)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function gcSession($maxLifetime)
    {
        static::$dbSessionClass::clearOldSessions();
        return true;
    }

    /**
     * @param string $token
     * @return $this
     * @throws \yii\base\Exception
     */
    protected function lock(string $token): self
    {
        $this->fileHandler = fopen(static::getFilePath($token), 'wb');
        flock($this->fileHandler, LOCK_EX);

        return $this;
    }

    /**
     * @return $this
     */
    protected function unlock(): self
    {
        if($this->fileHandler !== null) {
            flock($this->fileHandler, LOCK_UN);
            fclose($this->fileHandler);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws SessionException
     */
    protected function save(): self
    {
        if($this->dbSession) {
            if($this->needUpdateLastActivity) {
                $lastActivity = (new DateTime())->getTimestamp();
                $this->dbSession->setLastActivity($lastActivity);
            }

            try {
                $this->dbSession->save();
            } catch(BadDataException $e) {
                throw new SessionException($e->getMessage(), SessionException::STATUS_LOGIC_ERROR, $e, ['error' => 'Ошибка при сохранении сессии'], $e->getDebugData());
            }
        }

        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    protected function setData($data): self
    {
        $this->dbSession->setData($data);
        return $this;
    }
}