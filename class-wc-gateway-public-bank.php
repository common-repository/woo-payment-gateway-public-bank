<?php
/**
 * Plugin Name: Woocommerce Payment gateway Public Bank
 * Plugin URI: https://wordpress.org/plugins/woocommerce-payment-gateway-public-bank
 * Description: Malaysia Public Bank Standard Payment Gateway for Woocommerce. Using method.
 * Version: 1.1.0
 * Author: freelancerviet.net
 * Author URI: http://freelancerviet.net/
 * Text Domain: woocommerce-payment-gateway-public-bank
 *
 * Copyright: © 2009-2015 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}
add_action('plugins_loaded', 'init_wc_public_bank_cybersouce');

function init_wc_public_bank_cybersouce()
{
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_Gateway_Public_Bank_CyberSource extends WC_Payment_Gateway
    {


        public static $log_enabled = true;
        public static $log = false;

        public function __construct()
        {
            $this->id                 = 'public_bank_cybersource';
            $this->has_fields         = true; //if need some option in checkout page
            $this->order_button_text  = __('Proceed to Public bank Cybersource', 'woocommerce');
            $this->method_title       = __('Public bank Cybersource', 'woocommerce');
            $this->method_description = 'Setting for Public bank Cybersource';
            $this->supports           = array(
                'products'
            );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables.
            $this->title          = $this->get_option('title');
            $this->description    = $this->get_option('description');

            $this->testmode       = 'yes' === $this->get_option('testmode', 'no');

            $this->debug          = 'yes' === $this->get_option('debug', 'no');
            $this->ziip          = 'yes' === $this->get_option('ziip', 'no');
            $this->return_url = WC()->api_request_url('wc_public_bank_cybersource_return');
            $this->notify_url = WC()->api_request_url('wc_public_bank_cybersource');
            $this->thankyou_url = $this->get_option('thankyou_page');
            $this->profile_id = $this->testmode ? $this->get_option('profile_id_test') : $this->get_option('profile_id');
            $this->access_key = $this->testmode ? $this->get_option('access_key_test') : $this->get_option('access_key');
            $this->secret_key = $this->testmode ? $this->get_option('secret_key_test') : $this->get_option('secret_key');
			$this->profile_id_i6 = $this->testmode ? $this->get_option('profile_id_i6_test') : $this->get_option('profile_id_i6');
            $this->access_key_i6 = $this->testmode ? $this->get_option('access_key_i6_test') : $this->get_option('access_key_i6');
            $this->secret_key_i6 = $this->testmode ? $this->get_option('secret_key_i6_test') : $this->get_option('secret_key_i6');
			$this->profile_id_i12 = $this->testmode ? $this->get_option('profile_id_i12_test') : $this->get_option('profile_id_i12');
            $this->access_key_i12 = $this->testmode ? $this->get_option('access_key_i12_test') : $this->get_option('access_key_i12');
            $this->secret_key_i12 = $this->testmode ? $this->get_option('secret_key_i12_test') : $this->get_option('secret_key_i12');
            if ($this->testmode) {
                $this->endpoint_checkout = 'https://testsecureacceptance.cybersource.com/pay';
            } else {
                $this->endpoint_checkout = 'https://secureacceptance.cybersource.com/pay';
            }
            self::$log_enabled    = $this->debug;
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_api_wc_public_bank_cybersource', array($this, 'capture_payment'));
            add_action('woocommerce_api_wc_public_bank_cybersource_return', array($this, 'receipt_payment'));
            add_action('woocommerce_receipt_public_bank_cybersource', array($this, 'checkout_form'));
        }



        public static function log($error, $filename = 'log.txt')
        {
            if (self::$log_enabled) {
                date_default_timezone_set('Asia/Ho_Chi_Minh');
                $date = date('d/m/Y H:i:s');
                $error = $date . ": " . $error . "\n---------------------------\n";

                $log_file = __DIR__ . "/".$filename;
                if (!file_exists($log_file) || filesize($log_file) > 1048576) {
                    $fh = fopen($log_file, 'w');
                } else {
                    //echo "Append log to log file ".$log_file;
                    $fh = fopen($log_file, 'a');
                }

                fwrite($fh, $error);
                fclose($fh);
            }
        }

        public function get_icon()
        {
            $icon_html = '';
            return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
        }


        public function is_valid_for_use()
        {
            return true;
        }

        public function admin_options()
        {
            if ($this->is_valid_for_use()) {
                parent::admin_options();
            } else {
?>
                <div class="inline error">
                    <p><strong><?php _e('Gateway Disabled', 'woocommerce'); ?></strong>: <?php _e('Public Bank does not support your store currency.', 'woocommerce'); ?></p>
                </div>
<?php
            }
        }

        //form in checkout page
        public function payment_fields($order_id='')
        {	
			global $woocommerce;
			$cart = $woocommerce->cart;
			//echo '<pre>';print_r($cart);
			$cart_total = $woocommerce->cart->total;
			echo '<input type="hidden" id="public_bank_order_total" value="'.$cart_total.'" />';
			echo '<input type="hidden" id="public_bank_order_currency" value="'.get_woocommerce_currency_symbol().'" />';
            echo $this->description;
            echo '<br><div style="font-size:16px;">';
        
            if ($this->ziip) {
                $form .= '<p><input type="checkbox" value="1" name="ziip_cbs"  id="ziip_cbs" onclick="return togglerPaymentPlan()"/> Zero Interest Installment Plan</p>';
                $form .= '<div id="ziip_cbs_area" style="display:none">		
					<div style="font-weight:bold;font-size:17px;">Credit Card Details</div>
				<table cellpadding="5">
					<tr>
						<td>Installment period</td>
						<td>
						';
					if($this->access_key_i6 || $this->access_key_i12){
						$form .= '<select id="ziip_cbs_plan" name="ziip_cbs_plan" onchange="getTotalPerMonth()">						
							<option value="">Select Plan</option>						
							'.($this->access_key_i6 ? '<option value="6">6 months</option>' : '').'						
							'.($this->access_key_i12 ? '<option value="12">1 year</option>' : '').'						
							</select>';
					}
					$form .= '</td>
					</tr>
					<tr>
						<td>Card number</td>
						<td><input name="ziip_cbs_card[number]" id="ziip_cbs_pan" maxlength="16"/></td>
					</tr>
					<tr>
						<td>Expiry Date</td>
						<td>
							<select id="ziip_cbs_exp_month"  style="width:45px;float:left;display:block" onchange="changeYear()"></select>
							<select id="ziip_cbs_exp_year" style="width:60px;float:left;display:block" onchange="changeYear()"></select>
							<input type="hidden" name="ziip_cbs_card[expired]" id="ziip_cbs_expired"/>
						</td>
					</tr>
					<tr>
						<td>CVV</td>
						<td>
							<input name="ziip_cbs_card[cvv]" id="ziip_cbs_cvv" maxlength="3" style="width:40px;"/>							
						</td>
					</tr>
					<tr>
						<td colspan="2"><div id="ziip_cbs_total_per_month" style="color:red"></div></td>
						
					</tr>
				</table>
				</div>						';				
                $form .= '<script>	
				var html_year = "<option value=\'\'>YYYY</option>";
				var html_month = "<option value=\'\'>MM</option>";
				for(i=1;i<=12;i++){
					if(i<10){
						var j="0"+i;
					}else{var j=i;}
					html_month += "<option value=\""+j+"\">"+j+"</option>";
				}
				var date_now = new Date();
				var year_now=  date_now.getFullYear();
				for(i=0;i<=10;i++){		
					var year_now_txt = year_now.toString();
					html_year += "<option value=\'"+year_now_txt+"\'>"+year_now_txt+"</option>";
					year_now++;
				}
				jQuery("#ziip_cbs_exp_month").html(html_month);
				jQuery("#ziip_cbs_exp_year").html(html_year);
				
				function togglerPaymentPlan(){	
					if(jQuery("#ziip_cbs:checked").val() == 1){							
						jQuery("#ziip_cbs_area").show();
					}else{							
						jQuery("#ziip_cbs_area").hide();
					}					
					
				}	
				
				function changeYear(){
					var exp = jQuery("#ziip_cbs_exp_month").val().toString()+"-"+jQuery("#ziip_cbs_exp_year").val().toString();
					jQuery("#ziip_cbs_expired").val(exp);
				}
				
				function getTotalPerMonth(){
					var total = parseInt(jQuery("#public_bank_order_total").val());
					var month = jQuery("#ziip_cbs_plan").val();					
					if(month==""){
						jQuery("#ziip_cbs_total_per_month").html("");
						return false;
					}
					var month = parseInt(month);
					var currency = jQuery("#public_bank_order_currency").val();
					var moneyPerMonth = total/month;
					jQuery("#ziip_cbs_total_per_month").html("Amount: "+currency+" "+moneyPerMonth.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, "$&,")+" per month for "+month+" months");
				}
				
				</script>';
				$form .= '<style>#ziip_cbs_area table tr{height:40px;}</style>';
            }
			$form .= '</div>';
            echo $form;
        }

        //check form is valid
        public function validate_fields()
        {			
            $ziip = (int) ($_POST['ziip_cbs']);
            $ziip_plan = sanitize_text_field($_POST['ziip_cbs_plan']);
            $ziip_card = $_POST['ziip_cbs_card'];
			
            if (!$this->validateZiip($ziip, $ziip_plan, $ziip_card)) {
                wc_add_notice(__($this->error_message, 'woocommerce'), 'error');
                return false;
            }
            
            return true;
        }

        private function validate_merchant($method)
        {
            if (empty($method)) {
                return false;
            }
            if ($method != 'visa' && $method != 'master') {
                return false;
            }
            return true;
        }
		
		private function check_cc($cc){
			$cards = array(
				"visa" => "(4\d{12}(?:\d{3})?)",
				"amex" => "(3[47]\d{13})",
				"jcb" => "(35[2-8][89]\d\d\d{10})",
				"maestro" => "((?:5020|5038|6304|6579|6761)\d{12}(?:\d\d)?)",
				"solo" => "((?:6334|6767)\d{12}(?:\d\d)?\d?)",
				"mastercard" => "((5|2)[1-5]\d{14})",
				"switch" => "(?:(?:(?:4903|4905|4911|4936|6333|6759)\d{12})|(?:(?:564182|633110)\d{10})(\d\d)?\d?)",
			);
			$names = array("Visa", "AmericanExpress", "JCB", "Maestro", "Solo", "Mastercard", "Switch");
			$matches = array();
			$pattern = "#^(?:".implode("|", $cards).")$#";
			$result = preg_match($pattern, str_replace(" ", "", $cc), $matches);
			return ($result>0)?$names[sizeof($matches)-2]:false;
		}
		
		private function get_card_code($name){
			$cards = [
				"Visa" => '001',
				"Mastercard" => '002',
				"AmericanExpress" => '003',
				"JCB" => '007',
				"Maestro" => '042',
				"Discover" => '004',
				"Diners" => '005',
				"CarteBlanche" => '006',
				"EnRoute" => '014',
				"JAL" => '021',
				"MaestroUKDomestic" => '024',
				"Delta" => '031',
				"VisaElectron" => '033',
				"Dankort" => '034',
				"CarteBancaire" => '036',
				"CartaSi" => '037',
				"GeMoneyUkCard" => '043',
				"Hipercard" => '050',
				"Elo" => '054',
			];
			return $cards[$name];
		}
		
        private function validateZiip($ziip, $ziip_plan, $ziip_card,$card_type='')
        {
            if ($ziip) {
                $ziip_plan = (int)trim($ziip_plan);
                if ( $ziip_plan < 3 ||  $ziip_plan > 12){
					$this->error_message = 'Please choose installment plan';
					return false;
				}
                if (count($ziip_card) != 3) return false;
				
				$cardCheck = $this->check_cc($ziip_card['number']);
				if($cardCheck === false) {
					$this->error_message = 'Card number is invalid';
					return false;
				}
				
				if(strlen($ziip_card['expired']) != 7 || substr($ziip_card['expired'],3) < substr(date('Y'),3)){
					$this->error_message = 'Expired date is invalid';
					return false;
				}
				if(strlen($ziip_card['cvv']) != 3){
					$this->error_message = 'Cvv is invalid';
					return false;
				}
            }
            return true;
        }
		
        /**
         * Initialise Gateway Settings Form Fields.
         */
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable Malaysia Public Bank Payment', 'woocommerce'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                    'default' => 'Credit Card (Processed by PBE)',
                    'desc_tip'      => true,
                ),
				'ziip' => array(
                    'title' => __('ZIIP Plan'),
                    'type' => 'checkbox',
                    'description' => 'Enable Installment Payments'
                ),
                'description' => array(
                    'title' => __('Message show when choose the payment method', 'woocommerce'),
                    'type' => 'textarea',
                    'default' => ''
                ),
                'notify_url' => array(
                    'title' => __('Notify URL to setup Profile'),
                    'type' => 'checkbox',
                    'description' => WC()->api_request_url('wc_public_bank_cybersource')
                ),
                'return_url' => array(
                    'title' => __('Return URL to setup Profile'),
                    'type' => 'checkbox',
                    'description' => WC()->api_request_url('wc_public_bank_cybersource_return')
                ),
                'thankyou_page' => array(
                    'title' => __('Thank you page'),
                    'type' => 'select',
                    'options' => $this->get_pages('Choose...'),
                    'description' => "Chooseing page/url to redirect after checkout to Public Bank Success."
                ),
                'access_key' => array(
                    'title' => __('Access key'),
                    'type' => 'text',
                    'description' => ""
                ),
                'profile_id' => array(
                    'title' => __('Profile id'),
                    'type' => 'text',
                    'description' => ""
                ),
                'secret_key' => array(
                    'title' => __('Secret key'),
                    'type' => 'textarea',
                    'description' => ""
                ),
				'access_key_i6' => array(
                    'title' => __('Access key for Installment 6 months'),
                    'type' => 'text',
                    'description' => "Fill to show Installment option for 6 months"
                ),
                'profile_id_i6' => array(
                    'title' => __('Profile id Installment 6 months'),
                    'type' => 'text',
                    'description' => ""
                ),
                'secret_key_i6' => array(
                    'title' => __('Secret key Installment 6 months'),
                    'type' => 'textarea',
                    'description' => ""
                ),
				'access_key_i12' => array(
                    'title' => __('Access key Installment 12 months'),
                    'type' => 'text',
                    'description' => "Fill to show Installment option for 12 months"
                ),
                'profile_id_i12' => array(
                    'title' => __('Profile id Installment 12 months'),
                    'type' => 'text',
                    'description' => ""
                ),
                'secret_key_i12' => array(
                    'title' => __('Secret key Installment 12 months'),
                    'type' => 'textarea',
                    'description' => ""
                ),
                'testmode' => array(
                    'title' => __('Testmode', 'woocommerce'),
                    'type' => 'checkbox',
                    'description' => __('Enable test mode.', 'woocommerce'),
                    'default' => __('no', 'woocommerce'),
                ),
                'access_key_test' => array(
                    'title' => __('Access key for test'),
                    'type' => 'text',
                    'description' => ""
                ),
                'profile_id_test' => array(
                    'title' => __('Profile id for test'),
                    'type' => 'text',
                    'description' => ""
                ),
                'secret_key_test' => array(
                    'title' => __('Secret key for test'),
                    'type' => 'textarea',
                    'description' => ""
                ),
				 'access_key_i6_test' => array(
                    'title' => __('Access key for test Installment 6 months'),
                    'type' => 'text',
                    'description' => "Fill to show Installment option for 6 months"
                ),
                'profile_id_i6_test' => array(
                    'title' => __('Profile id for test Installment 6 months'),
                    'type' => 'text',
                    'description' => ""
                ),
                'secret_key_i6_test' => array(
                    'title' => __('Secret key for test Installment 6 months'),
                    'type' => 'textarea',
                    'description' => ""
                ),
				'access_key_i12_test' => array(
                    'title' => __('Access key for test Installment 12 months'),
                    'type' => 'text',
                    'description' => "Fill to show Installment option for 12 months"
                ),
                'profile_id_i12_test' => array(
                    'title' => __('Profile id for test Installment 12 months'),
                    'type' => 'text',
                    'description' => ""
                ),
                'secret_key_i12_test' => array(
                    'title' => __('Secret key for test Installment 12 months'),
                    'type' => 'textarea',
                    'description' => ""
                ),
                'debug' => array(
                    'title' => __('Debug', 'woocommerce'),
                    'type' => 'checkbox',
                    'description' => sprintf(__('Log Public Bank events, inside %s', 'woocommerce'), '<code>' . site_url('/wp-content/plugins/payment-cybersource/log.txt').'</code>'),
                    'default' => 'yes',
                )
            );
        }


        /**
         * Process the payment and return the result.
         * @param  int $order_id
         * @return array
         */
        public function process_payment($order_id)
        {
            global $woocommerce;
            $order = new WC_Order($order_id);
            $ziip = (int) $_POST['ziip_cbs'];
            $ziip_plan = sanitize_text_field($_POST['ziip_cbs_plan']);
            $ziip_card = $_POST['ziip_cbs_card'];
			$user = wp_get_current_user();
			if($this->testmode){
                $user = wp_get_current_user();
                if ( !in_array( 'administrator', (array) $user->roles ) ) {
                    return array(
                        'result'  => 'failed',
                        'message' => 'Sorry the payment is in test mode'
                    );
                }
            }
            
            if ($this->validateZiip($ziip, $ziip_plan,$ziip_card)) {
                return array(
                    'result'  => 'success',
                    'redirect'  => add_query_arg(array('order' => $order->id, 'ziip' => $ziip, 'ziip_plan' => $ziip_plan, 'ziip_card' => $ziip_card), add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
                );
            } else {
                return array(
                    'result'  => 'Invalid card'
                );
            }
        }
		
		private function getAccessKey($plan){
			if((int)$plan ==6){
				return $this->access_key_i6;
			}
			if((int)$plan ==12){
				return $this->access_key_i12;
			}
			return $this->access_key;
		}
		private function getProfileId($plan){
			if((int)$plan ==6){
				return $this->profile_id_i6;
			}
			if((int)$plan ==12){
				return $this->profile_id_i12;
			}
			return $this->profile_id;
		}
		private function getSecretKey($plan){
			if((int)$plan ==6){
				return $this->secret_key_i6;
			}
			if((int)$plan ==12){
				return $this->secret_key_i12;
			}
			return $this->secret_key;
		}

        function checkout_form($order_id)
        {
            global $woocommerce;
            $order = new WC_Order($order_id); 
            $tx_id = $order->get_id();//sprintf('%020d', $order->get_id());           
            //$has_key = 'PUBLICBANKBERHAD0001000000888800PBBSECRET3300000888';
            // if (!session_id()) {
            //     session_start();
            // }
            // $_SESSION['reference-number'] = $order_id;
			$ziip = (int) $_GET['ziip'];
			$ziip_plan = $_GET['ziip_plan'];
			$ziip_card = $_GET['ziip_card'];
			
            $params = array(
                'access_key' => $this->getAccessKey($ziip_plan),
                'profile_id' => $this->getProfileId($ziip_plan),
                'transaction_uuid' => uniqid(),
                'signed_field_names' => 'access_key,profile_id,transaction_uuid,signed_field_names,unsigned_field_names,signed_date_time,locale,transaction_type,reference_number,amount,currency,bill_to_address_city,bill_to_address_country,bill_to_address_line1,bill_to_address_postal_code,bill_to_email,bill_to_forename,bill_to_phone,bill_to_surname',
                // 'unsigned_field_names' => '',
                'unsigned_field_names' => '',
                'signed_date_time' => gmdate("Y-m-d\TH:i:s\Z"),
                'locale' => 'en',
                'transaction_type' => 'sale',
                'reference_number' => $tx_id,
                'amount' => $this->number_format($order->get_total(), $order),
                'currency' => get_woocommerce_currency(),
                'bill_to_address_city' => $order->get_billing_city(),
                'bill_to_address_country' => $order->get_billing_country(),
                'bill_to_address_line1' => $order->get_billing_address_1(),
                'bill_to_address_postal_code' => $order->get_billing_postcode(),
                //'bill_to_address_state' => $order->get_billing_state(),
                'bill_to_email' => $order->get_billing_email(),
                'bill_to_forename' => $order->get_billing_first_name(),
                'bill_to_phone' => $order->get_billing_phone(),
                'bill_to_surname' => $order->get_billing_last_name(),
            );
			if($ziip){
				//echo $this->get_card_code($this->check_cc($ziip_card['number']));
				//page 87
				//$one_month_amount = $this->number_format($order->get_total()/$ziip_plan, $order, true);
				//$params['recurring_frequency'] = 'monthly';
				//$params['recurring_number_of_installments'] = $ziip_plan - 1;
				//$params['recurring_amount'] = $one_month_amount;
				//$params['recurring_start_date'] = (new DateTime())->modify('+1 month')->format('Ymd');
				$params['transaction_type'] .= ',create_payment_token';
				$params['card_type'] = $this->get_card_code($this->check_cc($ziip_card['number']));
				$params['card_number'] = $ziip_card['number'];
				$params['card_expiry_date'] = $ziip_card['expired'];
				$params['card_cvn'] = $ziip_card['cvv'];
				$params['payment_method'] = 'card';
				
				$params['signed_field_names'] .= ',card_type,card_number,card_expiry_date,card_cvn,payment_method';
			}
            $params['signature'] = $this->sign($params,$ziip_plan);
            //$this->debug($params);die;
             //			$this->debug($params);die;
            // self::log('Checkout: '.json_encode($params));
            $link = $this->endpoint_checkout;				
            echo '<form action="' . $link . '" method="POST" name="jb_payment_form" id="jb_payment_form">';
            foreach ($params as $key => $val) {
                echo '<input name="' . $key . '" value="' . $val . '" type="hidden" />';
            }
            echo '<center style="font-size:18px;font-weight:bold;">Please wait while we redirect you to our payment processors website</center>';
            //echo '<center><button type="submit">'.__('continue', 'woocommerce').'</button></center>';
            echo '</form>';

            echo '<script>document.jb_payment_form.submit();</script>';
            return;
        }


        function sign ($params, $ziip_plan=0) {			
            return $this->signData($this->buildDataToSign($params), $this->getSecretKey($ziip_plan));
        }
        
        function signData($data, $secretKey) {
            return base64_encode(hash_hmac('sha256', $data, $secretKey, true));
        }
        
        function buildDataToSign($params) {
                $signedFieldNames = explode(",",$params["signed_field_names"]);
                foreach ($signedFieldNames as $field) {
                    $dataToSign[] = $field . "=" . $params[$field];
                }
                return $this->commaSeparate($dataToSign);
        }
        
        function commaSeparate ($dataToSign) {
            return implode(",",$dataToSign);
        }
		
		private function update_order_status(){
			$order_id = $_REQUEST['req_reference_number'];
            if(!$order_id){
                return false;
            }
            $order = wc_get_order((int) $order_id);
            if(!$order->get_id()){
                return false;
            }
			if (in_array($order->get_status(),['success','processing']))  {
				return $order;
			}
			

            $xml_prof = $_REQUEST['payer_authentication_proof_xml'];
            $tx_id = $_REQUEST['transaction_id'];
            $status = $_REQUEST['auth_response'];
            //$this->debug($_POST);
            //$this->debug($status);
            $card_number = $_REQUEST['req_card_number'];
            $params = $_REQUEST;
            unset($params['woocommerce-login-nonce']);
            unset($params['_wpnonce']);
            unset($params['woocommerce-reset-password-nonce']);
			$number_month = $_REQUEST['req_recurring_number_of_installments'];

            if (strcmp($params["signature"], $this->sign($params, $number_month))==0 && $status == '00' && $_REQUEST['decision']=='ACCEPT') {
				if($xml_prof){
					$order->add_order_note('proof_xml '.$xml_prof);
				}
                $order->add_order_note(sprintf(__('Payment of %1$s was captured - Credit card: %2$s, Transaction ID: %3$s', 'woocommerce'), $order_id, $card_number, $tx_id));
				if($number_month){
					$order->add_order_note('Number month of installments: '.$number_month.' Amount per month '.$_REQUEST['req_amount'].$_REQUEST['req_currency']);					
				}
				
                $order->payment_complete($tx_id);
                $order->reduce_order_stock();
                $order->update_meta_data( '_transaction_id', $tx_id );
            } else {
                $order->add_order_note("Payment failed. {$_REQUEST['message']}");
            }
			return $order;
		}
  

        //process in return
        public function capture_payment()
        {
            self::log('Notify: ' . json_encode($_REQUEST));
			
            $order = $this->update_order_status();
            exit;
        }
        public function receipt_payment()
        {
			self::log('return: ' . json_encode($_REQUEST));
			//sleep(2);
            $order = $this->update_order_status();
			
            if ($order && in_array($order->get_status(),['success','processing'])  ) {
                global $woocommerce;
                $woocommerce->cart->empty_cart();
                $thankyou_page = $this->thankyou_page ? get_permalink($this->thankyou_page) : esc_url_raw(add_query_arg('utm_nooverride', '1', $this->get_return_url($order)));
                wp_redirect($thankyou_page);
            } else {
				wc_add_notice($_REQUEST['message'],'error');
                wp_redirect(wc_get_page_permalink('cart'));
            }
            exit;
        }

        /**
         * Can the order be refunded via Public Bank?
         * @param  WC_Order $order
         * @return bool
         */
        public function can_refund_order($order)
        {
            return false;
        }

        /**
         * Process a refund if supported.
         * @param  int    $order_id
         * @param  float  $amount
         * @param  string $reason
         * @return bool True or false based on success, or a WP_Error object
         */
        public function process_refund($order_id, $amount = null, $reason = '')
        {
            $order = wc_get_order($order_id);
            //waitting for development
            return new WP_Error('error', __('None support refund', 'woocommerce'));
            /*
			if ( ! $this->can_refund_order( $order ) ) {
				self::log( 'Refund Failed: No transaction ID' );
				return new WP_Error( 'error', __( 'Refund Failed: No transaction ID', 'woocommerce' ) );
			}
			$order->add_order_note( sprintf( __( 'Refunded %s - Refund ID: %s', 'woocommerce' ), $result['GROSSREFUNDAMT'], $result['REFUNDTRANSACTIONID'] ) );
			*/
        }

        protected function number_format($price, $order=null, $up=false)
        {
            $decimals = 2;
			if($up){
				$price = ceil(($price*100))/100;	
			}			
            return sprintf('%012d', (number_format($price, $decimals, '.', '') ));
        }

        function get_pages($title = false, $indent = true)
        {
            $wp_pages = get_pages('sort_column=menu_order');
            $page_list = array();
            if ($title) $page_list[] = $title;
            foreach ($wp_pages as $page) {
                $prefix = '';
                // show indented child pages?
                if ($indent) {
                    $has_parent = $page->post_parent;
                    while ($has_parent) {
                        $prefix .=  ' - ';
                        $next_page = get_page($has_parent);
                        $has_parent = $next_page->post_parent;
                    }
                }
                // add to page list array array
                $page_list[$page->ID] = $prefix . $page->post_title;
            }
            return $page_list;
        }

        function debug($value)
        {
            echo '<pre>';
            print_r($value);
            echo '</pre>';
        }
    }

    $plugin = plugin_basename(__FILE__);
    add_filter("plugin_action_links_$plugin", 'plugin_public_bank_cybersource_action_links');
}
function woocommerce_add_malaysia_public_bank_cybersource($methods)
{
    $methods[] = 'WC_Gateway_Public_Bank_CyberSource';
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'woocommerce_add_malaysia_public_bank_cybersource');


function plugin_public_bank_cybersource_action_links($links)
{
    $plugin_links = array();

    if (version_compare(WC()->version, '2.6', '>=')) {
        $section_slug = 'public_bank_cybersource';
    } else {
        $section_slug = strtolower('WC_Gateway_Public_Bank_CyberSource');
    }
    $setting_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $section_slug);
    $plugin_links[] = '<a href="' . esc_url($setting_url) . '">' . esc_html__('Settings', 'woocommerce-payment-public-bank') . '</a>';

    return array_merge($plugin_links, $links);
}
