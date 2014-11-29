<?
header("Content-Type: text/html; charset=utf-8");

$mes = '<p>Ваш заказ № ' . intval($_GET['m_orderid']) . ' успешно оплачен.</p>';

?>

<html>
	<link rel="stylesheet" type="text/css" href="css/vamshop.css"/>
	<head>
		<style>
			body
			{
				font-size: 25px;
				background-color: #395969; 
			}
			
			.btn
			{
				cursor: pointer;
			}
			.btn:hover
			{
				color: #fff;
			}
			
			.payment_message
			{
				text-align: center;
				font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
				padding-top: 40px;
				box-shadow: 0px 0px 10px #fff;
				background-color: #ffffff;
				position: fixed;
				width: 500px;
				height: 200px;
				left: 50%;
				top: 30%;
				margin-left: -250px;
				margin-top: -100px;
				z-index: 1005;
				animation-name: payment_message;
				-webkit-animation-name: payment_message;	
				animation-duration: 0.5s;	
				-webkit-animation-duration: 0.5s;
				animation-timing-function: ease-out;	
				-webkit-animation-timing-function: ease-out;
			}
			@keyframes payment_message {
				0% {
					transform: scale(1) rotate(0deg) translateX(0%) translateY(-10%);
					opacity: 0;
				}
				100% {
					transform: scale(1) rotate(0deg) translateX(0%) translateY(0%);
					opacity: 1;
				}		
			}
			@-webkit-keyframes payment_message {
				0% {
					-webkit-transform: scale(1) rotate(0deg) translateX(0%) translateY(-10%);
					opacity: 0;
				}
				100% {
					-webkit-transform: scale(1) rotate(0deg) translateX(0%) translateY(0%);
					opacity: 1;
				}				
			}
		</style>
		<title>Заказ успешно оплачен</title>
	</head>
	
	<body>
		<div class='payment_message'>
			<h4><?=$mes;?></h4>
			<button class="btn btn-inverse" type="submit" onCLick="location='http://<?php echo $_SERVER['HTTP_HOST'] . '/orders/place_order/';?>';"> 
				Вернуться в магазин
			</button>
		</div>
	</body>
</html>