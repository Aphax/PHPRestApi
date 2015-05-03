# PHPRestApi
Simple REST PHP API, MVC based with Mysql database.
Install files at the root of your future REST web server.

## 1. Create a new Resource Support
Create a new file in models directory and register model structure in the constructor like below :
```
namespace Aphax\models;

class Resource extends Model {
    function __construct()
    {
        parent::__construct();
        $this->addField('resource_id', 'int');
        $this->addField('resource_name', 'string');
        $this->setPrimaryKey('resource_id');
        $this->setTableName('resource');
    }
}
```

## 2. Resource methods access
- READ resource : GET yourserver.path/resource/id
- CREATE resource : POST yourserver.path/resource/
- UPDATE resource : PUT yourserver.path/resource/
- DELETE resource : DELETE yourserver.path/resource/id

## 3. Relational methods access
- READ resource childs : GET yourserver.path/resource/id/child
- ADD resource child : POST yourserver.path/resource/id/child
- REMOVE resource child : DELETE yourserver.path/resource/id/child/id

## 4. Controller support
To control incoming request datas, you can create a controller with the name of your resource :
controllers/Resource.php

The controller inherits from RestController the following methods :
```
namespace Aphax\controllers;

use Aphax\exceptions\RestServerForbiddenException;
use Aphax\RestServer;

/**
 * Class User
 * @package Aphax\controllers
 */
class User extends RestController {
    /**
     * @param RestServer $server
     */
    function __construct(RestServer $server)
    {
        parent::__construct($server);
    }

    /**
     * @param array $params
     * @throws RestServerForbiddenException
     */
    public function create(array $params)
    {
        parent::create($params);
    }

    /**
     * @param array $params
     * @param $rootResourceId
     * @throws RestServerForbiddenException
     */
    public function createRelational($params = array(), $rootResourceId)
    {
        parent::createRelational($params, $rootResourceId);
    }

    /**
     * @param $id
     */
    public function read($id)
    {
        parent::read($id);
    }

    /**
     * @param $id
     */
    public function readRelational($id)
    {
        parent::readRelational($id);
    }

    /**
     * @param $id
     * @param array $data
     */
    public function update($id, $data = array())
    {
        parent::update($id, $data);
    }

    /**
     * @param $rootResourceId
     */
    public function delete($rootResourceId)
    {
        parent::delete($rootResourceId);
    }

    /**
     * @param $rootResourceId
     * @param $relationalResourceId
     */
    public function deleteRelational($rootResourceId, $relationalResourceId)
    {
        parent::deleteRelational($rootResourceId, $relationalResourceId);
    }
}
```
