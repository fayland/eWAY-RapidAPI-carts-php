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
//-->
</script>
  <form method='post' name='ewaypay' action='{$gateway_url}' class='eway_payment_form' onsubmit="return avoidDuplicationSubmit()">
    <input type='hidden' name='EWAY_ACCESSCODE' value='{$AccessCode}' />

    {if $payment_type == 'paypal' || $payment_type == 'masterpass' || $payment_type == 'vme'}
    <input type='hidden' name='EWAY_PAYMENTTYPE' value='{$payment_type}' />
    {else}

        {if $payment_type != 'creditcard'}
<table class="std">
    <tr>
        <td>Select Payment Option</td>
        <td>
        <select name="EWAY_PAYMENTTYPE" onchange="javascript:ChoosePaymentOption(this.options[this.options.selectedIndex].value)">
          <option value="creditcard">Credit Card</option>
          <option value="paypal">PayPal</option>
          <option value="masterpass">MasterPass</option>
          <option value="vme">V.me By Visa</option>
        </select>
        </td>
    </tr>
</table>
        <script>
        function ChoosePaymentOption(v) {
            if (v != "creditcard") {
                document.getElementById("creditcard_info").style.display = "none";
            } else {
                document.getElementById("creditcard_info").style.display = "block";
            }
        }
        </script>
        {/if}
<div id="creditcard_info">
<table class="std">
    <tr>
		<td>Credit Card Holder</td>
		<td><input type="text" class="text" name="EWAY_CARDNAME" id='EWAY_CARDNAME' /></td>
	</tr>

	<tr>
		<td>Credit Card Number</td>
		<td><input type="text" class="text" name="EWAY_CARDNUMBER" id='EWAY_CARDNUMBER' /></td>
	</tr>

	<tr>
		<td>Credit Card Expiry</td>
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
		<td>Credit Card CVN</td>
		<td><input type="text" class="text" name="EWAY_CARDCVN" id="EWAY_CARDCVN" /></td>
	</tr>
</table>
</div>

    {/if}
<table class="std">
    <tr><td colspan='2'><input type='image' src="{$module_dir}eway.gif" alt="{l s='Pay with Eway' mod='ewayrapidapi'}" /></td></tr>
</table>

  </form>
</p>