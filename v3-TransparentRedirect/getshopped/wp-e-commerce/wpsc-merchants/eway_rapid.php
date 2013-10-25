<?php

$nzshpcrt_gateways[$num]['name'] = 'eWAY rapid';
$nzshpcrt_gateways[$num]['internalname'] = 'eway_rapid';
$nzshpcrt_gateways[$num]['function'] = 'gateway_eway_rapid';
$nzshpcrt_gateways[$num]['form'] = "form_eway_rapid";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_eway_rapid";
$nzshpcrt_gateways[$num]['payment_type'] = "credit_card";
$nzshpcrt_gateways[$num]['display_name'] = __( 'Credit Card', 'wpsc' );

function gateway_eway_rapid($seperator, $sessionid) {
	global $wpdb, $wpsc_cart;

	$purchase_log_sql = "SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`= '".$sessionid."' LIMIT 1";
	$purchase_log = $wpdb->get_results($purchase_log_sql,ARRAY_A) ;
	$purchase_log = $purchase_log[0];

	if($_POST['collected_data'][get_option('eway_form_first_name')] != '') {
		$data['first_name'] = esc_attr($_POST['collected_data'][get_option('eway_form_first_name')]);
	}
	if($_POST['collected_data'][get_option('eway_form_last_name')] != '') {
		$data['last_name'] = esc_attr($_POST['collected_data'][get_option('eway_form_last_name')]);
	}
	if($_POST['collected_data'][get_option('eway_form_address')] != '') {
		$address_rows = $_POST['collected_data'][get_option('eway_form_address')];
		$data['address1'] = esc_attr(str_replace(array("\n", "\r"), '', $address_rows));
	}
	if($_POST['collected_data'][get_option('eway_form_city')] != '') {
		$data['city'] = esc_attr($_POST['collected_data'][get_option('eway_form_city')]);
	}
	if( empty( $_POST['collected_data'][get_option('eway_form_state')] ) && isset( $_POST['collected_data'][get_option('eway_form_country')][1]) && !empty( $_POST['collected_data'][get_option('eway_form_country')][1])) {
		$data['state'] = $_POST['collected_data'][get_option('eway_form_country')][1];
	}elseif(!empty( $_POST['collected_data'][get_option('eway_form_state')] ) ){
		$data['state'] = $_POST['collected_data'][get_option('eway_form_state')];
	}
	if($_POST['collected_data'][get_option('eway_form_country')]!='') {
		$data['country'] = $_POST['collected_data'][get_option('eway_form_country')][0];
	}
	if(is_numeric($_POST['collected_data'][get_option('eway_form_post_code')])) {
		$data['zip'] =  esc_attr($_POST['collected_data'][get_option('eway_form_post_code')] );
	}

	if($_POST['collected_data'][get_option('eway_form_shipping_first_name')] != '') {
		$data['shipping_first_name'] = esc_attr($_POST['collected_data'][get_option('eway_form_shipping_first_name')]);
	}
	if($_POST['collected_data'][get_option('eway_form_shipping_last_name')] != '') {
		$data['shipping_last_name'] = esc_attr($_POST['collected_data'][get_option('eway_form_shipping_last_name')]);
	}
	if($_POST['collected_data'][get_option('eway_form_shipping_address')] != '') {
		$address_rows = $_POST['collected_data'][get_option('eway_form_shipping_address')];
		$data['shipping_address1'] = esc_attr(str_replace(array("\n", "\r"), '', $address_rows));
	}
	if($_POST['collected_data'][get_option('eway_form_shipping_city')] != '') {
		$data['shipping_city'] = esc_attr($_POST['collected_data'][get_option('eway_form_shipping_city')]);
	}
	if( empty( $_POST['collected_data'][get_option('eway_form_shipping_state')] ) && isset( $_POST['collected_data'][get_option('eway_form_shipping_country')][1]) && !empty( $_POST['collected_data'][get_option('eway_form_shipping_country')][1])) {
		$data['shipping_state'] = $_POST['collected_data'][get_option('eway_form_shipping_country')][1];
	}elseif(!empty( $_POST['collected_data'][get_option('eway_form_state')] ) ){
		$data['shipping_state'] = $_POST['collected_data'][get_option('eway_form_shipping_state')];
	}
	if($_POST['collected_data'][get_option('eway_form_shipping_country')]!='') {
		$data['shipping_country'] = $_POST['collected_data'][get_option('eway_form_shipping_country')][0];
	}
	if(is_numeric($_POST['collected_data'][get_option('eway_form_shipping_post_code')])) {
		$data['shipping_zip'] =  esc_attr($_POST['collected_data'][get_option('eway_form_shipping_post_code')] );
	}

	if($_POST['collected_data'][get_option('eway_form_email')]) {
		$data['email'] =  $_POST['collected_data'][get_option('eway_form_email')];
	}
	if(($_POST['collected_data'][get_option('email_form_field')] != null) && ($data['email'] == null)) {
		$data['email'] = esc_attr( $_POST['collected_data'][get_option('email_form_field')] );
	}
	if($_POST['collected_data'][get_option('eway_form_phone')]) {
		$data['phone'] =  $_POST['collected_data'][get_option('eway_form_phone')];
	}
	if(($_POST['collected_data'][get_option('phone_form_field')] != null) && ($data['phone'] == null)) {
		$data['phone'] = esc_attr( $_POST['collected_data'][get_option('phone_form_field')] );
	}

//        error_reporting(E_ALL);
//        ini_set("display_errors", 1);

    require_once(realpath(dirname(__FILE__).'/eWAY/RapidAPI.php'));
    $username = get_option('eway_username');
    $password = get_option('eway_password');
    $livemode = get_option('eway_rapid_sandbox') ? false : true;
    $eway_service = new RapidAPI($livemode, $username, $password);

    $amount = number_format($purchase_log['totalprice'], 2, '.', '') * 100;
    $transact_url = get_option('siteurl')."/?eway_callback=true";

    // Create AccessCode Request Object
    $request = new CreateAccessCodeRequest();

    $request->Customer->Reference = 'getshopped';
    $request->Customer->Title = 'Mr.';
    $request->Customer->FirstName = strval($data['first_name']);
    $request->Customer->LastName = strval($data['last_name']);
    $request->Customer->CompanyName = '';
    $request->Customer->JobDescription = '';
    $request->Customer->Street1 = strval($data['address1']);
    $request->Customer->Street2 = '';
    $request->Customer->City = strval($data['city']);
    $request->Customer->State = strval($data['state']);
    $request->Customer->PostalCode = strval($data['zip']);
    $request->Customer->Country = strtolower(strval($data['country']));
    $request->Customer->Email = $data['email'];
    $request->Customer->Phone = $data['phone'];
    $request->Customer->Mobile = '';

    // require field
    $request->ShippingAddress->FirstName = strval($data['shipping_first_name']);
    $request->ShippingAddress->LastName = strval($data['shipping_last_name']);
    $request->ShippingAddress->Street1 = strval($data['shipping_address1']);
    $request->ShippingAddress->Street2 = '';
    $request->ShippingAddress->City = strval($data['shipping_city']);
    $request->ShippingAddress->State = strval($data['shipping_state']);
    $request->ShippingAddress->PostalCode = strval($data['shipping_zip']);
    $request->ShippingAddress->Country = strtolower(strval($data['shipping_country']));
    $request->ShippingAddress->Email = $data['email'];
    $request->ShippingAddress->Phone = $data['phone'];

    $request->ShippingAddress->ShippingMethod = "Unknown";

    $invoiceDesc = '';
    foreach ($wpsc_cart->cart_items as $item){
        $lineitem = new EwayLineItem();
        $lineitem->SKU = $item->product_id;
        $lineitem->Description = $item->product_name;
        $request->Items->LineItem[] = $lineitem;
        $invoiceDesc .= $item->product_name . ', ';
    }
	$invoiceDesc = substr($invoiceDesc, 0, -2);
	if(strlen($invoiceDesc) > 64) $invoiceDesc = substr($invoiceDesc , 0 , 61) . '...';

	$opt1 = new EwayOption();
    $opt1->Value = $sessionid;
    $request->Options->Option[0]= $opt1;

    $request->Payment->TotalAmount = $amount;
    $request->Payment->InvoiceNumber = $purchase_log['id'];
    $request->Payment->InvoiceDescription = $invoiceDesc;
    $request->Payment->InvoiceReference = '';

    $currency_converter  =  new CURRENCYCONVERTER();
    $currency_code       = $wpdb->get_results("SELECT `code` FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `id`='".get_option('currency_type')."' LIMIT 1",ARRAY_A);
    $local_currency_code = $currency_code[0]['code'];

    $request->Payment->CurrencyCode = $local_currency_code;

    $request->RedirectUrl = $transact_url;
    $request->Method = 'ProcessPayment';

    // Call RapidAPI
    $result = $eway_service->CreateAccessCode($request);
    if (isset($result->Errors)) {
        //Get Error Messages from Error Code. Error Code Mappings are in the Config.ini file
        $ErrorArray = explode(",", trim($result->Errors));
        $lblError = "";
        foreach ( $ErrorArray as $error ) {
            if (isset($eway_service->APIConfig[$error]))
                $lblError .= $error." ".$eway_service->APIConfig[$error]."<br>";
            else
                $lblError .= $error;
        }
    }

    if (isset($lblError)) {
        $_SESSION['wpsc_checkout_misc_error_messages'][] = $lblError;
        return false;
    }

    // Create Form to post
    get_header();
	$output = "
<div id='primary'>
<div id='content' role='main'>
  <h1 class='entry-title'>Payment</h1>
  <div class='entry-content'>
		<div id='checkout_page_container'>
  <form method='post' name='ewaypay' action='" . $result->FormActionURL . "'>
    <input type='hidden' name='EWAY_ACCESSCODE' value='" . $result->AccessCode . "' />
    <table class='wpsc_checkout_table table-1'>
	<tr>
		<td class='wpsc_checkout_form_12'> Credit Card Holder * </td>
		<td>
			<input type='text' value='" . $_POST['EWAY_CARDNAME'] . "' name='EWAY_CARDNAME' class='text' />
		</td>
	</tr>
	<tr>
		<td class='wpsc_checkout_form_12'> Credit Card Number * </td>
		<td>
			<input type='text' value='" . $_POST['EWAY_CARDNUMBER'] . "' name='EWAY_CARDNUMBER' class='text' />
		</td>
	</tr>
	<tr>
		<td class='wpsc_checkout_form_12'> Credit Card Expiry * </td>
		<td>
		<select class='wpsc_ccBox' name='EWAY_CARDEXPIRYMONTH'>
        <option value='01'>01</option>
        <option value='02'>02</option>
        <option value='03'>03</option>
        <option value='04'>04</option>
        <option value='05'>05</option>
        <option value='06'>06</option>
        <option value='07'>07</option>
        <option value='08'>08</option>
        <option value='09'>09</option>
        <option value='10'>10</option>
        <option value='11'>11</option>
        <option value='12'>12</option>
        </select> / <input type='text' size='2' maxlength='2' value='" . $_POST['EWAY_CARDEXPIRYYEAR'] . "' name='EWAY_CARDEXPIRYYEAR' />
		</td>
	</tr>
    <tr>
        <td class='wpsc_checkout_form_12'> CVN </td>
        <td>
            <input type='text' size='4'  maxlength='4' value='" . $_POST['EWAY_CARDCVN'] . "' name='EWAY_CARDCVN'/>
        </td>
    </tr>
    </table>
    <div class='wpsc_make_purchase'>
         <span>
         <input type='submit' value='Purchase' name='submit' class='make_purchase wpsc_buy_button' />
         </span>
    </div>
    </form>
    </div></div>
</div></div>
		";

    echo($output);
    get_footer();
    exit();
}

