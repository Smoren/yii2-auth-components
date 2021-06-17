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
        Yii::$app->controllerNamespace = static::getNamespace($controllerClass);

        return Yii::$app->runAction($action, $params)->data;
    }

    /**
     * @param string $controllerClass
     * @return string
     */
    public static function getNamespace(string $controllerClass): string
    {
        $buf = explode('\\', $controllerClass);
        array_pop($buf);
        return implode('\\', $buf);
    }

    /**
     * @return Application
     * @throws InvalidConfigException
     */
    public static function emulateWebContext(): Application
    {
        $configPath = Yii::getAlias('@app/config');

        $consoleConfig = require("{$configPath}/console.php");
        $webConfig = require("{$configPath}/web.php");

        $consoleConfig['id'] = $webConfig['id'];
        Yii::$app = new Application($consoleConfig);

        return Yii::$app;
    }
}