<?php
/**
 * Created by PhpStorm.
 * User: Aphax
 * Date: 25/04/2015
 * Time: 19:47
 */

namespace Aphax\models;

class User extends Model
{
    function __construct()
    {
        parent::__construct();
        $this->addField('user_id', 'int');
        $this->addField('name', 'string');
        $this->addField('email', 'string');
        $this->setPrimaryKey('user_id');
        $this->setTableName('user');
        $this->setManyToMany(array(
            'target' => 'song',
            'table'  => 'user_song',
        ));
    }
}