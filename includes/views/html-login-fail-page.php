<?php
//
?>
<html>
	<head>
		<title><?php _e( 'MercadoLivre' , ML()->textdomain ); ?></title>
		<style type="text/css">
			* {
				margin: 0;
				padding: 0;
			}
			body {
				padding: 0;
				margin: 0;
				overflow: hidden;
				background-color: #464646;
			}
			p {
				color: white;
				text-align: center;
				font-family: sans-serif;
			}
			img {
				margin-left: auto;
				margin-right: auto;
				display: block;
			}
			.main_content {
				height: 85%;
				width: 100%;
			}
			.footer {
				background-color: #ff4747;
				height: 15%;
				width: 100%;
			}
			.img_login {
				padding: 15% 0 3% 0;
			}
			.img_logo {
				padding: 3% 0 3% 0;
			}
			.free_message {
				padding-top: 3%;
			}
			.link_premium {
				color: #ffe04a;
			}
			.img_free {
				float: right;
				margin: -60px 0 0 0;
			}
		</style>
	</head>
	<body>
		<div class="content">
			<div class="main_content">
				<img src="<?php echo ML()->get_plugin_url( 'assets/img/login-fail.png' ); ?>" class="img_login">
				<p><?php printf( '%s: %s' , __( 'Error' , ML()->textdomain ) , $_GET['error_description'] ); ?></p>
				<img src="<?php echo ML()->get_plugin_url( 'assets/img/logos.png' ); ?>" class="img_logo">
			</div>
			<img src="<?php echo ML()->get_plugin_url( 'assets/img/woo-mercadolivre.png' ); ?>" class="img_free">
			<div class="footer">
				<p class="free_message"><?php printf( __( 'Click %shere%s to see the premium version of the plugin WooCommerce MercadoLivre.' , ML()->textdomain ) , '<a href="http://www.woocommercemercadolivre.com.br/" target="_blank" class="link_premium">' , '</a>' ); ?></p>
			</div>
		</div>
	</body>
</html>