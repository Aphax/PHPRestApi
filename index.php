<?php
/**
 * Created by PhpStorm.
 * User: Aphax
 * Date: 25/04/2015
 * Time: 14:26
 */

error_reporting(E_ALL);

require_once 'RestServer.php';
require_once 'controllers/RestController.php';
require_once 'controllers/User.php';
require_once 'controllers/Song.php';
require_once 'controllers/UserSong.php';
require_once 'models/Model.php';
require_once 'models/User.php';
require_once 'models/Song.php';
require_once 'models/UserSong.php';
require_once 'exceptions/RestServerForbiddenException.php';
require_once 'exceptions/RestServerNotFoundException.php';

use Aphax\RestServer;
use Aphax\exceptions\RestServerNotFoundException;
use Aphax\exceptions\RestServerForbiddenException;

spl_autoload_register('autoload');
function autoload($className)
{
//    echo "autoload : " . $className;
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