<?php
/**
 * Created by PhpStorm.
 * User: Aphax
 * Date: 25/04/2015
 * Time: 15:59
 */

namespace Aphax\controllers;

use Aphax\exceptions\RestServerForbiddenException;
use Aphax\RestServer;

class User extends RestController {
    function __construct(RestServer $server)
    {
        parent::__construct($server);
        $this->model = new \Aphax\models\User();
    }

    /**
     * @param array $params
     * @throws RestServerForbiddenException
     */
    public function create(array $params)
    {
        if (!isset($params['email']) || !strpos($params['email'], '@')) {
            throw new RestServerForbiddenException('Paramètres d\'ajout utilisateur invalides');
        }
        parent::create($params);
    }
}