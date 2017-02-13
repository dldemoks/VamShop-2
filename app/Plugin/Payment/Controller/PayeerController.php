<?php 
App::uses('PaymentAppController', 'Payment.Controller');

class PayeerController extends PaymentAppController 
{
	public $uses = array('PaymentMethod', 'Order');
	public $module_name = 'Payeer';
	public $icon = 'payeer.png';

	public function settings ()
	{
		$this->set('data', $this->PaymentMethod->findByAlias($this->module_name));
	}

	public function install()
	{
		$new_module = array();
		$new_module['PaymentMethod']['active'] = '1';
		$new_module['PaymentMethod']['default'] = '0';
		$new_module['PaymentMethod']['name'] = Inflector::humanize($this->module_name);
		$new_module['PaymentMethod']['icon'] = $this->icon;
		$new_module['PaymentMethod']['alias'] = $this->module_name;

		$new_module['PaymentMethodValue'][0]['payment_method_id'] = $this->PaymentMethod->id;
		$new_module['PaymentMethodValue'][0]['key'] = 'm_url';
		$new_module['PaymentMethodValue'][0]['value'] = 'https://payeer.com/merchant/';

		$new_module['PaymentMethodValue'][1]['payment_method_id'] = $this->PaymentMethod->id;
		$new_module['PaymentMethodValue'][1]['key'] = 'm_shop';
		$new_module['PaymentMethodValue'][1]['value'] = '';

		$new_module['PaymentMethodValue'][2]['payment_method_id'] = $this->PaymentMethod->id;
		$new_module['PaymentMethodValue'][2]['key'] = 'm_key';
		$new_module['PaymentMethodValue'][2]['value'] = '';
		
		$new_module['PaymentMethodValue'][3]['payment_method_id'] = $this->PaymentMethod->id;
		$new_module['PaymentMethodValue'][3]['key'] = 'pathlog';
		$new_module['PaymentMethodValue'][3]['value'] = '';
		
		$new_module['PaymentMethodValue'][4]['payment_method_id'] = $this->PaymentMethod->id;
		$new_module['PaymentMethodValue'][4]['key'] = 'ipfilter';
		$new_module['PaymentMethodValue'][4]['value'] = '';
		
		$new_module['PaymentMethodValue'][5]['payment_method_id'] = $this->PaymentMethod->id;
		$new_module['PaymentMethodValue'][5]['key'] = 'emailerr';
		$new_module['PaymentMethodValue'][5]['value'] = '';

		$this->PaymentMethod->saveAll($new_module);
		$this->Session->setFlash(__('Module Installed'));
		$this->redirect('/payment_methods/admin/');
	}

	public function uninstall()
	{
		$module_id = $this->PaymentMethod->findByAlias($this->module_name);
		$this->PaymentMethod->delete($module_id['PaymentMethod']['id'], true);
		$this->Session->setFlash(__('Module Uninstalled'));
		$this->redirect('/payment_methods/admin/');
	}

	public function before_process () 
	{
		$order = $this->Order->read(null, $_SESSION['Customer']['order_id']);
		
		$payment_method = $this->PaymentMethod->find('first', array(
			'conditions' => array(
				'alias' => $this->module_name
			)
		));
		$payeer_url = $this->PaymentMethod->PaymentMethodValue->find('first', array(
			'conditions' => array(
				'key' => 'm_url'
			)
		));
		$payeer_shop = $this->PaymentMethod->PaymentMethodValue->find('first', array(
			'conditions' => array(
				'key' => 'm_shop'
			)
		));
		$payeer_key = $this->PaymentMethod->PaymentMethodValue->find('first', array(
			'conditions' => array(
				'key' => 'm_key'
			)
		));
		
		$m_url = $payeer_url['PaymentMethodValue']['value'];
		$m_shop = $payeer_shop['PaymentMethodValue']['value'];
		$m_key = $payeer_key['PaymentMethodValue']['value'];
		$m_desc = base64_encode($order['OrderComment'][0]['comment']);
		$m_orderid = $_SESSION['Customer']['order_id'];
		$m_amount = number_format($order['Order']['total'], 2, '.', '');
		$m_curr = $_SESSION['Customer']['currency_code'] == 'RUR' ? 'RUB' : $_SESSION['Customer']['currency_code'];

		$arHash = array(
			$m_shop,
			$m_orderid,
			$m_amount,
			$m_curr,
			$m_desc,
			$m_key
		);

		$sign = strtoupper(hash('sha256', implode(':', $arHash)));

		$content = '
		<form action="' . $m_url . '" method="GET">
			<input type="hidden" name="m_shop" value="' . $m_shop . '">
			<input type="hidden" name="m_orderid" value="' . $m_orderid . '">
			<input type="hidden" name="m_amount" value="' . $m_amount . '">
			<input type="hidden" name="m_curr" value="' . $m_curr . '">
			<input type="hidden" name="m_desc" value="' . $m_desc . '">
			<input type="hidden" name="m_sign" value="' . $sign . '">
			<button 
				class="btn btn-inverse" 
				type="submit" 
				value="{lang}Process to Payment{/lang}">
				<i class="fa fa-check"></i> 
				{lang}Process to Payment{/lang}
			</button>
		</form>';
	
		foreach($_POST AS $key => $value)
		{
			$order['Order'][$key] = $value;
		}
		
		$default_status = $this->Order->OrderStatus->find('first', array(
			'conditions' => array(
				'default' => '1'
			)
		));
		
		$order['Order']['order_status_id'] = $default_status['OrderStatus']['id'];
		$this->Order->save($order);

		return $content;
	}

	public function after_process(){}
	
