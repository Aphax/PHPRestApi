<?php
/**
 * Created by PhpStorm.
 * User: Aphax
 * Date: 25/04/2015
 * Time: 15:43
 */

namespace Aphax;

use Aphax\exceptions\RestServerForbiddenException;

class RestServer {
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

    function __construct()
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            throw new \HttpException("Erreur HTTP : Impossible de récupérer le contenu de la requête");
        }

        $uri = $_SERVER['REQUEST_URI'];
        $uri = str_replace(dirname($_SERVER['PHP_SELF']).'/', '', $uri);
        if (!empty($uri)) {
            $this->uriparts = explode("/", $uri);
        }
    }

    public function appendResponse($index, $data)
    {
        $this->response[$index] = $data;
    }

    public function callAction()
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $call = array($this->getController(), 'read');
                if ($this->getResourceId() == NULL) {
                    throw new RestServerForbiddenException("Accès refusé : Aucun Id spécifié pour lire la ressource");
                }
                $call_parameters = array($this->getResourceId());
                break;
            case 'POST':
                $call = array($this->getController(), 'create');
                $parameters = $this->getRequestParameters();
                if (empty($parameters)) {
                    throw new RestServerForbiddenException("Accès refusé : Aucune données transmises pour crée la ressource");
                }
                $call_parameters = array($parameters);
                break;
            case 'PUT':
                $call = array($this->getController(), 'update');
                $parameters = $this->getRequestParameters();
                if (empty($parameters)) {
                    throw new RestServerForbiddenException("Accès refusé : Aucune données transmises pour modifier la ressource");
                }
                if ($this->getResourceId() == NULL) {
                    throw new RestServerForbiddenException("Accès refusé : Aucun Id spécifié pour modifier la ressource");
                }
                $call_parameters = array($this->getResourceId(), $parameters);
                break;
            case 'DELETE':
                $call = array($this->getController(), 'delete');
                if ($this->getResourceId() == NULL) {
                    throw new RestServerForbiddenException("Accès refusé : Aucun Id spécifié pour supprimer la ressource");
                }
                $call_parameters = array($this->getResourceId());
                break;
            default:
                throw new \HttpMalformedHeadersException("La méthode HTTP utilisée n'est pas compatible.");
        }

        $this->appendResponse('debug', array(
            'controller' => $call[0],
            'action' => $call[1],
            'resourceId' => $this->getResourceId(),
            'parameters' => $this->getRequestParameters(),
            'callable' => is_callable(array($call[0], $call[1]))
        ));

        if (is_callable($call)) {
            return call_user_func_array($call, $call_parameters);
        } else {
            throw new RestServerForbiddenException("Accès refusé");
        }
    }

    /**
     * Translate the first uri part as a controller name who handles a specific resource
     * @return null|string
     */
    private function getController()
    {
        if (!empty($this->uriparts[0])) {
            $controller = '\Aphax\controllers\\'.ucfirst($this->uriparts[0]);
            return new $controller($this);
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

    public function getResponse()
    {
        header('Content-type: text/json; charset=UTF-8');
        return json_encode($this->response);
    }

    public function getResponseException(\Exception $e)
    {
        $this->appendResponse('error', $e->getMessage());
        return $this->getResponse();
    }

    public function getResponseForbidden(\Exception $e)
    {
        header('HTTP/1.0 403 Forbidden');
        $this->appendResponse('error', $e->getMessage());
        return $this->getResponse();
    }

    /**
     * Get the ID resource provided in the second uri part
     * @return null|string
     */
    private function getResourceId()
    {
        // Resource Id is specified
        if (isset($this->uriparts[1])) {
            return $this->uriparts[1];
        }
        return NULL;
    }

    public function sendEmptyResponse()
    {
        echo json_encode(array());
    }
}