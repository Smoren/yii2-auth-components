<?php

namespace Smoren\Yii2\Auth\helpers;


use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\console\Exception;
use yii\console\Response;
use yii\web\Application;

class ConsoleHelper
{
    /**
     * @param string $controllerClass
     * @param string $action
     * @param ...$params
     * @return int|mixed|Response
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidRouteException
     */
    public static function callAction(string $controllerClass, string $action, ...$params)
    {
        static::emulateWebContext();
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
        $controller = strtolower(str_replace('Controller', '', array_pop($buf)));
        return [implode('\\', $buf), $controller];
    }

    /**
     * @return Application
     * @throws InvalidConfigException
     */
    public static function emulateWebContext(): Application
    {
        $configPath = Yii::getAlias('@app/config');
        $webConfig = require("{$configPath}/web.php");
        Yii::$app = new Application($webConfig);

        return Yii::$app;
    }
}