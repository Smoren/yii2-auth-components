<?php

namespace Smoren\Yii2\Auth\controllers;

use Smoren\Yii2\ActiveRecordExplicit\components\ActiveDataProvider;
use Smoren\Yii2\ActiveRecordExplicit\exceptions\DbException;
use Smoren\Yii2\ActiveRecordExplicit\helpers\FormValidator;
use Smoren\Yii2\ActiveRecordExplicit\models\ActiveQuery;
use Smoren\Yii2\ActiveRecordExplicit\models\ActiveRecord;
use Smoren\Yii2\ActiveRecordExplicit\models\Model;
use Smoren\Yii2\Auth\exceptions\ApiException;
use Smoren\Yii2\Auth\structs\StatusCode;
use Smoren\ExtendedExceptions\BaseException;
use yii\data\BaseDataProvider;
use Yii;
use Throwable;

trait RestControllerTrait
{
    /**
     * Map for matching actions and methods for collection APIs
     * @var string[]
     */
    protected static $collectionActionMethodMap = [
        'collection' => 'GET',
        'create' => 'POST',
        'options' => 'OPTIONS',
    ];
    /**
     * Map for matching actions and methods for item APIs
     * @var string[]
     */
    protected static $itemActionMethodMap = [
        'item' => 'GET',
        'update' => 'PUT',
        'delete' => 'DELETE',
        'options' => 'OPTIONS',
    ];

    /**
     * Generate routing rules
     * @param string $apiPath
     * @param string $controllerPath
     * @param string $itemIdValidationRegexp
     * @return array
     * @override if you want to add extra rules
     */
    public static function getRules(
        string $apiPath, string $controllerPath, string $itemIdValidationRegexp
    ): array
    {
        return [
            /**
             * API for getting collection
             * @see RestControllerTrait::actionCollection()
             */
            "GET {$apiPath}" => "{$controllerPath}/collection",

            /**
             * API for getting item
             * @see RestControllerTrait::actionItem()
             */
            "GET {$apiPath}/<id:{$itemIdValidationRegexp}>" => "{$controllerPath}/item",

            /**
             * API for creating new item
             * @see RestControllerTrait::actionCreate()
             */
            "POST {$apiPath}" => "{$controllerPath}/create",

            /**
             * API for updating item
             * @see RestControllerTrait::actionUpdate()
             */
            "PUT {$apiPath}/<id:{$itemIdValidationRegexp}>" => "{$controllerPath}/update",

            /**
             * API for deleting item
             * @see RestControllerTrait::actionDelete()
             */
            "DELETE {$apiPath}/<id:{$itemIdValidationRegexp}>" => "{$controllerPath}/delete",

            /**
             * API for getting collection options
             * @see RestControllerTrait::actionOptions()
             */
            "OPTIONS {$apiPath}" => "{$controllerPath}/options",

            /**
             * API for getting item options
             * @see RestControllerTrait::actionOptions()
             */
            "OPTIONS {$apiPath}/<id:{$itemIdValidationRegexp}>" => "{$controllerPath}/options",
        ];
    }

    /**
     * Action for getting collection
     * @return BaseDataProvider
     * @throws ApiException
     */
    public function actionCollection(): BaseDataProvider
    {
        $this->checkAccess(__FUNCTION__);
        Yii::$app->response->statusCode = StatusCode::OK;
        return $this->getDataProvider($this->getCollectionQuery());
    }

    /**
     * Action for getting item
     * @param string $id
     * @return ActiveRecord|array
     * @throws ApiException
     */
    public function actionItem(string $id)
    {
        $this->checkAccess(__FUNCTION__);

        try {
            Yii::$app->response->statusCode = StatusCode::OK;
            return $this->getItemQuery($id)->one();
        } catch(ApiException $e) {
            throw $e;
        } catch(DbException $e) {
            throw new ApiException('not found', StatusCode::NOT_FOUND, $e, $e->getData());
        } catch(BaseException $e) {
            throw new ApiException('server error', StatusCode::INTERNAL_SERVER_ERROR, $e, $e->getData());
        } catch(Throwable $e) {
            throw new ApiException('server error', StatusCode::INTERNAL_SERVER_ERROR, $e);
        }
    }

