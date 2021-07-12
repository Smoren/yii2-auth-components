<?php

namespace Smoren\Yii2\Auth\helpers;


use Smoren\Yii2\Auth\exceptions\ApiException;
use Smoren\Yii2\Auth\structs\StatusCode;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\console\Exception;
use yii\helpers\Inflector;
use yii\web\Application;
use yii\web\Response;

class ConsoleWebHelper
{
    /**
     * @var bool Is web context emulated flag
     */
    protected static $isContextEmulated = false;

    /**
     * @param string $controllerClass
     * @param string $action
     * @param array $params
     * @param string $method
     * @return mixed
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidRouteException
     * @throws ApiException
     */
    public static function callWebAction(string $controllerClass, string $action, array $params = [], string $method = 'GET')
    {
        static::emulateWebContext(false);
        $_SERVER['REQUEST_METHOD'] = $method;

        [Yii::$app->controllerNamespace, $controller] = static::parseControllerName($controllerClass);

        $arPath = [$controller, $action];

        if(preg_match('/^app\\\modules\\\([^\\\]+)\\\/', Yii::$app->controllerNamespace, $matches)) {
            array_unshift($arPath, $matches[1]);
        }

        /** @var Response $resp */
        $resp = Yii::$app->runAction(implode('/', $arPath), $params);
        if((int)$resp->statusCode !== StatusCode::OK) {
            $statusCode = $resp->statusCode;
            $resp->statusCode = StatusCode::OK;
            throw new ApiException($resp->data['message'], $statusCode, null, $resp->data['data'] ?? [], $resp->data['debug'] ?? []);
        }

        return $resp->data;
    }

    /**
     * @param string $controllerClass
     * @return array
     */
    public static function parseControllerName(string $controllerClass): array
    {
        $buf = explode('\\', $controllerClass);
        $controller = Inflector::camel2id(str_replace('Controller', '', array_pop($buf)));
        return [implode('\\', $buf), $controller];
    }

    /**
     * @param bool $force
     * @return Application
     * @throws InvalidConfigException
     */
    public static function emulateWebContext(bool $force = true): Application
    {
        if(!$force && static::$isContextEmulated) {
            return Yii::$app;
        }

        $configPath = Yii::getAlias('@app/config');
        $webConfig = require("{$configPath}/web.php");
        $webConfig['components']['db']['dsn'] = Yii::$app->db->dsn;
        $webConfig['components']['db']['username'] = Yii::$app->db->username;
        $webConfig['components']['db']['password'] = Yii::$app->db->password;
        $webConfig['components']['db']['charset'] = Yii::$app->db->charset;

        Yii::$app = new Application($webConfig);
        Yii::$app->session->open();

        static::$isContextEmulated = true;

        return Yii::$app;
    }

    /**
     * @param string $token
     * @throws InvalidConfigException
     */
    public static function setToken(string $token)
    {
        static::setWebRequestQueryParams([
            'token' => $token,
        ]);
    }

    /**
     * @param array $params
     * @throws InvalidConfigException
     */
    public static function setWebRequestQueryParams(array $params)
    {
        static::emulateWebContext(false);
        Yii::$app->request->setQueryParams($params);
    }

    /**
     * @param array $params
     * @throws InvalidConfigException
     */
    public static function setWebRequestBodyParams(array $params)
    {
        static::emulateWebContext(false);
        Yii::$app->request->setBodyParams($params);
    }
}