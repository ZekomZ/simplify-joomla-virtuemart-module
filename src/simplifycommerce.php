<?php 

defined('_JEXEC') or die('Restricted Access');
 
if (!class_exists('vmPSPlugin'))
    require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

class plgVmPaymentSimplifyCommerce extends vmPSPlugin {
	public static $_this = FALSE;

    function __construct(& $subject, $config) {
		parent::__construct($subject, $config);

		$varsToPush = array(
			"publicKey" => array('', 'char'),
			"privateKey" => array('', 'char')
		);

		$this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
	
		// Add report table	
		$this->_tablepkey = 'id';
		$this->_tableId = 'id';
		$this->tableFields = array_keys($this->getTableSQLFields());
    }

    function getTableSQLFields() {

		$SQLfields = array(
		    "id" => ' int(1) unsigned NOT NULL AUTO_INCREMENT ',
		    "virtuemart_order_id" => ' int(1) UNSIGNED DEFAULT NULL',
		    "order_number" => ' char(32) DEFAULT NULL',
		    "virtuemart_paymentmethod_id" => ' mediumint(1) UNSIGNED DEFAULT NULL',
		    "payment_name" => ' char(255) NOT NULL DEFAULT \'\' ',
		    "payment_order_total" => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
		    "payment_currency" => 'char(3) ',
		    "cost_per_transaction" => ' decimal(10,2) DEFAULT NULL ',
		    "cost_percent_total" => ' decimal(10,2) DEFAULT NULL '
		);

		return $SQLfields;
    }