    /**
     * Action for creating new item
     * @return ActiveRecord|mixed
     * @throws ApiException
     */
    public function actionCreate()
    {
        $this->checkAccess(__FUNCTION__);

        $form = $this->getCreateForm();
        FormValidator::validate($form, ApiException::class);

        try {
            $className = $this->getActiveRecordClassName();
            $item = new $className($form->getLoadedAttributes());
            $item = $this->beforeCreate($item, $form);
            $item->save();
            $item = $this->afterCreate($item, $form);

            Yii::$app->response->statusCode = StatusCode::CREATED;

            return $item;
        } catch(ApiException $e) {
            throw $e;
        } catch(DbException $e) {
            throw new ApiException('not acceptable', StatusCode::NOT_ACCEPTABLE, $e, $e->getData());
        } catch(BaseException $e) {
            throw new ApiException('server error', StatusCode::INTERNAL_SERVER_ERROR, $e, $e->getData());
        } catch(Throwable $e) {
            throw new ApiException('server error', StatusCode::INTERNAL_SERVER_ERROR, $e);
        }
    }

    /**
     * Action for updating item
     * @param string $id
     * @return ActiveRecord|mixed
     * @throws ApiException
     */
    public function actionUpdate(string $id)
    {
        $this->checkAccess(__FUNCTION__);

        $item = $this->getItem($id);
        $form = $this->getUpdateForm($id, $item);
        FormValidator::validate($form, ApiException::class);

        try {
            $item->setAttributes($form->getLoadedAttributes());
            $item = $this->beforeUpdate($item, $form);
            $item->save();
            $item = $this->afterUpdate($item, $form);

            Yii::$app->response->statusCode = StatusCode::ACCEPTED;

            return $item;
        } catch(ApiException $e) {
            throw $e;
        } catch(DbException $e) {
            throw new ApiException('not acceptable', StatusCode::NOT_ACCEPTABLE, $e, $e->getData());
        } catch(BaseException $e) {
            throw new ApiException('server error', StatusCode::INTERNAL_SERVER_ERROR, $e, $e->getData());
        } catch(Throwable $e) {
            throw new ApiException('server error', StatusCode::INTERNAL_SERVER_ERROR, $e);
        }
    }

    /**
     * Action for deleting item
     * @param string $id
     * @return ActiveRecord|mixed
     * @throws ApiException
     */
    public function actionDelete(string $id)
    {
        $this->checkAccess(__FUNCTION__);

        $item = $this->getItem($id);

        try {
            $item = $this->beforeDelete($item);
            $item->delete();
            $item = $this->afterDelete($item);

            Yii::$app->response->statusCode = StatusCode::ACCEPTED;

            return $item;
        } catch(ApiException $e) {
            throw $e;
        } catch(DbException $e) {
            throw new ApiException('not acceptable', StatusCode::NOT_ACCEPTABLE, $e, $e->getData());
        } catch(BaseException $e) {
            throw new ApiException('server error', StatusCode::INTERNAL_SERVER_ERROR, $e, $e->getData());
        } catch(Throwable $e) {
            throw new ApiException('server error', StatusCode::INTERNAL_SERVER_ERROR, $e);
        }
    }

    /**
     * Action for options
     * @param string|null $id
     * @throws ApiException
     */
    public function actionOptions(?string $id = null)
    {
        if(Yii::$app->getRequest()->getMethod() !== 'OPTIONS') {
            throw new ApiException('method not allowed', StatusCode::METHOD_NOT_ALLOWED);
        }

        if($id === null) {
            $actionMethodMap = static::$collectionActionMethodMap;
        } else {
            $actionMethodMap = static::$itemActionMethodMap;
        }

        foreach($this->getDisabledActions() as $disabledAction) {
            if(isset($actionMethodMap)) {
                unset($actionMethodMap[$disabledAction]);
            }
        }

        $options = array_values($actionMethodMap);
        $headers = Yii::$app->getResponse()->getHeaders();
        $headers->set('Allow', implode(', ', $options));
        $headers->set('Access-Control-Allow-Methods', implode(', ', $options));
    }

    /**
     * Returns form for creating new item with loaded user data
     * @see RestControllerTrait::actionCreate()
     * @return Model
     * @override always
     */
    abstract protected function getCreateForm(): Model;

