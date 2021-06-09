<?php

namespace Smoren\Yii2\Auth\behaviors;

use Smoren\ExtendedExceptions\BaseException;
use Smoren\Yii2\Auth\exceptions\ApiException;
use Smoren\Yii2\Auth\exceptions\TokenException;
use Smoren\Yii2\Auth\helpers\AuthHelper;
use Smoren\Yii2\Auth\models\StatusCode;
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
        if(isset(Yii::$app->session->needUpdateLastActivity)) {
            Yii::$app->session->needUpdateLastActivity = false;
        }
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
            [$secretKey, $encryptedField, $tokenField] = $this->getEncryptedSettings();
            return AuthHelper::getTokenEncrypted($secretKey, $encryptedField, $tokenField);
        }

        return AuthHelper::getToken();
    }

    /**
     * @param string|null $token
     * @param User $user
     * @return IdentityInterface|null
     * @throws TokenException
     */
    protected function processToken(string $token, User $user): ?IdentityInterface
    {
        return $this->getIdentity($token, $user);
    }

    /**
     * @return array
     * @throws TokenException
     */
    protected function getEncryptedSettings(): array
    {
        throw new TokenException('no secret key specified', TokenException::STATUS_LOGIC_ERROR);
    }
}
