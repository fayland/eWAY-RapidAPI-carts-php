<?php
class Cart66EwayRapid extends Cart66GatewayAbstract {

  protected $_username;
  protected $_password;
  protected $_eway_mode;

  var $field_string;
  var $fields = array();
  var $response_string;
  var $response = array();

  public function __construct() {
    parent::__construct();

    // initialize error arrays
    $this->_errors = array();
    $this->_jqErrors = array();

    $this->clearErrors();

    $this->_username   = Cart66Setting::getValue('eway_username');
    $this->_password   = Cart66Setting::getValue('eway_password');
    $this->_eway_mode  = Cart66Setting::getValue('eway_rapid_sandbox') ? false : true;
  }

  /**
   * Return an array of accepted credit card types where the keys are the display values and the values are the gateway values
   *
   * @return array
   */
  public function getCreditCardTypes() {
    $cardTypes = array();
    $cardTypes['MasterCard'] = 'mastercard';
    $cardTypes['Visa'] = 'visa';
    $cardTypes['American Express'] = 'amex';
    $cardTypes['Discover'] = 'discover';
    return $cardTypes;
  }

  public function addField($field, $value) {
    $this->fields[$field] = $value;
  }

  public function initCheckout($total) {
    $p = $this->getPayment();
    $b = $this->getBilling();
    $s = $this->getShipping();
    Cart66Common::log("Payment info for checkout: " . print_r($p, true));

    $this->addField('ewayTotalAmount', number_format($total, 2, '', ''));

  	$this->addField('ewayCustomerFirstName', $b['firstName']);
  	$this->addField('ewayCustomerLastName', $b['lastName']);
  	$this->addField('ewayCustomerEmail', $p['email']);
  	$this->addField('ewayCustomerPhone', $p['phone']);
  	$this->addField('ewayCustomerStreet1', $b['address']);
  	$this->addField('ewayCustomerStreet2', $b['address2']);
  	$this->addField('ewayCustomerCity', $b['city']);
  	$this->addField('ewayCustomerState', $b['state']);
  	$this->addField('ewayCustomerCountry', $b['country']);
  	$this->addField('ewayCustomerPostalCode', $b['zip']);

  	$this->addField('ewayCustomerShipFirstName', $s['firstName']);
  	$this->addField('ewayCustomerShipLastName', $s['lastName']);
  	$this->addField('ewayCustomerShipStreet1', $s['address']);
  	$this->addField('ewayCustomerShipStreet2', $s['address2']);
  	$this->addField('ewayCustomerShipCity', $s['city']);
  	$this->addField('ewayCustomerShipState', $s['state']);
  	$this->addField('ewayCustomerShipCountry', $s['country']);
  	$this->addField('ewayCustomerShipPostalCode', $s['zip']);

  	$this->addField('ewayCardHoldersName', $b['firstName'] . ' ' . $b['lastName']);
  	$this->addField('ewayCardNumber', $p['cardNumber']);
  	$this->addField('ewayCardExpiryMonth', $p['cardExpirationMonth']);
  	$this->addField('ewayCardExpiryYear', substr($p['cardExpirationYear'], 2));
    $this->addField('ewayCVN', $p['securityId']);

/*
    if(Cart66Setting::getValue('eway_geo_ip_anti_fraud')) {
	    $this->addField('ewayCustomerIPAddress', self::getRemoteIP());
	    $this->addField('ewayCustomerBillingCountry', $b['country']);
    }
*/
  }

