<?php
/**
 * eway_rapid.php payment module class for eWAY Transparent Redirect
 *
 * @package paymentMethod
 * @copyright Copyright 2013 eWAY
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version 3.1.0
 */

class eway_rapid extends base {
    var $code, $title, $description, $enabled, $auth_code, $transaction_id;

    var $enableDirectPayment = true;

    function eway_rapid() {
      global $order;

      $this->code = 'eway_rapid';
      $this->codeTitle = MODULE_PAYMENT_EWAYRAPID_TEXT_TITLE;
      $this->codeVersion = '3.1.0';
      $this->enableDirectPayment = true;
      $this->title = MODULE_PAYMENT_EWAYRAPID_TEXT_TITLE;

      if (MODULE_PAYMENT_EWAYRAPID_STATUS == 'True' && MODULE_PAYMENT_EWAYRAPID_USERNAME== '') {
        $this->title .= '<span class="alert"> (Not Configured)</span>';
      } elseif (MODULE_PAYMENT_EWAYRAPID_MODE == 'True') {
        $this->title .= '<span class="alert"> (in Testing mode)</span>';
      }

      $this->public_title = MODULE_PAYMENT_EWAYRAPID_TEXT_PUBLIC_TITLE;
      $this->description = MODULE_PAYMENT_EWAYRAPID_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_PAYMENT_EWAYRAPID_SORT_ORDER;
      $this->enabled = ((MODULE_PAYMENT_EWAYRAPID_STATUS == 'True') ? true : false);

      if ((int)MODULE_PAYMENT_EWAYRAPID_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_EWAYRAPID_ORDER_STATUS_ID;
      }

      if (is_object($order)) $this->update_status();
    }

