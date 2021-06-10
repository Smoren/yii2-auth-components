<?php

namespace Smoren\Yii2\Auth\behaviors;

use Smoren\Yii2\Auth\components\SessionManager;
use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveRecord;

/**
 * Поведение для удаления файлов, истёкших сессий
 */
class SessionDeleteBehavior extends Behavior
{
    /**
     * @inheritdoc
     */
    public function events()
    {
        return [ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete'];
    }

    /**
     * Действия события при удаление AR модели.
     * Удаляет файл, истёкшей сессии
     * @param Event $event
     */
    public function afterDelete(Event $event)
    {
        $sender = $event->sender;
        SessionManager::deleteSessionFile($sender->token);
    }
}