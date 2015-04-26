<?php
/**
 * Created by PhpStorm.
 * User: Aphax
 * Date: 27/04/2015
 * Time: 00:24
 */

namespace Aphax\controllers;


use Aphax\RestServer;

class Song extends RestController {
    function __construct(RestServer $server)
    {
        parent::__construct($server);
        $this->model = new \Aphax\models\Song();
    }
}