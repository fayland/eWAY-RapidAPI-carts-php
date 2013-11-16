<?php
class ControllerPaymentEwaysharedpage extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('payment/ewaysharedpage');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
			$this->model_setting_setting->editSetting('ewaysharedpage', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
            $this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

        //Help array here
        $helplist = array('heading_title', 'text_enabled', 'text_disabled', 'text_all_zones', 'text_none', 'text_yes', 'text_no', 'text_authorization', 'entry_test', 'entry_transaction', 'entry_order_status', 'entry_geo_zone', 'entry_status', 'entry_username', 'entry_password', 'help_testmode', 'help_username', 'help_password', 'help_ewaystatus', 'help_setorderstatus', 'help_sort_order', 'entry_sort_order', 'button_save', 'button_cancel', 'tab_general' );
        foreach( $helplist as $key ) {
            $this->data[$key] = $this->language->get($key);
        }

 		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}
		if (isset($this->error['username'])) {
			$this->data['error_username'] = $this->error['username'];
		} else {
			$this->data['error_username'] = '';
		}
		if (isset($this->error['password'])) {
			$this->data['error_password'] = $this->error['password'];
		} else {
			$this->data['error_password'] = '';
		}

        $this->data['breadcrumbs'] = array();
   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => false
   		);
   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_payment'),
			'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);
   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('payment/ewaysharedpage', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);

        $this->data['action'] = $this->url->link('payment/ewaysharedpage', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

		foreach (array('ewaysharedpage_payment_gateway', 'ewaysharedpage_test', 'ewaysharedpage_header_text', 'ewaysharedpage_logo_url', 'ewaysharedpage_transaction', 'ewaysharedpage_standard_geo_zone_id', 'ewaysharedpage_order_status_id', 'ewaysharedpage_username', 'ewaysharedpage_password', 'ewaysharedpage_status', 'ewaysharedpage_sort_order') as $vk) {
			if (isset($this->request->post[$vk])) {
				$this->data[$vk] = $this->request->post[$vk];
			} else {
				$this->data[$vk] = $this->config->get($vk);
			}
		}

		$this->load->model('localisation/geo_zone');
		$this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$this->load->model('localisation/order_status');
		$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$this->template = 'payment/ewaysharedpage.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);

		$this->response->setOutput($this->render(TRUE), $this->config->get('config_compression'));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'payment/ewaysharedpage')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		if (!$this->request->post['ewaysharedpage_username']) {
			$this->error['username'] = $this->language->get('error_username');
		}
		if (!$this->request->post['ewaysharedpage_password']) {
			$this->error['password'] = $this->language->get('error_password');
		}

		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}
?>