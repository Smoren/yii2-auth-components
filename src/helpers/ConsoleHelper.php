<?php

namespace Smoren\Yii2\Auth\helpers;


use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\console\Exception;
use yii\helpers\Inflector;
use yii\web\Application;

class ConsoleHelper
{
    protected static $isContextEmulated = false;

    /**
     * @param string $controllerClass
     * @param string $action
     * @param array $params
     * @return mixed
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidRouteException
     */
    public static function callWebAction(string $controllerClass, string $action, array $params = [])
    {
        static::emulateWebContext(false);
        [Yii::$app->controllerNamespace, $controller] = static::parseControllerName($controllerClass);

        return Yii::$app->runAction("{$controller}/{$action}", $params)->data;
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
        Yii::$app = new Application($webConfig);
        static::$isContextEmulated = true;

        return Yii::$app;
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