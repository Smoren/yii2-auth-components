<?php

namespace Smoren\Yii2\Auth\exceptions;


/**
 * Исключение проверки токена
 */
class TokenException extends ApiException
{
    const STATUS_EMPTY = 401;
    const STATUS_INVALID = 403;
    const STATUS_LOGIC_ERROR = 500;
}
