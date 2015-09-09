<div class="alert alert-block alert-info">
    <p>{$LANG.domainname}: <strong>{$domain}</strong></p>
</div>

{if $error}
<div class="alert alert-error">
	<strong>Sorry, we couldn't complete your request:</strong>
	<br />{$error}
</div>
<br />
{/if}

<p>The Whois Privacy service replaces your personal information in the Whois requests for
this domain to protect your privacy.</p>

<br /><br />

<div class="textcenter">
	<form method="post" action="clientarea.php">
		<input type="hidden" name="action" value="domaindetails" />
		<input type="hidden" name="id" value="{$domainid}" />
		<input type="hidden" name="modop" value="custom" />
		<input type="hidden" name="ok" value="ok" />
		<input type="hidden" name="a" value="whoisPrivacy" />
		
		{if !$status}
		<input type="hidden" name="privacy" value="on" />
		{/if}
		
		<input type="submit" class="btn btn-large {if $status}btn-danger{else}btn-success{/if}" value="{if $status}Disable{else}Enable{/if} Whois Privacy" />
	</form>
</div>

<br /><br />

<form method="post" action="{$smarty.server.PHP_SELF}?action=domaindetails">
<input type="hidden" name="id" value="{$domainid}" />
<p><input type="submit" value="{$LANG.clientareabacklink}" class="btn" /></p>
</form>