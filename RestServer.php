<?php
/**
 * Created by PhpStorm.
 * User: Aphax
 * Date: 25/04/2015
 * Time: 15:43
 */

namespace Aphax;

use Aphax\exceptions\RestServerForbiddenException;
use Aphax\exceptions\RestServerNotFoundException;

/**
 * Class RestServer
 * @package Aphax
 */
class RestServer
{
    /**
     * Parts of the uri string between slashes
     * @var array
     */
    private $uriparts = array();

    /**
     * Response content to be formatted to JSON
     * @var array
     */
    private $response = array();

    /**
     * @throws \HttpException
     */
    function __construct()
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            throw new \HttpException("Erreur HTTP : Impossible de récupérer le contenu de la requête");
        }

        $uri = $_SERVER['REQUEST_URI'];
        $uri = str_replace(dirname($_SERVER['PHP_SELF']) . '/', '', $uri);
        if (!empty($uri)) {
            $this->uriparts = explode("/", $uri);
        }
    }

    /**
     * @param $index
     * @param $data
     */
    public function appendResponse($index, $data)
    {
        $this->response[$index] = $data;
    }

    /**
     * @return mixed
     * @throws RestServerForbiddenException
     * @throws \HttpMalformedHeadersException
     */
    public function callAction()
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                if ($this->getResourceId() === NULL) {
                    throw new RestServerForbiddenException("Accès refusé : Aucun Id spécifié pour lire la ressource");
                }
                if ($this->getResourceRelationalModel() !== NULL) {
                    $call = array($this->getController(), 'readRelational');
                } else {
                    $call = array($this->getController(), 'read');
                }
                $call_parameters = array($this->getResourceId());
                break;
            case 'POST':
                $parameters = $_POST;
                if (empty($parameters)) {
                    throw new RestServerForbiddenException("Accès refusé : Aucune données transmises pour crée la ressource");
                }
                $call_parameters = array($parameters);
                // Parent resource ID specified, relational creation
                if ($this->getResourceId() !== NULL) {
                    $call = array($this->getController(), 'createRelational');
                    $call_parameters[] = $this->getResourceId();
                // Simple creation
                } else {
                    $call = array($this->getController(), 'create');
                }
                break;
            case 'PUT':
                $parameters = array();
                $call = array($this->getController(), 'update');
                parse_str(file_get_contents("php://input"), $parameters);
                if (empty($parameters)) {
                    throw new RestServerForbiddenException("Accès refusé : Aucune données transmises pour modifier la ressource");
                }
                if ($this->getResourceId() === NULL) {
                    throw new RestServerForbiddenException("Accès refusé : Aucun Id spécifié pour modifier la ressource");
                }
                $call_parameters = array($this->getResourceId(), $parameters);
                break;
            case 'DELETE':
                if ($this->getResourceId() === NULL) {
                    throw new RestServerForbiddenException("Accès refusé : Aucun Id spécifié pour supprimer la ressource");
                }
                $call_parameters = array($this->getResourceId());
                if ($this->getResourceRelationalId() !== NULL && $this->getResourceRelationalModel() != NULL) {
                    $call = array($this->getController(), 'deleteRelational');
                    $call_parameters[] = $this->getResourceRelationalId();
                } else {
                    $call = array($this->getController(), 'delete');
                }
                break;
            default:
                throw new \HttpMalformedHeadersException("La méthode HTTP utilisée n'est pas compatible.");
        }

        $this->appendResponse('debug', array(
            'controller' => $call[0],
            'action'     => $call[1],
            'resourceId' => $this->getResourceId(),
            'resourceRelationalId' => $this->getResourceRelationalId(),
            'parameters' => $this->getRequestParameters(),
            'callable'   => is_callable(array($call[0], $call[1]))
        ));

        if (is_callable($call)) {
            return call_user_func_array($call, $call_parameters);
        } else {
            throw new RestServerForbiddenException("Accès refusé");
        }
    }

    /**
     * Translate the first uri part as a controller name who handles a specific resource
     * If a specific controller doesn't exists for the resource, load default controller
     * @return null|string
     * @throws RestServerNotFoundException
     */
    private function getController()
    {
        $part = $this->getUriPart(0);
        if ($part !== NULL) {
            $controller = '\Aphax\controllers\\' . ucfirst($part);
            if (!class_exists($controller)) {
                throw new RestServerNotFoundException();
            }

            return new $controller($this);
        }

        return NULL;
    }

    /**
     * @return \Aphax\models\Model
     */
    public function getResourceModel()
    {
        $part = $this->getUriPart(0);
        if ($part !== NULL) {
            $model = '\Aphax\models\\' . ucfirst($part);
            if (class_exists($model)) {
                /** @var \Aphax\models\Model $model */
                $model = new $model();
                $part = $this->getResourceId();
                if ($part !== NULL) {
                    $model->setFieldValue($model->getPrimaryKey(), $this->getUriPart(1));
                }

                return $model;
            }
        }

        return NULL;
    }

    /**
     * @return \Aphax\models\Model
     */
    public function getResourceRelationalModel()
    {
        $part = $this->getUriPart(2);
        if ($part !== NULL) {
            $model = '\Aphax\models\\' . ucfirst($part);
            if (class_exists($model)) {
                /** @var \Aphax\models\Model $model */
                $model = new $model();
                $part = $this->getResourceRelationalId();
                if ($part !== NULL) {
                    $model->setFieldValue($model->getPrimaryKey(), $this->getUriPart(1));
                }

                return $model;
            }
        }

        return NULL;
    }

    /**
     * Get the request parameters
     * @return array
     */
    private function getRequestParameters()
    {
        return $_REQUEST;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        header('Content-type: text/json; charset=UTF-8');

        return json_encode($this->response);
    }

    /**
     * @param \Exception $e
     * @return string
     */
    public function getResponseException(\Exception $e)
    {
        $this->appendResponse('error', $e->getMessage());

        return $this->getResponse();
    }

    /**
     * @param \Exception $e
     * @return string
     */
    public function getResponseForbidden(\Exception $e)
    {
        header('HTTP/1.0 403 Forbidden');
        $this->appendResponse('error', $e->getMessage());

        return $this->getResponse();
    }

    /**
     * @param RestServerNotFoundException $e
     * @return string
     */
    public function getResponseNotFound(RestServerNotFoundException $e)
    {
        header('HTTP/1.0 404 Not Found');

        return $this->getResponse();
    }

    /**
     * Get the ID resource provided in the second uri part
     * @return null|string
     */
    public function getResourceId()
    {
        return $this->getUriPart(1);
    }

    /**
     * Get the ID resource provided in the second uri part
     * @return null|string
     */
    public function getResourceRelationalId()
    {
        return $this->getUriPart(3);
    }

    /**
     * @param $index
     * @return mixed
     */
    public function getUriPart($index)
    {
        if (isset($this->uriparts[$index])) {
            return $this->uriparts[$index];
        }

        return NULL;
    }

    /**
     *
     */
    public function sendEmptyResponse()
    {
        echo json_encode(array());
    }
}