  //Payment Function
  function doSale() {
    $sale = false;
    if($this->fields['ewayTotalAmount'] > 0) {
        $TotalAmount = $this->fields['ewayTotalAmount'];

        $checkoutPage = get_page_by_path('store/checkout');
        $ssl = Cart66Setting::getValue('auth_force_ssl');
        $checkoutUrl = get_permalink($checkoutPage->ID);
        if(Cart66Common::isHttps()) {
            $checkoutUrl = str_replace('http:', 'https:', $checkoutUrl);
        }

//        error_reporting(E_ALL);
//        ini_set("display_errors", 1);

        require_once(realpath(dirname(__FILE__).'/eWAY/RapidAPI.php'));
        $eway_service = new RapidAPI($this->_eway_mode, $this->_username, $this->_password);

        // Create AccessCode Request Object
        $request = new CreateAccessCodeRequest();

        $request->Customer->Reference = 'cart66';
        $request->Customer->Title = 'Mr.';
        $request->Customer->FirstName = strval($this->fields['ewayCustomerFirstName']);
        $request->Customer->LastName = strval($this->fields['ewayCustomerLastName']);
        $request->Customer->CompanyName = '';
        $request->Customer->JobDescription = '';
        $request->Customer->Street1 = strval($this->fields['ewayCustomerStreet1']);
        $request->Customer->Street2 = strval($this->fields['ewayCustomerStreet2']);
        $request->Customer->City = strval($this->fields['ewayCustomerCity']);
        $request->Customer->State = strval($this->fields['ewayCustomerState']);
        $request->Customer->PostalCode = strval($this->fields['ewayCustomerPostalCode']);
        $request->Customer->Country = strtolower(strval($this->fields['ewayCustomerCountry']));
        $request->Customer->Email = $this->fields['ewayCustomerEmail'];
        $request->Customer->Phone = $this->fields['ewayCustomerPhone'];
        $request->Customer->Mobile = '';

        // require field
        $request->ShippingAddress->FirstName = strval($this->fields['ewayCustomerShipFirstName']);
        $request->ShippingAddress->LastName = strval($this->fields['ewayCustomerShipLastName']);
        $request->ShippingAddress->Street1 = strval($this->fields['ewayCustomerShipStreet1']);
        $request->ShippingAddress->Street2 = strval($this->fields['ewayCustomerShipStreet2']);
        $request->ShippingAddress->City = strval($this->fields['ewayCustomerShipCity']);
        $request->ShippingAddress->State = strval($this->fields['ewayCustomerShipState']);
        $request->ShippingAddress->Country = strtolower(strval($this->fields['ewayCustomerShipCountry']));
        $request->ShippingAddress->PostalCode = strval($this->fields['ewayCustomerShipPostalCode']);
        $request->ShippingAddress->Email = $this->fields['ewayCustomerEmail'];
        $request->ShippingAddress->Phone = $this->fields['ewayCustomerPhone'];
        $request->ShippingAddress->ShippingMethod = "Unknown";

        $invoiceDesc = '';
        $items = Cart66Session::get('Cart66Cart')->getItems();
        foreach ($items as $itemIndex => $item) {
            $lineitem = new EwayLineItem();
            $lineitem->SKU = $item->getProductId();
            $lineitem->Description = $item->getFullDisplayName();
            $request->Items->LineItem[] = $lineitem;
            $invoiceDesc .= $item->getFullDisplayName() . ', ';
        }
        $invoiceDesc = substr($invoiceDesc, 0, -2);
        if(strlen($invoiceDesc) > 64) $invoiceDesc = substr($invoiceDesc , 0 , 61) . '...';

        $request->Payment->TotalAmount = $TotalAmount;
        $request->Payment->InvoiceNumber = '';
        $request->Payment->InvoiceDescription = $invoiceDesc;
        $request->Payment->InvoiceReference = '';

        $request->Payment->CurrencyCode = CURRENCY_CODE; // 'AUD'

        $request->RedirectUrl = $checkoutUrl;
        $request->Method = 'ProcessPayment';

        // Call RapidAPI
        $result = $eway_service->CreateAccessCode($request);
        if (isset($result->Errors)) {
            // Get Error Messages from Error Code. Error Code Mappings are in the Config.ini file
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
            $this->response['Response Reason Text'] = $lblError;
            return $sale;
        }

        // send post
        $AccessCode = $result->AccessCode;

        $post = array(
            EWAY_ACCESSCODE => $AccessCode,
            EWAY_CARDNAME   => $this->fields['ewayCardHoldersName'],
            EWAY_CARDNUMBER => $this->fields['ewayCardNumber'],
            EWAY_CARDEXPIRYMONTH  => $this->fields['ewayCardExpiryMonth'],
            EWAY_CARDEXPIRYYEAR   => $this->fields['ewayCardExpiryYear'],
            EWAY_CARDCVN    => $this->fields['ewayCVN'],
        );

        foreach ($post as $key => $value) {
            $this->field_string .= "$key=" . urlencode( $value ) . "&";
        }

	    $ch = curl_init($result->FormActionURL);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // Do not worry about checking for SSL certs
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $this->field_string, "& " ));

