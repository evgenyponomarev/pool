{foreach from=$WALLET_DATA item=wallet}
<article class="module width_quarter">
  <header><h3>Balance Summary ({$wallet.coin})</h3></header>
  <table width="25%" class="tablesorter" cellspacing="0">
  <tr>
    <td align="left">Wallet Balance</td>
    <td align="left">{$wallet.balance|number_format:"8"}</td>
  </tr>
  <tr>
    <td align="left">Locked for users</td>
    <td align="left">{$wallet.locked|number_format:"8"}</td>
  </tr>
  <tr>
    <td align="left">Unconfirmed</td>
    <td align="left">{$wallet.unconfirmed|number_format:"8"}</td>
  </tr>
  <tr>
    <td align="left">Liquid Assets</td>
    <td align="left">{($wallet.balance - $wallet.locked)|number_format:"8"}</td>
  </tr>
{if $NEWMINT >= 0}
  <tr>
    <td align="left">PoS New Mint</td>
    <td align="left">{$wallet.newmint|number_format:"8"}</td>
  </tr>
{/if}
</table>
</article>
{/foreach}

<article class="module width_3_quarter">
  <header><h3>Wallet Information</h3></header>
  <table class="tablesorter" cellspacing="0">
    <thead>
      <th align="center">Coin</th>
      <th align="center">Version</th>
      <th align="center">Protocol Version</th>
      <th align="center">Wallet Version</th>
      <th align="center">Connections</th>
      <th align="center">Errors</th>
    </thead>
    <tbody>
    {foreach from=$WALLET_DATA item=wallet}
      <tr>
        <td align="center">{$wallet.coin}</td>
        <td align="center">{$wallet.coininfo.version|default:""}</td>
        <td align="center">{$wallet.coininfo.protocolversion|default:""}</td>
        <td align="center">{$wallet.coininfo.walletversion|default:""}</td>
        <td align="center">{$wallet.coininfo.connections|default:""}</td>
        <td align="center"><font color="{if $wallet.coininfo.errors}red{else}green{/if}">{$wallet.coininfo.errors|default:"OK"}</font></td>
      </tr>
    {/foreach}
    </tbody>
  </table>
</article>