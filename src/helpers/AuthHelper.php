<?php


namespace Smoren\Yii2\Auth\helpers;


use Smoren\ExtendedExceptions\BaseException;
use Smoren\UrlSecurityManager\Exceptions\DecryptException;
use Smoren\UrlSecurityManager\Exceptions\UrlSecurityManagerException;
use Smoren\UrlSecurityManager\UrlSecurityManager;
use Smoren\Yii2\Auth\exceptions\TokenException;
use Yii;
use yii\filters\AccessControl;

class AuthHelper
{
    /**
     * @var string Токен пользователя
     */
    private static $token;

    /**
     * Название заголовка токена для передачи между клиентом и сервером
     */
    const HEADER_TOKEN_PARAM = 'X-Auth-Token';

    /**
     * Получение токена из заголовка
     * @return string
     * @throws TokenException
     */
    public static function getToken(): ?string
    {
        if(static::$token !== null) {
            return static::$token;
        }

        $token = Yii::$app->request->headers->get(self::HEADER_TOKEN_PARAM);

        if(!$token) {
            $token = Yii::$app->request->get('token');
        }

        static::$token = $token;

        if($token === null || $token === '') {
            throw new TokenException('empty', TokenException::STATUS_EMPTY);
        }

        return $token;
    }

    /**
     * @param string $secretKey
     * @param string $encryptedField
     * @param string $tokenField
     * @return string|null
     * @throws TokenException
     */
    public static function getTokenEncrypted(string $secretKey, string $encryptedField = 'data', string $tokenField = 'token'): ?string
    {
        try {
            $usm = UrlSecurityManager::parse()
                ->setEncryptParams($encryptedField)
                ->setSecretKey($secretKey)
                ->decrypt();

            $queryParams = $usm->getParams();
        } catch(DecryptException $e) {
            throw new TokenException('cannot decrypt url', TokenException::STATUS_INVALID, $e);
        } catch(UrlSecurityManagerException $e) {
            throw new TokenException('another security manager error', TokenException::STATUS_INVALID, $e);
        }

        $result = $queryParams[$tokenField] ?? null;

        if($result === null || $result === '') {
            throw new TokenException('empty', TokenException::STATUS_EMPTY);
        }

        return $result;
    }

    /**
     * @param $roles
     * @return array
     */
    public static function getRoleAccessBehaviorSettings($roles): array
    {
        return [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'roles' => $roles,
                ]
            ]
        ];
    }
}
