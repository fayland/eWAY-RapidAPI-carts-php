<?php
class Gateway {
    private $_module;
    private $_basket;

    public function __construct($module = false, $basket = false) {
        $this->_session =& $GLOBALS['user'];

        $this->_module  = $module;
        $this->_basket =& $GLOBALS['cart']->basket;
    }

    public function transfer() {
        $transfer   = array(
            'method'    => 'post',
            'target'    => '_self',
            'submit'    => 'manual',
        );
        return $transfer;
    }

    public function repeatVariables() {
        return false;
    }

    public function fixedVariables() {
        return false;
    }

    ##################################################

    public function call() {
        return false;
    }

    public function process() {
        if (isset($_GET['AccessCode'])) {

            require_once(realpath(dirname(__FILE__).'/lib/eWAY/RapidAPI.php'));

            $__username = $this->_module['eway_username'];
            $__password = $this->_module['eway_password'];
            $eway_params = array();
            if ($this->_module['testMode']) $eway_params['sandbox'] = true;
            $service = new eWAY\RapidAPI($__username, $__password, $eway_params);

            $request = new eWAY\GetAccessCodeResultRequest();
            $request->AccessCode = $_GET['AccessCode'];

            // Call RapidAPI to get the result
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

            $cart_order_id = $result->Options[0]->Value;
            $order         = Order::getInstance();
            $order_summary = $order->getSummary($cart_order_id);

            $transData['customer_id']   = $order_summary["customer_id"];
            $transData['gateway']       = "eWAY";
            $transData['amount']        = sprintf("%.2f", $result->TotalAmount / 100);

            if ($isError) {
                $transData['status']    = "Failed";
                $transData['notes']     = "Payment unsuccessful. $lblError";
            } else {
                $transData['status']    = "Success";
                $transData['notes']     = "Payment was successful.";
                $transData['trans_id']  = $result->TransactionID;
                $order->orderStatus(Order::ORDER_PROCESS, $cart_order_id);
                $order->paymentStatus(Order::PAYMENT_SUCCESS, $cart_order_id);
            }
            $order->logTransaction($transData);

            if ($isError) {
                $GLOBALS['gui']->setError($lblError);
                // httpredir(currentPage(array('_g', 'type', 'cmd', 'module'), array('_a' => 'confirm')));
                httpredir(str_replace('modules/gateway/EwayRapid/','',$GLOBALS['storeURL'].'/index.php?_a=confirm'), '', 200);
            }
        }

        // do not contain AccessCode again
        // httpredir(currentPage(array('_g', 'type', 'cmd', 'module'), array('_a' => 'complete')));
        httpredir(str_replace('modules/gateway/EwayRapid/','',$GLOBALS['storeURL'].'/index.php?_a=complete'), '', 200);
    }

    private function formatMonth($val) {
        return $val." - ".strftime("%b", mktime(0,0,0,$val,1 ,2009));
    }