    function update_status() {
        global $order, $db;
        if ($this->enabled && (int)$this->zone > 0) {
          $check_flag = false;
          $sql = "SELECT zone_id
                  FROM " . TABLE_ZONES_TO_GEO_ZONES . "
                  WHERE geo_zone_id = :zoneId
                  AND zone_country_id = :countryId
                  ORDER BY zone_id";
          $sql = $db->bindVars($sql, ':zoneId', $this->zone, 'integer');
          $sql = $db->bindVars($sql, ':countryId', $order->billing['country']['id'], 'integer');
          $check = $db->Execute($sql);
          while (!$check->EOF) {
            if ($check->fields['zone_id'] < 1) {
              $check_flag = true;
              break;
            } elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
              $check_flag = true;
              break;
            }
            $check->MoveNext();
          }

          if (!$check_flag) {
            $this->enabled = false;
          }
        }
    }

    function javascript_validation() {
      return false;
    }

    function selection() {
        return array('id' => $this->code, 'module' => $this->public_title);
    }

    function pre_confirmation_check() {
      return false;
    }

    function confirmation() {
        return false;
    }

    function process_button() {
        global $db, $order, $currencies, $currency;

        $amount = number_format($order->info['total'], 2, '.', '') * 100;
        $transact_url = zen_href_link(FILENAME_CHECKOUT_PROCESS, 'referer=eway_rapid', 'SSL', true, false);
        $customerId = $_SESSION['customer_id'];				// customerId
        $merchantRef = $customerId."-".date("YmdHis");		// merchant reference

        require_once(realpath(dirname(__FILE__).'/eway_rapid/lib/eWAY/RapidAPI.php'));
        $__username = MODULE_PAYMENT_EWAYRAPID_USERNAME;
        $__password = MODULE_PAYMENT_EWAYRAPID_PASSWORD;
        $__sandbox = ( MODULE_PAYMENT_EWAYRAPID_MODE == 'True' ) ? true : false;

        $eway_params = array();
        if ($__sandbox) $eway_params['sandbox'] = true;
        $service = new eWAY\RapidAPI($__username, $__password, $eway_params);

        // Create AccessCode Request Object
        $request = new eWAY\CreateAccessCodesSharedRequest();

        $request->Customer->Reference = 'zencart';
        $request->Customer->Title = 'Mr.';
        $request->Customer->FirstName = strval($order->billing['firstname']);
        $request->Customer->LastName  = strval($order->billing['lastname']);
        $request->Customer->CompanyName = '';
        $request->Customer->JobDescription = '';
        $request->Customer->Street1 = strval($order->billing['street_address']);
        $request->Customer->Street2 = strval($order->billing['suburb']);
        $request->Customer->City = strval($order->billing['city']);
        $request->Customer->State = strval($order->billing['state']);
        $request->Customer->PostalCode = strval($order->billing['postcode']);
        $request->Customer->Country = strtolower(strval($order->billing['country']['iso_code_2']));
        $request->Customer->Email = $order->customer['email_address'];
        $request->Customer->Phone = $order->customer['telephone'];
        $request->Customer->Mobile = '';

        // require field
        $request->ShippingAddress->FirstName = strval($order->delivery['firstname']);
        $request->ShippingAddress->LastName  = strval($order->delivery['lastname']);
        $request->ShippingAddress->Street1 = strval($order->delivery['street_address']);
        $request->ShippingAddress->Street2 = strval($order->delivery['suburb']);
        $request->ShippingAddress->City = strval($order->delivery['city']);
        $request->ShippingAddress->State = strval($order->delivery['state']);
        $request->ShippingAddress->PostalCode = strval($order->delivery['postcode']);
        $request->ShippingAddress->Country = strtolower(strval($order->delivery['country']['iso_code_2']));
        $request->ShippingAddress->Email = $order->customer['email_address'];
        $request->ShippingAddress->Phone = $order->customer['telephone'];
        $request->ShippingAddress->ShippingMethod = "Unknown";

        $invoiceDesc = '';
        foreach ($order->products as $product) {
            $item = new eWAY\LineItem();
            $item->SKU = $product['id'];
            $item->Description = $product['name'];
            $item->Quantity = $product['qty'];
            $item->UnitCost = number_format($product['price'], 2, '.', '') * 100;
            if (isset($product['tax'])) $item->Tax = number_format($product['tax'], 2, '.', '') * 100;
            $item->Total = number_format($product['final_price'] * $product['qty'], 2, '.', '') * 100;
            $request->Items->LineItem[] = $item;
            $invoiceDesc .= $product['name'] . ', ';
        }
        $invoiceDesc = substr($invoiceDesc, 0, -2);
        if(strlen($invoiceDesc) > 64) $invoiceDesc = substr($invoiceDesc , 0 , 61) . '...';

        $request->Payment->TotalAmount = $amount;
        $request->Payment->InvoiceNumber = $merchantRef;
        $request->Payment->InvoiceDescription = $invoiceDesc;

        // Calculate the next expected order id
        $last_order_id = $db->Execute("select * from " . TABLE_ORDERS . " order by orders_id desc limit 1");
        $new_order_id = $last_order_id->fields['orders_id'];
        $new_order_id = ($new_order_id + 1);
        // it's not so accurate but it's better than nothing I think.
        $new_order_id = 'Order Number: ' . (string) $new_order_id;

        $request->Payment->InvoiceReference = $new_order_id;
        $request->Payment->CurrencyCode = $order->info['currency'];

        $transact_url = str_replace('&amp;', '&', $transact_url);
        $request->RedirectUrl = $transact_url;
        $request->CancelUrl   = $transact_url;
        $request->Method = 'ProcessPayment';
        $request->TransactionType = 'Purchase';

        // Call RapidAPI
        $result = $service->CreateAccessCode($request);

        if (! $result) {
            return '<font style="font-weight:bold; color:red">API Username/Password is wrong for eWAY configuration.</a>';
        }

        // Check if any error returns
        if (isset($result->Errors)) {
            // Get Error Messages from Error Code. Error Code Mappings are in the Config.ini file
            $ErrorArray = explode(",", $result->Errors);
            $lblError = "";
            foreach ( $ErrorArray as $error ) {
                $error = $service->getMessage($error);
                $lblError .= $error . "<br />\n";
            }

            $messageStack->add_session('checkout_payment', $lblError, 'error');
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
            return false;
        }

        // close previous form
        $process_button_string = '
            </form>
            <script>
                function submiteWAYonce() {
                  var button = document.getElementById("btn_submit");
                  button.style.cursor="wait";
                  button.disabled = true;
                  return false;
                }
            </script>
            <form action="' . $result->FormActionURL . '" method="post" onsubmit="submiteWAYonce();">';
        $this->form_action_url = $result->FormActionURL;
        $process_button_string .= zen_draw_hidden_field('EWAY_ACCESSCODE', $result->AccessCode);

        $payment_type = MODULE_PAYMENT_EWAYRAPID_PAYMENTTYPE;
        if ($payment_type == 'paypal' || $payment_type == 'masterpass' || $payment_type == 'vme') {
            $process_button_string .= zen_draw_hidden_field('EWAY_PAYMENTTYPE', $payment_type);
        } else {
            $cc_string = '
            <label for="eway_rapid-cc-ownerf" class="inputLabelPayment">Cardholder Name:</label><input type="text" name="EWAY_CARDNAME" value="" id="eway_rapid-cc-ownerf" autocomplete="off" /><br class="clearBoth" />
            <label for="eway_rapid-cc-number" class="inputLabelPayment">Credit Card Number:</label><input type="text" name="EWAY_CARDNUMBER" id="eway_rapid-cc-number" autocomplete="off" /><br class="clearBoth" />
            <label for="eway_rapid-cc-expires-month" class="inputLabelPayment">Credit Card Expiry Date:</label>
            <select name="EWAY_CARDEXPIRYMONTH" id="eway_rapid-cc-expires-month">
              <option value="01">January - (01)</option>
              <option value="02">February - (02)</option>
              <option value="03">March - (03)</option>
              <option value="04">April - (04)</option>
              <option value="05">May - (05)</option>
              <option value="06">June - (06)</option>
              <option value="07">July - (07)</option>
              <option value="08">August - (08)</option>
              <option value="09">September - (09)</option>
              <option value="10">October - (10)</option>
              <option value="11">November - (11)</option>
              <option value="12">December - (12)</option>
            </select>
            &nbsp;<select name="EWAY_CARDEXPIRYYEAR" id="eway_rapid-cc-expires-year">
              <option value="13">2013</option>
              <option value="14">2014</option>
              <option value="15">2015</option>
              <option value="16">2016</option>
              <option value="17">2017</option>
              <option value="18">2018</option>
              <option value="19">2019</option>
              <option value="20">2020</option>
            </select>
            <br class="clearBoth" />
            <label for="eway_rapid-cc-cvv" class="inputLabelPayment">CVV</label><input type="text" name="EWAY_CARDCVN" size="4" maxlength="4" id="eway_rapid-cc-cvv" autocomplete="off" /><br class="clearBoth" />
            ';
            if ($payment_type == 'creditcard') {
                $process_button_string .= $cc_string;
            } else {
                // USER_PICK
                $process_button_string .= '
                <label class="inputLabelPayment">Select Payment Option:</label>
                <select name="EWAY_PAYMENTTYPE" onchange="javascript:ChoosePaymentOption(this.options[this.options.selectedIndex].value)">
                  <option value="creditcard">Credit Card</option>
                  <option value="paypal">PayPal</option>
                  <option value="masterpass">MasterPass</option>
                  <option value="vme">V.me By Visa</option>
                </select>
                <br class="clearBoth" />
                <script>
                function ChoosePaymentOption(v) {
                    if (v != "creditcard") {
                        document.getElementById("creditcard_info").style.display = "none";
                    } else {
                        document.getElementById("creditcard_info").style.display = "block";
                    }
                }
                </script>
                <div id="creditcard_info">
                ' . $cc_string . '</div>';

            }
        }

        return $process_button_string;
    }

    function before_process() {
        global $order, $order_totals, $db, $messageStack;
        if ( (isset($_GET['referer']) && $_GET['referer'] == 'eway_rapid') || (isset($_GET['amp;referer']) && $_GET['amp;referer'] == 'eway_rapid') ) {
            require_once(realpath(dirname(__FILE__).'/eway_rapid/lib/eWAY/RapidAPI.php'));
            $__username = MODULE_PAYMENT_EWAYRAPID_USERNAME;
            $__password = MODULE_PAYMENT_EWAYRAPID_PASSWORD;
            $__sandbox = ( MODULE_PAYMENT_EWAYRAPID_MODE == 'True' ) ? true : false;

            $eway_params = array();
            if ($__sandbox) $eway_params['sandbox'] = true;
            $service = new eWAY\RapidAPI($__username, $__password, $eway_params);

            $request = new eWAY\GetAccessCodeResultRequest();
            if ( isset($_GET['amp;AccessCode']) ) {
                $request->AccessCode = $_GET['amp;AccessCode'];
            } else {
                $request->AccessCode = $_GET['AccessCode'];
            }
            $result = $service->GetAccessCodeResult($request);

            $isError = false;
            // Check if any error returns
            if (isset($result->Errors)) {
                $ErrorArray = explode(",", $result->Errors);
                $lblError = "";
                $isError = true;
                foreach ( $ErrorArray as $error ) {
                    $error = $service->getMessage($error);
                    $lblError .= $error . "<br />\n";
                }
            }
            if (! $isError) {
                if (! $result->TransactionStatus) {
                    $isError = true;
                    $lblError = "Payment Declined - " . $result->ResponseCode;
                }
            }

            if ($isError) {
                $messageStack->add_session('checkout_payment', $lblError, 'error');
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
                return false;
            }

            // success
            $this->transaction_id = $result->TransactionID;
            $this->auth_code = $result->ResponseCode;
            $_SESSION['eway_transaction_passed'] = true;
            return true;
        } else {
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
        }
    }

    function after_process() {
        global $insert_id, $db;

        if ($_SESSION['eway_transaction_passed'] != true) {
          unset($_SESSION['eway_transaction_passed']);
          return false;
        } else {
          unset($_SESSION['eway_transaction_passed']);

          $commentString = "eway TransactionID: " . $this->transaction_id;
          $sql_data_array= array(array('fieldName'=>'orders_id', 'value'=>$insert_id, 'type'=>'integer'),
                           array('fieldName'=>'orders_status_id', 'value' => $this->order_status, 'type'=>'integer'),
                           array('fieldName'=>'date_added', 'value'=>'now()', 'type'=>'noquotestring'),
                           array('fieldName'=>'customer_notified', 'value'=>0, 'type'=>'integer'),
                           array('fieldName'=>'comments', 'value'=>$commentString, 'type'=>'string'));
          $db->perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        }
    }

    function get_error() {
        $error = array('title' => 'Error!',
                       'error' => stripslashes(urldecode($_GET['error'])));
        return $error;
    }

    function check() {
        global $db;
        if (!isset($this->_check)) {
            $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_EWAYRAPID_STATUS'");
            $this->_check = !$check_query->EOF;
        }
        return $this->_check;
    }

    function install() {
        global $db, $messageStack;

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable eWAY Payment Module', 'MODULE_PAYMENT_EWAYRAPID_STATUS', 'True', 'Do you want to authorize payment through eWAY Payment?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Test Mode', 'MODULE_PAYMENT_EWAYRAPID_MODE', 'True', 'You can set to go to testing mode here.', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('eWAY API Key', 'MODULE_PAYMENT_EWAYRAPID_USERNAME', '', 'Your eWAY API Key registered when you join eWAY.', '6', '0', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('eWay Password', 'MODULE_PAYMENT_EWAYRAPID_PASSWORD', '', 'Your eWAY password registered when you join eWAY.', '6', '0', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('eWAY Payment Type', 'MODULE_PAYMENT_EWAYRAPID_PAYMENTTYPE', 'USER_PICK', 'The type of payment you are processing (new). USER_PICK will show options to customer.', '6', '0', 'zen_cfg_select_option(array(\'USER_PICK\', \'creditcard\', \'paypal\', \'masterpass\', \'vme\'), ', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_EWAYRAPID_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_EWAYRAPID_SORT_ORDER', '1', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      global $db;
      $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_PAYMENT_EWAYRAPID_STATUS', 'MODULE_PAYMENT_EWAYRAPID_MODE', 'MODULE_PAYMENT_EWAYRAPID_USERNAME', 'MODULE_PAYMENT_EWAYRAPID_PASSWORD', 'MODULE_PAYMENT_EWAYRAPID_PAYMENTTYPE', 'MODULE_PAYMENT_EWAYRAPID_ORDER_STATUS_ID', 'MODULE_PAYMENT_EWAYRAPID_SORT_ORDER');
    }

}