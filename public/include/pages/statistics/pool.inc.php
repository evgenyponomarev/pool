<?php

// Make sure we are called from index.php
if (!defined('SECURITY')) die('Hacking attempt');

if (!$smarty->isCached('master.tpl', $smarty_cache_key)) {
  $debug->append('No cached version available, fetching from backend', 3);

  // Top share contributors
  $aContributorsShares = $statistics->getTopContributors('shares', 15);

  // Top hash contributors
  $aContributorsHashes = $statistics->getTopContributors('hashes', 15);

  // Grab the last 5 blocks found as a quick overview
  $iLimit = 5;
  $aBlocksFoundData = $statistics->getBlocksFound($iLimit);
  count($aBlocksFoundData) > 0 ? $aBlockData = $aBlocksFoundData[0] : $aBlockData = array();

  // Estimated time to find the next block
  $iCurrentPoolHashrate =  $statistics->getCurrentHashrate();


  // Time since last block
  if (!empty($aBlockData)) {
    $dTimeSinceLast = (time() - $aBlockData['time']);
    if ($dTimeSinceLast < 0) $dTimeSinceLast = 0;
  } else {
    $dTimeSinceLast = 0;
  }


  $dExpectedTimePerBlock = $statistics->getNetworkExpectedTimePerBlock();
  $dEstNextDifficulty = $statistics->getExpectedNextDifficulty();
  $iBlocksUntilDiffChange = $statistics->getBlocksUntilDiffChange();

  // Propagate content our template
  $smarty->assign("TIMESINCELAST", $dTimeSinceLast);
  $smarty->assign("BLOCKSFOUND", $aBlocksFoundData);
  $smarty->assign("BLOCKLIMIT", $iLimit);
  $smarty->assign("CONTRIBSHARES", $aContributorsShares);
  $smarty->assign("CONTRIBHASHES", $aContributorsHashes);
  $smarty->assign("CURRENTBLOCKHASH", @$sBlockHash);
  if (count($aBlockData) > 0) {
    $smarty->assign("LASTBLOCK", $aBlockData['height']);
    $smarty->assign("LASTBLOCKHASH", $aBlockData['blockhash']);
  } else {
    $smarty->assign("LASTBLOCK", 0);
  }
  $smarty->assign("REWARD", $config['reward']);
} else {
  $debug->append('Using cached page', 3);
}

// Public / private page detection
if ($setting->getValue('acl_pool_statistics')) {
  $smarty->assign("CONTENT", "default.tpl");
} else if ($user->isAuthenticated() && ! $setting->getValue('acl_pool_statistics')) {
  $smarty->assign("CONTENT", "default.tpl");
} else {
  $smarty->assign("CONTENT", "../default.tpl");
}
?>
