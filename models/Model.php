<?php
/**
 * Created by PhpStorm.
 * User: Aphax
 * Date: 25/04/2015
 * Time: 19:47
 */

namespace Aphax\models;

use Aphax\exceptions\RestServerForbiddenException;
use Aphax\exceptions\RestServerNotFoundException;

abstract class Model
{
    /**
     * @var array
     */
    private $fields = array();

    /**
     * @var \PDO
     */
    private static $db;

    /**
     * @var string
     */
    private $primaryKey;

    /**
     * @var string
     */
    private $tableName = '';

    /**
     * @var array
     */
    protected $relational = array();

    function __construct()
    {
        if (empty(self::$db)) {
            try {
                self::$db = new \PDO('mysql:dbname=deezer;host=localhost', 'aphax', '');
                self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\PDOException $e) {
                die('Database connection failed.');
            }
        }
    }

    /**
     * @param $name
     * @param $type
     */
    public function addField($name, $type)
    {
        $this->fields[$name] = array(
            'type'  => $type,
            'value' => ''
        );
    }

    /**
     * @param array $data
     */
    public function bind(array $data)
    {
        foreach ($this->getFields() as $name => $params) {
            if (isset($data[$name])) {
                $this->setFieldValue($name, $data[$name]);
            }
        }
    }

    /**
     * @return bool
     */
    public function create()
    {
        global $server;
        $request = sprintf('INSERT INTO %s (%s) VALUES (%s)',
            $this->getTableName(),
            $this->getFieldsInsertDeclaration(),
            $this->getFieldsInsertValues()
        );
        $server->appendResponse('request', $request);
        $stmt = self::$db->prepare($request);
        $success = $stmt->execute();
        $server->appendResponse('success', $success);
        if ($this->hasPrimaryKey()) {
            $this->setPrimaryKeyValue(self::$db->lastInsertId());
        }

        return $success;
    }

    /**
     * @param array $data
     * @param Model $parentModel
     * @return int
     */
    public function createRelational(array $data, Model $parentModel)
    {
        global $server;
        $data[$parentModel->getPrimaryKey()] = $parentModel->getPrimaryKeyValue();
        $request = sprintf('INSERT INTO %s (%s) VALUES (%s)',
            $this->getRelationalTable($parentModel),
            $this->getFieldsInsertDeclaration($data),
            $this->getFieldsInsertValues($data)
        );
        $server->appendResponse('createRelational.request', $request);

        return self::$db->exec($request);
    }

    public function delete($id)
    {
        if (!$this->hasPrimaryKey()) {
            throw new RestServerForbiddenException('Cette ressource ne peut pas être supprimée directement');
        }

        return self::$db->exec('DELETE FROM `' . $this->getTableName() . '` WHERE `' . $this->getPrimaryKey() . '`=' . ((int)$id));
    }

    /**
     * @param Model $relatedModel
     * @return int
     */
    public function deleteRelational(Model $relatedModel)
    {
        switch ($relatedModel->getRelationalType($this)) {
            case 'n:n':
                return self::$db->exec('DELETE FROM `' . $relatedModel->getRelationalTable($this) . '`'.
                    'WHERE `' . $this->getPrimaryKey() . '`=' . $this->getPrimaryKeyValue() .
                    'AND `' . $relatedModel->getPrimaryKey() . '`=' . $relatedModel->getPrimaryKeyValue()
                );
        }
    }

