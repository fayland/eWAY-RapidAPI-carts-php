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
//-->
</script>
<form id="gateway-transfer" action="{$FormActionURL}" method="post" target="_self" onsubmit="return avoidDuplicationSubmit()">
<input type="hidden" name="EWAY_ACCESSCODE" value="{$AccessCode}" />
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
{/if}
