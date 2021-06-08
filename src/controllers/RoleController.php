<?php


namespace Smoren\Yii2\Auth\controllers;


use yii\filters\AccessControl;

abstract class RoleController
{
    protected static $roles = [];

    /**
     * @inheritdocAuthController.php
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'roles' => static::$roles,
                ]
            ]
        ];

        return $behaviors;
    }
}