function nzshpcrt_eway_callback() {
    global $wpdb;

    if (isset($_GET['eway_callback']) && isset($_REQUEST['AccessCode']))	{

        require_once(realpath(dirname(__FILE__).'/eWAY/RapidAPI.php'));
        $username = get_option('eway_username');
        $password = get_option('eway_password');
        $livemode = get_option('eway_rapid_sandbox') ? false : true;
        $eway_service = new RapidAPI($livemode, $username, $password);

        $isError = false;
        $request = new GetAccessCodeResultRequest();
        $request->AccessCode = $_REQUEST['AccessCode'];

        //Call RapidAPI to get the result
        $result = $eway_service->GetAccessCodeResult($request);

        // Check if any error returns
        if(isset($result->Errors)) {
            // Get Error Messages from Error Code. Error Code Mappings are in the Config.ini file
            $ErrorArray = explode(",", $result->Errors);
            $lblError = "";
            $isError = true;
            foreach ( $ErrorArray as $error ) {
                $lblError .= $eway_service->APIConfig[$error]."<br>";
            }
        }

        if (! $isError) {
            if (! $result->TransactionStatus) {
                // Get Error Messages from Error Code. Error Code Mappings are in the Config.ini file
//                $ErrorArray = explode(",", trim($result->ResponseMessage));
//                $lblError = "";
                $isError = true;
//                foreach ( $ErrorArray as $error ) {
//                    $lblError .= $eway_service->APIConfig[$error]."<br>";
//                }
//                if (strlen($lblError) == 0) {
                    $lblError = "Payment Declined - " . $result->ResponseCode;
//                }
            }
        }

        if ($isError) {
            $sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`= '5' WHERE `sessionid`=".$sessionid;
            $wpdb->query($sql);

            $_SESSION['WpscGatewayErrorMessage'] = __('Sorry your transaction did not go through successfully, please try again. ');
            $_SESSION['WpscGatewayErrorMessage'] .= $lblError;
            $transact_url = get_option('checkout_url');
            header("Location: ".$transact_url);
        } else {
            $option = $result->Options->Option;
            $sessionid = $option[0]->Value;
            $wpdb->query("UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`='3' WHERE `sessionid`='".$sessionid."' LIMIT 1");
            unset($_SESSION['WpscGatewayErrorMessage']);

            if (get_option('permalink_structure') != '')
                $separator ="?";
            else
                $separator ="&";
            $transact_url = get_option('transact_url');
            header("Location: ".$transact_url.$separator."sessionid=".$sessionid);
        }

        exit();
    }
}

