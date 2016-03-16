<?php
echo $this->Form->input('payeer.m_url', array(
	'label' => __d('payeer','Merchant URL'),
	'type' => 'text',
	'value' => $data['PaymentMethodValue'][0]['value']
));
	
echo $this->Form->input('payeer.m_shop', array(
	'label' => __d('payeer','Merchant ID'),
	'type' => 'text',
	'value' => $data['PaymentMethodValue'][1]['value']
));
	
echo $this->Form->input('payeer.m_key', array(
	'label' => __d('payeer','Secret key'),
	'type' => 'text',
	'value' => $data['PaymentMethodValue'][2]['value']
));
	
echo $this->Form->input('payeer.pathlog', array(
	'label' => __d('payeer','Path to file to log payments through Payeer (for example, /payeer_orders.log)'),
	'type' => 'text',
	'value' => $data['PaymentMethodValue'][3]['value']
));
	
echo $this->Form->input('payeer.ipfilter', array(
	'label' => __d('payeer','IP filter handler payment'),
	'type' => 'text',
	'value' => $data['PaymentMethodValue'][4]['value']
));
	
echo $this->Form->input('payeer.emailerr', array(
	'label' => __d('payeer','Email for payment errors'),
	'type' => 'text',
	'value' => $data['PaymentMethodValue'][5]['value']
));
?>