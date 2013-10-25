<?php

if (!defined('_PS_VERSION_'))
	exit;

class Ewayrapid extends PaymentModule
{
	private	$_html = '';
	private $_postErrors = array();

	public function __construct() {
	    $this->name = 'ewayrapid';
		$this->tab = 'payments_gateways';
		$this->version = '0.0.3';
		$this->author = 'eWAY';

		parent::__construct();

        $this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('eWAY Rapid 3.0');
		$this->description = $this->l('Accepts payments by eWAY Rapid 3.0.');
		$this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');

		$this->currencies = true;
		$this->currencies_mode = 'radio';
	}

	public function install() {
		if (!parent::install()
			OR !Configuration::updateValue('EWAY_USERNAME', '')
			OR !Configuration::updateValue('EWAY_PASSWORD', '')
			OR !Configuration::updateValue('EWAY_TESTMODE', 1)
			OR !$this->registerHook('payment')
            OR !$this->registerHook('paymentReturn'))
			return false;
		return true;
	}

	public function uninstall()
	{
		if (!Configuration::deleteByName('EWAY_USERNAME')
			OR !Configuration::deleteByName('EWAY_PASSWORD')
			OR !Configuration::deleteByName('EWAY_TESTMODE')
			OR !parent::uninstall())
			return false;
		return true;
	}

	public function getContent() {
		$this->_html = '<h2>eWAY Rapid 3.0</h2>';
		if (isset($_POST['submiteWAYRapidAPI'])) {
		    if (!isset($_POST['sandbox']))
				$_POST['sandbox'] = 1;
			if ($_POST['sandbox']!=1) {
				if (empty($_POST['username'])){ $this->_postErrors[] = $this->l('eWAY API Key is required.'); }
				if (empty($_POST['password'])){ $this->_postErrors[] = $this->l('eWAY Password is required.'); }
			}
			if (!sizeof($this->_postErrors)) {
				Configuration::updateValue('EWAY_USERNAME', strval($_POST['username']));
				Configuration::updateValue('EWAY_PASSWORD', strval($_POST['password']));
				Configuration::updateValue('EWAY_TESTMODE', intval($_POST['sandbox']));
				$this->displayConf();
			}
			else
				$this->displayErrors();
		}

		$this->displayeWAY();
		$this->displayFormSettings();
		return $this->_html;
	}

	public function displayConf() {
		$this->_html .= '
		<div class="conf confirm">
			<img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />
			'.$this->l('Settings updated').'
		</div>';
	}

	public function displayErrors() {
		$nbErrors = sizeof($this->_postErrors);
		$this->_html .= '
		<div class="alert error">
			<h3>'.($nbErrors > 1 ? $this->l('There are') : $this->l('There is')).' '.$nbErrors.' '.($nbErrors > 1 ? $this->l('errors') : $this->l('error')).'</h3>
			<ol>';
		foreach ($this->_postErrors AS $error)
			$this->_html .= '<li>'.$error.'</li>';
		$this->_html .= '
			</ol>
		</div>';
	}

	public function displayeWAY() {
		$this->_html .= '<img src="../modules/ewayrapid/eway.gif" style="float:left; margin-right:15px;" />
		<b>'.$this->l('This module allows you to accept payments by eWAY.').'</b><br /><br />
		'.$this->l('If the client chooses this payment mode, your eWAY account will be automatically credited.').'<br />
		'.$this->l('You need to configure your eWAY account first before using this module.').'
		<div style="clear:both;">&nbsp;</div>';
	}