    function plgVmDisplayListFEPayment (VirtueMartCart $cart, $selected = 0, &$htmlIn) {
    	if ($this->getPluginMethods($cart->vendorId) === 0) {
			if (empty($this->_name)) {
				$app = JFactory::getApplication();
				$app->enqueueMessage(JText::_('COM_VIRTUEMART_CART_NO_' . strtoupper($this->_psType)));
				return FALSE;
			} else {
				return FALSE;
			}
		}
		$html = array();
		$method_name = $this->_psType . '_name';

		JHTML::script('vmcreditcard.js', 'components/com_virtuemart/assets/js/', FALSE);
		JFactory::getLanguage()->load('com_virtuemart');
		vmJsApi::jCreditCard();
		$htmla = '';
		$html = array();
		foreach ($this->methods as $method) {
			if ($this->checkConditions($cart, $method, $cart->pricesUnformatted)) {
				$methodSalesPrice = $this->setCartPrices($cart, $cart->pricesUnformatted, $method);
				$method->$method_name = $this->renderPluginName($method);
				$html = $this->getPluginHtml($method, $selected, $methodSalesPrice);

				$paymentName = $method->$method_name;
				$publicKey = $method->publicKey;

				$html .= <<<directorderform
		<link href="//netdna.bootstrapcdn.com/font-awesome/4.0.1/css/font-awesome.css" rel="stylesheet">
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	    <script type="text/javascript" src="https://www.simplify.com/commerce/v1/simplify.js"></script>
	    <style>
	    	#simplify-payment-form {margin-top:10px;padding:20px;background-color:#F0F0F0}
	    	#simplify-payment-form input {padding:5px 10px}
	    	#simplify-payment-form label {display:block;padding:5px 0;}
	    	#simplify-payment-form .frm-grp {padding-bottom:15px}
			#simplify-payment-form #cc-number {width: 150px;}
	    	#simplify-payment-form #cc-cvc {width: 25px;}
	    	#simplify-payment-form #process-payment-btn {display: inline-block;zoom: 1;background-color: #F60;padding: 5px 12px;color: #FFF;text-decoration: none;text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);border-bottom: 1px solid rgba(0, 0, 0, 0.25);position: relative;cursor: pointer;border: 0;text-align: center;vertical-align: middle;}
	    	#simplify-payment-form #process-payment-btn[disabled] {background-color: #AAA}
	    	#simplify-payment-form .simplify-processing {display: none}
	    	#error-container, #sys-error-container {margin-top: 20px;color: #FF0000}
	    </style>
		<script>
			function simplifyResponseHandler(data) {
	            var simplifyPaymentForm = $("#paymentForm");
	            $('#sys-error-container').remove();

	            // Check for errors
	            if (data.error) {
	            	var errorMessages = {
						'card.number': 'The credit card number you entered is invalid.',
						'card.expYear': 'The expiry year on the credit card is invalid.'
					};

	                // Show any validation errors
	                if (data.error.code == "validation") {
	                    var fieldErrors = data.error.fieldErrors,
	                            fieldErrorsLength = fieldErrors.length,
	                            errorList = "";
	                    for (var i = 0; i < fieldErrorsLength; i++) {
	                        errorList += "<div class='error'>" + errorMessages[fieldErrors[i].field] +
	                                " " + fieldErrors[i].message + ".</div>";
	                    }
	                    // Display the errors
	                    $('#error-container').html(errorList);
	                } else {
	                	$('#error-container').html('<div>' + data.error.message + '</div>');
	                }
	                // Re-enable the submit button
	                $("#process-payment-btn").removeAttr("disabled");
	                $('.simplify-processing').hide();
	            } else {
	                // The token contains id, last4, and card type
	                var token = data["id"];
	                // Insert the token into the form so it gets submitted to the server
	                simplifyPaymentForm.append("<input type='hidden' name='simplifyToken' value='" + token + "' />");
	                // Submit the form to the server
	                simplifyPaymentForm.get(0).submit();
	            }
	        }

			$(document).ready(function() {
	            $("#paymentForm").on("submit", function() {
	                // Disable the submit button

	                var simplifyId = $("span:contains('$paymentName')").parent().attr('for');
						
	                if($('#' + simplifyId).is(':checked')){
						$("#process-payment-btn").attr("disabled", "disabled");
		                $('.simplify-processing').show();
		                // Generate a card token & handle the response
		                SimplifyCommerce.generateToken({
		                    key: "$publicKey",
		                    card: {
		                        number: $("#cc-number").val(),
		                        cvc: $("#cc-cvc").val(),
		                        expMonth: $("#cc-exp-month").val(),
		                        expYear: $("#cc-exp-year").val()
		                    }
		                }, simplifyResponseHandler);
		                // Prevent the form from submitting
		                return false;
	                }
	            });
	        });
		</script>
		<div id="simplify-payment-form">
			<div class='frm-grp'>
		        <label>Credit Card Number: </label>
		        <input id="cc-number" type="text" maxlength="20" autocomplete="off" value="" autofocus />
		    </div>
		    <div class='frm-grp'>
		        <label>CVC: </label>
		        <input id="cc-cvc" type="text" maxlength="3" autocomplete="off" value=""/>
		    </div>
		    <div class='frm-grp'>
		        <label>Expiry Date: </label>
		        <select id="cc-exp-month">
		            <option value="01">Jan</option>
		            <option value="02">Feb</option>
		            <option value="03">Mar</option>
		            <option value="04">Apr</option>
		            <option value="05">May</option>
		            <option value="06">Jun</option>
		            <option value="07">Jul</option>
		            <option value="08">Aug</option>
		            <option value="09">Sep</option>
		            <option value="10">Oct</option>
		            <option value="11">Nov</option>
		            <option value="12">Dec</option>
		        </select>
		        <select id="cc-exp-year">
		            <option value="13">2013</option>
		            <option value="14">2014</option>
		            <option value="15">2015</option>
		            <option value="16">2016</option>
		            <option value="17">2017</option>
		            <option value="18">2018</option>
		            <option value="19">2019</option>
		            <option value="20">2020</option>
		            <option value="21">2021</option>
		            <option value="22">2022</option>
		        </select>
		    </div>
		    <div class='simplify-processing frm-grp'>Validating your credit card... <i class='fa fa-refresh fa-spin'></i></div>
		</div>
		<div id="error-container"></div>
directorderform;
		    
				$htmla[] = $html;
			}
		}
		$htmlIn[] = $htmla;

		return TRUE;
    }

	function plgVmConfirmedOrder($cart, $order) {
	   	if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
		    return null; // Another method was selected, do nothing
		}

