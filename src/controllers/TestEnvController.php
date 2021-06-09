<?php

namespace Smoren\Yii2\Auth\controllers;


use Smoren\Yii2\Auth\behaviors\TestEnvFilter;

/**
 * Абстрактный класс контроллера, унаследованный от BaseController, который будет давать доступ к апишкам, только если среда тестовая.
 */
abstract class TestEnvController extends BaseController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['testEnv'] = [
            'class' => TestEnvFilter::class
        ];
        return $behaviors;
    }
}