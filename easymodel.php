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

  public function fromSQL ($value) {
    return $value;
  }
  public function toSQL ($value) {
    return $value;
  }
  protected $length;
  protected $null;
  protected $default;
}
class PrimaryKeyField extends DBField {
}
class BooleanField extends DBField {
  public function fromSQL ($value) {
    return $value !== 0;
  }
  public function toSQL ($value) {
    return $value ? 1 : 0;
  }
}
class TextField extends DBField {
}
class VarcharField extends DBField {
}
class IntField extends DBField {
}
class TimestampField extends DBField {
  public function fromSQL ($value) {
    return $value != NULL ? strtotime($value) : NULL;
  }
  public function toSQL ($value) {
    return $value ? date('Y-m-d H:i:s', $value) : NULL;
  }
}
class CreateTimestampField extends TimestampField {
}
class UpdateTimestampField extends TimestampField {
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
    $loadQuery = "SELECT * FROM " . self::$tableName;
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
    $result = $sth->fetch(PDO::FETCH_ASSOC);
    $o = new static();
    foreach ($result as $key => $value) {
      $o->values[$key] = self::$fields[$key]->fromSQL($value);
    }
    return $o;
  }

  public static function loadMany ($query) {
    self::connectDB();
    $loadQuery = "SELECT * FROM " . self::$tableName;
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
        $o->values[$key] = self::$fields[$key]->fromSQL($value);
      }
      $results[] = $o;
    }
    return $results;
  }

  public function key () {
    if (self::$primaryKeyField) {
      if (isset($this->values[self::$primaryKeyField])) {
        return $this->values[self::$primaryKeyField];
      }
    }
    return NULL;
  }

  public function save () {
    self::connectDB();
    if ($this->key()) {
      $query = 'UPDATE ' . self::$tableName . ' SET ';
      $updates = array();
      $args = array();
      foreach (self::$fields as $key => $field) {
        if (!($field instanceof PrimaryKeyField)) {
          $updates[] = "$key = ?";
          if ($field instanceof UpdateTimestampField) {
            $this->values[$key] = time();
            $args[] = $field->toSQL($this->values[$key]);
          }
          else {
            $args[] = $field->toSQL($this->values[$key]);
          }
        }
      }
      $query .= implode(', ', $updates);
      $query .= ' WHERE ' . self::$primaryKeyField . ' = ?';
      $args[] = $this->key();

      $sth = self::$db->prepare($query);
      $sth->execute($args);
    }
    else {
      $query = 'INSERT INTO ' . self::$tableName . ' ';
      $insertFields = array();
      foreach (self::$fields as $key => $field) {
        if (!($field instanceof PrimaryKeyField)) {
          $insertFields[] = $key;
        }
      }
      $query .= '(' . implode(', ', $insertFields) . ') VALUES ('
        . implode(', ', array_fill(0, count($insertFields), '?')) . ')';

      foreach (self::$fields as $key => $value) {
        if ($value instanceof CreateTimestampField) {
          $this->values[$key] = time();
        }
      }
      $args = array();
      foreach ($insertFields as $field) {
        $args[] = self::$fields[$field]->toSQL($this->values[$field]);
      }
      $sth = self::$db->prepare($query);
      $sth->execute($args);
      if (self::$primaryKeyField) {
        $this->values[self::$primaryKeyField] = self::$db->lastInsertId();
      }
    }
  }

  public static function loadAll () {
    return self::loadMany(array());
  }

  private static function connectDB () {
    if (!self::$db) {
      self::$db = EasyModel::getDB();
    }
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