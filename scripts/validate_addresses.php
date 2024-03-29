#!/usr/bin/php
<?php

/*

Copyright:: 2013, Sebastian Grewe

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

 */

/**
 * Simple script to fetch all user accounts and their coin addresses, then runs
 * them against the RPC to validate. Will allow admins to find users with invalid addresses.
 **/

// Change to working directory
chdir(dirname(__FILE__));

// Include all settings and classes
require_once('shared.inc.php');

// Fetch all users
$users = $user->getAllAssoc();

// Table mask
$mask = "| %-35.35s | %-35.35s | %-40.40s | %-7.7s |\n";
echo 'Validating all coin addresses. This may take some time.' . PHP_EOL . PHP_EOL;

printf($mask, 'Username', 'E-Mail', 'Addresses', 'Status');
foreach ($users as $aData) {
    $accountWallets = $user->getCoinAddresses($aData['id']);
    $status = '';
    $addresses = '';
    foreach($accountWallets as $accountWallet) {
        $addresses .= $coin . " " . $accountWallet['coin_address']."\n";
        if (empty($accountWallet['coin_address'])) {
            $status .= "UNSET\n";
        } else {
            $ret = $wallets[$coin]->validateaddress($accountWallet['coin_address']);
            if ($ret['isvalid']) {
                $status .= "VALID\n";
            } else {
                $status .= "INVALID\n";
            }
        }
    }

    if ($aData['is_locked'] == 1) {
        $status = 'LOCKED';
    }

    printf($mask, $aData['username'], $aData['email'], $addresses, $status);
}
