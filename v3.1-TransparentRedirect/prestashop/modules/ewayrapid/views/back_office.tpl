
<div id="eway-wrapper">

	<h2>Eway</h2>

	<img src="../modules/ewayrapid/eway.gif" />
	<p><b>{l s='This module allows you to accept payments by eWAY.' mod='ewayrapid'}</b></p>
	<p>Log in to your Partner Portal account using the link below for your country.</p>
		<ul>
			<li>a. <a href='https://www.eway.com.au/PartnerPortal' target='_blank'>https://www.eway.com.au/PartnerPortal</a></li>
			<li>b. <a href='https://www.eway.co.uk/PartnerPortal' target='_blank'>https://www.eway.co.uk/PartnerPortal</a></li>
			<li>c. <a href='https://www.eway.co.nz/PartnerPortal' target='_blank'>https://www.eway.co.nz/PartnerPortal</a></li>
		</ul>

	<div class="separation"></div>

	{if isset($eWAY_save_success)}
	<div class="conf confirm">
		{l s='Settings updated'}
	</div>
	{/if}
	{if isset($eWAY_save_fail)}
	<div class="alert error">
		<ul>
		{foreach from=$eWAY_errors item=err}
		<li>{$err}</li>
		{/foreach}
		</ul>
	</div>
	{/if}

	<form method="post" action="{$smarty.server.REQUEST_URI|escape:'htmlall'}" id="eway_configuration">
		<fieldset>
			<legend><img src="../img/admin/contact.gif" />{l s='Settings'}</legend>

			<label>{l s='API Sandbox' mod='ewayrapid'}</label>
			<div class="margin-form">
				<input type="radio" name="sandbox" value="1" {if $sandbox} checked="checked"{/if} /> {l s='Yes'}
				<input type="radio" name="sandbox" value="0" {if ! $sandbox} checked="checked"{/if} /> {l s='No'}
			</div>

			<label>{l s='API Username' mod='ewayrapid'}</label>
			<div class="margin-form">
				<input type="text" size="33" name="username" id="username" value="{$username}" />
			</div>

			<label>{l s='API Password' mod='ewayrapid'}</label>
			<div class="margin-form">
				<input type="password" size="33" name="password" id="password" value="{$password}" />
			</div>

			<label>{l s='Payment Type' mod='ewayrapid'}</label>
			<div class="margin-form">
                <input type='checkbox' name='paymenttype[]' value='visa' {if in_array('visa', $paymenttype)} checked='checked'{/if} /> CC - Visa
				<input type='checkbox' name='paymenttype[]' value='mastercard' {if in_array('mastercard', $paymenttype)} checked='checked'{/if} /> CC - MasterCard
				<input type='checkbox' name='paymenttype[]' value='diners' {if in_array('diners', $paymenttype)} checked='checked'{/if} /> CC - Diners Clue
				<input type='checkbox' name='paymenttype[]' value='jcb' {if in_array('jcb', $paymenttype)} checked='checked'{/if} /> CC - JCB
				<input type='checkbox' name='paymenttype[]' value='amex' {if in_array('amex', $paymenttype)} checked='checked'{/if} /> CC - Amex
				<input type='checkbox' name='paymenttype[]' value='paypal' {if in_array('paypal', $paymenttype)} checked='checked'{/if} /> PayPal
				<input type='checkbox' name='paymenttype[]' value='masterpass' {if in_array('masterpass', $paymenttype)} checked='checked'{/if} /> MasterPass
				<input type='checkbox' name='paymenttype[]' value='vme' {if in_array('vme', $paymenttype)} checked='checked'{/if}/> V.me By Visa
            </div>

			<br />
			<center><input type="submit" name="submitRapideWAY" value="{l s='Update settings'}" class="button" /></center>
		</fieldset>
	</form>

</div>
