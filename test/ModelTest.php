<?php
/**
 * Created by PhpStorm.
 * User: Aphax
 * Date: 02/05/2015
 * Time: 16:55
 */

namespace test;

use Aphax\models\Song;
use Aphax\models\User;

class ModelTest extends \PHPUnit_Framework_TestCase {

    public function testGetRelationalModelName()
    {
        $user = new User();
        $this->assertEquals('UserSong', $user->getRelationalModel(new Song()), 'message');
    }
}
