<?php
/*
 EasyModel, by Darren M. Struthers <dstruthers@gmail.com>
 This is free software. See LICENSE for more info.

 Basic usage:

 class People extends EasyModelTable {
   public function fullName () {
     return $this->first . ' ' . $this->last;
   }
 }
 People::describe('people',
                  array('id' => new PrimaryKeyField(),
		        'first' => new VarcharField(32),
			'last' => new VarcharField(32),
			'email' => new VarcharField(64),
			'created' => new CreateTimestampField(),
			'updated' => new UpdateTimestampField()
			));

 // 1. Loading a single object
 $p = People::load(array('id' => 1));
 $echo $p->fullName();

 // 2. Loading many objects
 $people = People::loadMany(array('last' => 'Smith'));

 // 3. Creating a new object
 $p = new People();
 $p->first = 'John';
 $p->last = 'Smith';
 $p->email = 'jsmith@example.com';
 $p->save();

 // 4. Deleting an object
 $p->delete();

*/

class EasyModel {
  public static function init ($connectString, $user = '', $password = '') {
    self::$db = new PDO($connectString, $user, $password);
  }
  public static function getDB () {
    return self::$db;
  }
  private static $db;
}

class EasyModelException extends RuntimeException {
}

require_once 'dbfield.php';
require_once 'table.php';

?>