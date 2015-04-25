<?php
/**
 * Created by PhpStorm.
 * User: Aphax
 * Date: 25/04/2015
 * Time: 17:48
 */

namespace Aphax\controllers;

use Aphax\exceptions\RestServerForbiddenException;
use Aphax\RestServer;

/**
 * Class RestController
 * @package Aphax\controllers
 */
class RestController {
    /**
     * @var RestServer
     */
    private $server;

    /**
     * @var
     */
    protected $model;

    /**
     * @param RestServer $server
     */
    function __construct(RestServer $server)
    {
        $this->server = $server;
    }

    /**
     * @return \Aphax\models\Model
     */
    public function getModel()
    {
        return $this->model;
    }


    /**
     * @param array $data
     * @throws RestServerForbiddenException
     */
    public function create(array $data)
    {
        $model = $this->getModel();
        $fields = $model->getFields();
        unset($fields[$model->getPrimaryKey()]);
        foreach ($fields as $name => $params) {
            if (!isset($data[$name])) {
                throw new RestServerForbiddenException("Données $name manquante pour l'ajout de l'entité");
            }
            $model->setFieldValue($name, $data[$name]);
        }
        $model->create();
        $this->server->appendResponse(lcfirst(get_called_class()), $model->getFieldsValues());
    }

    /**
     * @param $id
     */
    public function read($id)
    {
        $model = $this->getModel();
        $model->read($id);
        $this->server->appendResponse(lcfirst($model->getTableName()), $model->getFieldsValues());
    }

    /**
     * @param $id
     * @param array $params
     */
    public function update($id, array $params)
    {
        $this->server->appendResponse('UPDATE ID', $id);
        $this->server->appendResponse('UPDATE params', $params);
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        $this->server->appendResponse('DELETE ID', $id);
    }
}