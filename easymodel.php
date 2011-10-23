<?php

class EasyModel {
  public static function init ($connectString, $user = '', $password = '') {
    self::$db = new PDO($connectString, $user, $password);
  }

  private static $db;
}

abstract class DBField {
  public function __construct ($name, $length = NULL, $nullable = TRUE, $defaultValue = NULL, $extra = NULL) {
    $this->name = $name;
    $this->length = $length;
    $this->null = $nullable;
    $this->default = $defaultValue;
  }
  protected $name;
  protected $length;
  protected $null;
  protected $default;
}
class PrimaryKeyField extends DBField {
}
class TextField extends DBField {
}
class VarcharField extends DBField {
}
class IntField extends DBField {
}
class DateTimeField extends DBField {
}
class ForeignKeyField extends DBField {
  public function __construct ($table, $name) {
    $this->table = $table;
    $this->name = $name;
  }
  private $table;
}

class EasyModelException extends RuntimeException {
}

?>