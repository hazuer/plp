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

$txtchg = ($_SESSION['uLocation']==2) ? "Tlaquiltenango":"Zacatepec";
$txtchgval = ($_SESSION['uLocation']==2) ? 1:2;
?>
<div class="row">
	<div class="col-md-12">
		<div class="btn-group" role="group">
			<button id="logoff" type="button" class="btn-sm btn-danger" data-toggle="tooltip" data-placement="top" title="Cerrar sesión">
				<i class="fa fa-power-off fa-lg" aria-hidden="true" style="color:#ffc107;"></i>
			</button>
			<button id="home" type="button" class="btn-sm btn-primary" data-toggle="tooltip" data-placement="top" title="Paquetes">
				<i class="fa fa-cubes fa-lg" aria-hidden="true"></i>
			</button>
			<!-- <button id="btn-grouped" type="button" class="btn-sm btn-primary" data-toggle="tooltip" data-placement="top" title="Usuarios y Paquetes Agrupados">
				<i class="fa fa-crosshairs fa-lg" aria-hidden="true"></i>
			</button> -->
			<button id="btn-report" type="button" class="btn-sm btn-primary" data-toggle="tooltip" data-placement="top" title="Reportes">
				<i class="fa fa-bar-chart fa-lg" aria-hidden="true"></i>
			</button>
			<button id="btn-chart" type="button" class="btn-sm btn-primary" data-toggle="tooltip" data-placement="top" title="Porcentaje de Entrega">
				<i class="fa fa-pie-chart fa-lg" aria-hidden="true"></i>
			</button>
			<button id="btn-map" type="button" class="btn-sm btn-primary" data-toggle="tooltip" data-placement="top" title="Map">
				<i class="fa fa-th fa-lg" aria-hidden="true"></i>
			</button>
			<button id="btn-list-contact" type="button" class="btn-sm btn-primary" data-toggle="tooltip" data-placement="top" title="Lista de Contactos">
				<i class="fa fa-users fa-lg" aria-hidden="true"></i>
			</button>
			<input id="display-user" style="width:100px;" type="text" class="form-control d-none d-md-inline" value="<?php echo $_SESSION['uName']; ?>" value="" disabled="">

			<div class="dropdown d-md-none">
                <button class="btn-sm btn-primary dropdown-toggle" type="button" id="mobileMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Opc
				</button>
                <div class="dropdown-menu" aria-labelledby="mobileMenuButton">
					<!-- Elementos agrupados en el menú desplegable -->
					<button id="option-location-1" class="dropdown-item" data-slocation="<?php echo $txtchgval; ?>" data-slocationd="<?php echo $txtchg;?>">
						<i class="fa fa-map-marker fa-lg" aria-hidden="true"></i> Cambiar Ubicación a <?php echo $txtchg;?>
					</button>
					<button id="btn-folio-1" class="dropdown-item">
						<i class="fa fa-hashtag fa-lg" aria-hidden="true"></i> Configurar Folio
					</button>
					<button id="btn-template-1" class="dropdown-item">
						<i class="fa fa-file-text-o fa-lg" aria-hidden="true"></i> Plantillas de Mensajes
					</button>
					<button id="btn-add-package-1" class="dropdown-item">
						<span style="font-size: 1.2rem;">📦</span></i> Nuevo Paquete
					</button>
					<!--<button id="btn-release-package-1" class="dropdown-item">
						<i class="fa fa-check-square-o fa-lg" aria-hidden="true"></i> Entrega de Paquetes
					</button>
					-->
					<button id="btn-sync-1" class="dropdown-item">
						<i class="fa fa-refresh fa-lg" aria-hidden="true"></i> Verificación de Guías Liberadas
					</button>
					<button id="btn-ocurre-1" class="dropdown-item">
						<i class="fa fa-barcode fa-lg" aria-hidden="true"></i> Crear Códigos de Barras
					</button>
                </div>
            </div>
			<select name="option-location" id="option-location" class="form-control d-none d-md-inline" <?php echo $selectDisabled; ?> >
				<option value="1" <?php echo ($_SESSION['uLocation']==1) ? 'selected': ''; ?> >TLQ</option>
				<option value="2" <?php echo ($_SESSION['uLocation']==2) ? 'selected': ''; ?> >ZAC</option>
			</select>
			<?php
			if($paginaInUse=="packages"){
			?>
			<button id="btn-folio" type="button" class="btn-sm btn-warning d-none d-md-inline" data-toggle="tooltip" data-placement="top" title="Configurar Folio">
				<i class="fa fa-hashtag fa-lg" aria-hidden="true"></i>
			</button>
			<button id="btn-template" type="button" class="btn-sm btn-warning d-none d-md-inline" data-toggle="tooltip" data-placement="top" title="Plantillas de Mensajes">
				<i class="fa fa-file-text-o fa-lg" aria-hidden="true"></i>
			</button>
			<button id="btn-add-package" type="button" class="btn-success btn-sm d-none d-md-inline" data-toggle="tooltip" data-placement="top" title="Nuevo Paquete">
				<span style="font-size: 1.2rem;">📦</span>
			</button>
			<?php if($host==NAME_HOST_LOCAL){?>
			<button id="btn-bot" type="button" class="btn-sm btn-primary d-none d-md-inline" data-toggle="tooltip" data-placement="top" title="Chatbot Envío de Mensajes">
				  <span style="font-size: 1.2rem;">🤖</span>
			</button>
			<button id="btn-handler" type="button" class="btn-sm btn-primary d-none d-md-inline" data-toggle="tooltip" data-placement="top" title="Envío Manual de Mensajes">
				<i class="fa fa-hand-o-up fa-lg" aria-hidden="true"></i>
			</button>
			<?php }?>
			<!-- <button id="btn-release-package" type="button" class="btn-sm btn-success d-none d-md-inline" data-toggle="tooltip" data-placement="top" title="Entrega de Paquetes">
				<i class="fa fa-check-square-o fa-lg" aria-hidden="true"></i>
			</button> -->
			<button id="btn-sync" type="button" class="btn-sm btn-success d-none d-md-inline" data-toggle="tooltip" data-placement="top" title="Verificación de Guías Liberadas">
				<i class="fa fa-refresh fa-lg" aria-hidden="true"></i>
			</button>
			<button id="btn-ocurre" type="button" class="btn-sm btn-success d-none d-md-inline" data-toggle="tooltip" data-placement="top" title="Crear Códigos de Barras">
				<i class="fa fa-barcode fa-lg" aria-hidden="true"></i>
			</button>
			<?php }?>
			<input type="text" class="d-none d-md-inline" data-toggle="tooltip" data-placement="top" placeholder="Verificador Guía" id="vGuia">
			<button id="btn-scan-qr" type="button" class="btn-primary btn-sm" title="Verificador Guía">
				<i class="fa fa-camera" aria-hidden="true"></i>
			</button>
		</div>
	</div>
</div>
<hr>