function submit_eway_rapid() {
	$options = array(
		'eway_rapid_sandbox',
		'eway_username',
		'eway_password'
	);
	foreach ( $options as $option ) {
        update_option( $option, $_POST[$option] );
	}
	if ( ! empty( $_POST['eway_form'] ) ) {
		foreach((array)$_POST['eway_form'] as $form => $value) {
			update_option(('eway_form_'.$form), $value);
		}
	}
	return true;
}

function form_eway_rapid() {
	// first create the options
	$output = '
	<tr>
		<td>
			eWAY Sandbox
		</td>
		<td>
			<input type="checkbox" name="eway_rapid_sandbox" id="eway_rapid_sandbox" class="eway_sandbox" value="1" ' .  (get_option('eway_rapid_sandbox') ? 'checked="checked"' : '') . ' />
		</td>
	</tr>
	' . "
	<tr>
		<td>
			eWAY API Key
		</td>
		<td>
		    <input type='text' name='eway_username' id='eway_username' value='" . get_option('eway_username') . "' />
		</td>
	</tr>
	<tr>
		<td>
			eWAY Password
		</td>
		<td>
		    <input type='text' name='eway_password' id='eway_password' value='" . get_option('eway_password') . "' />
		</td>
	</tr>
	";
	$output .= "
		<tr>
			<td> Billing First Name Field </td>
			<td>
				<select name='eway_form[first_name]'>
					".nzshpcrt_form_field_list(get_option('eway_form_first_name'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Billing Last Name Field </td>
			<td>
				<select name='eway_form[last_name]'>
					".nzshpcrt_form_field_list(get_option('eway_form_last_name'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Billing Address Field </td>
			<td>
				<select name='eway_form[address]'>
					".nzshpcrt_form_field_list(get_option('eway_form_address'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Billing City Field </td>
			<td>
				<select name='eway_form[city]'>
					".nzshpcrt_form_field_list(get_option('eway_form_city'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Billing State Field </td>
			<td>
				<select name='eway_form[state]'>
					".nzshpcrt_form_field_list(get_option('eway_form_state'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Billing Postal code Field </td>
			<td>
				<select name='eway_form[post_code]'>
					".nzshpcrt_form_field_list(get_option('eway_form_post_code'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Billing Country Field </td>
			<td>
				<select name='eway_form[country]'>
					".nzshpcrt_form_field_list(get_option('eway_form_country'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Shipping First Name Field </td>
			<td>
				<select name='eway_form[shipping_first_name]'>
					".nzshpcrt_form_field_list(get_option('eway_form_shipping_first_name'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Shipping Last Name Field </td>
			<td>
				<select name='eway_form[shipping_last_name]'>
					".nzshpcrt_form_field_list(get_option('eway_form_shipping_last_name'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Shipping Address Field </td>
			<td>
				<select name='eway_form[shipping_address]'>
					".nzshpcrt_form_field_list(get_option('eway_form_shipping_address'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Shipping City Field </td>
			<td>
				<select name='eway_form[shipping_city]'>
					".nzshpcrt_form_field_list(get_option('eway_form_shipping_city'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Shipping State Field </td>
			<td>
				<select name='eway_form[shipping_state]'>
					".nzshpcrt_form_field_list(get_option('eway_form_shipping_state'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Shipping Postal code Field </td>
			<td>
				<select name='eway_form[shipping_post_code]'>
					".nzshpcrt_form_field_list(get_option('eway_form_shipping_post_code'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Shipping Country Field </td>
			<td>
				<select name='eway_form[shipping_country]'>
					".nzshpcrt_form_field_list(get_option('eway_form_shipping_country'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Email Field </td>
			<td>
				<select name='eway_form[email]'>
					".nzshpcrt_form_field_list(get_option('eway_form_email'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Phone Field </td>
			<td>
				<select name='eway_form[phone]'>
					".nzshpcrt_form_field_list(get_option('eway_form_phone'))."
				</select>
			</td>
		</tr>";
	return $output;
}

add_action('init', 'nzshpcrt_eway_callback');

?>