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

abstract class Model {
    /**
     * @var array
     */
    private $fields = array();

    /**
     * @var PDO
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
            'type' => $type,
            'value' => ''
        );
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

    public function delete($id)
    {
        if (!$this->hasPrimaryKey()) {
            throw new RestServerForbiddenException('Cette ressource ne peut pas être supprimée directement');
        }
        return self::$db->exec('DELETE FROM `' . $this->getTableName() . '` WHERE `' . $this->getPrimaryKey() . '`=' . ((int)$id));
    }

    /**
     * @return mixed
     */
    public function getFields()
    {
        return $this->fields;
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
     * @return string
     */
    private function getFieldsInsertDeclaration()
    {
        $buffer = array();
        $fields = $this->fields;
        unset($fields[$this->primaryKey]);
        foreach ($fields as $name => $params) {
            $buffer[] = $name;
        }
        return implode(',', $buffer);
    }

    /**
     * @return string
     */
    private function getFieldsInsertValues()
    {
        $buffer = array();
        $fields = $this->fields;
        unset($fields[$this->primaryKey]);
        foreach ($fields as $params) {
            $buffer[] = $params['type'] == 'string' ? self::$db->quote($params['value']) : $params['value'];
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
        $fields = $this->fields;
        unset($fields[$this->primaryKey]);
        foreach ($fields as $name => $params) {
            $buffer[] = '`'.$name.'`='. ($params['type'] == 'string' ? self::$db->quote($params['value']) : $params['value']);
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
        if (!$this->hasPrimaryKey()) {
            throw new RestServerForbiddenException('Cette ressource n\'est pas accessible en lecture');
        }
        return self::$db->query('SELECT * FROM `' . $this->getTableName() . '_'.$child->getTableName().'` JOIN `'.$child->getTableName().'` USING('.$child->getPrimaryKey().') WHERE `' . $this->getPrimaryKey() . '`=' . $id)->fetchAll(\PDO::FETCH_ASSOC);
    }
}