    /**
     * Returns form for updating item with loaded user data
     * @see RestControllerTrait::actionUpdate()
     * @param string $itemId
     * @param ActiveRecord $item
     * @return Model
     * @override always
     */
    abstract protected function getUpdateForm(string $itemId, $item): Model;

    /**
     * Returns linked active record class name
     * @return ActiveRecord::class
     * @override always
     */
    abstract protected function getActiveRecordClassName(): string;

    /**
     * Returns list of disabled actions
     * Example: ['collection', 'item', 'create', 'update', 'delete', 'options']
     * @see RestControllerTrait::checkAccess()
     * @see RestControllerTrait::actionOptions()
     * @return string[]
     * @override if you want to disable some actions
     */
    protected function getDisabledActions(): array
    {
        return [];
    }

    /**
     * Returns form of filter config with loaded user data
     * @see RestControllerTrait::userFilter() to apply this form (need implementation)
     * @return Model|null
     * @override if you want to implement user filter with user data from request
     */
    protected function getFilterForm(): ?Model
    {
        return null;
    }

    /**
     * Returns form of order config with loaded user data
     * @see RestControllerTrait::userOrder() to apply this form (need implementation)
     * @return Model|null
     * @override if you want to implement user order with user data from request
     */
    protected function getOrderForm(): ?Model
    {
        return null;
    }

    /**
     * Applies FilterForm to collection query
     * @see RestControllerTrait::actionCollection()
     * @param ActiveQuery $query
     * @param Model|null $form
     * @return ActiveQuery
     * @override if you want to apply user filter
     */
    protected function userFilter(ActiveQuery $query, ?Model $form): ActiveQuery
    {
        return $query;
    }

    /**
     * Applies OrderForm to collection query
     * @see RestControllerTrait::actionCollection()
     * @param ActiveQuery $query
     * @param Model|null $form
     * @return ActiveQuery
     * @override if you want to apply user order
     */
    protected function userOrder(ActiveQuery $query, ?Model $form): ActiveQuery
    {
        return $query;
    }

    /**
     * Adds extra filter conditions for collection, item, update, delete actions
     * @see RestControllerTrait::actionCollection()
     * @see RestControllerTrait::actionItem()
     * @see RestControllerTrait::actionUpdate()
     * @see RestControllerTrait::actionDelete()
     * @param $query ActiveQuery
     * @return ActiveQuery
     * @override if you want to apply access filter
     */
    protected function accessFilter(ActiveQuery $query): ActiveQuery
    {
        return $query;
    }

    /**
     * Returns data provider for getting collection
     * @see RestControllerTrait::actionCollection()
     * @param ActiveQuery $query
     * @return BaseDataProvider
     * @override if you want to replace base ActiveDataProvider (e.g. for pagination)
     */
    protected function getDataProvider(ActiveQuery $query): BaseDataProvider
    {
        return new ActiveDataProvider([
            'query' => $query,
        ]);
    }

    /**
     * Extra instruction to execute before getting collection
     * @see RestControllerTrait::actionCollection()
     * @param ActiveQuery $query
     * @return ActiveQuery
     * @override if you want to execute some instructions before getting collection
     */
    protected function beforeGettingCollection(ActiveQuery $query): ActiveQuery
    {
        return $query;
    }

    /**
     * Extra instruction to execute before getting item
     * @see RestControllerTrait::actionItem()
     * @param ActiveQuery $query
     * @return ActiveQuery
     * @override if you want to execute some instructions before getting item
     */
    protected function beforeGettingItem(ActiveQuery $query): ActiveQuery
    {
        return $query;
    }

    /**
     * Extra instruction to execute before creating new item
     * @see RestControllerTrait::actionCreate()
     * @param ActiveRecord $item
     * @param Model $form
     * @return ActiveRecord
     * @override if you want to execute some instructions before creating new item
     */
    protected function beforeCreate(ActiveRecord $item, Model $form): ActiveRecord
    {
        return $item;
    }

    /**
     * Extra instruction to execute after creating new item
     * @see RestControllerTrait::actionCreate()
     * @param ActiveRecord $item
     * @param Model $form
     * @return ActiveRecord|mixed
     * @override if you want to execute some instructions after creating new item
     */
    protected function afterCreate(ActiveRecord $item, Model $form)
    {
        return $item;
    }

