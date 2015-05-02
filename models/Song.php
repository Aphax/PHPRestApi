<?php
/**
 * Created by PhpStorm.
 * User: Aphax
 * Date: 27/04/2015
 * Time: 00:06
 */

namespace Aphax\models;


class Song extends Model {
    function __construct()
    {
        parent::__construct();
        $this->addField('song_id', 'int');
        $this->addField('title', 'string');
        $this->addField('duration', 'int');
        $this->setPrimaryKey('song_id');
        $this->setTableName('song');
        $this->setManyToMany(array(
            'target' => 'user',
            'table'  => 'user_song',
        ));
    }
}