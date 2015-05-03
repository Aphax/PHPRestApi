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
     * @var Model
     */
    protected $rootModel;

    /**
     * @var Model
     */
    protected $relationalModel;

    /**
     * @param RestServer $server
     */
    function __construct(RestServer $server)
    {
        $this->server = $server;
        $this->rootModel = $server->getResourceModel();
        $this->relationalModel = $server->getResourceRelationalModel();
    }

    /**
     * @param array $data
     */
    public function create(array $data)
    {
        foreach ($this->rootModel->getFields() as $name => $params) {
            if (isset($data[$name])) {
                $this->rootModel->setFieldValue($name, $data[$name]);
            }
        }
        $this->rootModel->create();
        $this->server->appendResponse($this->rootModel->getTableName(), $this->rootModel->getFieldsValues());
    }

    /**
     * @param array $data
     * @param $rootResourceId
     * @throws RestServerForbiddenException
     * @throws RestServerNotFoundException
     */
    public function createRelational(array $data, $rootResourceId)
    {
        if ($this->relationalModel === NULL) {
            throw new RestServerForbiddenException('Aucune ressource relationnelle spécifiée pour l\'ajout.');
        }
        $this->rootModel->read($rootResourceId);
        $this->server->appendResponse('success', $this->relationalModel->createRelational($data, $this->rootModel));
    }

    /**
     * @param $id
     * @throws RestServerForbiddenException
     * @throws RestServerNotFoundException
     */
    public function read($id)
    {
        $this->rootModel->read($id);
        $this->server->appendResponse('data', $this->rootModel->getFieldsValues());
    }

    /**
     * @param $id
     * @throws RestServerForbiddenException
     * @throws RestServerNotFoundException
     */
    public function readRelational($id)
    {
        $this->rootModel->read($id);
        $this->server->appendResponse('data', $this->rootModel->getChilds($this->relationalModel));
    }

    /**
     * @param $id
     * @param array $data
     * @throws RestServerForbiddenException
     */
    public function update($id, $data = array())
    {
        $this->rootModel->read($id);
        $fields = $this->rootModel->getFields();
        foreach ($data as $name => $params) {
            if (isset($fields[$name])) {
                $this->rootModel->setFieldValue($name, $data[$name]);
            }
        }
        $this->server->appendResponse('updated', $this->rootModel->update());
    }

    /**
     * @param $rootResourceId
     */
    public function delete($rootResourceId)
    {
        $this->rootModel->read($rootResourceId);
        $this->server->appendResponse('deleted', $this->rootModel->delete($rootResourceId));
    }

    /**
     * @param $rootResourceId
     * @param $relationalResourceId
     * @throws RestServerForbiddenException
     * @throws RestServerNotFoundException
     */
    public function deleteRelational($rootResourceId, $relationalResourceId)
    {
        $this->rootModel->read($rootResourceId);
        $this->relationalModel->read($relationalResourceId);
        $this->server->appendResponse('deleted', $this->relationalModel->deleteRelational($this->rootModel));
    }
}