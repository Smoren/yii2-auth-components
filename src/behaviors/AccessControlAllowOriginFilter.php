<?php

namespace Smoren\Yii2\Auth\behaviors;

use Yii;
use yii\base\ActionFilter;

/**
 * Фильтр проверки frontend домена
 */
class AccessControlAllowOriginFilter extends ActionFilter
{
    /**
     * @var array Массив доступных доменов
     */
    public $origins = [];

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        $frontDomain = Yii::$app->request->headers->get('X-Frontend-Domain');
        if($frontDomain !== null && (in_array($frontDomain, $this->origins) || in_array('*', $this->origins))) {
            Yii::$app->response->headers->set('Access-Control-Allow-Origin', $frontDomain);
        }

        return true;
    }
}