<?php if (isset($error)) { ?>

<div class="warning">Eway Payment Error: <?php echo $error; ?></div>

<?php } else { ?>

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
    function ChoosePaymentOption(v) {
        if (v != "creditcard") {
            document.getElementById("creditcard_info").style.display = "none";
        } else {
            document.getElementById("creditcard_info").style.display = "block";
        }
    }
//-->
</script>
<form action="<?php echo $action; ?>" method="post" id="payment" onsubmit="return avoidDuplicationSubmit()">
<input type="hidden" name="EWAY_ACCESSCODE" value="<?php echo $AccessCode; ?>" />

  <?php if (isset($text_testing)) { ?>
  <div class="warning"><?php echo $text_testing; ?></div>
  <?php } ?>

    <?php if ($payment_type == 'paypal' || $payment_type == 'masterpass' || $payment_type == 'vme') { ?>
    <input type='hidden' name='EWAY_PAYMENTTYPE' value='<?php echo $payment_type ?>' />
    <?php } else { ?>

    <?php if ($payment_type != 'creditcard') { ?>
<div class="content">
<table cellspacing="0" cellpadding="3" border="0">
    <tr>
        <td>
<label class="inputLabelPayment">Select Payment Option:</label>
        </td>
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
</div>
    <?php } ?>

<div class="content" id="creditcard_info">
<font size="2pt"><strong>Credit Card Payment</strong></font>
<table id="eway_table" cellspacing="0" cellpadding="3" border="0">
  <tr>
    <td>
<span id="Label10">Card Holders Name:</span></td>
    <td>
<input name="EWAY_CARDNAME" type="text" id="EWAY_CARDNAME" /></td></tr>
  <tr>
    <td>
<span id="Label2">Card Number:</span></td>
    <td>
<input name="EWAY_CARDNUMBER" type="text" maxlength="17" id="EWAY_CARDNUMBER" />
</td></tr>
  <tr>
    <td>
<span id="Label3">Card Expiry:</span></td>
    <td>
<select name="EWAY_CARDEXPIRYMONTH" id="EWAY_CARDEXPIRYMONTH">
		<option value="01">01</option>
		<option value="02">02</option>
		<option value="03">03</option>
		<option value="04">04</option>
		<option value="05">05</option>
		<option value="06">06</option>
		<option value="07">07</option>
		<option value="08">08</option>
		<option value="09">09</option>
		<option value="10">10</option>
		<option value="11">11</option>
		<option value="12">12</option>
	</select>
<select name="EWAY_CARDEXPIRYYEAR" id="EWAY_CARDEXPIRYYEAR">

<?php
    $start = date ('Y');
    $end = $start + 7;
    for ($i = $start; $i <= $end; $i++) {
        $j = $i - 2000;
        echo "<option value='$j'>$i</option>";
    }
?>

	</select> month / year</td></tr>
  <tr>
    <td>
<span id="Label2">CVN Number:</span></td>
    <td>
<input name="EWAY_CARDCVN" type="text" maxlength="5" id="EWAY_CARDCVN" size="5" />
</td></tr>

</table>
</form>
</div>
    <?php } ?>

<div class="buttons">
    <div class="right"><a onclick="$('#payment').submit();" class="button"><span><?php echo $button_confirm; ?></span></a></div>
</div>

<?php } ?>
