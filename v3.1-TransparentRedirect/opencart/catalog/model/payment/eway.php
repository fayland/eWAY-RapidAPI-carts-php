<?php
class ModelPaymentEway extends Model {
    public function getMethod($address) {
        $this->load->language('payment/eway');

        if ($this->config->get('eway_status')) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('eway_standard_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
            if (! $this->config->get('eway_standard_geo_zone_id')) {
                $status = true;
            } elseif ($query->num_rows) {
                $status = true;
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        $method_data = array();
        if ($status) {
            // use images
            $eway_payment_type = (array) $this->config->get('eway_payment_type');
            if (count($eway_payment_type) == 0) $eway_payment_type = array('creditcard', 'paypal', 'masterpass', 'vme');
            $images = array();

            if (in_array('visa', $eway_payment_type)) {
                $images[] = "<img src='catalog/view/theme/default/image/eway_creditcard_visa.png' height='30' />";
            }
            if (in_array('mastercard', $eway_payment_type)) {
                $images[] = "<img src='catalog/view/theme/default/image/eway_creditcard_master.png' height='30' />";
            }
            if (in_array('diners', $eway_payment_type)) {
                $images[] = "<img src='catalog/view/theme/default/image/eway_creditcard_diners.png' height='30' />";
            }
            if (in_array('jcb', $eway_payment_type)) {
                $images[] = "<img src='catalog/view/theme/default/image/eway_creditcard_jcb.png' height='30' />";
            }
            if (in_array('amex', $eway_payment_type)) {
                $images[] = "<img src='catalog/view/theme/default/image/eway_creditcard_amex.png' height='30' />";
            }
            if (in_array('paypal', $eway_payment_type)) {
                $images[] = "<img src='catalog/view/theme/default/image/eway_paypal.png' height='30' />";
            }
            if (in_array('masterpass', $eway_payment_type)) {
                $images[] = "<img src='catalog/view/theme/default/image/eway_masterpass.png' height='30' />";
            }
            if (in_array('vme', $eway_payment_type)) {
                $images[] = "<img src='catalog/view/theme/default/image/eway_vme.png' height='30' />";
            }

            $method_data = array(
                'code'       => 'eway',
                'title'      => implode(' ', $images),
                'sort_order' => $this->config->get('eway_sort_order')
            );
        }

        return $method_data;
    }

    public function addOrder($order_data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "eway_order` SET `order_id` = '" . (int) $order_data['order_id'] . "', `created` = NOW(), `modified` = NOW(), `debug_data` = '" . $this->db->escape($order_data['debug_data']) . "', `amount` = '" . (double) $order_data['amount'] . "', `transaction_id` = '" . $this->db->escape($order_data['transaction_id']) . "'");

        return $this->db->getLastId();
    }
}
?>