<?php

namespace Smoren\Yii2\Auth\interfaces;

use Smoren\Yii2\ActiveRecordExplicit\models\ActiveRecord;
use Smoren\Yii2\Auth\exceptions\ApiException;
use yii\data\BaseDataProvider;

interface RestControllerInterface
{
    /**
     * Generate routing rules
     * @param string $apiPath
     * @param string $controllerPath
     * @param string $itemIdValidationRegexp
     * @return array
     * @override if you want to add extra rules
     */
    public static function getRules(string $apiPath, string $controllerPath, string $itemIdValidationRegexp): array;

    /**
     * Action for getting collection
     * @return BaseDataProvider
     * @throws ApiException
     */
    public function actionCollection(): BaseDataProvider;

    /**
     * Action for getting item
     * @param string $id
     * @return ActiveRecord|array
     * @throws ApiException
     */
    public function actionItem(string $id);

    /**
     * Action for creating new item
     * @return ActiveRecord|mixed
     * @throws ApiException
     */
    public function actionCreate();

    /**
     * Action for updating item
     * @param string $id
     * @return ActiveRecord|mixed
     * @throws ApiException
     */
    public function actionUpdate(string $id);

    /**
     * Action for deleting item
     * @param string $id
     * @return ActiveRecord|mixed
     * @throws ApiException
     */
    public function actionDelete(string $id);

    /**
     * Action for options
     * @param string|null $id
     * @throws ApiException
     */
    public function actionOptions(?string $id = null);
}
