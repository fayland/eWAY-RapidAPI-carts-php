{if isset($error)}
<table width="100%" cellpadding="3" cellspacing="10" border="0">
  <tr>
    <td>{$error}</td>
  </tr>
</table>
{else}

    <meta http-equiv='refresh' content='1;url={$SharedPaymentUrl}'>
    <a href='{$SharedPaymentUrl}'>You will be redirected to eWAY, if not, please click here.</a>
    </form><form action='{$SharedPaymentUrl}' onsubmit='return false;'>

{/if}
