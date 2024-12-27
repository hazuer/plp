<?php
session_start();
define( '_VALID_MOS', 1 );

require_once('../system/configuration.php');

if(isset($_SESSION["uActive"])){
	header('Location: '.BASE_URL.'/views/packages.php');
	die();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title><?php echo PAGE_TITLE; ?></title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->	
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL;?>/assets/img/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL;?>/assets/img/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL;?>/assets/img/favicon/favicon-16x16.png">
    <link rel="manifest" href="<?php echo BASE_URL;?>/assets/img/favicon/site.webmanifest">
    <link rel="mask-icon" href="<?php echo BASE_URL;?>/assets/img/favicon/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="<?php echo BASE_URL;?>/admin/vendor/bootstrap/css/bootstrap.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="<?php echo BASE_URL;?>/admin/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="<?php echo BASE_URL;?>/admin/vendor/animate/animate.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="<?php echo BASE_URL;?>/admin/css/util.css">
	<link rel="stylesheet" type="text/css" href="<?php echo BASE_URL;?>/admin/css/main.css">
<!--===============================================================================================-->
<script src="<?php echo BASE_URL;?>/assets/js/libraries/sweetalert.min.js"></script>

<script>
	let base_url = '<?php echo BASE_URL;?>';
</script>

</head>
<body>
	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<div class="login100-pic js-tilt" data-tilt>
					<img src="<?php echo BASE_URL;?>/admin/images/plp.png" alt="IMG">
				</div>

				<form class="login100-form validate-form">
					<span class="login100-form-title">
						Iniciar Sesión
					</span>

					<div class="wrap-input100 validate-input" data-validate = "Ingresa usuario">
						<input class="input100" type="text" name="username" id="username" title="Usuario"  placeholder="* Usuario" autocomplete="off">
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-user" aria-hidden="true"></i>
						</span>
					</div>

					<div class="wrap-input100 validate-input" data-validate = "Ingresa contraseña">
						<input class="input100" type="password" name="password" id="password" title="Contraseña" placeholder="* Contraseña" autocomplete="off">
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-lock" aria-hidden="true"></i>
						</span>
					</div>

					<div class="container-login100-form-btn">
						<button name="btn-login" id="btn-login" class="login100-form-btn" type="button">
							Ingresar
						</button>
					</div>

					<div class="text-center p-t-12">
						<span class="txt1">
							&NonBreakingSpace;
						</span>
						<a class="txt2" href="#">
							&NonBreakingSpace;
						</a>
					</div>

					<div class="text-center p-t-136">
						&NonBreakingSpace;
					</div>
				</form>
			</div>
		</div>
	</div>

<!--===============================================================================================-->	
	<script src="<?php echo BASE_URL;?>/admin/vendor/jquery/jquery-3.2.1.min.js"></script>
<!--===============================================================================================-->
	<script src="<?php echo BASE_URL;?>/admin/vendor/bootstrap/js/popper.js"></script>
	<script src="<?php echo BASE_URL;?>/admin/vendor/bootstrap/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
	<script src="<?php echo BASE_URL;?>/admin/vendor/tilt/tilt.jquery.min.js"></script>
	<script >
		$('.js-tilt').tilt({
			scale: 1.1
		})
	</script>
<!--===============================================================================================-->
	<script src="<?php echo BASE_URL;?>/admin/js/main.js"></script>
	<script src="<?php echo BASE_URL;?>/assets/js/login.js"></script>
	<script src="<?php echo BASE_URL;?>/assets/js/functions.js"></script>
</body>
</html>