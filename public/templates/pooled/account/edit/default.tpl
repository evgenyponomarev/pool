<form action="{$smarty.server.PHP_SELF}" method="post">
  <input type="hidden" name="page" value="{$smarty.request.page|escape}">
  <input type="hidden" name="action" value="{$smarty.request.action|escape}">
  <input type="hidden" name="do" value="updateAccount">
  <article class="module width_half">
    <header><h3>Account Details</h3></header>
    <div class="module_content">
      <fieldset>
        <label>Username</label>
        <input type="text" value="{$GLOBAL.userdata.username|escape}" readonly />
      </fieldset>
      <fieldset>
        <label>User Id</label>
        <input type="text" value="{$GLOBAL.userdata.id}" readonly />
      </fieldset>
      {if !$GLOBAL.website.api.disabled}
      <fieldset>
        <label>API Key</label>
        <a href="{$smarty.server.PHP_SELF}?page=api&action=getuserstatus&api_key={$GLOBAL.userdata.api_key}&id={$GLOBAL.userdata.id}">{$GLOBAL.userdata.api_key}</a>
      </fieldset>
      {/if}
      <fieldset>
        <label>E-Mail</label>
        <input type="text" name="email" value="{nocache}{$GLOBAL.userdata.email|escape}{/nocache}" size="20" />
      </fieldset>
        {foreach from=$PAYMENTADDRESSES key=coin item=address}
            <fieldset>
                  <label>{$address.coin} payments</label>
                  <fieldset>
                    <label>Payment Address</label>
                    <input type="text" name="paymentAddresses[{$address.coin}]"
                           value="{nocache}{$smarty.request.paymentAddresses[$address.coin]|default:$address.coin_address|escape}{/nocache}" size="40" />
                  </fieldset>
                  <fieldset>
                    <label>Donation Percentage</label>
                    <font size="1"> Donation amount in percent (example: 0.5)</font>
                    <input type="text" name="donatePercents[{$address.coin}]" value="{nocache}{$smarty.request.donatePercents[$address.coin]|default:$address.donate_percent|escape}{/nocache}" size="4" />
                  </fieldset>
                  <fieldset>
                    <label>Automatic Payout Threshold</label>
                    <font size="1">{$GLOBAL.config.ap_threshold.min}-{$GLOBAL.config.ap_threshold.max} {$address.coin}. Set to '0' for no auto payout.</font>
                    <input type="text" name="payoutThresholds[{$address.coin}]" value="{$smarty.request.payoutThresholds[$address.coin]|default:$address.ap_threshold|escape}" size="{$GLOBAL.config.ap_threshold.max|strlen}" maxlength="{$GLOBAL.config.ap_threshold.max|strlen}" />
                  </fieldset>
            </fieldset>
        {/foreach}
      <fieldset>
        <label>Anonymous Account</label>
        Hide username on website from others. Admins can still get your user information.
        <label class="checkbox" for="is_anonymous">
        <input class="ios-switch" type="hidden" name="is_anonymous" value="0" />
        <input class="ios-switch" type="checkbox" name="is_anonymous" value="1" id="is_anonymous" {if $GLOBAL.userdata.is_anonymous}checked{/if} />
        <div class="switch"></div>
        </label>
      </fieldset>
      <fieldset>
        <label>4 digit PIN</label>
        <font size="1">The 4 digit PIN you chose when registering</font>
        <input type="password" name="authPin" size="4" maxlength="4">
      </fieldset>
    </div>
    <footer>
      <div class="submit_link">
        <input type="submit" value="Update Account" class="alt_btn">
      </div>
    </footer>
  </article>
</form>

