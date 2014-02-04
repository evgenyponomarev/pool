
<form action="{$smarty.server.PHP_SELF}" method="post">
<article class="module width_half">
  <header><h3>Register new account</h3></header>
  <div class="module_content">
    <input type="hidden" name="page" value="{$smarty.request.page|escape}">
{if $smarty.request.token|default:""}
    <input type="hidden" name="token" value="{$smarty.request.token|escape}" />
{/if}
    <input type="hidden" name="action" value="register">
    <fieldset>
      <label>Username</label>
      <input type="text" class="text tiny" name="username" value="{$smarty.post.username|escape|default:""}" size="15" maxlength="20" placeholder="Enter username" required>
    </fieldset>
    <fieldset>
      <label>Password</label>
      <input type="password" class="text tiny" name="password1" value="" size="15" maxlength="100" placeholder="Choose password" required>
      {*<input type="password" class="text tiny" name="password2" value="" size="15" maxlength="100" placeholder="Confirm password" required>*}
    </fieldset>
    <fieldset>
      <label>Email</label>
      <input type="text" name="email1" class="text small" value="{$smarty.post.email1|escape|default:""}" size="15" placeholder="Enter e-mail" required>
      {*<input type="text" class="text small" name="email2" value="{$smarty.post.email2|escape|default:""}" size="15" placeholder="Confirm e-mail" required>*}
    </fieldset>
    <fieldset>
      <label>PIN</label>
      <input type="password" class="text pin" name="pin" value="" size="4" maxlength="4" placeholder="Choose PIN"><span class="hint"> (4 digit number. <b>Remember this pin!</b>)</span>
    </fieldset>
    <fieldset>
      <label>Terms and Conditions</label><a style="width:152px;" onclick="TINY.box.show({literal}{url:'?page=tacpop',height:500}{/literal})"><font size="1">Accept Terms and Conditions</font></a>
      <input type="checkbox" value="1" name="tac" id="tac">
      <label for="tac" style="margin:1px 0px 0px -20px"></label>
    </fieldset>
    <center>{nocache}{$RECAPTCHA|default:""}{/nocache}</center>
  </div>
  <footer>
    <div class="submit_link">
      <input type="submit" value="Register" class="alt_btn">
    </div>
  </footer>
</article>
</form>
