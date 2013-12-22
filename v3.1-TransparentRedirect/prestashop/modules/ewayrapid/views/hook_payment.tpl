<p class="payment_module">
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

function IsCC_checked(v) {
    if (! document.getElementById("creditcard_info")) return;
    if (v) {
        document.getElementById("creditcard_info").style.display = "block";
    } else {
        document.getElementById("creditcard_info").style.display = "none";
    }
}

//-->
</script>
  <form method='post' name='ewaypay' action='{$gateway_url}' class='eway_payment_form' onsubmit="return avoidDuplicationSubmit()">
    <input type='hidden' name='EWAY_ACCESSCODE' value='{$AccessCode}' />

    {if $payment_type|@count == 1}
    <input type='hidden' name='EWAY_PAYMENTTYPE' value='$payment_type[0]' />
    {else}
        {if (in_array('visa', $payment_type) || in_array('mastercard', $payment_type) || in_array('diners', $payment_type) || in_array('jcb', $payment_type) || in_array('amex', $payment_type))}
            <label><input type='radio' name='EWAY_PAYMENTTYPE' id='eway_radio_cc' value='creditcard' checked='checked' onchange='javascript:IsCC_checked(true)' />
            {if (in_array('visa', $payment_type))}
            <img src='{$module_dir}images/eway_creditcard_visa.png' height='30' />
            {/if}
            {if (in_array('mastercard', $payment_type))}
            <img src='{$module_dir}images/eway_creditcard_master.png' height='30' />
            {/if}
            {if (in_array('diners', $payment_type))}
            <img src='{$module_dir}images/eway_creditcard_diners.png' height='30' />
            {/if}
            {if (in_array('jcb', $payment_type))}
            <img src='{$module_dir}images/eway_creditcard_jcb.png' height='30' />
            {/if}
            {if (in_array('amex', $payment_type))}
            <img src='{$module_dir}images/eway_creditcard_amex.png' height='30' />
            {/if}
            </label>
        {/if}
        {if in_array('paypal', $payment_type)}
            <label><input type='radio' name='EWAY_PAYMENTTYPE' value='paypal' onchange='javascript:IsCC_checked(false)' /> <img src='{$module_dir}images/eway_paypal.png' height='30' /></label>
        {/if}
        {if in_array('masterpass', $payment_type)}
            <label><input type='radio' name='EWAY_PAYMENTTYPE' value='masterpass' onchange='javascript:IsCC_checked(false)' /> <img src='{$module_dir}images/eway_masterpass.png' height='30' /></label>
        {/if}
        {if in_array('vme', $payment_type)}
            <label><input type='radio' name='EWAY_PAYMENTTYPE' value='vme' onchange='javascript:IsCC_checked(false)' /> <img src='{$module_dir}images/eway_vme.png' height='30' /></label>
        {/if}
    {/if}

{if (in_array('visa', $payment_type) || in_array('mastercard', $payment_type) || in_array('diners', $payment_type) || in_array('jcb', $payment_type) || in_array('amex', $payment_type))}
<div id="creditcard_info">
<table class="std">
    <tr>
		<td align='right'>Credit Card Holder</td>
		<td><input type="text" class="text" name="EWAY_CARDNAME" id='EWAY_CARDNAME' /></td>
	</tr>

	<tr>
		<td align='right'>Credit Card Number</td>
		<td><input type="text" class="text" name="EWAY_CARDNUMBER" id='EWAY_CARDNUMBER' /></td>
	</tr>

	<tr>
		<td align='right'>Credit Card Expiry</td>
    <td>
		<select id="EWAY_CARDEXPIRYMONTH" name="EWAY_CARDEXPIRYMONTH">
            {foreach from=$months key=k item=month}
                <option value="{$k|string_format:"%02d"}">{$month}</option>
            {/foreach}
        </select>
         / <input type="text" class="text" id="EWAY_CARDEXPIRYYEAR" name="EWAY_CARDEXPIRYYEAR" size='2' maxlength='2' />
    </td>
	</tr>
	<tr>
		<td align='right'>Credit Card CVN</td>
		<td><input type="text" class="text" name="EWAY_CARDCVN" id="EWAY_CARDCVN" /></td>
	</tr>
</table>
</div>
{/if}

    <p class="cart_navigation submit">
        <input type="submit" name="processCarrier" value="{l s='Pay with Eway' mod='ewayrapidapi'} &raquo;" class="exclusive" />
    </p>

  </form>
</p>