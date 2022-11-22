<?php

namespace Smoren\Yii2\Auth\behaviors;

use Smoren\Yii2\Auth\exceptions\ApiException;
use Smoren\Yii2\Auth\structs\StatusCode;
use yii\base\Action;
use yii\base\ActionFilter;

/**
 * Фильтр тестовой среды
 */
class TestEnvFilter extends ActionFilter
{
    /**
     * Проверяет, что выполняемое действие предназначено для тестовой среды
     * @param Action $action
     * @return bool
     * @throws ApiException
     */
    public function beforeAction($action)
    {
        if(strtolower(YII_ENV) !== 'test') {
            throw new ApiException('you cannot perform this action', StatusCode::FORBIDDEN);
        }

        return true;
    }
}