		if (!$this->selectedThisElement($method->payment_element)) {
		    return false;
		}
		
		$session = JFactory::getSession();
		$return_context = $session->getId();
		
		$this->logInfo('plgVmConfirmedOrder order number: ' . $order['details']['BT']->order_number, 'message');

	    if (!class_exists('TableVendors'))
		    require(JPATH_VM_ADMINISTRATOR . DS . 'table' . DS . 'vendors.php');

		$vendorModel = new VirtueMartModelVendor();
		$vendorModel->setId(1);
		$vendor = $vendorModel->getVendor();
		$vendorModel->addImages($vendor, 1);

		$paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
		$totalInPaymentCurrency = round($paymentCurrency->convertCurrencyTo($method->payment_currency, $order['details']['BT']->order_total,false), 2);
		$cd = CurrencyDisplay::getInstance($cart->pricesCurrency);
		
		// Prepare data that should be stored in the database
		$dbValues["order_number"] = $order["details"]["BT"]->order_number;
		$dbValues["payment_name"] = $this->renderPluginName($method, $order);
		$dbValues["virtuemart_paymentmethod_id"] = $cart->virtuemart_paymentmethod_id;
		$dbValues["cost_per_transaction"] = $method->cost_per_transaction;
		$dbValues["cost_percent_total"] = $method->cost_percent_total;
		$dbValues["payment_currency"] = $method->payment_currency;
		$dbValues["payment_order_total"] = $totalInPaymentCurrency;
		$this->storePSPluginInternalData($dbValues);
		
		$orderId = $cart->virtuemart_order_id;

		$session = JFactory::getSession();
		$simplify = $session->get('simplify', 0, 'vm');

		if (!empty($simplify)) {
			$simplifyData = unserialize($simplify);
			$token = $simplifyData->token;

	   		require('lib/Simplify.php');

			Simplify::$publicKey = $method->publicKey;
			Simplify::$privateKey = $method->privateKey;

			try	{
				$simplifyPayment = Simplify_Payment::createPayment(array(
			        'amount' => $totalInPaymentCurrency * 100, // Cart total amount
			        'token' => $token, // Token returned by Simplify Card Tokenization
			        'description' => 'VirtueMart Order Number: '.(int)$orderId,
			        'currency' => 'USD'
			     ));

	      		$paymentStatus = $simplifyPayment->paymentStatus;

			} catch(Simplify_ApiException $e) {
				$errorMessage = 'There was an error processing your payment. Error message: '.$e->getMessage().'.<br>Please try again.';
				$this->handleError($cart, $order, $orderId, $errorMessage);
				return FALSE;
			}

			if($paymentStatus != 'APPROVED') {
				$errorMessage = 'There was an error processing your payment. The status of the payment is: '.$paymentStatus.'.';
				$this->handleError($cart, $order, $orderId, $errorMessage);
				return FALSE;
			}
	   	} else {
	   		$this->processPayment($order,$orderId);
	   		print '<!-- Copyright Online Store 2013 ';

	   		$cart->_confirmDone = FALSE;
			$cart->_dataValidated = FALSE;
			$cart->setCartIntoSession();

	   		return FALSE;
	   	}

	   	$html = '<h4>Your Order ID - '.$orderId.'</h4>'.
	   	'<p>You should receive an email shortly with your order confirmation.</p>';

		$modelOrder = VmModel::getModel ('orders');
		$order['order_status'] = 'C';
		$order['virtuemart_order_id'] = $orderId;
		$order['customer_notified'] = 1;
		$order['comments'] = '';
		$modelOrder->updateStatusForOneOrder ($orderId, $order, TRUE);

