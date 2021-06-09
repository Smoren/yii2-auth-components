<?php

namespace Smoren\Yii2\Auth\components;

use DateTime;
use Smoren\ExtendedExceptions\BadDataException;
use Smoren\Yii2\Auth\exceptions\SessionException;
use Smoren\Yii2\Auth\exceptions\TokenException;
use Smoren\Yii2\Auth\helpers\AuthHelper;
use Throwable;
use Yii;
use yii\db\Exception;
use yii\helpers\FileHelper;
use yii\web\DbSession;

/**
 * Реализация сессии
 */
class Session extends DbSession
{
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
     * @var UserSession AR модель сессии пользователя
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

    /**
     * Запрещаем повторное открытие сессии во время работы скрипта
     * @return void|null
     * @throws \yii\base\Exception
     */
    public function open()
    {
        if($this->beenOpened) {
            return null;
        }
        $this->beenOpened = true;

        if(!$this->token) {
            parent::open();
            return null;
        }

        $this->fileHandler = fopen(static::getFilePath($this->token), 'wb');
        flock($this->fileHandler, LOCK_EX);

        if($this->token) {
            try {
                $this->dbSession = UserSession::find()->byToken($this->token)->one();

                $this->setId($this->dbSession->token);

                if($this->logging) {
                    error_log("opened " . Session::getFilePath($this->token));
                }
            } catch(BadDataException $e) {
            }
        }

        parent::open();
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
     * @throws TokenException
     */
    public function init()
    {
        parent::init();
        $this->writeCallback = function() {
            return [
                'user_id' => Yii::$app->user->getId(),
            ];
        };

        if(YII_ENV === 'test' || Yii::$app->getRequest()->isConsoleRequest) {
            $this->token = UserSession::find()->byUser(Yii::$app->user->id)->first()->token;
        } else {
            // TODO encrypted?
            $this->token = AuthHelper::getToken();
        }
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

        $this->dbSession->data = $data;

        if(YII_ENV !== 'test') {
            try {
                $this->dbSession->save();
            } catch(BadDataException $e) {
                throw new SessionException('cannot save session', SessionException::STATUS_LOGIC_ERROR);
            }
        }

        if($this->logging) {
            error_log('write');
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

        return $this->dbSession->data ?: '';
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
                $this->dbSession->last_activity = $lastActivity;

                try {
                    $this->dbSession->user->active_at = $lastActivity;
                    $this->dbSession->user->save();
                } catch(Throwable $e) {
                }

                if(!Yii::$app->getRequest()->isConsoleRequest) {
                    (new UserHistoryService())->upsertUserHistory($this->dbSession->user->id);
                }
            }

            if(!Yii::$app->getRequest()->isConsoleRequest) {
                $this->dbSession->updateDeviceData();
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
     * @throws Exception
     */
    public function gcSession($maxLifetime)
    {
        $this->db->createCommand()
            ->delete(
                UserSession::tableName(),
                'lifetime > 0 and (lifetime + last_activity) < :time_now',
                [':time_now' => (new DateTime())->getTimestamp()]
            )
            ->execute();

        return true;
    }
}