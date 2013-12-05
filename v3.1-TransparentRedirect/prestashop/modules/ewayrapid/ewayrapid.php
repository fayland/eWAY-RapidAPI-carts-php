<?php

if (!defined('_PS_VERSION_'))
	exit;

class Ewayrapid extends PaymentModule {
	protected $_html = '';
	protected $_postErrors = array();

	public function __construct() {
	    $this->name = 'ewayrapid';
		$this->tab = 'payments_gateways';
		$this->version = '3.1';
		$this->author = 'eWAY';

		parent::__construct();

        $this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('eWAY Payments');
		$this->description = $this->l('Accepts payments with eWAY.');
		$this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');

		$this->currencies = true;
		$this->currencies_mode = 'radio';
	}

	public function install() {
		if (!parent::install()
			OR !Configuration::updateValue('EWAYRAPID_USERNAME', '')
			OR !Configuration::updateValue('EWAYRAPID_PASSWORD', '')
			OR !Configuration::updateValue('EWAYRAPID_SANDBOX', 1)
			OR !Configuration::updateValue('EWAYRAPID_PAYMENTTYPE', '')
			OR !$this->registerHook('payment')
            OR !$this->registerHook('paymentReturn'))
			return false;
		return true;
	}

	public function uninstall() {
		if (!Configuration::deleteByName('EWAYRAPID_USERNAME')
			OR !Configuration::deleteByName('EWAYRAPID_PASSWORD')
			OR !Configuration::deleteByName('EWAYRAPID_SANDBOX')
			OR !Configuration::deleteByName('EWAYRAPID_PAYMENTTYPE')
			OR !parent::uninstall())
			return false;
		return true;
	}

	public function getContent() {
		$this->_postProcess();

		$conf = Configuration::getMultiple(array('EWAYRAPID_SANDBOX', 'EWAYRAPID_USERNAME', 'EWAY_PASSWORD', 'EWAYRAPID_PAYMENTTYPE'));
		$data = array();

		$data['sandbox'] = isset($_POST['sandbox']) ? $_POST['sandbox'] : isset($conf['EWAYRAPID_SANDBOX']) ? $conf['EWAYRAPID_SANDBOX'] : 0;
		$data['username'] = isset($_POST['username']) ? $_POST['username'] : isset($conf['EWAYRAPID_USERNAME']) ? $conf['EWAYRAPID_USERNAME'] : '';
		$data['password'] = isset($_POST['password']) ? $_POST['password'] : isset($conf['EWAYRAPID_PASSWORD']) ? $conf['EWAYRAPID_PASSWORD'] : '';
		$data['paymenttype'] = isset($_POST['paymenttype']) ? $_POST['paymenttype'] : isset($conf['EWAYRAPID_PAYMENTTYPE']) ? $conf['EWAYRAPID_PAYMENTTYPE'] : '';

		$this->context->smarty->assign($data);
		return $this->display(__FILE__, 'views/back_office.tpl');
	}

	private function _postProcess()	{
		if (Tools::isSubmit('submitRapideWAY')) {
			if (!Tools::getValue('username') || !Tools::getValue('password')) {
				$this->_errors[] = $this->l('username/password cannot be empty');

				$this->context->smarty->assign('eWAY_save_fail', true);
				$this->context->smarty->assign('eWAY_errors', $this->_errors);
			} else {
				Configuration::updateValue('EWAYRAPID_SANDBOX', (int) Tools::getValue('sandbox'));
				Configuration::updateValue('EWAYRAPID_USERNAME', trim(Tools::getValue('username')));
				Configuration::updateValue('EWAYRAPID_PASSWORD', trim(Tools::getValue('password')));
				Configuration::updateValue('EWAYRAPID_PAYMENTTYPE', trim(Tools::getValue('paymenttype')));

				$this->context->smarty->assign('eWAY_save_success', true);
			}
		}
	}

