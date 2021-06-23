<?php


namespace Smoren\Yii2\Auth\models;


use Smoren\ExtendedExceptions\BadDataException;
use yii\db\ActiveQuery;

/**
 * Interface UserSessionInterface
 * @property string $token
 * @property mixed $user_id
 * @property int $lifetime
 * @property int $last_activity
 */
interface UserSessionInterface
{
    /**
     * @param string $token
     * @return UserSessionInterface
     * @throws BadDataException
     */
    public static function getByToken(string $token);

    /**
     * @param $userId
     * @return UserSessionInterface
     * @throws BadDataException
     */
    public static function getTokenByUser($userId);

    /**
     * Очистка старых сессий
     */
    public static function clearOldSessions();

    /**
     * @return string
     */
    public function getData();

    /**
     * @param $value
     * @return mixed
     */
    public function setData($value);

    /**
     * @param $value
     * @return mixed
     */
    public function setLastActivity($value);

    /**
     * @param bool $runValidation
     * @param mixed $attributeNames
     * @throws BadDataException
     * @return bool
     */
    public function save($runValidation = true, $attributeNames = null);

    /**
     * Свзяь с пользователем
     * @return ActiveQuery
     */
    public function getUser();

    /**
     * Генерирует новый токен
     * @param int $lifetime
     * @return $this
     */
    public function generateNewToken(int $lifetime): self;
}
