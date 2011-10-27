<?php

class EasyModel {
  public static function init ($connectString, $user = '', $password = '') {
    self::$db = new PDO($connectString, $user, $password);
  }
  public static function getDB () {
    return self::$db;
  }
  private static $db;
}

abstract class DBField {
  public function __construct ($length = NULL, $nullable = TRUE, $defaultValue = NULL, $extra = NULL) {
    $this->length = $length;
    $this->null = $nullable;
    $this->default = $defaultValue;
  }
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
class TimestampField extends DBField {
}
class ForeignKeyField extends DBField {
  public function __construct ($table, $name) {
    $this->table = $table;
    $this->name = $name;
  }
  private $table;
  private $name;
}

abstract class EasyModelTable {
  public static function describe ($tableName, $fields) {
    self::$tableName = $tableName;
    self::$fields = array();
    foreach ($fields as $key => $value) {
      self::$fields[$key] = $value;
      if ($value instanceof PrimaryKeyField) {
        if (!self::$primaryKeyField) {
          self::$primaryKeyField = $key;
        }
        else {
          throw new EasyModelException("Table cannot have more than one primary key field.");
        }
      }
    }
  }

  public function __get ($key) {
    if (isset(self::$fields[$key])) {
      return $this->values[$key];
    }
    else {
      throw new EasyModelException("Unknown field name: $key");
    }
  }

  public function __set ($key, $value) {
    if (isset(self::$fields[$key])) {
      if (self::$fields[$key] instanceof PrimaryKeyField) {
        throw new EasyModelException("Cannot modify primary key field of object");
      }
      else {
        $this->values[$key] = $value;
      }
    }
    else {
      throw new EasyModelException("Unknown field name: $key");
    }
  }

  public static function load ($query) {
    self::connectDB();
    $loadQuery = "SELECT * FROM " . self::$tableName . " WHERE ";
    $args = array();
    $conditions = array();
    foreach ($query as $key => $value) {
      $conditions[] = "$key = ?";
      $args[] = $value;
    }
    $loadQuery .= implode(' AND ', $conditions);
    $sth = self::$db->prepare($loadQuery);
    $sth->execute($args);
    $result = $sth->fetch(PDO::FETCH_ASSOC);
    $o = new static();
    foreach ($result as $key => $value) {
      $o->values[$key] = $value;
    }
    return $o;
  }

  public static function loadMany ($query) {
    self::connectDB();
    $loadQuery = "SELECT * FROM " . self::$tableName;;
    $args = array();
    $conditions = array();
    foreach ($query as $key => $value) {
      $conditions[] = "$key = ?";
      $args[] = $value;
    }
    if (count($conditions)) {
      $loadQuery .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sth = self::$db->prepare($loadQuery);
    $sth->execute($args);
    $queryResults = $sth->fetchAll(PDO::FETCH_ASSOC);
    $results = array();
    foreach ($queryResults as $result) {
      $o = new static();
      foreach ($result as $key => $value) {
        $o->values[$key] = $value;
      }
      $results[] = $o;
    }
    return $results;
  }

  public static function loadAll () {
    return self::loadMany();
  }

  private static function connectDB () {
    self::$db = EasyModel::getDB();
  }

  private static $tableName;
  private static $fields;
  private static $primaryKeyField;
  private static $db;
  private $values;
}

class EasyModelException extends RuntimeException {
}

?>