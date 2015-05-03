# PHPRestApi
Simple REST PHP API, MVC based with Mysql database.
Install files at the root of your future REST web server.

## 1. Create a new Resource Support :
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

## 2. Read / Edit Resource data :
GET yourserver.path/resource/id
POST yourserver.path/resource/
PUT yourserver.path/resource/
DELETE yourserver.path/resource/id
