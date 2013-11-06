<?php

if (!defined('_PS_VERSION_'))
	exit;

class Ewaysharedpage extends PaymentModule {
	protected $_html = '';
	protected $_errors = array();

	public function __construct() {
		$this->name = 'ewaysharedpage';
		$this->tab = 'payments_gateways';
		$this->version = '3.1';

		$this->currencies = true;
		$this->currencies_mode = 'radio';

        parent::__construct();

        $this->displayName = $this->l('Eway');
        $this->description = $this->l('Accepts payments with eWAY Responsive Shared Page');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
	}

	public function install() {
		if (! parent::install()
			OR !$this->registerHook('payment')
			OR !$this->registerHook('paymentReturn'))
			return false;
		return true;
	}

	public function uninstall() {
		if (!Configuration::deleteByName('EWAY_SANDBOX')
			OR !Configuration::deleteByName('EWAY_USERNAME')
			OR !Configuration::deleteByName('EWAY_PASSWORD')
			OR !Configuration::deleteByName('EWAY_LOGOURL')
			OR !Configuration::deleteByName('EWAY_HEADERTEXT')
			OR !parent::uninstall())
			return false;
		return true;
	}

	public function getContent() {
		error_log('call getCOn');
		$this->_postProcess();

		$conf = Configuration::getMultiple(array('EWAY_SANDBOX', 'EWAY_USERNAME', 'EWAY_PASSWORD', 'EWAY_LOGOURL','EWAY_HEADERTEXT'));
		$data = array();

		$data['sandbox'] = isset($_POST['sandbox']) ? $_POST['sandbox'] : isset($conf['EWAY_SANDBOX']) ? $conf['EWAY_SANDBOX'] : 0;
		$data['username'] = isset($_POST['username']) ? $_POST['username'] : isset($conf['EWAY_USERNAME']) ? $conf['EWAY_USERNAME'] : '';
		$data['password'] = isset($_POST['password']) ? $_POST['username'] : isset($conf['EWAY_PASSWORD']) ? $conf['EWAY_PASSWORD'] : '';
		$data['logourl'] = isset($_POST['logourl']) ? $_POST['logourl'] : isset($conf['EWAY_LOGOURL']) ? $conf['EWAY_LOGOURL'] : '';
		$data['headertext'] = isset($_POST['headertext']) ? $_POST['headertext'] : isset($conf['EWAY_HEADERTEXT']) ? $conf['EWAY_HEADERTEXT'] : '';

		$this->context->smarty->assign($data);
		return $this->display(__FILE__, 'views/back_office.tpl');
	}

	private function _postProcess()	{
		if (Tools::isSubmit('submiteWAY')) {

			if (!Tools::getValue('username') || !Tools::getValue('password')) {
				$this->_errors[] = $this->l('username/password cannot be empty');

				$this->context->smarty->assign('eWAY_save_fail', true);
				$this->context->smarty->assign('eWAY_errors', $this->_errors);
			} else {
				Configuration::updateValue('EWAY_SANDBOX', (int) Tools::getValue('sandbox'));
				Configuration::updateValue('EWAY_USERNAME', trim(Tools::getValue('username')));
				Configuration::updateValue('EWAY_PASSWORD', trim(Tools::getValue('password')));
				Configuration::updateValue('EWAY_LOGOURL', trim(Tools::getValue('logourl')));
				Configuration::updateValue('EWAY_HEADERTEXT', trim(Tools::getValue('headertext')));

				$this->context->smarty->assign('eWAY_save_success', true);
			}
		}
	}

	public function hookPayment($params) {
		if (! $this->active)
			return ;

		error_log("call hookPayment..");
		return true;
	}

	public function hookPaymentReturn($params) {
		if (!$this->active)
			return ;

		error_log("call hookPaymentReturn..");
		return true;
	}

}
