<?php

// Make sure we are called from index.php
if (!defined('SECURITY'))
  die('Hacking attempt');

/**
 * We use a wrapper class around BitcoinClient to add
 * some basic caching functionality and some debugging
 **/
class BitcoinWrapper extends BitcoinClient {
  public function __construct($type, $username, $password, $host, $debug, $memcache) {
    $this->type = $type;
    $this->username = $username;
    $this->password = $password;
    $this->host = $host;
    // $this->debug is already used
    $this->oDebug = $debug;
    $this->memcache = $memcache;
    return parent::__construct($this->type, $this->username, $this->password, $this->host);
  }
  /**
   * Wrap variouns methods to add caching
   **/
  // Caching this, used for each can_connect call
  public function getinfo() {
    $this->oDebug->append("STA " . __METHOD__, 4);
    if ($data = $this->memcache->get(__FUNCTION__)) return $data;
    return $this->memcache->setCache(__FUNCTION__, parent::getinfo(), 30);
  }
  public function getblockcount() {
    $this->oDebug->append("STA " . __METHOD__, 4);
    if ($data = $this->memcache->get(__FUNCTION__)) return $data;
    return $this->memcache->setCache(__FUNCTION__, parent::getblockcount(), 30);
  }
  public function getdifficulty() {
    $this->oDebug->append("STA " . __METHOD__, 4);
    if ($data = $this->memcache->get(__FUNCTION__)) return $data;
    $data = parent::getdifficulty();
    // Check for PoS/PoW coins
    if (is_array($data) && array_key_exists('proof-of-work', $data))
      $data = $data['proof-of-work'];
    return $this->memcache->setCache(__FUNCTION__, $data, 30);
  }
  public function getestimatedtime($iCurrentPoolHashrate) {
    $this->oDebug->append("STA " . __METHOD__, 4);
    if ($iCurrentPoolHashrate == 0) return 0;
    if ($data = $this->memcache->get(__FUNCTION__)) return $data;
    $dDifficulty = $this->getdifficulty();
    return $this->memcache->setCache(__FUNCTION__, $dDifficulty * pow(2,32) / $iCurrentPoolHashrate, 30);
  }
  public function getnetworkhashps() {
    $this->oDebug->append("STA " . __METHOD__, 4);
    if ($data = $this->memcache->get(__FUNCTION__)) return $data;
    try {
      $dNetworkHashrate = $this->getmininginfo();
      if (is_array($dNetworkHashrate)) {
        if (array_key_exists('networkhashps', $dNetworkHashrate)) {
          $dNetworkHashrate = $dNetworkHashrate['networkhashps'];
        } else if (array_key_exists('hashespersec', $dNetworkHashrate)) {
          $dNetworkHashrate = $dNetworkHashrate['hashespersec'];
        } else if (array_key_exists('netmhashps', $dNetworkHashrate)) {
          $dNetworkHashrate = $dNetworkHashrate['netmhashps'] * 1000 * 1000;
        } else {
          // Unsupported implementation
          $dNetworkHashrate = 0;
        }
      }
    } catch (Exception $e) {
      // getmininginfo does not exist, cache for an hour
      return $this->memcache->setCache(__FUNCTION__, 0, 3600);
    }
    return $this->memcache->setCache(__FUNCTION__, $dNetworkHashrate, 30);
  }
}

Class WalletRunner extends Base {
    public function runWallets() {
        $wallets = array();
        $stmt = $this->mysqli->prepare("SELECT * FROM coins");
        if ($this->checkStmt($stmt) && $stmt->execute() && $result = $stmt->get_result()) {
            foreach($result->fetch_all(MYSQLI_ASSOC) as $coin) {
                if(!$coin['wallet_type']) continue;
                $wallets[$coin['id']] = new BitcoinWrapper($coin['wallet_type'], $coin['wallet_username'], $coin['wallet_password'], $coin['wallet_host'], $this->debug, $this->memcache);
            }
        }

        return $wallets;
    }
}

// Load wrappers for all known coins
$walletRunner = new WalletRunner();
$walletRunner->setDebug($debug);
$walletRunner->setMysql($mysqli);
$walletRunner->setMemcache($memcache);
$walletRunner->setErrorCodes($aErrorCodes);

$wallets = $walletRunner->runWallets();
$wallets_arr = array_values($wallets);
$bitcoin = $wallets_arr[0];
