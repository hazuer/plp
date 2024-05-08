<br>
<?php
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
// Host
$host = $_SERVER['HTTP_HOST'];
// URI
$uri = $_SERVER['REQUEST_URI'];
// URL completa
$fullUrl = $protocol . $host . $uri;
$nombreArchivo = basename($fullUrl, ".php");
// Dividir la cadena para obtener solo el nombre del archivo
$paginaInUse = explode(".", $nombreArchivo)[0];

$selectDisabled='';
if($_SESSION["uId"] ==5 || $_SESSION["uId"]==6){
	$selectDisabled='disabled';
}
?>
<div class="row">
	<div class="col-md-12">
		<div class="btn-group" role="group">
			<button id="logoff" type="button" class="btn-sm btn-danger"  title="Cerrar sesión">
				<i class="fa fa-power-off fa-lg" aria-hidden="true" style="color:#ffc107;"></i>
			</button>
			<button id="home" type="button" class="btn-sm btn-primary"  title="Paquetes">
				<i class="fa fa-cubes fa-lg" aria-hidden="true"></i>
			</button>
			<button id="btn-inspect" type="button" class="btn-sm btn-primary"  title="Usuarios y Paquetes Pendientes de Entrega">
				<i class="fa fa-crosshairs fa-lg" aria-hidden="true"></i>
			</button>
			<button id="btn-report" type="button" class="btn-sm btn-primary" title="Reportes">
				<i class="fa fa-bar-chart fa-lg" aria-hidden="true"></i>
			</button>
			<input style="width:100px;" type="text" class="form-control" value="<?php echo $_SESSION['uName']; ?>" value="" disabled="">
			<select name="option-location" id="option-location" class="form-control" <?php echo $selectDisabled; ?> >
				<option value="1" <?php echo ($_SESSION['uLocation']==1) ? 'selected': ''; ?> >Tlaquiltenango</option>
				<option value="2" <?php echo ($_SESSION['uLocation']==2) ? 'selected': ''; ?> >Zacatepec</option>
			</select>
			<?php
			if($paginaInUse=="packages"){
			?>
			<button id="btn-folio" type="button" class="btn-sm btn-warning" title="Configurar folio">
				<i class="fa fa-hashtag fa-lg" aria-hidden="true"></i>
			</button>
			<button id="btn-contacto" type="button" class="btn-sm btn-warning" title="Agregar contacto">
				<i class="fa fa-user fa-lg" aria-hidden="true"></i>
			</button>
			<button id="btn-template" type="button" class="btn-sm btn-warning" title="Plantillas">
				<i class="fa fa-file-text-o fa-lg" aria-hidden="true"></i>
			</button>
			<button id="btn-add-package" type="button" class="btn-success btn-sm" title="Nuevo paquete">
				<i class="fa fa-cube fa-lg" aria-hidden="true"></i>
			</button>
			<?php if($host!='buildingsolutionspro.net'){?>
			<button id="btn-bot" type="button" class="btn-sm btn-primary" title="Bot">
				<i class="fa fa-simplybuilt fa-lg" aria-hidden="true"></i>
			</button>
			<?php }?>
			<button id="btn-release-package" type="button" class="btn-sm btn-success" title="Entrega de paquetes">
				<i class="fa fa-check-square-o fa-lg" aria-hidden="true"></i>
			</button>
			<button id="btn-sync" type="button" class="btn-sm btn-success" title="Sync">
				<i class="fa fa-refresh fa-lg" aria-hidden="true"></i>
			</button>
			<button id="btn-ocurre" type="button" class="btn-sm btn-success" title="Crear Códigos de Barras">
				<i class="fa fa-barcode fa-lg" aria-hidden="true"></i>
			</button>
			<?php }?>
		</div>
	</div>
</div>
<hr>