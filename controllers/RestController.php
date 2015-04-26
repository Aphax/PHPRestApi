<?php
/**
 * Created by PhpStorm.
 * User: Aphax
 * Date: 25/04/2015
 * Time: 17:48
 */

namespace Aphax\controllers;

use Aphax\exceptions\RestServerForbiddenException;
use Aphax\exceptions\RestServerNotFoundException;
use Aphax\RestServer;

/**
 * Class RestController
 * @package Aphax\controllers
 */
abstract class RestController {
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
        if (empty($this->model)) {
            return $this->server->getUriPart(0);
        }
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
        $this->server->appendResponse($model->getTableName(), $model->getFieldsValues());
    }

    /**
     * @param $id
     * @throws RestServerForbiddenException
     * @throws RestServerNotFoundException
     */
    public function read($id)
    {
        $model = $this->getModel();

        // Get a list of child resources like /parent/id/child
        if ($this->server->getUriPart(2) !== NULL) {
            $child = '\Aphax\models\\' . lcfirst($this->server->getUriPart(2));
            if (!class_exists($child)) {
                throw new RestServerNotFoundException();
            }
            $child = new $child();
            $this->server->appendResponse(lcfirst($child->getTableName()), $model->getManyToManyChilds($id, $child));
        } else {
            $model->read($id);
            $this->server->appendResponse(lcfirst($model->getTableName()), $model->getFieldsValues());
        }
    }

    /**
     * @param $id
     * @param array $data
     * @throws RestServerForbiddenException
     */
    public function update($id, array $data)
    {
        $model = $this->getModel();
        $model->read($id);
        $fields = $model->getFields();
        foreach ($data as $name => $params) {
            if (isset($fields[$name])) {
                $model->setFieldValue($name, $data[$name]);
            }
        }
        $model->update();
        $this->server->appendResponse('updated', $model->getFieldsValues());
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        $model = $this->getModel();
        $model->read($id);
        $this->server->appendResponse('deleted', $model->getFieldsValues());
        $model->delete($id);
    }
}