<?php

namespace Smoren\Yii2\Auth\components;

use Error;
use Yii;
use yii\base\InvalidRouteException;
use yii\console\Exception;
use yii\web\Response;

/**
 * Класс для обработки ошибок
 */
class ErrorHandler extends \yii\web\ErrorHandler
{
    /**
     * Отображение исключения
     * @param Error|\Exception $exception
     * @throws InvalidRouteException
     * @throws Exception
     */
    protected function renderException($exception)
    {
        if(Yii::$app->has('response')) {
            $response = Yii::$app->getResponse();
            // reset parameters of response to avoid interference with partially created response data
            // in case the error occurred while sending the response.
            $response->isSent = false;
            $response->stream = null;
            $response->data = null;
            $response->content = null;
        } else {
            $response = new Response();
        }

        $response->setStatusCodeByException($exception);

        if($response->format === Response::FORMAT_HTML || $response->format === Response::FORMAT_RAW) {
            parent::renderException($exception);
        } elseif($this->errorAction !== null) {
            $result = Yii::$app->runAction($this->errorAction);
            if($result instanceof Response) {
                $response = $result;
            } else {
                $response->data = $result;
            }
            $response->send();
        }
    }
}
