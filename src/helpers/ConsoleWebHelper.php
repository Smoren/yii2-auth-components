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
     * @param string|null $uri
     * @return Response
     * @throws ApiException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidRouteException
     */
    public static function callWebAction(
        string $controllerClass, string $action, array $params = [],
        string $method = 'GET', ?string $uri = null
    ): Response
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        static::emulateWebContext(false);

        [Yii::$app->controllerNamespace, $controller] = static::parseControllerName($controllerClass);

        $arPath = [];

        if(preg_match('/^app\\\modules\\\([\s\S]+)\\\controllers/', Yii::$app->controllerNamespace, $matches)) {
            foreach(explode('\\', $matches[1]) as $pathItem) {
                $arPath[] = $pathItem;
            }
        }

        if(preg_match('/^app\\\[\s\S]+\\\controllers\\\([\s\S]+)$/', Yii::$app->controllerNamespace, $matches)) {
            foreach(explode('\\', $matches[1]) as $pathItem) {
                $arPath[] = $pathItem;
            }
        }

        $arPath[] = $controller;
        $arPath[] = $action;

        Yii::$app->response->clear();

        /** @var Response $resp */
        $resp = Yii::$app->runAction(implode('/', $arPath), $params);

        static::setWebRequestQueryParams([]);
        static::setWebRequestBodyParams([]);

        if(!in_array((int)$resp->statusCode, [StatusCode::OK, StatusCode::ACCEPTED, StatusCode::CREATED])) {
            $statusCode = $resp->statusCode;
            $resp->statusCode = StatusCode::OK;
            throw new ApiException($resp->data['message'], $statusCode, null, $resp->data['data'] ?? [], $resp->data['debug'] ?? []);
        }

        return $resp;
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
        static::setWebRequestHeaders([
            'X-Auth-Token' => $token,
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

    /**
     * @param array $headers
     * @throws InvalidConfigException
     */
    public static function setWebRequestHeaders(array $headers)
    {
        static::emulateWebContext(false);
        foreach($headers as $key => $val) {
            Yii::$app->request->headers->set($key, $val);
        }
    }
}