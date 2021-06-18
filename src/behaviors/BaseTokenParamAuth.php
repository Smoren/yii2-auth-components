<?php

namespace Smoren\Yii2\Auth\behaviors;

use Smoren\ExtendedExceptions\BaseException;
use Smoren\Yii2\Auth\components\Session;
use Smoren\Yii2\Auth\components\SessionManager;
use Smoren\Yii2\Auth\exceptions\ApiException;
use Smoren\Yii2\Auth\exceptions\SessionException;
use Smoren\Yii2\Auth\exceptions\TokenException;
use Smoren\Yii2\Auth\helpers\AuthHelper;
use Smoren\Yii2\Auth\structs\StatusCode;
use Throwable;
use Yii;
use yii\filters\auth\QueryParamAuth;
use yii\web\IdentityInterface;
use yii\web\User;

/**
 * Базовое поведение для авторизации через токен
 */
abstract class BaseTokenParamAuth extends QueryParamAuth
{
    /**
     * @var bool Использовать ли шифрование
     */
    protected $useEncryption = false;
    /**
     * @var callable|null Функция получения настроек шифрование
     */
    protected $encryptionParams = null;
    /**
     * @var User|null
     */
    protected $identity = null;
    /**
     * @var array Данные исключения
     */
    protected $throwableData = [];
    /**
     * @var int Код исключения
     */
    protected $throwableCode = StatusCode::NOT_AUTHORIZED;

    /**
     * {@inheritdoc}
     * @param $user User
     * @return IdentityInterface|null
     * @throws ApiException
     */
    public function authenticate($user, $request, $response)
    {
        try {
            $token = $this->getToken();
            return $this->processToken($token, $user);
        } catch(BaseException $e) {
            $this->throwableData = $e->getData();
            $this->throwableCode = $e->getCode();
            $this->handleFailure($response);
        } catch(Throwable $e) {
            $this->handleFailure($response);
        }

        return null;
    }

    /**
     * @param $response
     * @throws ApiException
     */
    public function handleFailure($response)
    {
        /** @var Session $session */
        $session = Yii::$app->session;
        $session->disableUpdateLastActivity();

        throw new ApiException('unauthorized', $this->throwableCode, null, $this->throwableData);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setUseEncryption(bool $value): self
    {
        $this->useEncryption = $value;
        return $this;
    }

    /**
     * @param array|null $params
     * @return $this
     */
    public function setEncryptionParams(?array $params): self
    {
        $this->encryptionParams = $params;
        return $this;
    }

    /**
     * @param string $token
     * @param User $user
     * @return IdentityInterface
     * @throws TokenException
     */
    abstract protected function getIdentity(string $token, User $user): IdentityInterface;

    /**
     * @param array $params
     * @return string|null
     * @throws TokenException
     */
    protected function getToken(array $params = []): string
    {
        if($this->useEncryption) {
            [$secretKey, $encryptedField, $tokenField] = $this->encryptionParams;
            return AuthHelper::getTokenEncrypted($secretKey, $encryptedField, $tokenField);
        }

        return AuthHelper::getToken();
    }

    /**
     * @param string|null $token
     * @param User $user
     * @return IdentityInterface|null
     * @throws TokenException
     * @throws SessionException
     */
    protected function processToken(string $token, User $user): IdentityInterface
    {
        $identity = $this->getIdentity($token, $user);
        SessionManager::setToken($token);
        SessionManager::open();

        return $identity;
    }
}