    public function form() {
        ## Show Expire Months
        $selectedMonth  = (isset($_POST['expirationMonth'])) ? $_POST['expirationMonth'] : date('m');
        for ($i=1;$i<=12;$i++) {
            $val = sprintf('%02d',$i);
            $smarty_data['card']['expire']['months'][]  = array(
                'selected'  => ($val == $selectedMonth) ? 'selected="selected"' : '',
                'value'     => $val,
                'display'   => $this->formatMonth($val),
            );
        }

        ## Show Expire Years
        $thisYear = date("Y");
        $maxYear = $thisYear + 10;
        $selectedYear = isset($_POST['expirationYear']) ? $_POST['expirationYear'] : ($thisYear+2);
        for($i=$thisYear;$i<=$maxYear;$i++) {
            $smarty_data['card']['expire']['years'][]   = array(
                'selected'  => ($i == $selecetdYear) ? 'selected="selected"' : '',
                'value'     => str_pad(substr($i,-2), 2, '0', STR_PAD_LEFT),
                'display'   => $i,
            );
        }
        $GLOBALS['smarty']->assign('CARD', $smarty_data['card']);

//        error_reporting(E_ALL);
//        ini_set("display_errors", 1);

        $url = $GLOBALS['storeURL'] . '/modules/gateway/EwayRapid/return.php';
        $amount = $this->_basket['total'];

        require_once(realpath(dirname(__FILE__).'/lib/eWAY/RapidAPI.php'));

        // Create AccessCode Request Object
        $request = new eWAY\CreateAccessCodesSharedRequest();

        $first_name = isset($_POST['firstName']) ? $_POST['firstName'] : $this->_basket['billing_address']['first_name'];
        $last_name  = isset($_POST['lastName']) ? $_POST['lastName'] : $this->_basket['billing_address']['last_name'];
        $addr1 = isset($_POST['addr1']) ? $_POST['addr1'] : $this->_basket['billing_address']['line1'];
        $addr2 = isset($_POST['addr2']) ? $_POST['addr2'] : $this->_basket['billing_address']['line2'];
        $city = isset($_POST['city']) ? $_POST['city'] : $this->_basket['billing_address']['town'];
        $state = isset($_POST['state']) ? $_POST['state'] : $this->_basket['billing_address']['state'];
        $postcode = isset($_POST['postcode']) ? $_POST['postcode'] : $this->_basket['billing_address']['postcode'];
        $countrycode = isset($_POST['country']) ? $_POST['country'] : $this->_basket['billing_address']['country_iso'];
        $cemail = isset($_POST['emailAddress']) ? $_POST['emailAddress'] : $this->_basket['billing_address']['email'];

        $request->Customer->Reference = 'cubecart';
        // Mr., Ms., Mrs., Miss, Dr., Sir., Prof.
        $title_array = array('Mr.', 'Ms.', 'Mrs.', 'Miss', 'Dr.', 'Sir.', 'Prof.');
        $user_title = (isset($this->_basket['billing_address']['title']) && in_array($this->_basket['billing_address']['title'], $title_array)) ? $this->_basket['billing_address']['title'] : 'Mr.';
        $request->Customer->Title = $user_title;
        $request->Customer->FirstName = strval($first_name);
        $request->Customer->LastName  = strval($last_name);
        $request->Customer->CompanyName = '';
        $request->Customer->JobDescription = '';
        $request->Customer->Street1 = strval($addr1);
        $request->Customer->Street2 = strval($addr2);
        $request->Customer->City = strval($city);
        $request->Customer->State = strval($state);
        $request->Customer->PostalCode = strval($postcode);
        $request->Customer->Country = strtolower($countrycode);
        $request->Customer->Email = $cemail;
        $request->Customer->Phone = $this->_basket['billing_address']['phone'];
        $request->Customer->Mobile = isset($this->_basket['customer']['mobile']) ? $this->_basket['customer']['mobile'] : '';

        // require field
        $request->ShippingAddress->FirstName = strval($this->_basket['delivery_address']['first_name']);
        $request->ShippingAddress->LastName = strval($this->_basket['delivery_address']['last_name']);
        $request->ShippingAddress->Street1 = strval($this->_basket['delivery_address']['line1']);
        $request->ShippingAddress->Street2 = strval($this->_basket['delivery_address']['line2']);
        $request->ShippingAddress->City = strval($this->_basket['delivery_address']['town']);
        $request->ShippingAddress->State = strval($this->_basket['delivery_address']['state']);
        $request->ShippingAddress->Country = strtolower($this->_basket['delivery_address']['country_iso']);
        $request->ShippingAddress->PostalCode = strval($this->_basket['delivery_address']['postcode']);
        $request->ShippingAddress->Email = isset($this->_basket['customer']['email']) ? $this->_basket['customer']['email'] : '';
        $request->ShippingAddress->Phone = isset($this->_basket['customer']['phone']) ? $this->_basket['customer']['phone'] : '';
        // Unknown, LowCost, DesignatedByCustomer, International, Military, NextDay, StorePickup, TwoDayService, ThreeDayService, Other
        $request->ShippingAddress->ShippingMethod = "Unknown";

        $invoiceDesc = '';
        foreach ($this->_basket['contents'] as $key => $product) {
            $item = new eWAY\LineItem();
            $item->SKU = $product['product_id'];
            $item->Description = $product['name'];
            $item->Quantity = $product['quantity'];
            $item->UnitCost = number_format($product['price'], 2, '.', '') * 100;
            if (isset($product['tax_each'])) $item->Tax = number_format($product['tax_each'], 2, '.', '') * 100;
            $item->Total = number_format($product['total_price_each'] * $product['quantity'], 2, '.', '') * 100;
            $request->Items->LineItem[] = $item;
            $invoiceDesc .= $product['name'] . ', ';
        }
        $invoiceDesc = substr($invoiceDesc, 0, -2);
        if(strlen($invoiceDesc) > 64) $invoiceDesc = substr($invoiceDesc , 0 , 61) . '...';

        $opt1 = new eWAY\Option();
        $opt1->Value = $this->_basket['cart_order_id'];
        $request->Options->Option[0]= $opt1;

        $request->Payment->TotalAmount = number_format($amount, 2, '.', '') * 100;
        $request->Payment->InvoiceNumber = '';
        $request->Payment->InvoiceDescription = $invoiceDesc;
        $request->Payment->InvoiceReference = '#' . $this->_basket['cart_order_id'];
        $request->Payment->CurrencyCode = $GLOBALS['config']->get('config', 'default_currency');

        $request->RedirectUrl = $url;
        $request->CancelUrl   = $url;
        $request->Method = 'ProcessPayment';
        $request->TransactionType = 'Purchase';

        // Call RapidAPI
        $__username = $this->_module['eway_username'];
        $__password = $this->_module['eway_password'];
        $eway_params = array();
        if ($this->_module['testMode']) $eway_params['sandbox'] = true;
        $service = new eWAY\RapidAPI($__username, $__password, $eway_params);
        $result = $service->CreateAccessCode($request);

        // Check if any error returns
        if(isset($result->Errors)) {
            // Get Error Messages from Error Code. Error Code Mappings are in the Config.ini file
            $ErrorArray = explode(",", $result->Errors);
            $lblError = "";
            foreach ( $ErrorArray as $error ) {
                $error = $service->getMessage($error);
                $lblError .= $error . "<br />\n";
            }
        }

        if (isset($lblError)) {
            $GLOBALS['smarty']->assign('error', $lblError);
        } else {
            $eway_payment_type = array();
            if ($this->_module['eway_payment_type_visa']) $eway_payment_type[] = 'visa';
            if ($this->_module['eway_payment_type_mastercard']) $eway_payment_type[] = 'mastercard';
            if ($this->_module['eway_payment_type_diners']) $eway_payment_type[] = 'diners';
            if ($this->_module['eway_payment_type_jcb']) $eway_payment_type[] = 'jcb';
            if ($this->_module['eway_payment_type_amex']) $eway_payment_type[] = 'amex';
            if ($this->_module['eway_payment_type_paypal']) $eway_payment_type[] = 'paypal';
            if ($this->_module['eway_payment_type_masterpass']) $eway_payment_type[] = 'masterpass';
            if ($this->_module['eway_payment_type_vme']) $eway_payment_type[] = 'vme';
            $GLOBALS['smarty']->assign('payment_type', $eway_payment_type);
            $GLOBALS['smarty']->assign('AccessCode', $result->AccessCode);
            $GLOBALS['smarty']->assign('FormActionURL', $result->FormActionURL);
            $GLOBALS['smarty']->assign('eWAY_images_url', $GLOBALS['storeURL'] . '/modules/gateway/EwayRapid/images');
        }

        ## Check for custom template for module in skin folder
        $file_name = 'form.tpl';
        $form_file = $GLOBALS['gui']->getCustomModuleSkin('gateway', dirname(__FILE__), $file_name);
        $GLOBALS['gui']->changeTemplateDir($form_file);
        $ret = $GLOBALS['smarty']->fetch($file_name);
        $GLOBALS['gui']->changeTemplateDir();
        return $ret;
    }
}