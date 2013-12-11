<?php
class ControllerPaymentEway extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('payment/eway');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
			$this->model_setting_setting->editSetting('eway', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
            $this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

        //Help array here
        $helplist = array('heading_title', 'text_enabled', 'text_disabled', 'text_all_zones', 'text_none', 'text_yes', 'text_no', 'text_authorization', 'entry_test', 'entry_payment_type', 'entry_transaction', 'entry_order_status', 'entry_geo_zone', 'entry_status', 'entry_username', 'entry_password', 'help_testmode', 'help_username', 'help_password', 'help_ewaystatus', 'help_setorderstatus', 'help_sort_order', 'help_payment_type', 'entry_sort_order', 'button_save', 'button_cancel', 'tab_general' );
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
			'href'      => $this->url->link('payment/eway', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);

        $this->data['action'] = $this->url->link('payment/eway', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

		foreach (array('eway_payment_gateway', 'eway_test', 'eway_payment_type', 'eway_transaction', 'eway_standard_geo_zone_id', 'eway_order_status_id', 'eway_username', 'eway_password', 'eway_status', 'eway_sort_order') as $vk) {
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

		$this->template = 'payment/eway.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);

		$this->response->setOutput($this->render(TRUE), $this->config->get('config_compression'));
	}

	public function install() {
        $this->load->model('payment/eway');
        $this->model_payment_eway->install();
    }

    public function uninstall() {
        $this->load->model('payment/eway');
        $this->model_payment_eway->uninstall();
    }

    public function orderAction() {
        $this->load->model('payment/eway');

        $eway_order = $this->model_payment_eway->getOrder($this->request->get['order_id']);
        if ($eway_order) {
            $this->data['eway_order'] = $eway_order;
            $this->data['token'] = $this->session->data['token'];

            $this->data['order_id'] = $this->request->get['order_id'];

            $this->data['refund_link'] = $this->url->link('payment/eway/refund', 'token=' . $this->session->data['token'], 'SSL');

            $this->template = 'payment/eway_order.tpl';
            $this->response->setOutput($this->render());
        }
    }

	private function validate() {
		if (!$this->user->hasPermission('modify', 'payment/eway')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		if (!$this->request->post['eway_username']) {
			$this->error['username'] = $this->language->get('error_username');
		}
		if (!$this->request->post['eway_password']) {
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