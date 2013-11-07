<?php

/**
 * ewaysharedpage.php payment module class for eWAY Shared Page
 *
 * @package paymentMethod
 * @copyright Copyright 2013 eWAY
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version 3.1.0
 */

class ewaysharedpage extends base {
    var $code, $title, $description, $enabled, $auth_code, $transaction_id;

    var $enableDirectPayment = false;

    function ewaysharedpage() {
      global $order;

      $this->code = 'ewaysharedpage';
      $this->codeTitle = MODULE_PAYMENT_EWAYSHAREDPAGE_TEXT_TITLE;
      $this->codeVersion = '3.1.0';
      $this->enableDirectPayment = false;
      $this->title = MODULE_PAYMENT_EWAYSHAREDPAGE_TEXT_TITLE;

      if (MODULE_PAYMENT_EWAYSHAREDPAGE_STATUS == 'True' && MODULE_PAYMENT_EWAYSHAREDPAGE_USERNAME== '') {
        $this->title .= '<span class="alert"> (Not Configured)</span>';
      } elseif (MODULE_PAYMENT_EWAYSHAREDPAGE_MODE == 'True') {
        $this->title .= '<span class="alert"> (in Testing mode)</span>';
      }

      $this->public_title = MODULE_PAYMENT_EWAYSHAREDPAGE_TEXT_PUBLIC_TITLE;
      $this->description = MODULE_PAYMENT_EWAYSHAREDPAGE_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_PAYMENT_EWAYSHAREDPAGE_SORT_ORDER;
      $this->enabled = ((MODULE_PAYMENT_EWAYSHAREDPAGE_STATUS == 'True') ? true : false);

      if ((int) MODULE_PAYMENT_EWAYSHAREDPAGE_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_EWAYSHAREDPAGE_ORDER_STATUS_ID;
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
        $transact_url = zen_href_link(FILENAME_CHECKOUT_PROCESS, 'referer=eway', 'SSL', true, false);
        $customerId = $_SESSION['customer_id'];				// customerId
        $merchantRef = $customerId."-".date("YmdHis");		// merchant reference

        require_once(realpath(dirname(__FILE__).'/ewaysharedpage/lib/eWAY/RapidAPI.php'));
        $__username = MODULE_PAYMENT_EWAYSHAREDPAGE_USERNAME;
        $__password = MODULE_PAYMENT_EWAYSHAREDPAGE_PASSWORD;
        $__sandbox = ( MODULE_PAYMENT_EWAYSHAREDPAGE_MODE == 'True' ) ? true : false;

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
            $item->UnitCost = $product['price'];
            if (isset($product['tax'])) $item->Tax = $product['tax'];
            $item->Total = $product['final_price'] * $product['qty'];
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

        $__logourl = MODULE_PAYMENT_EWAYSHAREDPAGE_LOGOURL;
        $__headertext = MODULE_PAYMENT_EWAYSHAREDPAGE_HEADERTEXT;
        if (strlen($__logourl)) $request->LogoUrl = $__logourl;
        $request->HeaderText = $__headertext;
        $request->CustomerReadOnly = true;

        // Call RapidAPI
        $result = $service->CreateAccessCodesShared($request);

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
        } else {
            $responseurl = $result->SharedPaymentUrl;
            return "<meta http-equiv='refresh' content='1;url=$responseurl'><a href='$responseurl'>You will be redirected to eWAY.</a>";
        }
    }

    function before_process() {
        global $order, $order_totals, $db, $messageStack;
        if ( (isset($_GET['referer']) && $_GET['referer'] == 'eway') || (isset($_GET['amp;referer']) && $_GET['amp;referer'] == 'eway') ) {

            require_once(realpath(dirname(__FILE__).'/ewaysharedpage/lib/eWAY/RapidAPI.php'));
            $__username = MODULE_PAYMENT_EWAYSHAREDPAGE_USERNAME;
            $__password = MODULE_PAYMENT_EWAYSHAREDPAGE_PASSWORD;
            $__sandbox = ( MODULE_PAYMENT_EWAYSHAREDPAGE_MODE == 'True' ) ? true : false;

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
            $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_EWAYSHAREDPAGE_STATUS'");
            $this->_check = !$check_query->EOF;
        }
        return $this->_check;
    }

    function install() {
        global $db, $messageStack;

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable eWAY Payment Module', 'MODULE_PAYMENT_EWAYSHAREDPAGE_STATUS', 'True', 'Do you want to authorize payment through eWAY Payment?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Sandox Mode', 'MODULE_PAYMENT_EWAYSHAREDPAGE_MODE', 'True', 'You can set to go to testing mode here.', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('eWAY API Key', 'MODULE_PAYMENT_EWAYSHAREDPAGE_USERNAME', '', 'Your eWAY API Key.', '6', '0', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('eWay Password', 'MODULE_PAYMENT_EWAYSHAREDPAGE_PASSWORD', '', 'Your eWAY API password.', '6', '0', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Logo Url', 'MODULE_PAYMENT_EWAYSHAREDPAGE_LOGOURL', '', 'logo on shared page (optional)', '6', '0', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Header Text', 'MODULE_PAYMENT_EWAYSHAREDPAGE_HEADERTEXT', '', 'header text on shared page (optional)', '6', '0', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_EWAYSHAREDPAGE_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_EWAYSHAREDPAGE_SORT_ORDER', '1', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      global $db;
      $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_PAYMENT_EWAYSHAREDPAGE_STATUS', 'MODULE_PAYMENT_EWAYSHAREDPAGE_MODE', 'MODULE_PAYMENT_EWAYSHAREDPAGE_USERNAME', 'MODULE_PAYMENT_EWAYSHAREDPAGE_PASSWORD', 'MODULE_PAYMENT_EWAYSHAREDPAGE_LOGOURL', 'MODULE_PAYMENT_EWAYSHAREDPAGE_HEADERTEXT', 'MODULE_PAYMENT_EWAYSHAREDPAGE_ORDER_STATUS_ID', 'MODULE_PAYMENT_EWAYSHAREDPAGE_SORT_ORDER');
    }

}