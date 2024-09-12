<?php
session_start();

define( '_VALID_MOS', 1 );

date_default_timezone_set('America/Mexico_City');

require_once('../system/configuration.php');
require_once('../system/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);

require_once('../system/session_cookies.php');
$id_location = $_SESSION['uLocation'];

$rFstatus = $_POST['rFstatus'] ?? 3;
#$rFIni    = $_POST['rFIni'] ?? date('Y-m-d', strtotime('-10 days'));
#$rFFin    = $_POST['rFFin'] ?? date('Y-m-d');
$rFIni    = $_POST['rFIni'] ?? null;
$rFFin    = $_POST['rFFin'] ?? null;
$rGuia    = $_POST['rGuia'] ?? null;
$rFolio   = $_POST['rFolio'] ?? null;
$rTelefono   = $_POST['rTelefono'] ?? null;

$rFIniLib    = $_POST['rFIniLib'] ?? date('Y-m-d');
$rFFinLib    = $_POST['rFFinLib'] ?? date('Y-m-d');

# $andStatusIn =" AND p.id_status IN (1,2,3,4,5,6,7)";
if(isset($rFstatus)){
	if($rFstatus!='99'){
		$andStatusIn = " AND p.id_status IN ($rFstatus)";
	}else{
		$andStatusIn =" AND p.id_status IN (1,2,3,4,5,6,7)";
	}
}

$andFechasRegistro = "";
if(!empty($rFIni) && !empty($rFFin)){
	$andFechasRegistro = " AND p.c_date BETWEEN '$rFIni 00:00:00' AND '$rFFin 23:59:59'";
}

$andGuia ='';
if(!empty($rGuia)){
	$andGuia = " AND p.tracking IN('$rGuia')";
}

$andFolio ='';
if(!empty($rFolio)){
	$andFolio = " AND p.folio IN('$rFolio')";
}

$andTelefono ='';
if(!empty($rTelefono)){
	$andTelefono = " AND cc.phone IN('$rTelefono')";
}

$andFechasLiberacion = "";
if(!empty($rFIniLib) && !empty($rFFinLib)){
	$andFechasLiberacion = " AND p.d_date BETWEEN '$rFIniLib 00:00:00' AND '$rFFinLib 23:59:59'";
}

$sql = "SELECT 
p.id_package,
cl.location_desc,
DATE_FORMAT(p.c_date, '%Y-%m-%d') c_date,
uc.user registro,
p.tracking,
cc.phone,
p.folio,
p.marker,
cc.contact_name receiver,
cs.status_desc,
DATE_FORMAT(p.n_date, '%Y-%m-%d') n_date,
un.user sms_by_user,
(SELECT count(n.id_notification) FROM notification n WHERE n.id_package in(p.id_package)) t_sms_sent,
p.d_date,
ud.user user_libera,
p.note,
(SELECT count(e.id_evidence) FROM evidence e WHERE e.id_package IN(p.id_package)) t_evidence 
FROM package p 
LEFT JOIN cat_contact cc ON cc.id_contact=p.id_contact 
LEFT JOIN cat_status cs ON cs.id_status=p.id_status 
LEFT JOIN users uc ON uc.id = p.c_user_id 
LEFT JOIN cat_location cl ON cl.id_location = p.id_location 
LEFT JOIN users un ON un.id = p.n_user_id 
LEFT JOIN users ud ON ud.id = p.d_user_id 
WHERE 1 
AND p.id_location IN ($id_location) 
$andStatusIn 
$andFechasRegistro 
$andGuia 
$andFolio 
$andTelefono 
$andFechasLiberacion 
ORDER BY p.id_package DESC";
$packages = $db->select($sql);
//echo $sql;
?>
<!doctype html>
<html lang = "en">
	<head>
		<?php include '../views/header.php'; ?>
		<script src="<?php echo BASE_URL;?>/assets/js/reports.js"></script>
		<script src="<?php echo BASE_URL;?>/assets/js/functions.js"></script>
		<link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet">
		<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
		<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
	</head>
	<body>
		<div class="main">
			<?php
				include '../views/navTop.php';
			?>
			<h3>Reportes <?php echo $desc_loc;?></h3>
			<form id="frm-reports" action="<?php echo BASE_URL;?>/views/reports.php" method="POST">
				<div class="row">
					<div class="col-md-3">
						<div class="form-group">
							<label for="rFstatus"><b>Estatus:</b></label>
							<select name="rFstatus" id="rFstatus" class="form-control">
								<option value="99" <?php echo ($rFstatus==99) ? 'selected': ''; ?>>Todos</option>
								<option value="1" <?php echo ($rFstatus==1) ? 'selected': ''; ?>>Nuevo</option>
								<option value="2" <?php echo ($rFstatus==2) ? 'selected': ''; ?>>Mensaje Enviado</option>
								<option value="3" <?php echo ($rFstatus==3) ? 'selected': ''; ?>>Entregado</option>
								<option value="4" <?php echo ($rFstatus==4) ? 'selected': ''; ?>>Devuelto</option>
								<option value="5" <?php echo ($rFstatus==5) ? 'selected': ''; ?>>Confirmado</option>
								<option value="6" <?php echo ($rFstatus==6) ? 'selected': ''; ?>>Error al enviar mensaje</option>
								<option value="7" <?php echo ($rFstatus==7) ? 'selected': ''; ?>>Contactado</option>
							</select>
						</div>
					</div>
					<div class="col-md-2">
						<div class="form-group">
							<label for="rFIni"><b>Fecha Ini. Registro:</b></label>
							<input type="date" class="form-control" name="rFIni" id="rFIni" value="<?php echo $rFIni; ?>">
						</div>
					</div>
					<div class="col-md-2">
						<div class="form-group">
							<label for="rFFin"><b>Fecha Fin Registro:</b></label>
							<input type="date" class="form-control" name="rFFin" id="rFFin" value="<?php echo $rFFin; ?>">
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<label for="rGuia"><b>Guía:</b></label>
							<input type="text" class="form-control" name="rGuia" id="rGuia" value="<?php echo $rGuia; ?>" autocomplete="off">
						</div>
					</div>
					<div class="col-md-2">
						<div class="form-group">
							<label for="rFolio"><b>Folio:</b></label>
							<input type="text" class="form-control" name="rFolio" id="rFolio" value="<?php echo $rFolio; ?>" autocomplete="off">
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-3">
						<div class="form-group">
							<label for="rTelefono"><b>Télefono:</b></label>
							<input type="text" class="form-control" name="rTelefono" id="rTelefono" value="<?php echo $rTelefono; ?>" autocomplete="off">
						</div>
					</div>
					<div class="col-md-2">
						<div class="form-group">
							<label for="rFIniLib"><b>Fecha Ini. Entrega:</b></label>
							<input type="date" class="form-control" name="rFIniLib" id="rFIniLib" value="<?php echo $rFIniLib; ?>">
						</div>
					</div>
					<div class="col-md-2">
						<div class="form-group">
							<label for="rFFinLib"><b>Fecha Fin Entrega:</b></label>
							<input type="date" class="form-control" name="rFFinLib" id="rFFinLib" value="<?php echo $rFFinLib; ?>">
						</div>
					</div>
					<div class="col-md-1"><br>
						<div class="form-group">
							<button id="btn-filter-rep" type="submit" class="btn btn-success" title="Filtrar" data-dismiss="modal">Filtrar</button>
						</div>
					</div>
					<div class="col-md-1"><br>
						<button id="btn-f-erase" type="button" class="btn btn-default" title="Borrar">Borrar</button>
					</div>
				</div>
			</form>
			<hr>
			<table id="tbl-reports" class="table table-striped table-bordered nowrap table-hover" cellspacing="0" style="width:100%">
				<thead>
					<tr>
						<th>id_package</th>
						<th>location_desc</th>
						<th>fecha_registro</th>
						<th>registrado_por</th>
						<th>guia</th>
						<th>folio</th>
						<th>phone</th>
						<th>receiver</th>
						<th>status_desc</th>
						<th>fecha_envio_sms</th>
						<th>sms_enviado_por</th>
						<th>total_sms</th>
						<th>fecha_liberacion</th>
						<th>libero</th>
						<th>note</th>
						<th>evidence</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($packages as $d): ?>
						<tr>
						<td title="Ver Historial" id="id-logger" style="cursor: pointer; text-decoration: underline;"><?php echo $d['id_package']; ?></td>
						<td><?php echo $d['location_desc']; ?></td>
						<td><?php echo $d['c_date']; ?></td>
						<td><?php echo $d['registro']; ?></td>
						<td><?php echo $d['tracking']; ?></td>
						<td style="font-weight: bold; color: <?php echo $d['marker']; ?>;"><?php echo $d['folio']; ?></td>
						<td><?php echo $d['phone']; ?></td>
						<td><?php echo $d['receiver']; ?></td>
						<td><?php echo $d['status_desc']; ?></td>
						<td><?php echo $d['n_date']; ?></td>
						<td><?php echo $d['sms_by_user']; ?></td>
						<td>
							<?php if($d['t_sms_sent']==0){ echo "0";}else{ ?>
								<span class="badge badge-pill badge-info" style="cursor: pointer;" id="btn-details" title="Ver"><?php echo $d['t_sms_sent']; ?></span>
							<?php
							} ?>
						</td>
						<td><?php echo $d['d_date']; ?></td>
						<td><?php echo $d['user_libera']; ?></td>
						<td><?php echo $d['note']; ?></td>
						<td>
							<?php if($d['t_evidence']!=0){ ?>
								<span class="badge badge-pill badge-warning" style="cursor: pointer;" id="btn-evidence" title="Evidencia(s)">
									<?php echo $d['t_evidence']; ?> <i class="fa fa-file-image-o fa-lg" aria-hidden="true"></i>
								</span>
							<?php
							} ?>
						</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		include('modal/sms-report.php');
		include('modal/logger.php');
		include('modal/evidence.php');
		?>
	</body>
</html>