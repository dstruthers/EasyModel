<?php
/*
 EasyModel, by Darren M. Struthers <dstruthers@gmail.com>
 This is free software. See LICENSE for more info.

 See README for basic usage.
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