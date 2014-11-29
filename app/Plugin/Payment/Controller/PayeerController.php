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
		$new_module['PaymentMethodValue'][0]['value'] = '//payeer.com/merchant/';

		$new_module['PaymentMethodValue'][1]['payment_method_id'] = $this->PaymentMethod->id;
		$new_module['PaymentMethodValue'][1]['key'] = 'm_shop';
		$new_module['PaymentMethodValue'][1]['value'] = '';

		$new_module['PaymentMethodValue'][2]['payment_method_id'] = $this->PaymentMethod->id;
		$new_module['PaymentMethodValue'][2]['key'] = 'm_key';
		$new_module['PaymentMethodValue'][2]['value'] = '';
		
		$new_module['PaymentMethodValue'][3]['payment_method_id'] = $this->PaymentMethod->id;
		$new_module['PaymentMethodValue'][3]['key'] = 'm_desc';
		$new_module['PaymentMethodValue'][3]['value'] = '';
		
		$new_module['PaymentMethodValue'][4]['payment_method_id'] = $this->PaymentMethod->id;
		$new_module['PaymentMethodValue'][4]['key'] = 'pathlog';
		$new_module['PaymentMethodValue'][4]['value'] = '';
		
		$new_module['PaymentMethodValue'][5]['payment_method_id'] = $this->PaymentMethod->id;
		$new_module['PaymentMethodValue'][5]['key'] = 'ipfilter';
		$new_module['PaymentMethodValue'][5]['value'] = '';
		
		$new_module['PaymentMethodValue'][6]['payment_method_id'] = $this->PaymentMethod->id;
		$new_module['PaymentMethodValue'][6]['key'] = 'emailerr';
		$new_module['PaymentMethodValue'][6]['value'] = '';

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
		
		$payment_method = $this->PaymentMethod->find('first', array('conditions' => array('alias' => $this->module_name)));

		$payeer_url = $this->PaymentMethod->PaymentMethodValue->find('first', array('conditions' => array('key' => 'm_url')));
		$m_url = $payeer_url['PaymentMethodValue']['value'];
		
		$payeer_shop = $this->PaymentMethod->PaymentMethodValue->find('first', array('conditions' => array('key' => 'm_shop')));
		$m_shop = $payeer_shop['PaymentMethodValue']['value'];
		
		$payeer_key = $this->PaymentMethod->PaymentMethodValue->find('first', array('conditions' => array('key' => 'm_key')));
		$m_key = $payeer_key['PaymentMethodValue']['value'];
		
		$payeer_desc = $this->PaymentMethod->PaymentMethodValue->find('first', array('conditions' => array('key' => 'm_desc')));
		$m_desc = base64_encode($payeer_desc['PaymentMethodValue']['value']);
		
		$m_orderid = $_SESSION['Customer']['order_id'];
		$m_amount = number_format($order['Order']['total'], 2, '.', '');
		$m_curr = $_SESSION['Customer']['currency_code'];
		
		if ($m_curr == 'RUR')
		{
			$m_curr = 'RUB';
		}

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
		
		$default_status = $this->Order->OrderStatus->find('first', array('conditions' => array('default' => '1')));
		$order['Order']['order_status_id'] = $default_status['OrderStatus']['id'];
		
		$this->Order->save($order);

		return $content;
	}

	public function after_process()
	{
	}
	
	public function result()
	{
		$this->layout = false;
		
		if (isset($_POST["m_operation_id"]) && isset($_POST["m_sign"]))
		{
			$payeer_key = $this->PaymentMethod->PaymentMethodValue->find('first', array('conditions' => array('key' => 'm_key')));
			$m_key = $payeer_key['PaymentMethodValue']['value'];
			
			$payeer_ipfilter = $this->PaymentMethod->PaymentMethodValue->find('first', array('conditions' => array('key' => 'ipfilter')));
			$ipfilter = $payeer_ipfilter['PaymentMethodValue']['value'];
			
			$payeer_pathlog = $this->PaymentMethod->PaymentMethodValue->find('first', array('conditions' => array('key' => 'pathlog')));
			$pathlog = $payeer_pathlog['PaymentMethodValue']['value'];
			
			$payeer_emailerr = $this->PaymentMethod->PaymentMethodValue->find('first', array('conditions' => array('key' => 'emailerr')));
			$emailerr = $payeer_emailerr['PaymentMethodValue']['value'];
			
			$arHash = array(
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
				$m_key);
			$sign_hash = strtoupper(hash('sha256', implode(":", $arHash)));
			
			// проверка принадлежности ip списку доверенных ip
			$list_ip_str = str_replace(' ', '', $ipfilter);
			
			if (!empty($list_ip_str)) 
			{
				$list_ip = explode(',', $list_ip_str);
				$this_ip = $_SERVER['REMOTE_ADDR'];
				$this_ip_field = explode('.', $this_ip);
				$list_ip_field = array();
				$i = 0;
				$valid_ip = FALSE;
				foreach ($list_ip as $ip)
				{
					$ip_field[$i] = explode('.', $ip);
					if ((($this_ip_field[0] ==  $ip_field[$i][0]) or ($ip_field[$i][0] == '*')) and
						(($this_ip_field[1] ==  $ip_field[$i][1]) or ($ip_field[$i][1] == '*')) and
						(($this_ip_field[2] ==  $ip_field[$i][2]) or ($ip_field[$i][2] == '*')) and
						(($this_ip_field[3] ==  $ip_field[$i][3]) or ($ip_field[$i][3] == '*')))
						{
							$valid_ip = TRUE;
							break;
						}
					$i++;
				}
			}
			else
			{
				$valid_ip = TRUE;
			}
			
			$log_text = 
				"--------------------------------------------------------\n".
				"operation id		".$_POST["m_operation_id"]."\n".
				"operation ps		".$_POST["m_operation_ps"]."\n".
				"operation date		".$_POST["m_operation_date"]."\n".
				"operation pay date	".$_POST["m_operation_pay_date"]."\n".
				"shop				".$_POST["m_shop"]."\n".
				"order id			".$_POST["m_orderid"]."\n".
				"amount				".$_POST["m_amount"]."\n".
				"currency			".$_POST["m_curr"]."\n".
				"description		".base64_decode($_POST["m_desc"])."\n".
				"status				".$_POST["m_status"]."\n".
				"sign				".$_POST["m_sign"]."\n\n";
					
			if (!empty($pathlog))
			{	
				file_put_contents($_SERVER['DOCUMENT_ROOT'] . $pathlog, $log_text, FILE_APPEND);
			}
			
			if ($_POST["m_sign"] == $sign_hash && $_POST['m_status'] == "success" && $valid_ip)
			{
				$payment_method = $this->PaymentMethod->find('first', array('conditions' => array('alias' => $this->module_name)));
				$order_data = $this->Order->find('first', array('conditions' => array('Order.id' => $_POST['m_orderid'])));
				$order_data['Order']['order_status_id'] = $payment_method['PaymentMethod']['order_status_id'];
				$this->Order->save($order_data);
				
				exit($_POST['m_orderid'] . '|success');
			}
			else
			{
				$to = $emailerr;
				$subject = __d('payeer','Error payment');
				$message = __d('payeer','Failed to make the payment through the system Payeer for the following reasons') . ":\n\n";
				if ($_POST["m_sign"] != $sign_hash)
				{
					$message .= __d('payeer',' - Do not match the digital signature') . "\n";
				}
				if ($_POST['m_status'] != "success")
				{
					$message .= __d('payeer',' - The payment status is not success') . "\n";
				}
				if (!$valid_ip)
				{
					$message .= __d('payeer',' - the ip address of the server is not trusted') . "\n";
					$message .= __d('payeer','   trusted ip: ') . $ipfilter . "\n";
					$message .= __d('payeer','   ip of the current server: ') . $_SERVER['REMOTE_ADDR'] . "\n";
				}
				$message .= "\n" . $log_text;
				$headers = "From: no-reply@" . $_SERVER['HTTP_SERVER'] . "\r\nContent-type: text/plain; charset=utf-8 \r\n";
				mail($to, $subject, $message, $headers);
				exit ($_POST['m_orderid'] . '|error');
			}
		}
			
			
		
		
	}
	
}

?>