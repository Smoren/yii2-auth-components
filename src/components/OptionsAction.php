<?php

namespace Smoren\Yii2\Auth\components;

use Smoren\Yii2\Auth\exceptions\ApiException;
use Smoren\Yii2\Auth\models\StatusCode;
use Yii;

class OptionsAction extends \yii\rest\OptionsAction
{
    /**
     * @param null $id
     * @throws ApiException
     */
    public function run($id = null)
    {
        if(Yii::$app->getRequest()->getMethod() !== 'OPTIONS') {
            throw new ApiException('method not allowed', StatusCode::METHOD_NOT_ALLOWED);
        }

        parent::run($id);
    }
}