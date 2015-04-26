<?php
/**
 * Created by PhpStorm.
 * User: Aphax
 * Date: 25/04/2015
 * Time: 16:00
 */

namespace Aphax\controllers;

use Aphax\RestServer;

class Usersong extends RestController {
    function __construct(RestServer $server)
    {
        parent::__construct($server);
        $this->model = new \Aphax\models\UserSong();
    }

}