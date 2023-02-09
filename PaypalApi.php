<?php

namespace App\Libraries;

class PaypalApi {
    
    private $_api_live_mode = false;

    private $_api_username = 'sb-eapvx23949878_api1.business.example.com';
    private $_api_password = '46Z9WHPM999JFGHA';
    private $_api_signature = 'AVRjs9UGDm7Gp7UInyBOcfdT3tPKAXAMldYd5WR647R5uvXDL3998ec0';
    
    private $_api_version = 64;
    private $_api_appid = 'APP-77W2741265P519543S';
    private $_api_use_proxy = false;
    private $_api_proxy = array(
		'host'	=> '127.0.0.1',
		'port'	=>  808
	);

    private $_API_Endpoint = '';
    private $_Adaptive_API_Endpoint = '';
    private $_PAYPAL_URL = '';
    private $_Adaptive_PAYPAL_URL = '';
    
    private $_AdaptivePayments = false;
    private $_AdaptivePaymentsPreapproval = false;
    private $_BNCode = ''; // BN Code is only applicable for partners
    
    private $_logo = '';
    private $_shop_name = '';
    private $_locale = 'en_US';
    private $_currency = 'HKD';
    private $_payment_type = 'Sale';

    private $_shipping_amount = 0;
    private $_shipping_discount = 0;
    private $_tax = 0;
    
    private $_return_url = '#';
    private $_cancel_url = '#';
    
    private $_token = null;
    private $_response = null;
    private $_payer_id = null;
            
    function __construct($live_mode = false, $config = array()) {
        $this->_api_live_mode = $live_mode;
        
        if(!empty($config)) {
            $this->_api_username = $config['username'];
            $this->_api_password = $config['password'];
            $this->_api_signature = $config['signature'];
            
            if(!empty($config['api_version'])) {
                $this->_api_version = $config['api_version'];
            }
            if(!empty($config['api_appid'])) {
                $this->_api_appid = $config['api_appid'];
            }
            if(!empty($config['use_proxy'])) {
                $this->_api_use_proxy = $config['use_proxy'];
            }
            if(!empty($config['sbn_code'])) {
                $this->_api_sbn_code = $config['sbn_code'];
            }
        }
        
        if(!$this->_api_live_mode) {
            $this->_API_Endpoint = 'https://api-3t.sandbox.paypal.com/nvp';
            $this->_Adaptive_API_Endpoint = 'https://svcs.sandbox.paypal.com/AdaptivePayments';
            $this->_PAYPAL_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
            $this->_Adaptive_PAYPAL_URL = 'https://www.sandbox.paypal.com/webapps/adaptivepayment/flow/pay';
        }
        else {
            $this->_API_Endpoint = 'https://api-3t.paypal.com/nvp';
            $this->_Adaptive_API_Endpoint = 'https://svcs.paypal.com/AdaptivePayments';
            $this->_PAYPAL_URL = 'https://www.paypal.com/cgi-bin/webscr';
            $this->_Adaptive_PAYPAL_URL = 'https://www.paypal.com/webapps/adaptivepayment/flow/pay';
        }
    }
    
    function setLogo($url = '#') {
        $this->_logo = $url;
        return $this;
    }
    
    function shopName($value = '') {
        $this->_shop_name = $value;
        return $this;
    }
    
    function setLocale($value = 'en_US') {
        $this->_locale = $value;
        return $this;
    }
    
    function setCurrency ($value = 'HKD') {
        $this->_currency = $value;
        return $this;
    }
    
    function shippingAmount($value = 0) {
        $this->_shipping_amount = $value;
    }
    
    function shippingDiscount($value = 0) {
        $this->_shipping_discount = $value;
    }
    
    function tax($value = 0) {
        $this->_tax = $value;
    }

    public function returnURL($url = '#') {
		$this->_return_url = $url;
		return $this;
	}
	
	public function cancelURL($url = '#') {
		$this->_cancel_url = $url;
		return $this;
	}
    
    public function getToken() {
		return $this->_token;
	}
    
