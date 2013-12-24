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
<form action="<?php echo $action; ?>" method="post" id="payment" onsubmit="return avoidDuplicationSubmit()">
<input type="hidden" name="EWAY_ACCESSCODE" value="<?php echo $AccessCode; ?>" />

  <?php if (isset($text_testing)) { ?>
  <div class="warning"><?php echo $text_testing; ?></div>
  <?php } ?>

    <div style="margin-bottom: 10px;">
    <?php
        if (count($payment_type) == 0) $payment_type = array('visa', 'mastercard', 'jcb', 'diners', 'amex', 'paypal', 'masterpass', 'vme');
        if (count($payment_type) == 1) {
            echo "<input type='hidden' name='EWAY_PAYMENTTYPE' value='" . $payment_type[0] . "' />";
        } else {
            if (in_array('visa', $payment_type) || in_array('mastercard', $payment_type) || in_array('diners', $payment_type) || in_array('jcb', $payment_type) || in_array('amex', $payment_type)) {
                echo "<label><input type='radio' name='EWAY_PAYMENTTYPE' id='eway_radio_cc' value='creditcard' checked='checked' onchange='javascript:select_eWAYPaymentOption(\"creditcard\")' /> ";
                if (in_array('visa', $payment_type)) {
                  echo "<img src='catalog/view/theme/default/image/eway_creditcard_visa.png' height='30' /> ";
                }
                if (in_array('mastercard', $payment_type)) {
                  echo "<img src='catalog/view/theme/default/image/eway_creditcard_master.png' height='30' /> ";
                }
                if (in_array('diners', $payment_type)) {
                  echo "<img src='catalog/view/theme/default/image/eway_creditcard_diners.png' height='30' /> ";
                }
                if (in_array('jcb', $payment_type)) {
                  echo "<img src='catalog/view/theme/default/image/eway_creditcard_jcb.png' height='30' /> ";
                }
                if (in_array('amex', $payment_type)) {
                  echo "<img src='catalog/view/theme/default/image/eway_creditcard_amex.png' height='30' /> ";
                }
                echo "</label> ";
            }
            if (in_array('paypal', $payment_type)) {
                echo "<label><input type='radio' name='EWAY_PAYMENTTYPE' value='paypal' onchange='javascript:select_eWAYPaymentOption(\"paypal\")' /> <img src='catalog/view/theme/default/image/eway_paypal.png' height='30' /></label> ";
            }
            if (in_array('masterpass', $payment_type)) {
                echo "<label><input type='radio' name='EWAY_PAYMENTTYPE' value='masterpass' onchange='javascript:select_eWAYPaymentOption(\"masterpass\")' /> <img src='catalog/view/theme/default/image/eway_masterpass.png' height='30' /></label> ";
            }
            if (in_array('vme', $payment_type)) {
                echo "<label><input type='radio' name='EWAY_PAYMENTTYPE' value='vme' onchange='javascript:select_eWAYPaymentOption(\"vme\")' /> <img src='catalog/view/theme/default/image/eway_vme.png' height='30' /></label> ";
            }
        }
    ?>

    </div>

    <?php
        if (in_array('paypal', $payment_type)) {
            echo '<p id="tip_paypal" style="display:none;">After you click "Confirm Order" Please note that you will be redirected to "PayPal" to complete your payment.</p>';
        }
        if (in_array('masterpass', $payment_type)) {
            echo '<p id="tip_masterpass" style="display:none;">After you click "Confirm Order" Please note that you will be redirected to "MasterPass by MasterCard" to complete your payment.</p>';
        }
        if (in_array('vme', $payment_type)) {
            echo '<p id="tip_vme" style="display:none;">After you click "Confirm Order" Please note that you will be redirected to "V.Me by Visa" to complete your payment.</p>';
        }
    ?>

    <?php if (in_array('visa', $payment_type) || in_array('mastercard', $payment_type) || in_array('diners', $payment_type) || in_array('jcb', $payment_type) || in_array('amex', $payment_type)) { ?>
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
</div>
    <?php } ?>

</form>

<div class="buttons">
    <div class="right"><a onclick="$('#payment').submit();" class="button"><span><?php echo $button_confirm; ?></span></a></div>
</div>

<?php } ?>
