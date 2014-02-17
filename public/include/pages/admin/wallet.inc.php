<?php

// Make sure we are called from index.php
if (!defined('SECURITY')) die('Hacking attempt');

// Check user to ensure they are admin
if (!$user->isAuthenticated() || !$user->isAdmin($_SESSION['USERDATA']['id'])) {
    header("HTTP/1.1 404 Page not found");
    die("404 Page not found");
}

$wallet_data = array();

if (!$smarty->isCached('master.tpl', $smarty_cache_key)) {
    $debug->append('No cached version available, fetching from backend', 3);
    foreach ($wallets as $coin => $wallet) {
        if ($wallet->can_connect() === true) {
            $dBalance = $wallet->getbalance();
            $aGetInfo = $wallet->getinfo();
            if (is_array($aGetInfo) && array_key_exists('newmint', $aGetInfo)) {
                $dNewmint = $aGetInfo['newmint'];
            } else {
                $dNewmint = -1;
            }
        } else {
            $aGetInfo = array('errors' => 'Unable to connect');
            $dBalance = 0;
            $dNewmint = -1;
            $_SESSION['POPUP'][] = array('CONTENT' => 'Unable to connect to wallet RPC service '.$coin.': ' . $wallet->can_connect(), 'TYPE' => 'errormsg');
        }
        // Fetch unconfirmed amount from blocks table
        empty($config['network_confirmations']) ? $confirmations = 120 : $confirmations = $config['network_confirmations'];
        $aBlocksUnconfirmed = $block->getAllUnconfirmedCoin($coin, $confirmations);
        $dBlocksUnconfirmedBalance = 0;
        if (!empty($aBlocksUnconfirmed))
            foreach ($aBlocksUnconfirmed as $aData) $dBlocksUnconfirmedBalance += $aData['amount'];

        // Fetch locked balance from transactions
        $dLockedBalance = $transaction->getLockedBalanceByCoin($coin);

        // Cold wallet balance
        $dColdCoins = 0;
        if (!$dColdCoins = $setting->getValue('wallet_cold_coins')) $dColdCoins = 0;

        $wallet_data[$coin] = array(
            'coin' => $coin,
            'unconfirmed' => $dBlocksUnconfirmedBalance,
            'balance' => $dBalance,
            'coldcoins' => $dColdCoins,
            'locked' => $dLockedBalance,
            'newmint' => $dNewmint,
            'coininfo' => $aGetInfo
        );

    }
} else {
    $debug->append('Using cached page', 3);
}

$smarty->assign("WALLET_DATA", $wallet_data);

// Tempalte specifics
$smarty->assign("CONTENT", "default.tpl");

?>
