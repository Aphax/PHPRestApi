<?php
/**
 * Created by PhpStorm.
 * User: Aphax
 * Date: 27/04/2015
 * Time: 00:34
 */

namespace Aphax\models;


class UserSong extends Model {
    function __construct()
    {
        parent::__construct();
        $this->addField('user_id', 'int');
        $this->addField('song_id', 'int');
        $this->setTableName('user_song');
    }

}