	public function hookPayment($params) {
		if (!$this->active)
			return ;

		/* Load objects */
        $address = new Address((int)($params['cart']->id_address_invoice));
        $shipping_address = new Address((int)($params['cart']->id_address_delivery));
        $customer = new Customer((int)($params['cart']->id_customer));
        $currency = new Currency((int)($params['cart']->id_currency));

        $TotalAmount = number_format($params['cart']->getOrderTotal(), 2, '.', '') * 100;
        $RedirectUrl = (Configuration::get('PS_SSL_ENABLED') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/'.$this->name.'/eway.php';

        include_once(_PS_MODULE_DIR_.'/ewayrapid/lib/eWAY/RapidAPI.php');

		$__sandbox = Configuration::get('EWAYRAPID_SANDBOX');
		$__username = Configuration::get('EWAYRAPID_USERNAME');
		$__password = Configuration::get('EWAYRAPID_PASSWORD');
		$__paymenttype = Configuration::get('EWAYRAPID_PAYMENTTYPE');

        // Create Responsive Shared Page Request Object
    	$request = new eWAY\CreateAccessCodesSharedRequest();

        $countryObj = new Country((int)($address->id_country), Configuration::get('PS_LANG_DEFAULT'));
        $state = '';
        if ($address->id_state)
            $state = new State((int)($address->id_state));
            $state = $state->iso_code;
        $request->Customer->Reference = 'Prestashop';
        $request->Customer->Title = 'Mr.';
        $request->Customer->FirstName = strval($address->firstname);
        $request->Customer->LastName = strval($address->lastname);
        $request->Customer->CompanyName = '';
        $request->Customer->JobDescription = '';
        $request->Customer->Street1 = strval($address->address1);
        $request->Customer->Street2 = strval($address->address2);
        $request->Customer->City = strval($address->city);
        $request->Customer->State = strval($state);
        $request->Customer->PostalCode = strval($address->postcode);
        $request->Customer->Country = strtolower(strval($countryObj->iso_code));
        $request->Customer->Email = $customer->email;
        $request->Customer->Phone = $address->phone;
        $request->Customer->Mobile = $address->phone_mobile;

        // require field
        $countryObj = new Country((int)($shipping_address->id_country), Configuration::get('PS_LANG_DEFAULT'));
        $state = '';
        if ($address->id_state)
            $state = new State((int)($shipping_address->id_state));
            $state = $state->iso_code;
        $request->ShippingAddress->FirstName = strval($shipping_address->firstname);
        $request->ShippingAddress->LastName = strval($shipping_address->lastname);
        $request->ShippingAddress->Street1 = strval($shipping_address->address1);
        $request->ShippingAddress->Street2 = strval($shipping_address->address2);
        $request->ShippingAddress->City = strval($shipping_address->city);
        $request->ShippingAddress->State = strval($state);
        $request->ShippingAddress->PostalCode = strval($shipping_address->postcode);
        $request->ShippingAddress->Country = strtolower(strval($countryObj->iso_code));
        $request->ShippingAddress->Email = $customer->email;
        $request->ShippingAddress->Phone = $shipping_address->phone;
        $request->ShippingAddress->ShippingMethod = "Unknown";

        $invoiceDesc = '';
        $products = $params['cart']->getProducts();
        foreach ($products as $product) {
            $item = new eWAY\LineItem();
            $item->SKU = $product['id_product'];
            $item->Description = $product['name'];
            $item->Quantity = $product['cart_quantity'];
            $item->UnitCost = number_format($product['price_wt'], 2, '.', '') * 100;
            if (isset($product['ecotax'])) $item->Tax = number_format($product['ecotax'], 2, '.', '') * 100;
            $item->Total = number_format($product['total_wt'], 2, '.', '') * 100;
            $request->Items->LineItem[] = $item;
            $invoiceDesc .= $product['name'] . ', ';
        }
        $invoiceDesc = substr($invoiceDesc, 0, -2);
        if(strlen($invoiceDesc) > 64) $invoiceDesc = substr($invoiceDesc , 0 , 61) . '...';

        $opt1 = new eWAY\Option();
        $opt1->Value = (int)($params['cart']->id).'_'.date('YmdHis').'_'.$params['cart']->secure_key;
        $request->Options->Option[0]= $opt1;

        $request->Payment->TotalAmount = $TotalAmount;
        $request->Payment->InvoiceNumber = '';
        $request->Payment->InvoiceDescription = $invoiceDesc;
        $request->Payment->InvoiceReference = '';
        $request->Payment->CurrencyCode = $currency->iso_code;

        $request->RedirectUrl = $RedirectUrl;
	    $request->CancelUrl   = $RedirectUrl;
	    $request->Method = 'ProcessPayment';
	    $request->TransactionType = 'Purchase';

	    // Call RapidAPI
	    $eway_params = array();
	    if ($__sandbox) $eway_params['sandbox'] = true;
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

	        $this->response['Response Reason Text'] = $lblError;
            return $sale;
	    }

        $years = Tools::dateYears();
        $months = Tools::dateMonths();
        $smarty = $this->context->smarty;
		$smarty->assign(array(
            'AccessCode' => $result->AccessCode,
            'gateway_url'  => $result->FormActionURL,
            'years'  => $years,
            'months' => $months,
            'payment_type' => $__paymenttype
        ));

		return $this->display(__FILE__, 'views/hook_payment.tpl');
	}

	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return ;

		return $this->display(__FILE__, 'views/confirmation.tpl');
	}

	public function GetAccessCodeResult() {
	    if (! $_REQUEST['AccessCode']) {
	        Tools::redirect('order.php');
	        return false;
	    }

//        error_reporting(E_ALL);
//        ini_set("display_errors", 1);

	    include_once(_PS_MODULE_DIR_.'/ewayrapid/lib/eWAY/RapidAPI.php');

		$__sandbox = Configuration::get('EWAYRAPID_SANDBOX');
		$__username = Configuration::get('EWAYRAPID_USERNAME');
		$__password = Configuration::get('EWAYRAPID_PASSWORD');

		// Call RapidAPI
	    $eway_params = array();
	    if ($__sandbox) $eway_params['sandbox'] = true;
	    $service = new eWAY\RapidAPI($__username, $__password, $eway_params);

	    $request = new eWAY\GetAccessCodeResultRequest();
        $request->AccessCode = $_REQUEST['AccessCode'];
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
            $smarty = $this->context->smarty;
			$smarty->assign('errors', array('[eWAY] ' . $lblError));
			$_SERVER['HTTP_REFERER'] = 'order.php?step=3';
			$smarty->display(_PS_THEME_DIR_ . 'errors.tpl');
            return false;
        }

        $Option1 = $result->Options[0]->Value;
        $id_cart = (int) (substr($Option1, 0, strpos($Option1, '_')));
        if (_PS_VERSION_ >= 1.5)
            Context::getContext()->cart = new Cart((int)$id_cart);
        $cart = Context::getContext()->cart;
        $secure_cart = explode('_', $Option1);

        $customer = new Customer((int)$cart->id_customer);

        $this->validateOrder($cart->id, Configuration::get('PS_OS_PAYMENT'), $result->TotalAmount / 100, $this->displayName, $this->l('eWAY Transaction ID: ') . $result->TransactionID, array(), NULL, false, $customer->secure_key);

        $confirmurl = 'index.php?controller=order-confirmation&';
		if (_PS_VERSION_ < '1.5')
			$confirmurl = 'order-confirmation.php?';
		Tools::redirect($confirmurl.'id_module='.(int)$this->id.'&id_cart='.
			(int)$cart->id.'&key='.$customer->secure_key);
	}

}