    /**
     * @return mixed
     */
    public function getFields()
    {
        $fields = $this->fields;
        if (isset($fields[$this->primaryKey])) {
            unset($fields[$this->primaryKey]);
        }
        return $fields;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getFieldValue($name)
    {
        return $this->fields[$name]['value'];
    }

    /**
     * @param array $data
     * @return string
     */
    private function getFieldsInsertDeclaration($data = array())
    {
        $buffer = array();
        $fields = empty($data) ? $this->getFields() : $data;

        foreach ($fields as $name => $params) {
            $buffer[] = $name;
        }

        return '`' . implode('`,`', $buffer) . '`';
    }

    /**
     * @param array $data
     * @return string
     */
    private function getFieldsInsertValues($data = array())
    {
        $buffer = array();
        $fields = empty($data) ? $this->getFields() : $data;
        foreach ($fields as $mixed) {
            // From field model declaration
            if (is_array($mixed)) {
                $buffer[] = $mixed['type'] == 'string' ? self::$db->quote($mixed['value']) : $mixed['value'];
                // On the fly non-numeric field setting
            } else if (is_string($mixed) && !is_numeric($mixed)) {
                $buffer[] = self::$db->quote($mixed);
                // On the fly numeric field setting
            } else {
                $buffer[] = $mixed;
            }
        }

        return implode(',', $buffer);
    }

    /**
     * @return string
     */
    function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @return bool
     */
    public function hasPrimaryKey()
    {
        return !empty($this->primaryKey);
    }

    /**
     * @param string $field
     * @param mixed $value
     */
    public function setFieldValue($field, $value)
    {
        $this->fields[$field]['value'] = $value;
    }

    /**
     * @param array $fields
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return array
     */
    public function getFieldsValues()
    {
        $values = array();
        foreach ($this->fields as $name => $params) {
            $values[$name] = $params['value'];
        }

        return $values;
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @return mixed
     */
    public function getPrimaryKeyValue()
    {
        return $this->getFieldValue($this->getPrimaryKey());
    }

    /**
     * @param $id
     * @throws RestServerForbiddenException
     * @throws RestServerNotFoundException
     */
    public function read($id)
    {
        global $server;
        if (!$this->hasPrimaryKey()) {
            throw new RestServerForbiddenException('Cette ressource n\'est pas accessible en lecture');
        }
        $this->setFieldValue($this->getPrimaryKey(), $id);
        $row = self::$db->query('SELECT * FROM `' . $this->getTableName() . '` WHERE `' . $this->getPrimaryKey() . '`=' . $id)->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            throw new RestServerNotFoundException();
        }
        foreach ($row as $field => $value) {
            $this->setFieldValue($field, $value);
        }
    }

    /**
     * @param $field
     */
    protected function setPrimaryKey($field)
    {
        $this->primaryKey = $field;
    }

    /**
     * @param $lastInsertId
     */
    private function setPrimaryKeyValue($lastInsertId)
    {
        $this->fields[$this->getPrimaryKey()]['value'] = $lastInsertId;
    }

    /**
     * @return bool
     */
    public function update()
    {
        $buffer = array();
        $fields = $this->getFields();
        foreach ($fields as $name => $params) {
            $buffer[] = '`' . $name . '`=' . ($params['type'] == 'string' ? self::$db->quote($params['value']) : $params['value']);
        }

        global $server;
        $request = sprintf('UPDATE `%s` SET %s WHERE `%s`=%d',
            $this->getTableName(),
            implode(',', $buffer),
            $this->getPrimaryKey(),
            $this->getPrimaryKeyValue()
        );
        $stmt = self::$db->prepare($request);
        $success = $stmt->execute();
        $server->appendResponse('request', $request);
        $server->appendResponse('success', $success);

        return $success;
    }

    /**
     * @param $id
     * @param Model $child
     * @return array
     * @throws RestServerForbiddenException
     */
    public function getOneToManyChilds($id, Model $child)
    {
        if (!$this->hasPrimaryKey()) {
            throw new RestServerForbiddenException('Cette ressource n\'est pas accessible en lecture');
        }

        return self::$db->query('SELECT * FROM `' . $child->getTableName() . '` WHERE `' . $this->getPrimaryKey() . '`=' . $id)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @param Model $child
     * @return array
     * @throws RestServerForbiddenException
     */
    public function getManyToManyChilds($id, Model $child)
    {
    }

    public function getChilds(Model $childModel)
    {
        if (!$this->hasPrimaryKey()) {
            throw new RestServerForbiddenException('Cette ressource n\'est pas accessible en lecture');
        }
        $relationalType = $this->getRelationalType($childModel);
        if ($relationalType !== NULL) {
            switch ($relationalType) {
                case 'n:n':
                    return self::$db->query('SELECT * ' .
                        'FROM `' . $this->getTableName() . '_' . $childModel->getTableName() . '`' .
                        'JOIN `' . $childModel->getTableName() . '` USING(' . $childModel->getPrimaryKey() . ')' .
                        'WHERE `' . $this->getPrimaryKey() . '`=' . $this->getPrimaryKeyValue()
                    )->fetchAll(\PDO::FETCH_ASSOC);
                    break;
                case '1:n':
                    break;
                case '1:1':
                    break;
            }
        }
    }

    /**
     * @param Model $childModel
     * @return Model
     */
    public function getRelationalModel(Model $childModel)
    {
        switch ($this->getRelationalType($childModel)) {
            case 'n:n':
                return $this->getRelationalModelName($childModel);
            case '1:n':
                $childModel->setFieldValue($this->getPrimaryKey(), $this->getPrimaryKeyValue());

                return $childModel;
            case '1:1':
                break;
        }
    }

    /**
     * @param $childModel
     * @return string
     */
    private function getRelationalModelName($childModel)
    {
        $explode = explode('_', $this->getRelationalTable($childModel));
        $array_map = array_map('ucfirst', $explode);

        return implode('', $array_map);
    }

    protected function setManyToMany($params)
    {
        $this->relational[$params['target']] = array('type' => 'n:n', 'table' => $params['table']);
    }

    /**
     * @param Model $model
     * @return string
     */
    public function getRelationalTable(Model $model)
    {
        if (isset($this->relational[$model->getTableName()]['table'])) {
            return $this->relational[$model->getTableName()]['table'];
        }

        return $this->getTableName();
    }

    /**
     * @param Model $model
     * @return string
     */
    private function getRelationalType(Model $model)
    {
        if (isset($this->relational[$model->getTableName()]['type'])) {
            return $this->relational[$model->getTableName()]['type'];
        }

        return NULL;
    }
}