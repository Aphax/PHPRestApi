<?php
/**
 * Created by PhpStorm.
 * User: Aphax
 * Date: 02/05/2015
 * Time: 17:12
 */

spl_autoload_register('autoload');
function autoload($className)
{
    $className = str_replace("Aphax\\", "", $className);
    $className = str_replace("\\", "/", $className);
    require_once($className.".php");
}

require_once '../vendor/autoload.php';