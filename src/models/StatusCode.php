<?php

namespace Smoren\Yii2\Auth\models;

/**
 * Класс для списка статусов ответа от сервера
 */
class StatusCode
{
    const OK = 200;
    const CREATED = 201;
    const ACCEPTED = 202;

    const BAD_REQUEST = 400;
    const NOT_AUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;
    const NOT_ACCEPTABLE = 406;
    const TOO_MANY_REQUESTS = 429;
    const I_AM_TEAPOT = 418;

    const INTERNAL_SERVER_ERROR = 500;
}
