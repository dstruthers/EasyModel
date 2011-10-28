<?php
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

  public static function create ($values) {
    $o = new static();
    foreach ($values as $key => $value) {
      $o->$key = $value;
    }
    return $o;
  }

  public static function load ($query) {
    self::connectDB();
    $loadQuery = 'SELECT * FROM ' . self::$tableName;
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
    $loadQuery = 'SELECT * FROM ' . self::$tableName;
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

  public static function loadAll () {
    return self::loadMany(array());
  }

  public static function count () {
    self::connectDB();
    $query = 'SELECT COUNT(*) AS count FROM ' . self::$tableName;
    $sth = self::$db->query($query);
    $sth->execute();
    $result = $sth->fetch(PDO::FETCH_ASSOC);
    return (int)$result['count'];
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
	  if (isset($this->values[$key]) || $field instanceof CreateTimestampField) {
	    $insertFields[] = $key;
	  }
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

  public function delete () {
    if ($this->key()) {
      $sth = self::$db->prepare('DELETE FROM ' . self::$tableName . ' WHERE '
                                . self::$primaryKeyField . ' = ?');
      $sth->execute(array($this->key()));
    }
    else {
      throw new EasyModelException("Cannot delete object with no primary key");
    }
  }

  public function key () {
    if (self::$primaryKeyField) {
      if (isset($this->values[self::$primaryKeyField])) {
        return $this->values[self::$primaryKeyField];
      }
    }
    return NULL;
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
?>