{if !$GLOBAL.config.disable_payouts && !$GLOBAL.config.disable_manual_payouts}
<form action="{$smarty.server.PHP_SELF}" method="post">
  <input type="hidden" name="page" value="{$smarty.request.page|escape}">
  <input type="hidden" name="action" value="{$smarty.request.action|escape}">
  <input type="hidden" name="do" value="cashOut">
  <article class="module width_half">
    <header>
      <h3>Cash Out</h3>
    </header>
    <div class="module_content">
      <p style="padding-left:30px; padding-redight:30px; font-size:10px;">
        Please note: a {if $GLOBAL.config.txfee > 0.00001}{$GLOBAL.config.txfee}{else}{$GLOBAL.config.txfee|number_format:"8"}{/if} {$GLOBAL.config.currency} transaction will apply when processing "On-Demand" manual payments
      </p>
      <fieldset>
        <label>Account Balance</label>
          <br/>
          <br/>
          <select name="coin" id="coin_selection" style="float:left;width:70px;">
              {foreach from=$COINS item=coin}
                  <option value="{$coin.id}">{$coin.id}</option>
              {/foreach}
          </select>
          <input id="coin_balance" type="text" value="" readonly style="float:left;width: 300px;"/>
      </fieldset>
      <fieldset>
        <label>Payout to</label>
        <input id="coin_address" type="text" value="" readonly/>
      </fieldset>
      <fieldset>
        <label>4 digit PIN</label>
        <input type="password" name="authPin" size="4" maxlength="4" />
      </fieldset>
        <script type="text/javascript">
            var balances = {nocache}{$GLOBAL.userdata.balance|@json_encode}{/nocache};
            var addresses = {nocache}{$PAYMENTADDRESSES|@json_encode}{/nocache};
            $("#coin_selection")
                    .change(function() {
                        $.each(balances, function(i, balance) {
                            if(balance.coin == $("#coin_selection").val()) $("#coin_balance").val(balance.confirmed);
                        });

                        $.each(addresses, function(i, address) {
                            if(address.coin == $("#coin_selection").val()) $("#coin_address").val(address.coin_address);
                        });
                    });
            if(balances[0]) $("#coin_balance").val(balances[0].confirmed);
            if(addresses[0]) $("#coin_address").val(addresses[0].coin_address);
        </script>

    </div>
    <footer>
      <div class="submit_link">
        <input type="submit" value="Cash Out" class="alt_btn">
      </div>
    </footer>
  </article>
</form>
{/if}

<form action="{$smarty.server.PHP_SELF}" method="post"><input type="hidden" name="act" value="updatePassword">
  <input type="hidden" name="page" value="{$smarty.request.page|escape}">
  <input type="hidden" name="action" value="{$smarty.request.action|escape}">
  <input type="hidden" name="do" value="updatePassword">
  <article class="module width_half">
    <header>
      <h3>Change Password</h3>
    </header>
    <div class="module_content">
      <p style="padding-left:30px; padding-redight:30px; font-size:10px;">
      Note: You will be redirected to login on successful completion of a password change
      </p>
      <fieldset>
        <label>Current Password</label>
        <input type="password" name="currentPassword" />
      </fieldset>
      <fieldset>
        <label>New Password</label>
        <input type="password" name="newPassword" />
      </fieldset>
      <fieldset>
        <label>New Password Repeat</label>
        <input type="password" name="newPassword2" />
      </fieldset>
      <fieldset>
        <label>4 digit PIN</label>
        <input type="password" name="authPin" size="4" maxlength="4" />
      </fieldset>
    </div>
    <footer>
      <div class="submit_link">
        <input type="submit" value="Change Password" class="alt_btn">
      </div>
    </footer>
  </article>
</form>


<form action="{$smarty.server.PHP_SELF}" method="post">
  <input type="hidden" name="page" value="{$smarty.request.page|escape}">
  <input type="hidden" name="action" value="{$smarty.request.action|escape}">
  <input type="hidden" name="do" value="genPin">
	<article class="module width_half">
	  <header>
		  <h3>Reset PIN</h3>
		</header>
		<div class="module_content">
      <fieldset>
		  <label>Current Password</label>
		  <input type="password" name="currentPassword" />
		  </fieldset>
		</div>
		<footer>
      <div class="submit_link">
        <input type="submit" class="alt_btn" value="Reset PIN">
      </div>
    </footer>
  </article>
</form>