    public function getResponse() {
		return $this->_response;
	}
    
    public function getPayerID() {
		return $this->_payer_id;
	}
    
    public function getTransactionID(){
		return $this->_response['PAYMENTINFO_0_TRANSACTIONID'];
	}
    
    /*
    $item = [
        [
            'name' => 'xxxxx',
            'price' => 2.00,
            'quantity' => 1
        ],
        [
            'name' => 'yyyyy',
            'price' => 1.00,
            'quantity' => 3
        ]
    ];

    $shipTo = [
        'name' => 'chan tai man',
        'email' => 'xxx@test.com',
        'street' => '-',
        'city' => '-',
        'state' => '',
        'country_code' => 'HK',
        'zip' => '000000',
        'street2' => '',
        'phone_num' => '',
    ];
    */
    public function checkOut($items = array(), $shipTo = array(), $hash = '') {
        $checkout_items_total = 0;
        if(!empty($items)) {
            foreach ($items as $key => $value) {
                $checkout_items_total += ($value['price']*$value['quantity']);
            }
        }
        $checkout_items_total = round($checkout_items_total+($this->_shipping_amount-$this->_shipping_discount)+$this->_tax,2);

        if(!empty($hash)) {
            $this->_return_url.= (strpos($this->_return_url, '?') !== false ? '&' : '?').'hash='.urlencode($hash);
            $this->_cancel_url.= (strpos($this->_cancel_url, '?') !== false ? '&' : '?').'hash='.urlencode($hash);
        }
  
        $resArray = $this->callMarkExpressCheckout(
                $checkout_items_total, 
                $this->_currency,
                $this->_payment_type,
                $this->_logo,
                $this->_shop_name,
                ((!empty($shipTo['email']))?$shipTo['email']:''),
                $this->_return_url,
                $this->_cancel_url,
                ((!empty($shipTo['name']))?$shipTo['name']:''),
                ((!empty($shipTo['street']))?$shipTo['street']:''),
                ((!empty($shipTo['city']))?$shipTo['city']:''),
                ((!empty($shipTo['state']))?$shipTo['state']:''),
                ((!empty($shipTo['country_code']))?$shipTo['country_code']:''),
                ((!empty($shipTo['zip']))?$shipTo['zip']:''),
                ((!empty($shipTo['street2']))?$shipTo['street2']:''),
                ((!empty($shipTo['phone_num']))?$shipTo['phone_num']:''),
                $this->_shipping_amount,
                $this->_shipping_discount,
                $this->_tax,
                $items,
                $this->_locale
        );

		if(strtoupper($resArray['ACK'])=='SUCCESS' || strtoupper($resArray['ACK']) == 'SUCCESSWITHWARNING'){
			$this->_token = urldecode($resArray['TOKEN']);
			return $this->redirectURL($this->_token);
		}
		else {
			$this->showError($resArray);
        }
    }
    

    public function confirm($total, $token = '', $payer_id = ''){
        $this->_token = (!empty($token)?$token:$_GET['token']);;
        $this->_payer_id = (!empty($payer_id)?$payer_id:$_GET['PayerID']);
        
		$resArray = $this->getShippingDetails($this->_token);
     
		if(strtoupper($resArray['ACK']) == 'SUCCESS' || strtoupper($resArray['ACK']) == 'SUCCESSWITHWARNING') {
			$resArray = $this->confirmPayment($total, $resArray);
			if(strtoupper($resArray['ACK']) == 'SUCCESS' || strtoupper($resArray['ACK']) == 'SUCCESSWITHWARNING') {
                $this->_response = $resArray;
				return true;
			}
		}
        
        $this->showError($resArray);
	}
    