		//We delete the old stuff
		$cart->emptyCart ();
		$this->clearSimplifySession();
		JRequest::setVar ('html', $html);
		return TRUE;
	}

	function clearSimplifySession () {
		$session = JFactory::getSession();
		$session->clear('simplify', 'vm');
	}

	function handleError($cart, $order, $orderId, $errorMessage) {
		error_log($errorMessage);
		print '<div id="sys-error-container">'.$errorMessage.'.</div><!-- Copyright Online Store 2013 ';

		$cart->_confirmDone = FALSE;
		$cart->_dataValidated = FALSE;
		$cart->setCartIntoSession();
	}

	public function renderPluginName ($plugin) {
		$pluginName='Credit Card';
		return $pluginName;
	}

	public function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrencyId) {
		if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
		    return null; // Another method was selected, do nothing
		}

		if (!$this->selectedThisElement($method->payment_element)) {
		    return false;
		}

		$this->getPaymentCurrency($method);
		$paymentCurrencyId = $method->payment_currency;
	}

	public function plgVmOnShowOrderBEPayment($virtuemart_order_id, $payment_method_id) {
		
		if (!$this->selectedThisByMethodId($payment_method_id)) {
		    return null; // Another method was selected, do nothing
		}

		$db = JFactory::getDBO();
		$q = 'SELECT * FROM `' . $this->_tablename . '` '
			. 'WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;
		$db->setQuery($q);
		if (!($paymentTable = $db->loadObject())) {
		    // JError::raiseWarning(500, $db->getErrorMsg());
		    return '';
		}

		$this->getPaymentCurrency($paymentTable);
		$q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $paymentTable->payment_currency . '" ';
		$db = &JFactory::getDBO();
		$db->setQuery($q);
		$currency_code_3 = $db->loadResult();
		$html = '<table class="adminlist">' . "\n";
		$html .=$this->getHtmlHeaderBE();
		$html .= $this->getHtmlRowBE('Payment_Type', $paymentTable->payment_name);
		//$html .= $this->getHtmlRowBE('PAYPAL_PAYMENT_TOTAL_CURRENCY', $paymentTable->payment_order_total.' '.$currency_code_3);
		$code = "paypal_response_";

		foreach ($paymentTable as $key => $value) {
		    if (substr($key, 0, strlen($code)) == $code) {
			$html .= $this->getHtmlRowBE($key, $value);
		    }
		}

		$html .= '</table>' . "\n";

		return $html;
	}

	protected function checkConditions ($cart, $method, $cart_prices) {
		$address = (($cart->ST == 0) ? $cart->BT : $cart->ST);

		$amount = $cart_prices['salesPrice'];
		$amount_cond = ($amount >= 0 AND $amount <= 9999999);

		$countries = array();
		if (!empty($method->countries)) {
			if (!is_array ($method->countries)) {
				$countries[0] = $method->countries;
			} else {
				$countries = $method->countries;
			}
		}

		if (!is_array ($address)) {
			$address = array();
			$address['virtuemart_country_id'] = 0;
		}

		if (!isset($address['virtuemart_country_id'])) {
			$address['virtuemart_country_id'] = 0;
		}

		if (in_array ($address['virtuemart_country_id'], $countries) || count ($countries) == 0) {
			if ($amount_cond) {
				return TRUE;
			}
		}

		return FALSE;
	}

	public function getVmPluginCreateTableSQL() {
		return $this->createTableSQL('Simplify Commerce Payments Table');
    }

    public function plgVmDeclarePluginParamsPayment($name, $id, &$data) {
		return $this->declarePluginParams('payment', $name, $id, $data);
    }

    public function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {
		return $this->setOnTablePluginParams($name, $id, $table);
    }

    public function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {
		return $this->onStoreInstallPluginTable($jplugin_id);
    }

	public function plgVmOnPaymentResponseReceived(&$html) {
	    return null; 
	}

    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart) {
    	if (!$this->selectedThisByMethodId($cart->virtuemart_paymentmethod_id)) {
			return NULL; // Another method was selected, do nothing
		}

		if(isset($_POST['simplifyToken'])) {
			$session = JFactory::getSession();
			$simplify = new stdClass();
			$simplify->token = $_POST['simplifyToken'];
			$session->set('simplify', serialize($simplify), 'vm');
		}

		return TRUE;
    }

    public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
		return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }

    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array()) {
		return $this->onCheckAutomaticSelected($cart, $cart_prices);
    }

    public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {
		$this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
    }

    function plgVmonShowOrderPrintPayment($order_number, $method_id) {
		return $this->onShowOrderPrint($order_number, $method_id);
    }

}
