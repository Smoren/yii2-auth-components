<?php


namespace Smoren\Yii2\Auth\controllers;


use Smoren\ExtendedExceptions\BaseException;
use Smoren\Yii2\ActiveRecordExplicit\components\ActiveDataProvider;
use Smoren\Yii2\ActiveRecordExplicit\exceptions\DbException;
use Smoren\Yii2\ActiveRecordExplicit\helpers\FormValidator;
use Smoren\Yii2\ActiveRecordExplicit\models\ActiveQuery;
use Smoren\Yii2\ActiveRecordExplicit\models\ActiveRecord;
use Smoren\Yii2\ActiveRecordExplicit\models\Model;
use Smoren\Yii2\Auth\exceptions\ApiException;
use Smoren\Yii2\Auth\structs\StatusCode;
use Throwable;
use Yii;

trait RestControllerTrait
{
    /**
     * @param string $apiPath
     * @param string $controllerPath
     * @param string $itemIdValidationRegexp
     * @return array
     */
    public static function getRules(
        string $apiPath, string $controllerPath, string $itemIdValidationRegexp
    ): array
    {
        return [
            /**
             * API получения коллекциии
             * @see RestControllerTrait::actionCollection()
             */
            "GET {$apiPath}" => "{$controllerPath}/collection",

            /**
             * API получения элемента
             * @see RestControllerTrait::actionItem()
             */
            "GET {$apiPath}/<id:{$itemIdValidationRegexp}>" => "{$controllerPath}/item",

            /**
             * API создания элемента
             * @see RestControllerTrait::actionCreate()
             */
            "POST {$apiPath}" => "{$controllerPath}/create",

            /**
             * API редактирования элемента
             * @see RestControllerTrait::actionUpdate()
             */
            "PUT {$apiPath}/<id:{$itemIdValidationRegexp}>" => "{$controllerPath}/update",

            /**
             * API удаления элемента
             * @see RestControllerTrait::actionDelete()
             */
            "DELETE {$apiPath}/<id:{$itemIdValidationRegexp}>" => "{$controllerPath}/delete",
        ];
    }

    /**
     * @return ActiveDataProvider
     * @throws ApiException
     */
    public function actionCollection(): ActiveDataProvider
    {
        $this->checkAccess(__FUNCTION__);
        return $this->getDataProvider($this->getCollectionQuery());
    }

    /**
     * @param string $id
     * @return ActiveRecord
     * @throws ApiException
     */
    public function actionItem(string $id): ActiveRecord
    {
        $this->checkAccess(__FUNCTION__);

        try {
            return $this->getItemQuery($id)->one();
        } catch(DbException $e) {
            throw new ApiException('not found', StatusCode::NOT_FOUND, $e, $e->getData());
        } catch(BaseException $e) {
            throw new ApiException('server error', StatusCode::INTERNAL_SERVER_ERROR, $e, $e->getData());
        } catch(Throwable $e) {
            throw new ApiException('not acceptable', StatusCode::INTERNAL_SERVER_ERROR, $e);
        }
    }

    /**
     * @return ActiveRecord
     * @throws ApiException
     */
    public function actionCreate(): ActiveRecord
    {
        $this->checkAccess(__FUNCTION__);

        $form = $this->getCreateForm();
        FormValidator::validate($form, ApiException::class);

        try {
            $item = new ($this->getActiveRecordClassName())($form->getLoadedAttributes());
            $item = $this->beforeCreate($item, $form);
            $item->save();
            $this->afterCreate($item, $form);

            Yii::$app->response->statusCode = StatusCode::CREATED;

            return $item;
        } catch(DbException $e) {
            throw new ApiException('not acceptable', StatusCode::NOT_ACCEPTABLE, $e, $e->getData());
        } catch(BaseException $e) {
            throw new ApiException('server error', StatusCode::INTERNAL_SERVER_ERROR, $e, $e->getData());
        } catch(Throwable $e) {
            throw new ApiException('not acceptable', StatusCode::INTERNAL_SERVER_ERROR, $e);
        }
    }

    /**
     * @param string $id
     * @return ActiveRecord
     * @throws ApiException
     */
    public function actionUpdate(string $id): ActiveRecord
    {
        $this->checkAccess(__FUNCTION__);

        $item = $this->actionItem($id);
        $form = $this->getUpdateForm();
        FormValidator::validate($form, ApiException::class);

        try {
            $item->setAttributes($form->getLoadedAttributes());
            $item = $this->beforeUpdate($item, $form);
            $item->save();
            $this->afterUpdate($item, $form);

            Yii::$app->response->statusCode = StatusCode::ACCEPTED;

            return $item;
        } catch(DbException $e) {
            throw new ApiException('not acceptable', StatusCode::NOT_ACCEPTABLE, $e, $e->getData());
        } catch(BaseException $e) {
            throw new ApiException('server error', StatusCode::INTERNAL_SERVER_ERROR, $e, $e->getData());
        } catch(Throwable $e) {
            throw new ApiException('not acceptable', StatusCode::INTERNAL_SERVER_ERROR, $e);
        }
    }

