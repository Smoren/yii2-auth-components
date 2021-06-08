<?php

namespace Smoren\Yii2\Auth\controllers;

use Smoren\Yii2\Auth\behaviors\AccessControlAllowOriginFilter;
use Smoren\Yii2\Auth\components\OptionsAction;
use Smoren\Yii2\Auth\exceptions\ApiException;
use Smoren\Yii2\Auth\models\StatusCode;
use Yii;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\TooManyRequestsHttpException;

/**
 * Базовый класс API контроллера
 */
abstract class BaseController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $corsSettings = $this->getCorsSettings();

        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => $corsSettings,
        ];

        $behaviors['accessOrigin'] = [
            'class' => AccessControlAllowOriginFilter::class,
            'origins' => $corsSettings['origins'] ?? ['*'],
        ];

        return $behaviors;
    }

    /**
     * Дополнительные действия контроллера
     * @return array
     */
    public function actions()
    {
        $collectionOptions = Yii::$app->params['access-control-actions'] ?? ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];

        return [
            'options' => [
                'class' => OptionsAction::class,
                'collectionOptions' => $collectionOptions,
            ],
        ];
    }

    /**
     * Переопределенный функционал запуска действия.
     * Любой запрос проходит через это действие.
     * Позволяет отлавливать все исключения в приложении.
     * @param string $id ID действия
     * @param array $params Параметры
     * @return mixed|\yii\web\Response
     */
    public function runAction($id, $params = [])
    {
        try {
            if(Yii::$app->request->getMethod() === 'OPTIONS') {
                return parent::runAction($id, $params = []);
            }

            $actionMap = $this->actions();
            if(isset($actionMap[$id])) {
                $action = Yii::createObject($actionMap[$id], [$id, $this]);
                return $this->success($action->run());
            } else {
                $actionName = 'action' . str_replace(' ', '', ucwords(str_replace('-', ' ', $id)));
                $controllerClass = get_class($this);
            }

            if(!method_exists($this, $actionName)) {
                throw new ApiException("not found", StatusCode::NOT_FOUND, null, [],
                    ['extra' => "action '{$actionName}' not found in controller '$controllerClass'"]
                );
            }

            try {
                $result = parent::runAction($id, $params);
                return $this->success($result);
            } catch(BadRequestHttpException $e) {
                throw new ApiException($e->getMessage(), StatusCode::BAD_REQUEST, $e);
            } catch(TooManyRequestsHttpException $e) {
                throw new ApiException($e->getMessage(), StatusCode::TOO_MANY_REQUESTS, $e);
            }
        } catch(ApiException $e) {
            $this->testingLog($e);
            return $this->failure($e->getMessage(), $e->getCode(), $e->getData(), $e->getDebugData());
        } catch(\Throwable $e) {
            $this->testingLog($e);
            return $this->failure('server error', StatusCode::INTERNAL_SERVER_ERROR, null, ApiException::extendDebugData($e));
        }
    }

    /**
     * Тестовый метод для логирования любых ошибок
     * @param \Throwable $e
     */
    protected function testingLog(\Throwable $e)
    {
        $code = $e->getCode();
        if(!(!$code || $code < 100 || $code == 500)) {
            return;
        }
        error_log('API exception: ' . $e->getMessage() . '; code: ' . $code);
        try {
            $debugData = $e->getDebugData();
            error_log("file: {$debugData['file']}:{$debugData['line']}");
        } catch(\Throwable $e) {
            error_log("no debug data");
        }
    }

    /**
     * Успешный ответ от сервера
     * @param array|Response $data Данные для отправки на frontend
     * @return \yii\web\Response
     */
    public function success($data = [])
    {
        if($data instanceof Response) {
            return $data;
        }
        return $this->asJson($data);
    }

    /**
     * Неуспешный ответ от сервера
     * @param string $message Сообщение об ошибке
     * @param int $statusCode HTTP код
     * @param array|null $data Данные для отправки на frontend
     * @param array|null $debugData Отладочные данные. В среде prod не отображается
     * @return \yii\web\Response
     */
    public function failure(string $message, int $statusCode = StatusCode::NOT_ACCEPTABLE, ?array $data = null, ?array $debugData = null)
    {
        try {
            Yii::$app->response->statusCode = $statusCode;
        } catch(\Throwable $e) {
            Yii::$app->response->statusCode = StatusCode::INTERNAL_SERVER_ERROR;
        }

        $resp = [
            'message' => $message,
            'data' => $data,
        ];

        if(YII_DEBUG) {
            $resp['debug'] = $debugData;
        }

        return $this->asJson($resp);
    }

    /**
     * @return array
     */
    protected function getCorsSettings(): array
    {
        $origins = Yii::$app->params['origins'] ?? ['*'];
        $methods = Yii::$app->params['access-control-request-method'] ?? ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
        $requestHeaders = Yii::$app->params['access-control-request-headers'] ?? ['*'];
        $credentials = Yii::$app->params['access-control-request-credentials'] ?? null;
        $maxAge = Yii::$app->params['access-control-max-age'] ?? 86400;
        $exposeHeaders = Yii::$app->params['access-control-expose-headers'] ?? ['*'];

        return [
            'Origin' => $origins,
            'Access-Control-Request-Method' => $methods,
            'Access-Control-Request-Headers' => $requestHeaders,
            'Access-Control-Allow-Credentials' => $credentials,
            'Access-Control-Max-Age' => $maxAge,
            'Access-Control-Expose-Headers' => $exposeHeaders,
        ];
    }
}