<?php

// Make sure we are called from index.php
if (!defined('SECURITY')) die('Hacking attempt');

class Coin Extends Base {
  protected $table = 'coins';

  public function getCoins() {
    $stmt = $this->mysqli->prepare("SELECT * FROM $this->table ORDER BY id");
    if ($this->checkStmt($stmt) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_all(MYSQLI_ASSOC);
    return $this->sqlError('E0050');
  }

    public function getCoin($coinID) {
        $stmt = $this->mysqli->prepare("SELECT * FROM $this->table WHERE id = ?");
        if ($this->checkStmt($stmt) && $stmt->bind_param("s", $coinID) && $stmt->execute() && $result = $stmt->get_result())
            return $result->fetch_assoc();
        return $this->sqlError('E0050');
    }

    public function getDefaultCoin() {
        $stmt = $this->mysqli->prepare("SELECT * FROM $this->table LIMIT 0,1");
        if ($this->checkStmt($stmt) && $stmt->execute() && $result = $stmt->get_result())
            return $result->fetch_assoc();
        return $this->sqlError('E0050');
    }
}

$oCoin = new Coin();
$oCoin->setDebug($debug);
$oCoin->setMysql($mysqli);
$oCoin->setErrorCodes($aErrorCodes);

?>
