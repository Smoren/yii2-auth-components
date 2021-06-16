<?php

namespace Smoren\Yii2\Auth\components;

use Exception;
use Yii;

/**
 * Менеджер для работы с ролями
 */
class UserRoleManager
{
    /**
     * Проверяет есть ли у пользователя роль
     * @param mixed $userId
     * @param string $roleName
     * @return bool
     */
    public function hasRole($userId, string $roleName): bool
    {
        $ids = Yii::$app->authManager->getUserIdsByRole($roleName);
        return in_array($userId, $ids);
    }

    /**
     * Добавляет роль пользователю
     * @param mixed $userId
     * @param string $roleName
     * @return UserRoleManager
     * @throws Exception
     */
    public function addRole($userId, string $roleName)
    {
        $auth = Yii::$app->authManager;
        $role = $auth->getRole($roleName);
        $auth->assign($role, $userId);
        return $this;
    }
}
