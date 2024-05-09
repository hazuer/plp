<?php
session_start();
define( '_VALID_MOS', 1 );

require_once('../system/configuration.php');
require_once('../system/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);


require_once('../system/session_cookies.php');
$id_location = $_SESSION['uLocation'];


$sql = "SELECT 
p.id_package,
p.tracking,
cc.phone,
p.id_location,
p.c_date,
p.folio,
CASE 
	WHEN DAYOFWEEK(p.c_date) = 6 THEN IF(DATEDIFF(NOW(), p.c_date) BETWEEN 0 AND 1,
		'',
		IF(DATEDIFF(NOW(), p.c_date)=3,
			'background-color: #FFFF99;',
			'background-color: #FF9999;')
	) 
	WHEN DAYOFWEEK(p.c_date) = 7 THEN IF(DATEDIFF(NOW(), p.c_date) BETWEEN 0 AND 1,
		'',
		IF(DATEDIFF(NOW(), p.c_date)=2,
			'background-color: #FFFF99;',
			'background-color: #FF9999;')
	) 
	WHEN DAYOFWEEK(p.c_date) = 1 THEN IF(DATEDIFF(NOW(), p.c_date) BETWEEN 0 AND 1,
		'',
		IF(DATEDIFF(NOW(), p.c_date)=2,
			'background-color: #FFFF99;',
			'background-color: #FF9999;')
	) 
	ELSE IF(DATEDIFF(NOW(), p.c_date) BETWEEN 0 AND 1,
		'',
		IF(DATEDIFF(NOW(), p.c_date)=2,
			'background-color: #FFFF99;',
			'background-color: #FF9999;')
	) 
END AS styleCtrlDays,
CASE 
	WHEN DAYOFWEEK(p.c_date) = 6 THEN IF(DATEDIFF(NOW(), p.c_date) BETWEEN 0 AND 1,
		'',
		IF(DATEDIFF(NOW(), p.c_date)=3,
			'2DT',
			'3DT')
	) 
	WHEN DAYOFWEEK(p.c_date) = 7 THEN IF(DATEDIFF(NOW(), p.c_date) BETWEEN 0 AND 1,
		'',
		IF(DATEDIFF(NOW(), p.c_date)=2,
			'2DT',
			'3DT')
	) 
	WHEN DAYOFWEEK(p.c_date) = 1 THEN IF(DATEDIFF(NOW(), p.c_date) BETWEEN 0 AND 1,
		'',
		IF(DATEDIFF(NOW(), p.c_date)=2,
			'2DT',
			'3DT')
	) 
	ELSE IF(DATEDIFF(NOW(), p.c_date) BETWEEN 0 AND 1,
		'',
		IF(DATEDIFF(NOW(), p.c_date)=2,
			'2DT',
			'3DT')
	) 
END AS diasTrans,
cc.contact_name receiver,
cs.id_status,
IF(cs.id_status=6,'color:#FFA500;', '') colorErrorMessage,
cs.status_desc,
p.note,
IF(p.n_date is null,'', CONCAT('el ',DATE_FORMAT(p.n_date, '%Y-%m-%d'))) n_date,
(SELECT count(n.id_notification) FROM notification n WHERE n.id_package in(p.id_package)) t_sms_sent,
p.id_contact,
p.marker 
FROM package p 
LEFT JOIN cat_contact cc ON cc.id_contact=p.id_contact 
LEFT JOIN cat_status cs ON cs.id_status=p.id_status 
WHERE 1 
AND p.id_location IN ($id_location)
AND p.id_status IN(1,2,6,7)";
$packages = $db->select($sql);

$sqlTemp ="SELECT template FROM cat_template WHERE id_location IN ($id_location) LIMIT 1";
$user = $db->select($sqlTemp);
$templateMsj=$user[0]['template'];

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
		<script src="<?php echo BASE_URL;?>/assets/js/functions.js"></script>
		<link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet">
		<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
		<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
		<style>
			@media only screen and (max-width: 768px) {
				table.dataTable {
					font-size: 11px; /* Reducción del tamaño de letra en un 25% */
				}
			}
			.label-info {
				padding: 0.2em 0.6em 0.3em;
				font-size: 15px;
				font-weight: 700;
				line-height: 1;
				color: #fff;
				text-align: center;
				white-space: nowrap;
				vertical-align: baseline;
				border-radius: 0.25em;
				color: white;
				background-color: #5bc0de;
			}

			#coincidencias {
				position: absolute;
				top: calc(100% + 7px); /* Posición debajo del campo #phone */
				left: 0;
				width: 100%;
				max-height: 200px; /* Altura máxima para evitar el desplazamiento */
				overflow-y: auto; /* Mostrar barra de desplazamiento vertical si es necesario */
				background-color: white; /* Color de fondo */
				border: 1px solid #ccc; /* Borde */
				box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Sombra */
				z-index: 1000; /* Z-index para que se superponga a otros elementos */
			}

			#coincidencias p {
				padding: 10px;
				margin: 0;
				cursor: pointer; /* Cambiar el cursor al pasar sobre los elementos de la lista */
			}

			#coincidencias p:hover {
				background-color: #ADD8E6; /* Cambiar el color de fondo al pasar el cursor */
			}

		</style>
	</head>
	<body>
		<div class="main">
			<?php
				include '../views/navTop.php';
			?>

      		<?php if(empty($packages)): ?>
				<div class="alert alert-info" role="alert" style="text-align: center;">
					No hay paquetes en la ubicacion seleccionada, haz clik en el boton nuevo paquete <br>
					<button id="btn-first-package" type="button" class="btn-success btn-sm" title="Nuevo paquete">
						<i class="fa fa-cube fa-lg" aria-hidden="true"></i>
					</button>
				</div
			<?php else: ?>
				<form id="frm-package">
				<h3>Paquetes <?php echo $desc_loc;?></h3>
					<table id="tbl-packages" class="table table-striped table-bordered nowrap table-hover" cellspacing="0" style="width:100%">
						<thead>
							<tr>
								<th></th>
								<th>guia</th>
								<th>phone</th>
								<th>id_location</th>
								<th>c_date</th>
								<th>folio</th>
								<th>receiver</th>
								<th>id_status</th>
								<th>status_desc</th>
								<th>note</th>
								<th>id_contact</th>
								<th>Opciones</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($packages as $d): ?>
								<tr style="<?php echo $d['styleCtrlDays']; ?>">
								<td><?php echo $d['id_package']; ?></td>
								<td><?php echo $d['tracking']; ?></td>
								<td><?php echo $d['phone']; ?></td>
								<td><?php echo $d['id_location']; ?></td>
								<td><?php echo $d['c_date']; ?></td>
								<td style="font-weight: bold; color: <?php echo $d['marker']; ?>;"><?php echo $d['folio']; ?></td>
								<td><?php echo $d['receiver']; ?></td>
								<td><?php echo $d['id_status']; ?></td>
								<td style="<?php echo $d['colorErrorMessage']; ?>" ><?php echo $d['diasTrans']; ?> <?php echo $d['status_desc']; ?> <?php echo $d['n_date']; ?> <?php if($d['t_sms_sent']!=0){ ?>
								<span class="badge badge-pill badge-info" style="cursor: pointer;" id="btn-details-p" title="Ver"><?php echo $d['t_sms_sent']; ?></span>
							<?php
							} ?></td>
								<td><?php echo $d['note']; ?></td>
								<td><?php echo $d['id_contact']; ?></td>
								<td style="text-align: center;">
									<div class="row">
										<div class="col-md-6">
										<?php if($d['id_status']==2 || $d['id_status']==7){ ?>
											<span class="badge badge-pill badge-success" style="cursor: pointer;" id="btn-tbl-liberar" title="Liberar">
												<i class="fa fa-check-square-o fa-lg" aria-hidden="true"></i>
											</span>
										<?php }?>
										</div>
										<div class="col-md-6">
											<span class="badge badge-pill badge-info" style="cursor: pointer;" id="btn-records" title="Editar">
												<i class="fa fa-edit fa-lg" aria-hidden="true"></i>
											</span>
										</div>
									</div>
								</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</form>
			<?php endif; ?>
		</div>
		<?php
		include('modal/folio.php');
		include('modal/template.php');
		include('modal/package.php');
		include('modal/messages.php');
		include('modal/release.php');
		include('modal/sync.php');
		include('modal/bot.php');
		include('modal/sms-report.php');
		?>
	</body>
</html>