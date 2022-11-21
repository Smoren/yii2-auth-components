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
use yii\data\BaseDataProvider;

trait RestControllerTrait
{
    /**
     * @var string[]
     */
    protected static $collectionActionMethodMap = [
        'collection' => 'GET',
        'create' => 'POST',
        'options' => 'OPTIONS',
    ];
    /**
     * @var string[]
     */
    protected static $itemActionMethodMap = [
        'item' => 'GET',
        'update' => 'PUT',
        'delete' => 'DELETE',
        'options' => 'OPTIONS',
    ];

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
             * API получения коллекции
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

            /**
             * API options коллекции
             * @see RestControllerTrait::actionOptions()
             */
            "OPTIONS {$apiPath}" => "{$controllerPath}/options",

            /**
             * API options элемента
             * @see RestControllerTrait::actionOptions()
             */
            "OPTIONS {$apiPath}/<id:{$itemIdValidationRegexp}>" => "{$controllerPath}/options",
        ];
    }

    /**
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
     * @param string $id
     * @return ActiveRecord|mixed
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
     * @return Model
     */
    abstract protected function getCreateForm(): Model;

    /**
     * @param string $itemId
     * @param ActiveRecord $item
     * @return Model
     */
    abstract protected function getUpdateForm(string $itemId, $item): Model;

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
     * @return Model|null
     */
    protected function getOrderForm(): ?Model
    {
        return null;
    }

    /**
     * @override
     * @param ActiveQuery $query
     * @param Model|null $form
     * @return ActiveQuery
     */
    protected function userFilter(ActiveQuery $query, ?Model $form): ActiveQuery
    {
        return $query;
    }

    /**
     * @override
     * @param ActiveQuery $query
     * @param Model|null $form
     * @return ActiveQuery
     */
    protected function userOrder(ActiveQuery $query, ?Model $form): ActiveQuery
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
     * @return BaseDataProvider
     */
    protected function getDataProvider(ActiveQuery $query): BaseDataProvider
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
     * @return ActiveRecord|mixed
     */
    protected function afterCreate(ActiveRecord $item, Model $form)
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
     * @return ActiveRecord|mixed
     */
    protected function afterUpdate(ActiveRecord $item, Model $form)
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
     * @param mixed $item
     * @return ActiveRecord|mixed
     */
    protected function afterDelete(ActiveRecord $item)
    {
        return $item;
    }

    /**
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
     * @param $id string
     * @return ActiveQuery
     */
    protected function getItemQuery(string $id): ActiveQuery
    {
        return $this->beforeGettingItem($this->accessFilter($this->getActiveRecordClassName()::find())->byId($id));
    }

    /**
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