    /**
     * Extra instruction to execute before updating item
     * @see RestControllerTrait::actionUpdate()
     * @param ActiveRecord $item
     * @param Model $form
     * @return ActiveRecord
     * @override if you want to execute some instructions before updating item
     */
    protected function beforeUpdate(ActiveRecord $item, Model $form): ActiveRecord
    {
        return $item;
    }

    /**
     * Extra instruction to execute after updating item
     * @see RestControllerTrait::actionUpdate()
     * @param ActiveRecord $item
     * @param Model $form
     * @return ActiveRecord|mixed
     * @override if you want to execute some instructions after updating item
     */
    protected function afterUpdate(ActiveRecord $item, Model $form)
    {
        return $item;
    }

    /**
     * Extra instruction to execute before deleting item
     * @see RestControllerTrait::actionDelete()
     * @param ActiveRecord $item
     * @return ActiveRecord
     * @override if you want to execute some instructions before deleting item
     */
    protected function beforeDelete(ActiveRecord $item): ActiveRecord
    {
        return $item;
    }

    /**
     * Extra instruction to execute after deleting item
     * @see RestControllerTrait::actionDelete()
     * @param mixed $item
     * @return ActiveRecord|mixed
     * @override if you want to execute some instructions after deleting item
     */
    protected function afterDelete(ActiveRecord $item)
    {
        return $item;
    }

    /**
     * Applies filter conditions for collection query using user filter and access filter
     * @see RestControllerTrait::accessFilter()
     * @see RestControllerTrait::userFilter()
     * @param ActiveQuery $query
     * @param bool $validate
     * @return ActiveQuery
     */
    protected function filter(ActiveQuery $query, bool $validate = false): ActiveQuery
    {
        $filterForm = $this->getFilterForm();

        if($filterForm && $validate) {
            FormValidator::validate($filterForm, ApiException::class);
        }

        $query = $this->accessFilter($query);
        $query = $this->userFilter($query, $filterForm);

        return $query;
    }

    /**
     * Applies order conditions using user order
     * @see RestControllerTrait::userOrder()
     * @param ActiveQuery $query
     * @param bool $validate
     * @return ActiveQuery
     */
    protected function order(ActiveQuery $query, bool $validate = false): ActiveQuery
    {
        $orderForm = $this->getOrderForm();

        if($orderForm && $validate) {
            FormValidator::validate($orderForm, ApiException::class);
        }

        return $this->userOrder($query, $orderForm);
    }

    /**
     * Returns getting collection query using access filter, user filter and user order
     * @see RestControllerTrait::accessFilter()
     * @see RestControllerTrait::userFilter()
     * @see RestControllerTrait::userOrder()
     * @param bool $validate
     * @return ActiveQuery
     */
    protected function getCollectionQuery(bool $validate = true): ActiveQuery
    {
        $query = $this->getActiveRecordClassName()::find();

        $query = $this->filter($query, $validate);
        $query = $this->order($query, $validate);

        return $this->beforeGettingCollection($query);
    }

    /**
     * Returns getting item query using access filter
     * @see RestControllerTrait::accessFilter()
     * @param $id string
     * @return ActiveQuery
     */
    protected function getItemQuery(string $id): ActiveQuery
    {
        return $this->beforeGettingItem($this->accessFilter($this->getActiveRecordClassName()::find())->byId($id));
    }

    /**
     * Returns item with using access filter
     * @see RestControllerTrait::accessFilter()
     * @param string $id
     * @return ActiveRecord
     * @throws ApiException
     */
    protected function getItem(string $id): ActiveRecord
    {
        try {
            return $this->accessFilter($this->getActiveRecordClassName()::find())->byId($id)->one();
        } catch(DbException $e) {
            throw new ApiException('not found', StatusCode::NOT_FOUND, $e, $e->getData());
        } catch(BaseException $e) {
            throw new ApiException('server error', StatusCode::INTERNAL_SERVER_ERROR, $e, $e->getData());
        } catch(Throwable $e) {
            throw new ApiException('server error', StatusCode::INTERNAL_SERVER_ERROR, $e);
        }
    }

    /**
     * Checks access to action using method getDisabledActions()
     * @see RestControllerTrait::getDisabledActions()
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
