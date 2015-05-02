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
use Aphax\models\Model;
use Aphax\RestServer;

/**
 * Class RestController
 * @package Aphax\controllers
 */
abstract class RestController {
    /**
     * @var RestServer
     */
    protected $server;

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
     * @return Model
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
     */
    public function create(array $data)
    {
        $model = $this->getModel();

        foreach ($model->getFields() as $name => $params) {
            if (isset($data[$name])) {
                $model->setFieldValue($name, $data[$name]);
            }
        }
        $model->create();
        $this->server->appendResponse($model->getTableName(), $model->getFieldsValues());
    }

    /**
     * @param array $data
     * @param $parentId : In case of relationship resource creation, id of the parent resource
     * @throws RestServerForbiddenException
     * @throws RestServerNotFoundException
     */
    public function createRelational(array $data, $parentId)
    {
        $childModel = $this->server->getRelationalResource(1);
        $parentModel = $this->getModel();
        $parentModel->setFieldValue($parentModel->getPrimaryKey(), $parentId);
        $this->server->appendResponse('success', $childModel->createRelational($data, $parentModel));
    }

    /**
     * @param $id
     * @throws RestServerForbiddenException
     * @throws RestServerNotFoundException
     */
    public function read($id)
    {
        $childModel = $this->server->getRelationalResource(1);
        $parentModel = $this->getModel();
        $parentModel->read($id);

        // Get a list of child resources like /parent/id/child
        if ($childModel !== NULL) {
            $this->server->appendResponse(lcfirst($childModel->getTableName()), $parentModel->getChilds($childModel));
        } else {
            $this->server->appendResponse(lcfirst($parentModel->getTableName()), $parentModel->getFieldsValues());
        }
    }

    /**
     * @param $id
     * @param array $data
     * @throws RestServerForbiddenException
     */
    public function update($id, array $data)
    {
        $this->server->appendResponse('data', $data);
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

    /**
     * @param $id : Parent model id
     * @param Model $childModel
     */
    public function deleteRelational($id, Model $childModel)
    {
        $parentModel = $this->getModel();
        $parentModel->read($id);
        $this->server->appendResponse('deleted', $childModel->deleteRelational($parentModel));
    }
}