    private function callMarkExpressCheckout(
            $paymentAmount,
            $currencyCodeType,
            $paymentType,
            $shopLogo,
            $shopName,
            $email,
            $returnURL,
            $cancelURL,
            $shipToName,
            $shipToStreet,
            $shipToCity,
            $shipToState,
            $shipToCountryCode,
            $shipToZip,
            $shipToStreet2,
            $phoneNum,
            $shippingAmount = 0, 
            $shippingDiscount = 0,
            $tax = 0,
            $items= array(), 
            $locale = 'en_US'
        ) {

		$nvpstr = 'PAYMENTREQUEST_0_AMT='.urlencode($paymentAmount);
		$nvpstr.= '&PAYMENTREQUEST_0_ITEMAMT='.urlencode($paymentAmount - ($shippingAmount - $shippingDiscount) - $tax);
		$nvpstr.= '&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode($paymentType);
		$nvpstr.= '&RETURNURL='.urlencode($returnURL);
		$nvpstr.= '&CANCELURL='.urlencode($cancelURL);
		$nvpstr.= '&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($currencyCodeType);
		$nvpstr.= '&ADDROVERRIDE=1';
		$nvpstr.= '&LANDINGPAGE='.urlencode("Billing");
		$nvpstr.= '&HDRIMG='.urlencode($shopLogo);
		$nvpstr.= '&BRANDNAME='.urlencode($shopName);
		$nvpstr.= '&EMAIL='.urlencode($email);
		$nvpstr.= '&LOCALECODE='.urlencode($locale);
		$nvpstr.= '&BUYEREMAILOPTINENABLE=0';	
		if(!$shipToName &&
		   !$shipToStreet &&
		   !$shipToStreet2 &&
		   !$shipToCity &&
		   !$shipToState &&
		   !$shipToCountryCode &&
		   !$shipToZip &&
		   !$phoneNum) {
			$nvpstr.= '&NOSHIPPING=1';	
	    }
		else {
			$nvpstr.= '&PAYMENTREQUEST_0_SHIPTONAME='.urlencode($shipToName);
			$nvpstr.= '&PAYMENTREQUEST_0_SHIPTOSTREET='.urlencode($shipToStreet);
			$nvpstr.= '&PAYMENTREQUEST_0_SHIPTOSTREET2='.urlencode($shipToStreet2);
			$nvpstr.= '&PAYMENTREQUEST_0_SHIPTOCITY='.urlencode($shipToCity);
			$nvpstr.= '&PAYMENTREQUEST_0_SHIPTOSTATE='.urlencode($shipToState);
			$nvpstr.= '&PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE='.urlencode($shipToCountryCode);
			$nvpstr.= '&PAYMENTREQUEST_0_SHIPTOZIP='.urlencode($shipToZip);
			$nvpstr.= '&PAYMENTREQUEST_0_SHIPTOPHONENUM='.urlencode($phoneNum);
		}
        
		$nvpstr.= '&PAYMENTREQUEST_0_SHIPPINGAMT='.urlencode($shippingAmount);	
		if($shippingDiscount > 0) {
			$nvpstr.= '&PAYMENTREQUEST_0_SHIPDISCAMT='.urlencode($shippingDiscount);
		}
        
		$nvpstr.= '&PAYMENTREQUEST_0_TAXAMT='.urlencode($tax);
		if(!empty($items)) {
			foreach($items as $key=>$value) {
				$nvpstr.= '&L_PAYMENTREQUEST_0_NAME'.$key.'='.urlencode($value['name']);	
				$nvpstr.= '&L_PAYMENTREQUEST_0_AMT'.$key.'='.urlencode($value['price']);
				$nvpstr.= '&L_PAYMENTREQUEST_0_QTY'.$key.'='.urlencode($value['quantity']);
			}
		}

        // Make the API call to PayPal
        // If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment. 
        // If an error occured, show the resulting errors
        $resArray = $this->hashCall('SetExpressCheckout', $nvpstr);
		$ack = strtoupper($resArray['ACK']);
		if($ack == 'SUCCESS' || $ack == 'SUCCESSWITHWARNING') {
			$this->_token = urldecode($resArray['TOKEN']);
		}
		   
	    return $resArray;
	}
    
