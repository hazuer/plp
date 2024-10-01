<?php
session_start();
define( '_VALID_MOS', 1 );

require_once('../system/configuration.php');
require_once('../system/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);

require_once('../system/session_cookies.php');
$id_location = $_SESSION['uLocation'];

?>
<!doctype html>
<html lang = "en">
	<head>
		<?php include '../views/header.php'; ?>
		<script>
    	let templateMsj =`<?php echo $templateMsj;?>`;
		let uMarker =`<?php echo $_SESSION["uMarker"];?>`;
		</script>
		<script src="<?php echo BASE_URL;?>/assets/js/packages.js"></script>
		<style>
			.mensaje {
				color: gray;
				text-decoration: none; /* Quitar subrayado */
			}
			.mensaje-enviado {
				color: green;
				text-decoration: none; /* Quitar subrayado */
			}
		</style>
	</head>
	<body>
		<div class="main">
			<?php
				include '../views/navTop.php';
			?>

			<form id="frm-package">
			<h3>🤖 Envío Manual de Mensajes <?php echo $desc_loc;?></h3>
			<?php
				include('modal/handler.php');
			?>
			</form>
		</div>
		<script>
		$('#msjbt').hide();
		let baseController = 'controllers/packageController.php';
        let enlaces = document.querySelectorAll(".mensaje");

        enlaces.forEach(function(enlace) {
            enlace.addEventListener("click", function() {
                enlace.classList.add("mensaje-enviado");
            });
        });

		let enviar = document.querySelectorAll('.mensaje');
        enviar.forEach(function(enlace) {
            enlace.addEventListener('click', function(event) {
                event.preventDefault();
                let telefono = enlace.getAttribute('data-phone');
				let formData = new FormData();
				formData.append('id_location',$('#idlocbt').val());
				formData.append('uidbt',$('#uidbt').val());
				formData.append('msjbt',$('#msjbt').val());
				formData.append('telefono',telefono);
				formData.append('option','mensajeManual');
				$.ajax({
					url: `${base_url}/${baseController}`,
					type       : 'POST',
					data       : formData,
					cache      : false,
					contentType: false,
					processData: false,
				})
				.done(function(response) {
					if (response.success === 'true') {
						let msjbt = $('#msjbt').val();
						let folios = response.message;
						let fullMessage = encodeURIComponent(`🤖 ${msjbt} ${folios}`);
						var url = `https://api.whatsapp.com/send/?phone=${telefono}&text=${fullMessage}`;
						window.open(url);
					}else{
						swal('Atención.!', 'Ya has procesado el número '+ telefono, "warning");
					}
				}).fail(function(e) {
					console.log("Opps algo salio mal",e);
				});
            });
        });

    </script>
	<?php
	include('modal/package.php');
	include('footer.php');
	?>
	</body>
</html>