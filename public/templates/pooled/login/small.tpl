{if $smarty.session.AUTHENTICATED|default:"0" == 0}
<div class="login_small">
  <form action="{$smarty.server.PHP_SELF}" method="post" id="loginForm">
    <input type="hidden" name="page" value="login" />
    <input type="hidden" name="to" value="{$smarty.server.PHP_SELF}?page=dashboard" />
    <fieldset2 class="small">
      <input type="text" name="username" size="22" maxlength="100" placeholder="Username or e-mail" required />
      <fieldset2 class="small">
        <input type="password" name="password" size="22" maxlength="100" placeholder="Password" required />
      </fieldset2>
    </fieldset2>
    <input type="submit" value="Login" class="alt_btn" />
  </form>
</div>
{/if}