    private function getShippingDetails($token) {
		/*
        At this point, the buyer has completed authorizing the payment
        at PayPal.  The function will call PayPal to obtain the details
        of the authorization, incuding any shipping information of the
        buyer.  Remember, the authorization is not a completed transaction
        at this state - the buyer still needs an additional step to finalize
        the transaction
		
		Build a second API request to PayPal, using the token as the
		ID to get the details on the payment authorization
		*/
	    $nvpstr = 'TOKEN='.$token;

		/*
        Make the API call and store the results in an array.  
			If the call was a success, show the authorization details, and provide
		 	an action to complete the payment.  
			If failed, show the error
		*/
	    $resArray = $this->hashCall('GetExpressCheckoutDetails', $nvpstr);
	    $ack = strtoupper($resArray['ACK']);
		if($ack == 'SUCCESS' || $ack == 'SUCCESSWITHWARNING') {	
			$this->_payer_id =	$resArray['PAYERID'];
		}
        
		return $resArray;
	}
    
    function confirmPayment($FinalPaymentAmt, $detailArray = array()){
        // Gather the information to make the final call to finalize the PayPal payment. The variable nvpstr holds the name value pairs

		// Format the other parameters that were stored in the session from the previous calls	
		$token 				= urlencode($this->_token);
		$paymentType 		= urlencode($this->_payment_type);
		$currencyCodeType 	= urlencode($this->_currency);
		$payerID 			= urlencode($this->_payer_id);

		$serverName 		= urlencode($_SERVER['SERVER_NAME']);
        
        $nvpstr = implode('&', array(
            'TOKEN='.$token,
            'PAYERID='.$payerID,
            'PAYMENTREQUEST_0_PAYMENTACTION='.$paymentType,
            'PAYMENTREQUEST_0_AMT='.$FinalPaymentAmt,
            'PAYMENTREQUEST_0_CURRENCYCODE='.$currencyCodeType,
            'IPADDRESS='.$serverName
        ));

		if(!empty($detailArray)) {
			$shippingAmount = $detailArray['PAYMENTREQUEST_0_SHIPPINGAMT'];
			$shippingDiscount = $detailArray['PAYMENTREQUEST_0_SHIPDISCAMT'];
            $tax = $detailArray['PAYMENTREQUEST_0_TAXAMT'];
			$nvpstr.= '&PAYMENTREQUEST_0_SHIPPINGAMT=' . urlencode($shippingAmount);	
			$nvpstr.= '&PAYMENTREQUEST_0_SHIPDISCAMT=' . urlencode($shippingDiscount);
			$nvpstr.= '&PAYMENTREQUEST_0_ITEMAMT=' . urlencode($FinalPaymentAmt - ($shippingAmount - $shippingDiscount) - $tax);
			foreach($detailArray as $key => $value) {
				foreach(array(
					'L_PAYMENTREQUEST_0_NAME',
					'L_PAYMENTREQUEST_0_AMT',
					'L_PAYMENTREQUEST_0_QTY'
				) as $val) {					
					if(preg_match('/^' . $val . '\d+$/i', $key)) {
						$nvpstr .= '&' . $key . '=' . urlencode($value);
					}
				}
			}
		}
 
		// Make the call to PayPal to finalize payment If an error occured, show the resulting errors
		$resArray = $this->hashCall('DoExpressCheckoutPayment', $nvpstr);

		/* 
        Display the API response back to the browser.
        If the response from PayPal was a success, display the response parameters'
        If the response was an error, display the errors received using APIError.php.
        */
		return $resArray;
	}
    