        $this->response_string = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->response['Response Reason Text'] = curl_error($ch);
        } else {
            curl_close($ch);
        }

        $isError = false;
        $request = new GetAccessCodeResultRequest();
        $request->AccessCode = $AccessCode;

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
                $isError = true;
                $lblError = "Payment Declined - " . $result->ResponseCode;
            }
        }

        if ($isError) {
            $this->response['Response Reason Text'] = $lblError;
            $this->response['Transaction ID'] = $result->TransactionID;
            $this->response['Reason Response Code'] = $result->ResponseCode;
            return $sale;
        }

        $this->response['Transaction ID'] = $result->TransactionID;
        $this->response['Response Reason Text'] = $result->ResponseMessage;
        $this->response['Reason Response Code'] = $result->ResponseCode;
        $sale = $result->TransactionID;
    }  else {
      // Process free orders without sending to the Eway gateway
      $this->response['Transaction ID'] = 'MT-' . Cart66Common::getRandString();
      $sale = $this->response['Transaction ID'];
    }
    return $sale;
  }

  function getResponseReasonText() {
    return $this->response['Response Reason Text'];
  }

  function getTransactionId() {
   return $this->response['Transaction ID'];
  }

  public function getTransactionResponseDescription() {
    $description['errormessage'] = $this->getResponseReasonText();
    $description['errorcode'] = $this->response['Response Reason Code'];
    $this->_logFields();
    $this->_logResponse();
    return $description;
  }

  protected function _logResponse() {
    $out = "eWay Response Log\n";
    foreach ($this->response as $key => $value) {
      $out .= "\t$key = $value\n";
    }
    Cart66Common::log('[' . basename(__FILE__) . ' - line ' . __LINE__ . "] $out");
   }

   protected function _logFields() {
     $out = "eWay Field Log\n";
     foreach ($this->fields as $key => $value) {
        $out .= "\t$key = $value\n";
     }
     Cart66Common::log('[' . basename(__FILE__) . ' - line ' . __LINE__ . "] $out");
   }

   function dumpFields() {

      // Used for debugging, this function will output all the field/value pairs
      // that are currently defined in the instance of the class using the
      // add_field() function.

      echo "<h3>eway_class->dump_fields() Output:</h3>";
      echo "<table width=\"95%\" border=\"1\" cellpadding=\"2\" cellspacing=\"0\">
            <tr>
               <td bgcolor=\"black\"><b><font color=\"white\">Field Name</font></b></td>
               <td bgcolor=\"black\"><b><font color=\"white\">Value</font></b></td>
            </tr>";

      foreach ($this->fields as $key => $value) {
         echo "<tr><td>$key</td><td>".urldecode($value)."&nbsp;</td></tr>";
      }

      echo "</table><br>";
   }

   function dumpResponse() {

      // Used for debugging, this function will output all the response field
      // names and the values returned for the payment submission.  This should
      // be called AFTER the process() function has been called to view details
      // about eway's response.

      echo "<h3>eway_class->dump_response() Output:</h3>";
      echo "<table width=\"95%\" border=\"1\" cellpadding=\"2\" cellspacing=\"0\">
            <tr>
               <td bgcolor=\"black\"><b><font color=\"white\">Index&nbsp;</font></b></td>
               <td bgcolor=\"black\"><b><font color=\"white\">Field Name</font></b></td>
               <td bgcolor=\"black\"><b><font color=\"white\">Value</font></b></td>
            </tr>";

      $i = 0;
      foreach ($this->response as $key => $value) {
         echo "<tr>
                  <td valign=\"top\" align=\"center\">$i</td>
                  <td valign=\"top\">$key</td>
                  <td valign=\"top\">$value&nbsp;</td>
               </tr>";
         $i++;
      }
      echo "</table><br>";
   }

   /**
 	 * Returns the (best guess) customer's IP
 	 *
 	 * @return string
 	 */
 	public function getRemoteIP() {
 	  $remoteIP = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null);
 		if (strstr($remoteIP, ',')) {
 		  $chunks = explode(',', $remoteIP);
 			$remoteIP = trim($chunks[0]);
 		}
 		return $remoteIP;
 	}

}