	public function displayFormSettings() {
		$conf = Configuration::getMultiple(array('EWAY_USERNAME', 'EWAY_PASSWORD', 'EWAY_TESTMODE'));
		$username = array_key_exists('username', $_POST) ? $_POST['username'] : (array_key_exists('EWAY_USERNAME', $conf) ? $conf['EWAY_USERNAME'] : '');
		$password = array_key_exists('password', $_POST) ? $_POST['password'] : (array_key_exists('EWAY_PASSWORD', $conf) ? $conf['EWAY_PASSWORD'] : '');
		$sandbox = array_key_exists('sandbox', $_POST) ? $_POST['sandbox'] : (array_key_exists('EWAY_TESTMODE', $conf) ? $conf['EWAY_TESTMODE'] : '');

		$this->_html .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post" style="clear: both;">
		<fieldset>
			<legend><img src="../img/admin/contact.gif" />'.$this->l('Settings').'</legend>
			<label>'.$this->l('Test Account').'</label>
			<div class="margin-form">
				<input type="radio" name="sandbox" value="1" '.($sandbox ? 'checked="checked"' : '').' /> '.$this->l('Yes').'
				<input type="radio" name="sandbox" value="0" '.(!$sandbox ? 'checked="checked"' : '').' /> '.$this->l('No').'
			</div>
			<label>'.$this->l('eWAY API Key').'</label>
			<div class="margin-form"><input type="text" size="33" name="username" id="username" value="'.htmlentities($username, ENT_COMPAT, 'UTF-8').'" /> Your eWAY API Key registered when you join eWAY.</div>
			<label>'.$this->l('eWAY Password').'</label>
			<div class="margin-form"><input type="text" size="33" name="password" id="password" value="'.htmlentities($password, ENT_COMPAT, 'UTF-8').'" /> Your eWAY password registered when you join eWAY.</div>
			<br /><center><input type="submit" name="submiteWAYRapidAPI" value="'.$this->l('Update settings').'" class="button" /></center>
		</fieldset>
		</form><br /><br />
		<fieldset class="width3">
			<legend><img src="../img/admin/warning.gif" />'.$this->l('Information').'</legend>
			'.$this->l('In order to use your eWAY payment module, you have to configure your eWAY account (sandbox account as well as live account). Log in to eWAY and follow the instructions.').'<br /><br />
		</fieldset>';
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

        require_once(realpath(dirname(__FILE__).'/RapidAPI.php'));

        $username = Configuration::get('EWAY_USERNAME');
        $password = Configuration::get('EWAY_PASSWORD');
        $livemode = Configuration::get('EWAY_TESTMODE') ? false : true;
        $eway_service = new RapidAPI($livemode, $username, $password);

        //Create AccessCode Request Object
        $request = new CreateAccessCodeRequest();

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
            $item = new EwayLineItem();
            $item->SKU = $product['id_product'];
            $item->Description = $product['name'];
            $request->Items->LineItem[] = $item;
            $invoiceDesc .= $product['name'] . ', ';
        }
        $invoiceDesc = substr($invoiceDesc, 0, -2);
        if(strlen($invoiceDesc) > 64) $invoiceDesc = substr($invoiceDesc , 0 , 61) . '...';

        $opt1 = new EwayOption();
        $opt1->Value = (int)($params['cart']->id).'_'.date('YmdHis').'_'.$params['cart']->secure_key;
        $request->Options->Option[0]= $opt1;

        $request->Payment->TotalAmount = $TotalAmount;
        $request->Payment->InvoiceNumber = '';
        $request->Payment->InvoiceDescription = $invoiceDesc;
        $request->Payment->InvoiceReference = '';
        $request->Payment->CurrencyCode = $currency->iso_code;

        $request->RedirectUrl = $RedirectUrl;
        $request->Method = 'ProcessPayment';

        //Call RapidAPI
        $result = $eway_service->CreateAccessCode($request);

        if (isset($result->Errors)) {
            //Get Error Messages from Error Code. Error Code Mappings are in the Config.ini file
            $ErrorArray = explode(",", $result->Errors);
            $lblError = "";
            foreach ( $ErrorArray as $error ) {
                if (isset($eway_service->APIConfig[$error]))
                    $lblError .= $error." ".$eway_service->APIConfig[$error]."<br>";
                else
                    $lblError .= $error;
            }
        }

        if (isset($lblError)) {
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
        ));

		return $this->display(__FILE__, 'ewayrapid.tpl');
	}

	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return ;

		return $this->display(__FILE__, 'confirmation.tpl');
	}

	public function afterRedirect() {
	    if (! $_REQUEST['AccessCode']) {
	        Tools::redirect('order.php');
	        return false;
	    }

//        error_reporting(E_ALL);
//        ini_set("display_errors", 1);

        require_once(realpath(dirname(__FILE__).'/RapidAPI.php'));
        $username = Configuration::get('EWAY_USERNAME');
        $password = Configuration::get('EWAY_PASSWORD');
        $livemode = Configuration::get('EWAY_TESTMODE') ? false : true;
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
                $error = trim($error);
                $lblError .= $eway_service->APIConfig[$error]."<br>";
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
			$smarty->assign('errors', array('[eWAY] '.$lblError));
			$_SERVER['HTTP_REFERER'] = self::getHttpHost(true, true).__PS_BASE_URI__.'order.php?step=3';
			$smarty->display(_PS_THEME_DIR_.'errors.tpl');
            return false;
        }

        $Option1 = $result->Options->Option[0]->Value;
        $id_cart = (int)(substr($Option1, 0, strpos($Option1, '_')));
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

    // Retro compatibility with 1.2.5
	static private function getHttpHost($http = false, $entities = false)
	{
		$host = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST']);

		if ($entities)
			$host = htmlspecialchars($host, ENT_COMPAT, 'UTF-8');
		if ($http)
			$host = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').$host;

		return $host;
	}

}