	public function result()
	{
		if (isset($_POST["m_operation_id"]) && isset($_POST["m_sign"]))
		{
			$err = false;
			$message = '';
			$this->layout = false;
			
			$payeer_pathlog = $this->PaymentMethod->PaymentMethodValue->find('first', array(
				'conditions' => array(
					'key' => 'pathlog'
				)
			));
			$payeer_key = $this->PaymentMethod->PaymentMethodValue->find('first', array(
				'conditions' => array(
					'key' => 'm_key'
				)
			));
			$payeer_ipfilter = $this->PaymentMethod->PaymentMethodValue->find('first', array(
				'conditions' => array(
					'key' => 'ipfilter'
				)
			));
			$payeer_emailerr = $this->PaymentMethod->PaymentMethodValue->find('first', array(
				'conditions' => array(
					'key' => 'emailerr'
				)
			));
			
			$pathlog = $payeer_pathlog['PaymentMethodValue']['value'];
			$m_key = $payeer_key['PaymentMethodValue']['value'];
			$ipfilter = $payeer_ipfilter['PaymentMethodValue']['value'];
			$emailerr = $payeer_emailerr['PaymentMethodValue']['value'];
			
			// запись логов
			
			$log_text = 
			"--------------------------------------------------------\n" .
			"operation id       " . $_POST['m_operation_id'] . "\n" .
			"operation ps       " . $_POST['m_operation_ps'] . "\n" .
			"operation date     " . $_POST['m_operation_date'] . "\n" .
			"operation pay date " . $_POST['m_operation_pay_date'] . "\n" .
			"shop               " . $_POST['m_shop'] . "\n" .
			"order id           " . $_POST['m_orderid'] . "\n" .
			"amount             " . $_POST['m_amount'] . "\n" .
			"currency           " . $_POST['m_curr'] . "\n" .
			"description        " . base64_decode($_POST['m_desc']) . "\n" .
			"status             " . $_POST['m_status'] . "\n" .
			"sign               " . $_POST['m_sign'] . "\n\n";
			
			$log_file = $pathlog;
			
			if (!empty($log_file))
			{
				file_put_contents($_SERVER['DOCUMENT_ROOT'] . $log_file, $log_text, FILE_APPEND);
			}
			
			// проверка цифровой подписи и ip

			$sign_hash = strtoupper(hash('sha256', implode(":", array(
				$_POST['m_operation_id'],
				$_POST['m_operation_ps'],
				$_POST['m_operation_date'],
				$_POST['m_operation_pay_date'],
				$_POST['m_shop'],
				$_POST['m_orderid'],
				$_POST['m_amount'],
				$_POST['m_curr'],
				$_POST['m_desc'],
				$_POST['m_status'],
				$m_key
			))));
			
			$valid_ip = true;
			$sIP = str_replace(' ', '', $ipfilter);
			
			if (!empty($sIP))
			{
				$arrIP = explode('.', $_SERVER['REMOTE_ADDR']);
				if (!preg_match('/(^|,)(' . $arrIP[0] . '|\*{1})(\.)' .
				'(' . $arrIP[1] . '|\*{1})(\.)' .
				'(' . $arrIP[2] . '|\*{1})(\.)' .
				'(' . $arrIP[3] . '|\*{1})($|,)/', $sIP))
				{
					$valid_ip = false;
				}
			}
			
			if (!$valid_ip)
			{
				$message .= __d('payeer',' - the ip address of the server is not trusted') . "\n" .
				__d('payeer','   trusted ip: ') . $ipfilter . "\n" .
				__d('payeer','   ip of the current server: ') . $_SERVER['REMOTE_ADDR'] . "\n";
				$err = true;
			}

			if ($_POST['m_sign'] != $sign_hash)
			{
				$message .= __d('payeer',' - Do not match the digital signature') . "\n";
				$err = true;
			}
			
			if (!$err)
			{
				// загрузка заказа

				$order = $this->Order->read(null, $_POST['m_orderid']);
				
				if (!$order)
				{
					$message .= __d('payeer',' - undefined order id') . "\n";
					$err = true;
				}
				else
				{
					$order_amount = number_format($order['Order']['total'], 2, '.', '');
					$payment_method = $this->PaymentMethod->find('first', array(
						'conditions' => array(
							'alias' => $this->module_name
						)
					));
					
					// проверка суммы
				
					if ($_POST['m_amount'] != $order_amount)
					{
						$message .= __d('payeer',' - wrong amount') . "\n";
						$err = true;
					}
					
					// проверка оплачен ли заказ
					
					if ($order['Order']['order_status_id'] == $payment_method['PaymentMethod']['order_status_id'])
					{
						$message .= __d('payeer',' - order already paid') . "\n";
						$err = true;
					}
					
					// проверка статуса
					
					if (!$err)
					{
						switch ($_POST['m_status'])
						{
							case 'success':
								
								$order_data = $this->Order->find('first', array(
									'conditions' => array(
										'Order.id' => $_POST['m_orderid']
									)
								));
								
								$order_data['Order']['order_status_id'] = $payment_method['PaymentMethod']['order_status_id'];
								$this->Order->save($order_data);
								
								break;
								
							default:
							
								$message .= __d('payeer',' - The payment status is not success') . "\n";
								$err = true;
								
								break;
						}
					}
				}
			}
			
			if ($err)
			{
				$to = $emailerr;

				if (!empty($to))
				{
					$message = __d('payeer','Failed to make the payment through the system Payeer for the following reasons') . ":\n\n" . $message . "\n" . $log_text;
					$headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n" . 
					"Content-type: text/plain; charset=utf-8 \r\n";
					mail($to, __d('payeer','Error payment'), $message, $headers);
				}
				
				exit($_POST['m_orderid'] . '|error');
			}
			else
			{
				exit($_POST['m_orderid'] . '|success');
			}
		}
	}
}
?>