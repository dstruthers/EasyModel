<?php
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
?>