    private function hashCall($methodName, $nvpStr) {
		//setting the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, (($this->_AdaptivePayments)?$this->_Adaptive_API_Endpoint.'/'.$methodName:$this->_API_Endpoint));
		curl_setopt($ch, CURLOPT_VERBOSE, 1);

		//turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		
		if($this->_api_use_proxy) {
			curl_setopt ($ch, CURLOPT_PROXY, $this->_api_proxy['host']. ":" . $this->_api_proxy['port']); 
        }

		//NVPRequest for submitting to server
		if($this->_AdaptivePayments) {
			$nvpreq = $nvpStr;	
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'X-PAYPAL-SECURITY-USERID:'.$this->_api_username,
				'X-PAYPAL-SECURITY-PASSWORD:'.$this->_api_password,
				'X-PAYPAL-SECURITY-SIGNATURE:'.$this->_api_signature,
				'X-PAYPAL-REQUEST-DATA-FORMAT:NV',
				'X-PAYPAL-RESPONSE-DATA-FORMAT:NV',
				'X-PAYPAL-APPLICATION-ID:'. $this->_api_appid
			));
		}
		else{
			$nvpreq = implode('&', [
                'METHOD='.urlencode($methodName),
                'VERSION='.urlencode($this->_api_version),
                'PWD='.urlencode($this->_api_password),
                'USER='.urlencode($this->_api_username),
                'SIGNATURE='.urlencode($this->_api_signature),
                $nvpStr
            ]);
        }
		
		if($this->_BNCode) {
			$nvpreq.= '&BUTTONSOURCE='.urlencode($this->_BNCode);
		}	

		//setting the nvpreq as POST FIELD to curl
		curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

		//getting response from server
		$response = curl_exec($ch);

		//convrting NVPResponse to an Associative Array
		$nvpResArray = $this->deformatNVP($response);
		$nvpReqArray = $this->deformatNVP($nvpreq);
		
		if (curl_errno($ch)) {
            echo '<pre>'.curl_errno($ch).': '.curl_error($ch).'</pre>';
            exit();
		} 
		else {
			//closing the curl
		  	curl_close($ch);
		}

		return $nvpResArray;
	}
    
    private function deformatNVP($nvpstr) {
		$intial=0;
	 	$nvpArray = array();

		while(strlen($nvpstr)) {
			//postion of Key
			$keypos= strpos($nvpstr,'=');
			//position of value
			$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

			/*getting the Key and Value values and storing in a Associative Array*/
			$keyval=substr($nvpstr, $intial, $keypos);
			$valval=substr($nvpstr, $keypos+1, $valuepos-$keypos-1);
			//decoding the respose
			$nvpArray[urldecode($keyval)] = urldecode( $valval);
			$nvpstr=substr($nvpstr, $valuepos+1, strlen($nvpstr));
        }
        
		return $nvpArray;
	}

    private function redirectURL($token) {
		// Redirect to paypal.com here
		if($this->_AdaptivePayments) {
			if($this->_AdaptivePaymentsPreapproval){
				$this->_PAYPAL_URL = $this->_PAYPAL_URL.'?cmd=_ap-preapproval&preapprovalkey=';
			}
			else {				
				$this->_PAYPAL_URL = $this->_Adaptive_PAYPAL_URL.'?paykey=';	
			}
		}
		else {
			$this->_PAYPAL_URL = $this->_PAYPAL_URL.'?cmd=_express-checkout&token=';
		}
        
        return $this->_PAYPAL_URL.urlencode($token);
	}
    
    private function showError($data, $adaptive = false) {
		if($adaptive) {
			$ErrorCode = urldecode($data['error(0).errorId']);
			$ErrorShortMsg = urldecode($data['error(0).parameter(1)']);
			$ErrorLongMsg = urldecode($data['error(0).message']);
			$ErrorSeverityCode = urldecode($data['error(0).severity']);
		}
		else {
			$ErrorCode = urldecode($data['L_ERRORCODE0']);
			$ErrorShortMsg = urldecode($data['L_SHORTMESSAGE0']);
			$ErrorLongMsg = urldecode($data['L_LONGMESSAGE0']);
			$ErrorSeverityCode = urldecode($data['L_SEVERITYCODE0']);	
		}
        
		$msg = 'Detailed Error Message: '.$ErrorLongMsg.'<br/>';
		$msg.= 'Short Error Message: '.$ErrorShortMsg.'<br/>';
		$msg.= 'Error Code: '.$ErrorCode.'<br/>';
		$msg.= 'Error Severity Code: '.$ErrorSeverityCode;
        
        echo '<pre>'.$msg.'</pre>';
		exit();
	}
}