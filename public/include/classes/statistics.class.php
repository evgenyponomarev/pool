<?php

// Make sure we are called from index.php
if (!defined('SECURITY'))
  die('Hacking attempt');


//compare top users
function compare($a, $b) {
    if ($a['hashrate'] == $b['hashrate']) {
        return 0;
    }
    return ($a['hashrate'] > $b['hashrate']) ? -1 : 1;
}


/*
 * We give access to plenty of statistics through this class
 * Statistics should be non-intrusive and not change any
 * rows in our database to ensure data integrity for the backend
 **/
class Statistics extends Base {
  protected $table = 'statistics_shares';
  private $getcache = true;

  // Disable fetching values from cache
  public function setGetCache($set=false) {
    $this->getcache = $set;
  }
  public function getGetCache() {
    return $this->getcache;
  }

  /**
   * Get our first block found
   *
   **/
  public function getFirstBlockFound() {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($data = $this->memcache->get(__FUNCTION__)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT IFNULL(MIN(time), 0) AS time FROM " . $this->block->getTableName());
    if ($this->checkStmt($stmt) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_object()->time;
    return false;
  }

  /**
   * Fetch last found blocks by time
   **/
  function getLastBlocksbyTime() {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($data = $this->memcache->get(__FUNCTION__)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT
        COUNT(id) AS Total,
        IFNULL(SUM(IF(confirmations > 0, 1, 0)), 0) AS TotalValid,
        IFNULL(SUM(IF(confirmations = -1, 1, 0)), 0) AS TotalOrphan,
        IFNULL(SUM(IF(confirmations > 0, difficulty, 0)), 0) AS TotalDifficulty,
        IFNULL(ROUND(SUM(IF(confirmations > -1, shares, 0))), 0) AS TotalShares,
        IFNULL(ROUND(SUM(IF(confirmations > -1, POW(2, ( 32 - " . $this->config['target_bits'] . " )) * difficulty / POW(2, (" . $this->config['difficulty'] . " -16)), 0))), 0) AS TotalEstimatedShares,
        IFNULL(SUM(IF(confirmations > -1, amount, 0)), 0) AS TotalAmount,
        IFNULL(SUM(IF(FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 3600 SECOND), 1, 0)), 0) AS 1HourTotal,
        IFNULL(SUM(IF(confirmations > 0 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 3600 SECOND), 1, 0)), 0) AS 1HourValid,
        IFNULL(SUM(IF(confirmations = -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 3600 SECOND), 1, 0)), 0) AS 1HourOrphan,
        IFNULL(SUM(IF(confirmations > 0 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 3600 SECOND), difficulty, 0)), 0) AS 1HourDifficulty,
        IFNULL(ROUND(SUM(IF(confirmations > -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 3600 SECOND), shares, 0))), 0) AS 1HourShares,
        IFNULL(ROUND(SUM(IF(confirmations > -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 3600 SECOND), POW(2, ( 32 - " . $this->config['target_bits'] . " )) * difficulty / POW(2, (" . $this->config['difficulty'] . " -16)), 0))), 0) AS 1HourEstimatedShares,
        IFNULL(SUM(IF(confirmations > -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 3600 SECOND), amount, 0)), 0) AS 1HourAmount,
        IFNULL(SUM(IF(FROM_UNIXTIME(time)    >= DATE_SUB(now(), INTERVAL 86400 SECOND), 1, 0)), 0) AS 24HourTotal,
        IFNULL(SUM(IF(confirmations > 0 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 86400 SECOND), 1, 0)), 0) AS 24HourValid,
        IFNULL(SUM(IF(confirmations = -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 86400 SECOND), 1, 0)), 0) AS 24HourOrphan,
        IFNULL(SUM(IF(confirmations > 0 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 86400 SECOND), difficulty, 0)), 0) AS 24HourDifficulty,
        IFNULL(ROUND(SUM(IF(confirmations > -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 86400 SECOND), shares, 0))), 0) AS 24HourShares,
        IFNULL(ROUND(SUM(IF(confirmations > -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 86400 SECOND), POW(2, ( 32 - " . $this->config['target_bits'] . " )) * difficulty / POW(2, (" . $this->config['difficulty'] . " -16)), 0))), 0) AS 24HourEstimatedShares,
        IFNULL(SUM(IF(confirmations > -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 86400 SECOND), amount, 0)), 0) AS 24HourAmount,
        IFNULL(SUM(IF(FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 604800 SECOND), 1, 0)), 0) AS 7DaysTotal,
        IFNULL(SUM(IF(confirmations > 0 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 604800 SECOND), 1, 0)), 0) AS 7DaysValid,
        IFNULL(SUM(IF(confirmations = -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 604800 SECOND), 1, 0)), 0) AS 7DaysOrphan,
        IFNULL(SUM(IF(confirmations > 0 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 604800 SECOND), difficulty, 0)), 0) AS 7DaysDifficulty,
        IFNULL(ROUND(SUM(IF(confirmations > -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 604800 SECOND), shares, 0))), 0) AS 7DaysShares,
        IFNULL(ROUND(SUM(IF(confirmations > -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 604800 SECOND), POW(2, ( 32 - " . $this->config['target_bits'] . " )) * difficulty / POW(2, (" . $this->config['difficulty'] . " -16)), 0))), 0) AS 7DaysEstimatedShares,
        IFNULL(SUM(IF(confirmations > -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 604800 SECOND), amount, 0)), 0) AS 7DaysAmount,
        IFNULL(SUM(IF(FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 2419200 SECOND), 1, 0)), 0) AS 4WeeksTotal,
        IFNULL(SUM(IF(confirmations > 0 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 2419200 SECOND), 1, 0)), 0) AS 4WeeksValid,
        IFNULL(SUM(IF(confirmations = -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 2419200 SECOND), 1, 0)), 0) AS 4WeeksOrphan,
        IFNULL(SUM(IF(confirmations > 0 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 2419200 SECOND), difficulty, 0)), 0) AS 4WeeksDifficulty,
        IFNULL(ROUND(SUM(IF(confirmations > -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 2419200 SECOND), shares, 0))), 0) AS 4WeeksShares,
        IFNULL(ROUND(SUM(IF(confirmations > -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 2419200 SECOND), POW(2, ( 32 - " . $this->config['target_bits'] . " )) * difficulty / POW(2, (" . $this->config['difficulty'] . " -16)), 0))), 0) AS 4WeeksEstimatedShares,
        IFNULL(SUM(IF(confirmations > -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 2419200 SECOND), amount, 0)), 0) AS 4WeeksAmount,
        IFNULL(SUM(IF(FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 29030400 SECOND), 1, 0)), 0) AS 12MonthTotal,
        IFNULL(SUM(IF(confirmations > 0 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 29030400 SECOND), 1, 0)), 0) AS 12MonthValid,
        IFNULL(SUM(IF(confirmations = -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 29030400 SECOND), 1, 0)), 0) AS 12MonthOrphan,
        IFNULL(SUM(IF(confirmations > 0 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 29030400 SECOND), difficulty, 0)), 0) AS 12MonthDifficulty,
        IFNULL(ROUND(SUM(IF(confirmations > -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 29030400 SECOND), shares, 0))), 0) AS 12MonthShares,
        IFNULL(ROUND(SUM(IF(confirmations > -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 29030400 SECOND), POW(2, ( 32 - " . $this->config['target_bits'] . " )) * difficulty / POW(2, (" . $this->config['difficulty'] . " -16)), 0))), 0) AS 12MonthEstimatedShares,
        IFNULL(SUM(IF(confirmations > -1 AND FROM_UNIXTIME(time) >= DATE_SUB(now(), INTERVAL 29030400 SECOND), amount, 0)), 0) AS 12MonthAmount
      FROM " . $this->block->getTableName());
    if ($this->checkStmt($stmt) && $stmt->execute() && $result = $stmt->get_result())
    	return $this->memcache->setCache(__FUNCTION__, $result->fetch_assoc());
    return $this->sqlError();
  }

  /**
   * Get our last $limit blocks found
   * @param limit int Last limit blocks
   * @return array
   **/
  public function getBlocksFound($limit=10) {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($data = $this->memcache->get(__FUNCTION__ . $limit)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT
        b.*,
        a.username AS finder,
        a.is_anonymous AS is_anonymous,
        ROUND((difficulty * POW(2, 32 - " . $this->config['target_bits'] . ")) / POW(2, (" . $this->config['difficulty'] . " -16)), 0) AS estshares
      FROM " . $this->block->getTableName() . " AS b
      LEFT JOIN " . $this->user->getTableName() . " AS a
      ON b.account_id = a.id
      ORDER BY time DESC LIMIT ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param("i", $limit) && $stmt->execute() && $result = $stmt->get_result())
      return $this->memcache->setCache(__FUNCTION__ . $limit, $result->fetch_all(MYSQLI_ASSOC), 5);
    return $this->sqlError();
  }

  /**
   * Get our last $limit blocks found by height
   * @param limit int Last limit blocks
   * @return array
   **/
  public function getBlocksFoundHeight($iHeight=0, $limit=10) {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($data = $this->memcache->get(__FUNCTION__ . $iHeight . $limit)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT
        b.*,
        a.username AS finder,
        a.is_anonymous AS is_anonymous,
        ROUND((difficulty * POW(2, 32 - " . $this->config['target_bits'] . ")) / POW(2, (" . $this->config['difficulty'] . " -16)), 0) AS estshares
      FROM " . $this->block->getTableName() . " AS b
      LEFT JOIN " . $this->user->getTableName() . " AS a 
      ON b.account_id = a.id
      WHERE b.height <= ?
      ORDER BY time DESC LIMIT ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param("ii", $iHeight, $limit) && $stmt->execute() && $result = $stmt->get_result())
      return $this->memcache->setCache(__FUNCTION__ . $iHeight . $limit, $result->fetch_all(MYSQLI_ASSOC), 5);
    return $this->sqlError();
  }

  /**
   * Get SUM of blocks found and generated Coins for each Account
   * @param limit int Last limit blocks
   * @return array
   **/
  public function getBlocksSolvedbyAccount($limit=25) {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($data = $this->memcache->get(__FUNCTION__ . $limit)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT
        b.*,
        a.username AS finder,
        a.is_anonymous AS is_anonymous,
        COUNT(b.id) AS solvedblocks, 
        SUM(b.amount) AS generatedcoins
      FROM " . $this->block->getTableName() . " AS b
      LEFT JOIN " . $this->user->getTableName() . " AS a 
      ON b.account_id = a.id
      WHERE confirmations > 0
      GROUP BY finder
      ORDER BY solvedblocks DESC LIMIT ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param("i", $limit) && $stmt->execute() && $result = $stmt->get_result())
      return $this->memcache->setCache(__FUNCTION__ . $limit, $result->fetch_all(MYSQLI_ASSOC), 5);
    return $this->sqlError();
  }
  
  /**
   * Get SUM of blocks found and generated Coins for each worker
   * @param limit int Last limit blocks
   * @return array
   **/
  public function getBlocksSolvedbyWorker($account_id, $limit=25) {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($data = $this->memcache->get(__FUNCTION__ . $account_id . $limit)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT
      	worker_name AS finder,
        COUNT(id) AS solvedblocks, 
        SUM(amount) AS generatedcoins
      FROM " . $this->block->getTableName() . "
      WHERE account_id = ? AND worker_name != 'unknown'
      GROUP BY finder
      ORDER BY solvedblocks DESC LIMIT ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param("ii", $account_id, $limit) && $stmt->execute() && $result = $stmt->get_result())
      return $this->memcache->setCache(__FUNCTION__ . $account_id . $limit, $result->fetch_all(MYSQLI_ASSOC), 5);
    return $this->sqlError();
  }
  
  /**
   * Currently the only function writing to the database
   * Stored per block user statistics of valid and invalid shares
   * @param aStats array Array with user id, valid and invalid shares
   * @param iBlockId int Block ID as store in the Block table
   * @return bool
   **/
  public function updateShareStatistics($aStats, $iBlockId) {
    $this->debug->append("STA " . __METHOD__, 4);
    $stmt = $this->mysqli->prepare("INSERT INTO $this->table (account_id, valid, invalid, block_id) VALUES (?, ?, ?, ?)");
    if ($this->checkStmt($stmt) && $stmt->bind_param('iiii', $aStats['id'], $aStats['valid'], $aStats['invalid'], $iBlockId) && $stmt->execute()) return true;
    return $this->sqlError();
  }

    public function updateShareStatisticsByCoin($coinID, $aStats, $iBlockId) {
        $this->debug->append("STA " . __METHOD__, 4);
        $stmt = $this->mysqli->prepare("INSERT INTO $this->table (coin, account_id, valid, invalid, block_id) VALUES (?, ?, ?, ?, ?)");
        if ($this->checkStmt($stmt) && $stmt->bind_param('siiii', $coinID, $aStats['id'], $aStats['valid'], $aStats['invalid'], $iBlockId) && $stmt->execute()) return true;
        return $this->sqlError();
    }

  /**
   * update user statistics of valid and invalid pplns shares
   **/
  public function updatePPLNSShareStatistics($aStats, $iBlockId) {
    $this->debug->append("STA " . __METHOD__, 4);
    $stmt = $this->mysqli->prepare("
      UPDATE $this->table SET pplns_valid = ?, pplns_invalid = ? WHERE account_id = ? AND block_id = ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param('iiii', $aStats['valid'], $aStats['invalid'], $aStats['id'], $iBlockId) && $stmt->execute()) return true;
    return $this->sqlError();
  }

  /**
   * insert user statistics of valid and invalid pplns shares "rbpplns"
   **/
  public function insertPPLNSShareStatisticsByCoin($coinID, $aStats, $iBlockId) {
    $this->debug->append("STA " . __METHOD__, 4);
    $stmt = $this->mysqli->prepare("INSERT INTO $this->table (coin, account_id, valid, invalid, pplns_valid, pplns_invalid, block_id) VALUES (?, ?, 0, 0, ?, ?, ?)");
    if ($this->checkStmt($stmt) && $stmt->bind_param('siiii', $coinID, $aStats['id'], $aStats['valid'], $aStats['invalid'], $iBlockId) && $stmt->execute()) return true;
    return $this->sqlError();
  }

  /**
   * Fetch the share ID from stats for rbpplns
   **/
  function getIdShareStatistics($aStats, $iBlockId) {
    $stmt = $this->mysqli->prepare("
      SELECT id AS id FROM $this->table
      WHERE account_id = ? AND block_id = ?
      ");
    if ($this->checkStmt($stmt) && $stmt->bind_param('ii', $aStats['id'], $iBlockId) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_object()->id;
    return $this->sqlError();
  }

  /**
   * Get our current pool hashrate for the past 10 minutes across both
   * shares and shares_archive table
   * @param none
   * @return data object Return our hashrateas an object
   **/
  public function getCurrentHashrate($interval=600) {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($this->getGetCache() && $data = $this->memcache->get(__FUNCTION__)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT
      (
        (
          SELECT IFNULL(ROUND(SUM(IF(difficulty=0, POW(2, (" . $this->config['difficulty'] . " - 16)), difficulty)) * POW(2, " . $this->config['target_bits'] . ") / ? / 1000), 0) AS hashrate
          FROM " . $this->share->getTableName() . "
          WHERE time > DATE_SUB(now(), INTERVAL ? SECOND)
          AND our_result = 'Y'
        ) + (
          SELECT IFNULL(ROUND(SUM(IF(difficulty=0, POW(2, (" . $this->config['difficulty'] . " - 16)), difficulty)) * POW(2, " . $this->config['target_bits'] . ") / ? / 1000), 0) AS hashrate
          FROM " . $this->share->getArchiveTableName() . "
          WHERE time > DATE_SUB(now(), INTERVAL ? SECOND)
          AND our_result = 'Y'
        )
      ) AS hashrate
      FROM DUAL");
    if ($this->checkStmt($stmt) && $stmt->bind_param('iiii', $interval, $interval, $interval, $interval) && $stmt->execute() && $result = $stmt->get_result() ) {
        return $this->memcache->setCache(__FUNCTION__, $result->fetch_object()->hashrate + $this->getTopUsersHashrate());
    }
    return $this->sqlError();
  }

  /**
   * Same as getCurrentHashrate but for Shares
   * @param none
   * @return data object Our share rate in shares per second
   **/
  public function getCurrentShareRate($interval=600) {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($data = $this->memcache->get(__FUNCTION__)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT
      (
        (
          SELECT ROUND(COUNT(id) / ?, 2) AS sharerate
          FROM " . $this->share->getTableName() . "
          WHERE time > DATE_SUB(now(), INTERVAL ? SECOND)
          AND our_result = 'Y'
        ) + (
          SELECT ROUND(COUNT(id) / ?, 2) AS sharerate
          FROM " . $this->share->getArchiveTableName() . "
          WHERE time > DATE_SUB(now(), INTERVAL ? SECOND)
          AND our_result = 'Y'
        )
      ) AS sharerate
      FROM DUAL");
    if ($this->checkStmt($stmt) && $stmt->bind_param('iiii', $interval, $interval, $interval, $interval) && $stmt->execute() && $result = $stmt->get_result() ) return $this->memcache->setCache(__FUNCTION__, $result->fetch_object()->sharerate);
    return $this->sqlError();
  }

  /**
   * Get total shares for this round, since last block found
   * @param none
   * @return data array invalid and valid shares
   **/
  public function getRoundShares() {
    $this->debug->append("STA " . __METHOD__, 4);
    // Try the statistics cron cache, then function cache, then fallback to SQL
    if ($data = $this->memcache->get(STATISTICS_ALL_USER_SHARES)) {
      $this->debug->append("Found data in statistics cache", 2);
      $total = array('valid' => 0, 'invalid' => 0);
      foreach ($data['data'] as $aUser) {
        $total['valid'] += $aUser['valid'];
        $total['invalid'] += $aUser['invalid'];
      }
      return $total;
    }
    if ($data = $this->memcache->get(STATISTICS_ROUND_SHARES)) {
      $this->debug->append("Found data in local cache", 2);
      return $data;
    }
    $stmt = $this->mysqli->prepare("
      SELECT
        ROUND(IFNULL(SUM(IF(our_result='Y', IF(difficulty=0, POW(2, (" . $this->config['difficulty'] . " - 16)), difficulty), 0)), 0) / POW(2, (" . $this->config['difficulty'] . " - 16)), 0) AS valid,
        ROUND(IFNULL(SUM(IF(our_result='N', IF(difficulty=0, POW(2, (" . $this->config['difficulty'] . " - 16)), difficulty), 0)), 0) / POW(2, (" . $this->config['difficulty'] . " - 16)), 0) AS invalid
      FROM " . $this->share->getTableName() . "
      WHERE UNIX_TIMESTAMP(time) > IFNULL((SELECT MAX(time) FROM " . $this->block->getTableName() . "), 0)");
    if ( $this->checkStmt($stmt) && $stmt->execute() && $result = $stmt->get_result() )
      return $this->memcache->setCache(STATISTICS_ROUND_SHARES, $result->fetch_assoc());
    return $this->sqlError();
  }

  /**
   * Get amount of shares for a all users
   * Used in statistics cron to refresh memcache data
   * @param account_id int User ID
   * @return data array invalid and valid share counts
   **/
  public function getAllUserShares() {
    $this->debug->append("STA " . __METHOD__, 4);
    if (! $data = $this->memcache->get(STATISTICS_ALL_USER_SHARES)) {
      $data['share_id'] = 0;
      $data['data'] = array();
    }
    $stmt = $this->mysqli->prepare("
      SELECT
        ROUND(IFNULL(SUM(IF(our_result='Y', IF(s.difficulty=0, POW(2, (" . $this->config['difficulty'] . " - 16)), s.difficulty), 0)), 0) / POW(2, (" . $this->config['difficulty'] . " - 16)), 0) AS valid,
        ROUND(IFNULL(SUM(IF(our_result='N', IF(s.difficulty=0, POW(2, (" . $this->config['difficulty'] . " - 16)), s.difficulty), 0)), 0) / POW(2, (" . $this->config['difficulty'] . " - 16)), 0) AS invalid,
        u.id AS id,
        u.donate_percent AS donate_percent,
        u.is_anonymous AS is_anonymous,
        u.username AS username,
        s.coin AS coin
      FROM " . $this->share->getTableName() . " AS s,
           " . $this->user->getTableName() . " AS u
      WHERE u.username = SUBSTRING_INDEX( s.username, '.', 1 )
        AND UNIX_TIMESTAMP(s.time) > IFNULL(
          (
            SELECT MAX(b.time)
            FROM " . $this->block->getTableName() . " AS b
          ) ,0 )
        AND s.id > ?
        GROUP BY u.id");
    if ($stmt && $stmt->bind_param('i', $data['share_id']) && $stmt->execute() && $result = $stmt->get_result()) {
      $data_new = array();
      while ($row = $result->fetch_assoc()) {
        if (! array_key_exists($row['id'], $data['data'])) {
          $data['data'][$row['id']] = $row;
        } else {
          $data['data'][$row['id']]['valid'] += $row['valid'];
          $data['data'][$row['id']]['invalid'] += $row['invalid'];
          $data['data'][$row['id']]['donate_percent'] = $row['donate_percent'];
          $data['data'][$row['id']]['is_anonymous'] = $row['is_anonymous'];
          $data['data'][$row['id']]['coin'] = $row['coin'];
        }
      }
      $data['share_id'] = $this->share->getMaxShareId();

      return $this->memcache->setCache(STATISTICS_ALL_USER_SHARES, $data);
    }
    return $this->sqlError();
  }

  /**
   * Get amount of shares for a specific user
   * @param account_id int User ID
   * @return data array invalid and valid share counts
   **/
  public function getUserShares($account_id) {
    $this->debug->append("STA " . __METHOD__, 4);
    // Dual-caching, try statistics cron first, then fallback to local, then fallbock to SQL
    if ($data = $this->memcache->get(STATISTICS_ALL_USER_SHARES)) {
      if (array_key_exists($account_id, $data['data']))
        return $data['data'][$account_id];
      // We have no cached value, we return defaults
      return array('valid' => 0, 'invalid' => 0, 'donate_percent' => 0, 'is_anonymous' => 0);
    }
    if ($data = $this->memcache->get(__FUNCTION__ . $account_id)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT
        ROUND(IFNULL(SUM(IF(our_result='Y', IF(s.difficulty=0, POW(2, (" . $this->config['difficulty'] . " - 16)), s.difficulty), 0)), 0) / POW(2, (" . $this->config['difficulty'] . " - 16)), 0) AS valid,
        ROUND(IFNULL(SUM(IF(our_result='N', IF(s.difficulty=0, POW(2, (" . $this->config['difficulty'] . " - 16)), s.difficulty), 0)), 0) / POW(2, (" . $this->config['difficulty'] . " - 16)), 0) AS invalid
      FROM " . $this->share->getTableName() . " AS s,
           " . $this->user->getTableName() . " AS u
      WHERE
        u.username = SUBSTRING_INDEX( s.username, '.', 1 )
        AND UNIX_TIMESTAMP(s.time) >IFNULL((SELECT MAX(b.time) FROM " . $this->block->getTableName() . " AS b),0)
        AND u.id = ?");
    if ($stmt && $stmt->bind_param("i", $account_id) && $stmt->execute() && $result = $stmt->get_result())
      return $this->memcache->setCache(__FUNCTION__ . $account_id, $result->fetch_assoc());
    return $this->sqlError();
  }

  /**
   * Admin panel specific query
   * @return data array User settings and shares
   **/
  public function getAllUserStats($filter='%',$limit=1,$start=0) {
    $this->debug->append("STA " . __METHOD__, 4);
    $sql = "
      SELECT
        a.id AS id,
        a.is_admin as is_admin,
        a.is_locked as is_locked,
        a.no_fees as no_fees,
        a.username AS username,
        a.donate_percent AS donate_percent,
        a.email AS email
      FROM " . $this->user->getTableName() . " AS a";
    if (is_array($filter)) {
      $aFilter = array();
      foreach ($filter as $key => $value) {
        if (isset($value) && $value != "" ) {
          switch ($key) {
          case 'account':
            $aFilter[] = "a.username LIKE ?";
            $this->addParam('s', $value);
            break;
          case 'email':
              $aFilter[] = "a.email LIKE ?";
              $this->addParam('s', $value);
            break;
          case 'is_admin':
              $aFilter[] = "a.is_admin = ?";
              $this->addParam('i', $value);
            break;
          case 'is_locked':
              $aFilter[] = "a.is_locked = ?";
              $this->addParam('i', $value);
            break;
          case 'no_fees':
              $aFilter[] = "a.no_fees = ?";
              $this->addParam('i', $value);
            break;
          }
        }
      }
    }
    if (!empty($aFilter)) {
      $sql .= " WHERE ";
      $sql .= implode(' AND ', $aFilter);
    }
    $sql .= "
      ORDER BY username
      LIMIT ?,?";
    $this->addParam('i', $start);
    $this->addParam('i', $limit);
    $stmt = $this->mysqli->prepare($sql);
    if ($this->checkStmt($stmt) && call_user_func_array( array($stmt, 'bind_param'), $this->getParam()) && $stmt->execute() && $result = $stmt->get_result()) {
      // Add our cached shares to the users
      $aUsers = array();
      while ($row = $result->fetch_assoc()) {
        $row['shares'] = $this->getUserShares($row['id']);
        $aUsers[] = $row;
      }
      if (count($aUsers) > 0) {
        return $aUsers;
      }
    }
    return $this->sqlError();
  }

  /**
   * Fetch total user hashrate based on shares and archived shares
   * @param account_id integer User ID
   * @return data integer Current Hashrate in khash/s
   **/
  public function getUserHashrate($account_id, $interval=600) {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($this->getGetCache() && $data = $this->memcache->get(__FUNCTION__ . $account_id)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT
        IFNULL(IF(our_result='Y', ROUND(SUM(IF(difficulty=0, POW(2, (" . $this->config['difficulty'] . " - 16)), difficulty)) * POW(2, " . $this->config['target_bits'] . ") / ? / 1000), 0), 0) AS hashrate
      FROM (
        SELECT
          s.id, s.our_result, IF(s.difficulty = 0, POW(2, (" . $this->config['difficulty'] . " - 16)), s.difficulty) AS difficulty
        FROM
          shares AS s,
          accounts AS u
        WHERE u.username = SUBSTRING_INDEX( s.username, '.', 1 )
          AND s.time > DATE_SUB(now(), INTERVAL ? SECOND)
          AND s.our_result = 'Y'
          AND u.id = ?
      UNION
        SELECT
          s.share_id, s.our_result, IF(s.difficulty = 0, POW(2, (" . $this->config['difficulty'] . " - 16)), s.difficulty) AS difficulty
        FROM
          shares_archive AS s,
          accounts AS u
        WHERE u.username = SUBSTRING_INDEX( s.username, '.', 1 )
          AND s.time > DATE_SUB(now(), INTERVAL ? SECOND)
          AND s.our_result = 'Y'
          AND u.id = ?
        ) AS temp");
    if ($this->checkStmt($stmt) && $stmt->bind_param("iiiii", $interval, $interval, $account_id, $interval, $account_id) && $stmt->execute() && $result = $stmt->get_result() )
      return $this->memcache->setCache(__FUNCTION__ . $account_id, $result->fetch_object()->hashrate);
    return $this->sqlError();
  }

  public function getUserUnpaidPPSShares($account_id, $last_paid_pps_id) {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($this->getGetCache() && $data = $this->memcache->get(__FUNCTION__ . $account_id)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT
        ROUND(IFNULL(SUM(IF(s.difficulty=0, POW(2, (" . $this->config['difficulty'] . " - 16)), s.difficulty)), 0) / POW(2, (" . $this->config['difficulty'] . " - 16)), 0) AS total
      FROM " . $this->share->getTableName() . " AS s
      JOIN " . $this->user->getTableName() . " AS a
      ON a.username = SUBSTRING_INDEX( s.username, '.', 1 )
      AND a.id = ?
      AND s.id > ?
      WHERE our_result = 'Y'");
    if ($this->checkStmt($stmt) && $stmt->bind_param("ii", $account_id, $last_paid_pps_id) && $stmt->execute() && $result = $stmt->get_result() )
      return $this->memcache->setCache(__FUNCTION__ . $account_id, $result->fetch_object()->total);
    return $this->sqlError();
  }

  /**
   * Get average share difficulty across all workers for user
   * @param account_id int Account ID
   * @param interval int Data interval in seconds
   * @return double Share difficulty or 0
   **/
  public function getUserShareDifficulty($account_id, $interval=600) {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($this->getGetCache() && $data = $this->memcache->get(__FUNCTION__ . $account_id)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT
        IFNULL(AVG(IF(difficulty=0, pow(2, (" . $this->config['difficulty'] . " - 16)), difficulty)), 0) AS avgsharediff,
        COUNT(s.id) AS total
      FROM " . $this->share->getTableName() . " AS s JOIN " . $this->user->getTableName() . " AS a
      ON a.username = SUBSTRING_INDEX( s.username, '.', 1 )
      WHERE s.time > DATE_SUB(now(), INTERVAL ? SECOND)
      AND our_result = 'Y'
      AND a.id = ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param("ii", $interval, $account_id) && $stmt->execute() && $result = $stmt->get_result() )
      return $this->memcache->setCache(__FUNCTION__ . $account_id, $result->fetch_object()->avgsharediff);
    return $this->sqlError();
  }

  /**
   * Same as getUserHashrate for Sharerate
   * @param account_id integer User ID
   * @return data integer Current Sharerate in shares/s
   **/
  public function getUserSharerate($account_id, $interval=600) {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($this->getGetCache() && $data = $this->memcache->get(__FUNCTION__ . $account_id)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT
        IFNULL(COUNT(*) / ?, 0) AS sharerate
      FROM (
        SELECT
          s.id
        FROM
          shares AS s,
          accounts AS u
        WHERE u.username = SUBSTRING_INDEX( s.username, '.', 1 )
          AND s.time > DATE_SUB(now(), INTERVAL ? SECOND)
          AND s.our_result = 'Y'
          AND u.id = ?
      UNION
        SELECT
          s.share_id
        FROM
          shares_archive AS s,
          accounts AS u
        WHERE u.username = SUBSTRING_INDEX( s.username, '.', 1 )
          AND s.time > DATE_SUB(now(), INTERVAL ? SECOND)
          AND s.our_result = 'Y'
          AND u.id = ?
      ) AS temp");
    if ($this->checkStmt($stmt) && $stmt->bind_param("iiiii", $interval, $interval, $account_id, $interval, $account_id) && $stmt->execute() && $result = $stmt->get_result() )
      return $this->memcache->setCache(__FUNCTION__ . $account_id, $result->fetch_object()->sharerate);
    return $this->sqlError();
  }

  /**
   * Get hashrate for a specific worker
   * @param worker_id int Worker ID to fetch hashrate for
   * @return data int Current hashrate in khash/s
   **/
  public function getWorkerHashrate($worker_id) {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($data = $this->memcache->get(__FUNCTION__ . $worker_id)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT IFNULL(ROUND(SUM(IF(difficulty=0, POW(2, (" . $this->config['difficulty'] . " - 16)), difficulty)) * POW(2, " . $this->config['target_bits'] . ") / 600 / 1000), 0) AS hashrate
      FROM " . $this->share->getTableName() . " AS s,
           " . $this->user->getTableName() . " AS u
      WHERE u.username = SUBSTRING_INDEX( s.username, '.', 1 )
        AND our_result = 'Y'
        AND s.time > DATE_SUB(now(), INTERVAL 600 SECOND)
        AND u.id = ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param("i", $account_id) && $stmt->execute() && $result = $stmt->get_result() )
      return $this->memcache->setCache(__FUNCTION__ . $worker_id, $result->fetch_object()->hashrate);
    return $this->sqlError();
  }

    public function getTopUsers() {
        $users = array();
        $stmt = $this->mysqli->prepare("
        SELECT
          a.username AS account,
          a.donate_percent AS donate_percent,
          a.is_anonymous AS is_anonymous
        FROM " . $this->user->getTableName() . " AS a
        WHERE pass=''");
        if ($this->checkStmt($stmt) && $stmt->execute() && $result = $stmt->get_result()) {
            $users = $result->fetch_all(MYSQLI_ASSOC);
            foreach($users as $i => $user) {
                $users[$i]['hashrate'] = rand(500,4000);
            }
        }
        return $users;
    }

    public function getTopUsersHashrate() {
        $res = 0;
        foreach($this->getTopUsers() as $user) {
            $res += $user['hashrate'];
        }

        return $res;
    }


  /**
   * get our top contributors for either shares or hashrate
   * @param type string shares or hashes
   * @param limit int Limit result to $limit
   * @return data array Users with shares, account or hashrate, account
   **/
  public function getTopContributors($type='shares', $limit=15) {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($this->getGetCache() && $data = $this->memcache->get(__FUNCTION__ . $type . $limit)) return $data;
    switch ($type) {
    case 'shares':
      if ($data = $this->memcache->get(STATISTICS_ALL_USER_SHARES)) {
        // Use global cache to build data, if we have any data there
        if (!empty($data['data']) && is_array($data['data'])) {
          foreach($data['data'] as $key => $aUser) {
            $shares[$key] = $aUser['valid'];
            $username[$key] = $aUser['username'];
          }
          array_multisort($shares, SORT_DESC, $username, SORT_ASC, $data['data']);
          $count = 0;
          foreach ($data['data'] as $key => $aUser) {
            if ($count == $limit) break;
            $count++;
            $data_new[$key]['coin'] = $aUser['coin'];
            $data_new[$key]['shares'] = $aUser['valid'];
            $data_new[$key]['account'] = $aUser['username'];
            $data_new[$key]['donate_percent'] = $aUser['donate_percent'];
            $data_new[$key]['is_anonymous'] = $aUser['is_anonymous'];
          }
          return $data_new;
        }
      }

      // No cached data, fallback to SQL and cache in local cache
      $stmt = $this->mysqli->prepare("
        SELECT
          s.coin,
          a.username AS account,
          a.donate_percent AS donate_percent,
          a.is_anonymous AS is_anonymous,
          ROUND(IFNULL(SUM(IF(s.difficulty=0, POW(2, (" . $this->config['difficulty'] . " - 16)), s.difficulty)), 0) / POW(2, (" . $this->config['difficulty'] . " - 16)), 0) AS shares
        FROM " . $this->share->getTableName() . " AS s
        LEFT JOIN " . $this->user->getTableName() . " AS a
        ON SUBSTRING_INDEX( s.username, '.', 1 ) = a.username
        WHERE our_result = 'Y'
        GROUP BY account
        ORDER BY shares DESC
        LIMIT ?");
      if ($this->checkStmt($stmt) && $stmt->bind_param("i", $limit) && $stmt->execute() && $result = $stmt->get_result()) {
          return $this->memcache->setCache(__FUNCTION__ . $type . $limit, $result->fetch_all(MYSQLI_ASSOC));
      }
      return $this->sqlError();
      break;

    case 'hashes':
        $stmt = $this->mysqli->prepare("
         SELECT
          t1.coin,
          a.username AS account,
          a.donate_percent AS donate_percent,
          a.is_anonymous AS is_anonymous,
          IFNULL(ROUND(SUM(t1.difficulty)  * POW(2, " . $this->config['target_bits'] . ") / 600 / 1000, 2), 0) AS hashrate
        FROM
        (
          SELECT id, coin, IFNULL(IF(difficulty=0, pow(2, (" . $this->config['difficulty'] . " - 16)), difficulty), 0) AS difficulty, username FROM " . $this->share->getTableName() . " WHERE time > DATE_SUB(now(), INTERVAL 10 MINUTE) AND our_result = 'Y'
          UNION
          SELECT share_id, coin, IFNULL(IF(difficulty=0, pow(2, (" . $this->config['difficulty'] . " - 16)), difficulty), 0) AS difficulty, username FROM " . $this->share->getArchiveTableName() ." WHERE time > DATE_SUB(now(), INTERVAL 10 MINUTE) AND our_result = 'Y'
        ) AS t1
        LEFT JOIN " . $this->user->getTableName() . " AS a
        ON SUBSTRING_INDEX( t1.username, '.', 1 ) = a.username
        GROUP BY account
        ORDER BY hashrate DESC LIMIT ?");
      if ($this->checkStmt($stmt) && $stmt->bind_param("i", $limit) && $stmt->execute() && $result = $stmt->get_result()) {
          $res = $result->fetch_all(MYSQLI_ASSOC);
        $res = array_merge($res, $this->getTopUsers());

        usort($res, "compare");

        return $this->memcache->setCache(__FUNCTION__ . $type . $limit, $res);
      }
      return $this->sqlError();
      break;
    }
  }

  /**
   * get Hourly hashrate for a user
   * @param account_id int User ID
   * @return data array NOT FINISHED YET
   **/
  public function getHourlyHashrateByAccount($account_id) {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($data = $this->memcache->get(__FUNCTION__ . $account_id)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT
        a.id,
        IFNULL(ROUND(SUM(IF(s.difficulty=0, pow(2, (" . $this->config['difficulty'] . " - 16)), s.difficulty)) * POW(2, " . $this->config['target_bits'] . ") / 3600 / 1000), 0) AS hashrate,
        HOUR(s.time) AS hour
      FROM " . $this->share->getTableName() . " AS s, accounts AS a
      WHERE time < NOW() - INTERVAL 1 HOUR
        AND our_result = 'Y'
        AND time > NOW() - INTERVAL 25 HOUR
        AND a.username = SUBSTRING_INDEX( s.username, '.', 1 )
        AND a.id = ?
      GROUP BY HOUR(time)
      UNION
      SELECT
        share_id,
        IFNULL(ROUND(SUM(IF(s.difficulty=0, pow(2, (" . $this->config['difficulty'] . " - 16)), s.difficulty)) * POW(2, " . $this->config['target_bits'] . ") / 3600 / 1000), 0) AS hashrate,
        HOUR(s.time) AS hour
      FROM " . $this->share->getArchiveTableName() . " AS s, accounts AS a
      WHERE time < NOW() - INTERVAL 1 HOUR
        AND our_result = 'Y'
        AND time > NOW() - INTERVAL 25 HOUR
        AND a.username = SUBSTRING_INDEX( s.username, '.', 1 )
        AND a.id = ?
      GROUP BY HOUR(time)");
    if ($this->checkStmt($stmt) && $stmt->bind_param('ii', $account_id, $account_id) && $stmt->execute() && $result = $stmt->get_result()) {
      $iStartHour = date('G');
      // Initilize array
      for ($i = 0; $i < 24; $i++) $aData[($iStartHour + $i) % 24] = 0;
      // Fill data
      while ($row = $result->fetch_assoc()) $aData[$row['hour']] = $row['hashrate'];
      return $this->memcache->setCache(__FUNCTION__ . $account_id, $aData);
    }
    return $this->sqlError();
  }

  /**
   * get Hourly hashrate for the pool 
   * @param none
   * @return data array NOT FINISHED YET
   **/
  public function getHourlyHashrateByPool() {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($this->getGetCache() && $data = $this->memcache->get(__FUNCTION__)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT
        id,
      	IFNULL(ROUND(SUM(IF(s.difficulty=0, pow(2, (" . $this->config['difficulty'] . " - 16)), s.difficulty)) * POW(2, " . $this->config['target_bits'] . ") / 3600 / 1000), 0) AS hashrate,
        HOUR(s.time) AS hour
      FROM " . $this->share->getTableName() . " AS s
      WHERE time < NOW() - INTERVAL 1 HOUR
        AND time > NOW() - INTERVAL 25 HOUR
        AND our_result = 'Y'
      GROUP BY HOUR(time)
      UNION
      SELECT
        share_id,
        IFNULL(ROUND(SUM(IF(s.difficulty=0, pow(2, (" . $this->config['difficulty'] . " - 16)), s.difficulty)) * POW(2, " . $this->config['target_bits'] . ") / 3600 / 1000), 0) AS hashrate,
        HOUR(s.time) AS hour
      FROM " . $this->share->getArchiveTableName() . " AS s
      WHERE time < NOW() - INTERVAL 1 HOUR
        AND time > NOW() - INTERVAL 25 HOUR
        AND our_result = 'Y'
      GROUP BY HOUR(time)");
    if ($this->checkStmt($stmt) && $stmt->execute() && $result = $stmt->get_result()) {
      $iStartHour = date('G');
      // Initilize array
      for ($i = 0; $i < 24; $i++) $aData[($iStartHour + $i) % 24] = 0;
      // Fill data
      while ($row = $result->fetch_assoc()) $aData[$row['hour']] = (int) $row['hashrate'];
      return $this->memcache->setCache(__FUNCTION__, $aData);
    }
    return $this->sqlError();
  }

  /**
   * get user estimated payouts based on share counts
   * @param value1 mixed Round shares OR share rate
   * @param value2 mixed User shares OR share difficulty
   * @param dDonate double User donation setting
   * @param bNoFees bool User no-fees option setting
   * @return aEstimates array User estimations
   **/
  public function getUserEstimates($value1, $value2, $dDonate, $bNoFees, $ppsvalue=0) {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($this->config['payout_system'] != 'pps') {
      if (@$value1['valid'] > 0  && @$value2['valid'] > 0) {
        $aEstimates['block'] = round(( (int)$value2['valid'] / (int)$value1['valid'] ) * (float)$this->config['reward'], 8);
        $bNoFees == 0 ? $aEstimates['fee'] = round(((float)$this->config['fees'] / 100) * (float)$aEstimates['block'], 8) : $aEstimates['fee'] = 0;
        $aEstimates['donation'] = round((( (float)$dDonate / 100) * ((float)$aEstimates['block'] - (float)$aEstimates['fee'])), 8);
        $aEstimates['payout'] = round((float)$aEstimates['block'] - (float)$aEstimates['donation'] - (float)$aEstimates['fee'], 8);
      } else {
        $aEstimates['block'] = 0;
        $aEstimates['fee'] = 0;
        $aEstimates['donation'] = 0;
        $aEstimates['payout'] = 0;
      }
    } else {
      // Hack so we can use this method for PPS estimates too
      // value1 = shares/s
      // value2 = avg share difficulty
      if (@$value1 > 0 && @$value2 > 0) {
        $hour = 60 * 60;
        $pps = $value1 * $value2 * $ppsvalue;
        $aEstimates['hours1'] = $pps * $hour;
        $aEstimates['hours24'] = $pps * 24 * $hour;
        $aEstimates['days7'] = $pps * 24 * 7 * $hour;
        $aEstimates['days14'] = $pps * 14 * 24 * $hour;
        $aEstimates['days30'] = $pps * 30 * 24 * $hour;
      } else {
        $aEstimates['hours1'] = 0;
        $aEstimates['hours24'] = 0;
        $aEstimates['days7'] = 0;
        $aEstimates['days14'] = 0;
        $aEstimates['days30'] = 0;
      }
    }
    return $aEstimates;
  }

  /**
   * Get pool stats last 24 hours
   * @param limit int Last number of hours
   * @return array
   **/
  public function getPoolStatsHours($hour=24) {
    $this->debug->append("STA " . __METHOD__, 4);
    if ($data = $this->memcache->get(__FUNCTION__ . $hour)) return $data;
    $stmt = $this->mysqli->prepare("
      SELECT 
      IFNULL(COUNT(id), 0) as count, 
      IFNULL(AVG(difficulty), 0) as average,
      IFNULL(ROUND(SUM((POW(2, ( 32 - " . $this->config['target_bits'] . " )) * difficulty) / POW(2, (" . $this->config['difficulty'] . " -16))), 0), 0) AS expected,
      IFNULL(ROUND(SUM(shares)), 0) as shares,
      IFNULL(SUM(amount), 0) as rewards 
      FROM " . $this->block->getTableName() . "
      WHERE FROM_UNIXTIME(time) > DATE_SUB(now(), INTERVAL ? HOUR)
      AND confirmations >= 1");
    if ($this->checkStmt($stmt) && $stmt->bind_param("i", $hour) && $stmt->execute() && $result = $stmt->get_result())
      return $this->memcache->setCache(__FUNCTION__ . $hour, $result->fetch_assoc());
    return $this->sqlError();
  }

  /**
   * Caclulate estimated shares based on network difficulty and pool difficulty
   * @param dDiff double Network difficulty
   * @return shares integer Share count
   **/
  public function getEstimatedShares($dDiff) {
    return round((POW(2, (32 - $this->config['target_bits'])) * $dDiff) / pow(2, ($this->config['difficulty'] - 16)));
  }

  /**
   * Get the Expected Time per Block in the whole Network in seconde
   * @return seconds double Seconds per Block
   */
  public function getNetworkExpectedTimePerBlock(){
    if ($data = $this->memcache->get(__FUNCTION__)) return $data;

    if ($this->bitcoin->can_connect() === true) {
      $dNetworkHashrate = $this->bitcoin->getnetworkhashps();
      $dDifficulty = $this->bitcoin->getdifficulty();
    } else {
      $dNetworkHashrate = 1;
      $dDifficulty = 1;
    }
    if($dNetworkHashrate <= 0){
      return $this->memcache->setCache(__FUNCTION__, $this->config['cointarget']);
    }

    return $this->memcache->setCache(__FUNCTION__, pow(2, 32) * $dDifficulty / $dNetworkHashrate);
  }

  /**
   * Get the Expected next Difficulty
   * @return difficulty double Next difficulty
   **/
  public function getExpectedNextDifficulty(){
    if ($data = $this->memcache->get(__FUNCTION__)) return $data;

    if ($this->bitcoin->can_connect() === true) {
      $dDifficulty = $this->bitcoin->getdifficulty();
    } else {
      $dDifficulty = 1;
    }

    return $this->memcache->setCache(__FUNCTION__, round($dDifficulty * $this->config['cointarget'] / $this->getNetworkExpectedTimePerBlock(), 8));
  }

  /**
   * Get Number of blocks until next difficulty change
   * @return blocks int blocks until difficulty change
   **/
  public function getBlocksUntilDiffChange(){
    if ($data = $this->memcache->get(__FUNCTION__)) return $data;

    if ($this->bitcoin->can_connect() === true) {
      $iBlockcount = $this->bitcoin->getblockcount();
    } else {
      $iBlockcount = 1;
    }

    return $this->memcache->setCache(__FUNCTION__, $this->config['coindiffchangetarget'] - ($iBlockcount % $this->config['coindiffchangetarget']));
  }

  /**
   * Get current PPS value
   * @return value double PPS Value
   **/

  public function getPPSValue() {
    // Fetch RPC difficulty
    if ($this->bitcoin->can_connect() === true) {
      $dDifficulty = $this->bitcoin->getdifficulty();
    } else {
      $dDifficulty = 1;
    }

    if ($this->config['pps']['reward']['type'] == 'blockavg' && $this->block->getBlockCount() > 0) {
      $pps_reward = round($this->block->getAvgBlockReward($this->config['pps']['blockavg']['blockcount']));
    } else {
      if ($this->config['pps']['reward']['type'] == 'block') {
        if ($aLastBlock = $this->block->getLast()) {
          $pps_reward = $aLastBlock['amount'];
        } else {
          $pps_reward = $this->config['pps']['reward']['default'];
        }
      } else {
        $pps_reward = $this->config['pps']['reward']['default'];
      }
    }
    return round($pps_reward / (pow(2, $this->config['target_bits']) * $dDifficulty), 12);
  }
}

$statistics = new Statistics();
$statistics->setDebug($debug);
$statistics->setMysql($mysqli);
$statistics->setShare($share);
$statistics->setUser($user);
$statistics->setBlock($block);
$statistics->setMemcache($memcache);
$statistics->setConfig($config);
$statistics->setBitcoin($bitcoin);
$statistics->setErrorCodes($aErrorCodes);

?>