    /**
     * @param string $id
     * @return ActiveRecord
     * @throws ApiException
     */
    public function actionDelete(string $id): ActiveRecord
    {
        $this->checkAccess(__FUNCTION__);

        $item = $this->actionItem($id);

        try {
            $item = $this->beforeDelete($item);
            $item->delete();
            $this->afterDelete($item);

            Yii::$app->response->statusCode = StatusCode::ACCEPTED;

            return $item;
        } catch(DbException $e) {
            throw new ApiException('not acceptable', StatusCode::NOT_ACCEPTABLE, $e, $e->getData());
        } catch(BaseException $e) {
            throw new ApiException('server error', StatusCode::INTERNAL_SERVER_ERROR, $e, $e->getData());
        } catch(Throwable $e) {
            throw new ApiException('not acceptable', StatusCode::INTERNAL_SERVER_ERROR, $e);
        }
    }

    /**
     * @return Model
     */
    abstract protected function getCreateForm(): Model;

    /**
     * @return Model
     */
    abstract protected function getUpdateForm(): Model;

    /**
     * @return ActiveRecord::class
     */
    abstract protected function getActiveRecordClassName(): string;

    /**
     * @override
     * @return string[]
     */
    protected function getDisabledActions(): array
    {
        return [];
    }

    /**
     * @override
     * @return Model|null
     */
    protected function getFilterForm(): ?Model
    {
        return null;
    }

    /**
     * @override
     * @param $query ActiveQuery
     * @param $form Model
     * @return ActiveQuery
     */
    protected function userFilter(ActiveQuery $query, ?Model $form): ActiveQuery
    {
        return $query;
    }

    /**
     * @override
     * @param $query ActiveQuery
     * @return ActiveQuery
     */
    protected function accessFilter(ActiveQuery $query): ActiveQuery
    {
        return $query;
    }

    /**
     * @override
     * @param ActiveQuery $query
     * @return ActiveDataProvider
     */
    protected function getDataProvider(ActiveQuery $query): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => $query,
        ]);
    }

    /**
     * @override
     * @param ActiveQuery $query
     * @return ActiveQuery
     */
    protected function beforeGettingCollection(ActiveQuery $query): ActiveQuery
    {
        return $query;
    }

    /**
     * @override
     * @param ActiveQuery $query
     * @return ActiveQuery
     */
    protected function beforeGettingItem(ActiveQuery $query): ActiveQuery
    {
        return $query;
    }

    /**
     * @override
     * @param ActiveRecord $item
     * @param Model $form
     * @return ActiveRecord
     */
    protected function beforeCreate(ActiveRecord $item, Model $form): ActiveRecord
    {
        return $item;
    }

    /**
     * @override
     * @param ActiveRecord $item
     * @param Model $form
     * @return ActiveRecord
     */
    protected function afterCreate(ActiveRecord $item, Model $form): ActiveRecord
    {
        return $item;
    }

    /**
     * @override
     * @param ActiveRecord $item
     * @param Model $form
     * @return ActiveRecord
     */
    protected function beforeUpdate(ActiveRecord $item, Model $form): ActiveRecord
    {
        return $item;
    }

    /**
     * @override
     * @param ActiveRecord $item
     * @param Model $form
     * @return ActiveRecord
     */
    protected function afterUpdate(ActiveRecord $item, Model $form): ActiveRecord
    {
        return $item;
    }

    /**
     * @override
     * @param ActiveRecord $item
     * @return ActiveRecord
     */
    protected function beforeDelete(ActiveRecord $item): ActiveRecord
    {
        return $item;
    }

    /**
     * @override
     * @param ActiveRecord $item
     * @return ActiveRecord
     */
    protected function afterDelete(ActiveRecord $item): ActiveRecord
    {
        return $item;
    }

    /**
     * @param $query ActiveQuery
     * @return ActiveQuery
     */
    protected function filter($query): ActiveQuery
    {
        $query = $this->accessFilter($query);
        $query = $this->userFilter($query, $this->getFilterForm());

        return $query;
    }

    /**
     * @return ActiveQuery
     */
    protected function getCollectionQuery(): ActiveQuery
    {
        return $this->beforeGettingCollection($this->filter($this->getActiveRecordClassName()::find()));
    }

    /**
     * @param $id string
     * @return ActiveQuery
     */
    protected function getItemQuery(string $id): ActiveQuery
    {
        return $this->beforeGettingItem($this->accessFilter($this->getActiveRecordClassName()::find()->byId($id)));
    }

    /**
     * @param string $methodName
     * @return $this
     * @throws ApiException
     */
    protected function checkAccess(string $methodName): self
    {
        $actionName = strtolower(preg_replace('/^action/', '', $methodName));
        if(in_array($actionName, $this->getDisabledActions())) {
            throw new ApiException('forbidden', StatusCode::FORBIDDEN);
        }

        return $this;
    }
}