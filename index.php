<?php
/**
 * Created by PhpStorm.
 * User: Aphax
 * Date: 25/04/2015
 * Time: 14:26
 */

error_reporting(E_ALL);

use Aphax\RestServer;
use Aphax\exceptions\RestServerNotFoundException;
use Aphax\exceptions\RestServerForbiddenException;

spl_autoload_register('autoload');
function autoload($className)
{
    $className = str_replace("Aphax\\", "", $className);
    $className = str_replace("\\", "/", $className);
    require_once($className.".php");
}

try {
    $server = new RestServer();
    $server->callAction();
    echo $server->getResponse();
} catch (RestServerForbiddenException $e) {
    echo $server->getResponseForbidden($e);
} catch (RestServerNotFoundException $e) {
    echo $server->getResponseNotFound($e);
} catch (\Exception $e) {
    echo $server->getResponseException($e);
}