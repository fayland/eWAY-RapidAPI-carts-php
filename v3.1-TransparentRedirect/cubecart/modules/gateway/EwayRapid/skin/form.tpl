<h2>{$LANG.orders.title_card_details}</h2>
{if isset($error)}
<table width="100%" cellpadding="3" cellspacing="10" border="0">
  <tr>
    <td>{$error}</td>
  </tr>
</table>
{else}
</form>
<!-- reset form action -->
<script language="JavaScript" type="text/javascript" >
//<!--
  var submitcount = 0;
  function avoidDuplicationSubmit(){
    if (submitcount == 0) {
      // sumbit form
      submitcount++;
      return true;
    } else {
      alert("Transaction is in progress.");
      return false;
    }
  }

function select_eWAYPaymentOption(v) {
    if (document.getElementById("creditcard_info"))
        document.getElementById("creditcard_info").style.display = "none";
    if (document.getElementById("tip_paypal"))
        document.getElementById("tip_paypal").style.display = "none";
    if (document.getElementById("tip_masterpass"))
        document.getElementById("tip_masterpass").style.display = "none";
    if (document.getElementById("tip_vme"))
        document.getElementById("tip_vme").style.display = "none";
    if (v == 'creditcard') {
        document.getElementById("creditcard_info").style.display = "block";
    } else {
        document.getElementById("tip_" + v).style.display = "block";
    }
}

//-->
</script>
<form id="gateway-transfer" action="{$FormActionURL}" method="post" target="_self" onsubmit="return avoidDuplicationSubmit()">
<input type="hidden" name="EWAY_ACCESSCODE" value="{$AccessCode}" />

<div style="margin-bottom: 10px;">
    {if $payment_type|@count == 1}
    <input type='hidden' name='EWAY_PAYMENTTYPE' value='$payment_type[0]' />
    {else}
        {if (in_array('visa', $payment_type) || in_array('mastercard', $payment_type) || in_array('diners', $payment_type) || in_array('jcb', $payment_type) || in_array('amex', $payment_type))}
            <label><input type='radio' name='EWAY_PAYMENTTYPE' id='eway_radio_cc' value='creditcard' checked='checked' onchange='javascript:select_eWAYPaymentOption("creditcard")' />
            {if (in_array('visa', $payment_type))}
            <img src='{$eWAY_images_url}/eway_creditcard_visa.png' height='30' />
            {/if}
            {if (in_array('mastercard', $payment_type))}
            <img src='{$eWAY_images_url}/eway_creditcard_master.png' height='30' />
            {/if}
            {if (in_array('diners', $payment_type))}
            <img src='{$eWAY_images_url}/eway_creditcard_diners.png' height='30' />
            {/if}
            {if (in_array('jcb', $payment_type))}
            <img src='{$eWAY_images_url}/eway_creditcard_jcb.png' height='30' />
            {/if}
            {if (in_array('amex', $payment_type))}
            <img src='{$eWAY_images_url}/eway_creditcard_amex.png' height='30' />
            {/if}
            </label>
        {/if}
        {if in_array('paypal', $payment_type)}
            <label><input type='radio' name='EWAY_PAYMENTTYPE' value='paypal' onchange='javascript:select_eWAYPaymentOption("paypal")' /> <img src='{$eWAY_images_url}/eway_paypal.png' height='30' /></label>
        {/if}
        {if in_array('masterpass', $payment_type)}
            <label><input type='radio' name='EWAY_PAYMENTTYPE' value='masterpass' onchange='javascript:select_eWAYPaymentOption("masterpass")' /> <img src='{$eWAY_images_url}/eway_masterpass.png' height='30' /></label>
        {/if}
        {if in_array('vme', $payment_type)}
            <label><input type='radio' name='EWAY_PAYMENTTYPE' value='vme' onchange='javascript:select_eWAYPaymentOption("vme")' /> <img src='{$eWAY_images_url}/eway_vme.png' height='30' /></label>
        {/if}
    {/if}

</div>

        {if in_array('paypal', $payment_type)}
            <p id="tip_paypal" style="display:none;">After you click "Make Payment" Please note that you will be redirected to "PayPal" to complete your payment.</p>
        {/if}
        {if in_array('masterpass', $payment_type)}
            <p id="tip_masterpass" style="display:none;">After you click "Make Payment" Please note that you will be redirected to "MasterPass by MasterCard" to complete your payment.</p>
        {/if}
        {if in_array('vme', $payment_type)}
            <p id="tip_vme" style="display:none;">After you click "Make Payment" Please note that you will be redirected to "V.Me by Visa" to complete your payment.</p>
        {/if}

{if (in_array('visa', $payment_type) || in_array('mastercard', $payment_type) || in_array('diners', $payment_type) || in_array('jcb', $payment_type) || in_array('amex', $payment_type))}
<div id="creditcard_info">
<table width="100%" cellpadding="3" cellspacing="10" border="0">
  <tr>
	<td width="140">Card Holders Name</td>
	<td><input type="text" name="EWAY_CARDNAME" /></td>
  </tr>
  <tr>
	<td width="140">{$LANG.gateway.card_number}</td>
	<td><input type="text" name="EWAY_CARDNUMBER" value="" size="18" maxlength="18" /></td>
  </tr>
  <tr>
	<td width="140">{$LANG.gateway.card_expiry_date}</td>
	<td>
	  <select name="EWAY_CARDEXPIRYMONTH">
		{foreach from=$CARD.expire.months item=month}<option value="{$month.value}" {$month.selected}>{$month.display}</option>{/foreach}
	  </select>
	  /
	  <select name="EWAY_CARDEXPIRYYEAR">
		{foreach from=$CARD.expire.years item=year}<option value="{$year.value}" {$year.selected}>{$year.display}</option>{/foreach}
	  </select>
	</td>
  </tr>
  <tr>
	<td width="140">CVN</td>
	<td>
	  <input type="text" name="EWAY_CARDCVN" value="" size="5" class="textbox_small" />
	</td>
  </tr>
</table>
</div>
{/if}

{/if}