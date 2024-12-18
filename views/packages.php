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
DATEDIFF(NOW(), p.c_date) tdt,
cc.contact_name receiver,
cs.id_status,
IF(cs.id_status=6,'color:#DC143C;', '') colorErrorMessage,
cs.status_desc,
p.note,
IF(p.n_date IS NULL,'', (SELECT DATE_FORMAT(n.n_date, '%m-%d') FROM notification n WHERE n.id_package IN(p.id_package) ORDER BY id_notification ASC LIMIT 1)) n_date,
(SELECT count(n.id_notification) FROM notification n WHERE n.id_package IN(p.id_package)) t_sms_sent,
p.id_contact,
(SELECT 
    CASE 
        WHEN DATEDIFF(NOW(), n.n_date) = 0 OR DATEDIFF(NOW(), n.n_date) = 1 THEN '' 
        WHEN DATEDIFF(NOW(), n.n_date) = 2 THEN 'background-color: #FFFF99;' 
        WHEN DATEDIFF(NOW(), n.n_date) >= 3 THEN 'background-color: #FF9999;' 
        ELSE 'sin color' 
    END AS color 
FROM notification n 
WHERE n.id_package IN (p.id_package) 
ORDER BY n.id_notification ASC 
LIMIT 1) styleCtrlDays,
(SELECT DATEDIFF(NOW(), n_date) FROM notification n WHERE n.id_package IN(p.id_package) ORDER BY id_notification ASC LIMIT 1) dcolor,
p.marker,
(SELECT count(e.id_evidence) FROM evidence e WHERE e.id_package IN(p.id_package)) t_evidence,
p.id_cat_parcel,
cp.parcel 
FROM package p 
LEFT JOIN cat_contact cc ON cc.id_contact=p.id_contact 
LEFT JOIN cat_status cs ON cs.id_status=p.id_status 
LEFT JOIN cat_parcel cp ON cp.id_cat_parcel=p.id_cat_parcel 
WHERE 1 
AND p.id_location IN ($id_location)
AND p.id_status IN(1,2,5,6,7)";
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
		let uIdCatParcel =`<?php echo $_SESSION["uIdCatParcel"];?>`;
		let largo = `<?php echo LARGO;?>`;
		let alto = `<?php echo ALTO;?>`;
		let rVoice =`<?php echo $_SESSION["uVoice"]; ?>`
		</script>
		<script src="<?php echo BASE_URL;?>/assets/js/packages.js?version=<?php echo time(); ?>"></script>
		<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
		<script src="<?php echo BASE_URL;?>/assets/js/libraries/jquery-ui.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js"></script>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css">

		<style>
    		@media only screen and (max-width: 768px) {
                table.dataTable td:nth-child(4),
                table.dataTable th:nth-child(4) {
                    display: none;
                }
				table.dataTable td:nth-child(9),
                table.dataTable th:nth-child(9) {
                    display: none;
                }
				table.dataTable td:nth-child(10),
                table.dataTable th:nth-child(10) {
                    display: none;
                }
                #lbl-title-location {
                    display: none;
                }
            }
        </style>
        <script>
			function truncateText() {
				const table = document.getElementById('tbl-packages');
				const rows = table.getElementsByTagName('tr');

				for (let i = 1; i < rows.length; i++) { // Empezamos desde 1 para omitir el encabezado
					const cells = rows[i].getElementsByTagName('td');
					if (cells.length > 6) { // Asegúrate de que hay al menos 7 columnas
						const cell = cells[6]; // La columna 7 tiene un índice de 6
						const text = cell.innerText;

						// Si el texto es más largo que 20 caracteres, truncarlo
						if (text.length > 10) {
							cell.innerText = text.substring(0, 10) + '...'; // Añadir "..." al final
						}
					}
				}
			}

			// Ejecutar la función al cargar la página
			window.onload = function() {
				if (window.innerWidth <= 768) {
					truncateText();
				}
			};

			// También ejecuta la función al redimensionar la ventana
			window.onresize = function() {
				if (window.innerWidth <= 768) {
					truncateText();
				}
			};
		</script>
	</head>
	<body>
		<div class="main">
		<?php
			include '../views/navTop.php';
		?>
      	<?php if(empty($packages)): ?>
			<div class="alert alert-info" role="alert" style="text-align: center;">
				No hay paquetes en la ubicación seleccionada, haz clik en el boton nuevo paquete <br>
				<button id="btn-first-package" type="button" class="btn-success btn-sm" data-toggle="tooltip" data-placement="top" title="Nuevo paquete">
					<i class="fa fa-cube fa-lg" aria-hidden="true"></i>
				</button>
			</div
		</div>
		<?php else: ?>
			<form id="frm-package">
				<h3 id="lbl-title-location">Paquetes <?php echo $desc_loc;?></h3>
				<div class="col-md-12 px-4">
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
								<th>id_cat_parcel</th>
								<th>parcel</th>
								<th>messages</th>
								<th>tdiast</th>
								<th style="text-align: center; width:20%;">
									<button type="button" id="confirmg" name="confirmg" class="btn btn-success btn-sm" data-toggle="tooltip" data-placement="top" title="Confirmar Guías Seleccionadas">
										<i class="fa fa-flag-o fa-lg" aria-hidden="true"></i>
									</button>
									<button type="button" id="releaseg" name="releaseg" class="btn btn-success btn-sm" data-toggle="tooltip" data-placement="top" title="Liberar Guías Seleccionadas">
										<i class="fa fa-check-square-o fa-lg" aria-hidden="true"></i>
									</button>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($packages as $d):
								?>
								<tr id="<?php echo 'row_id_'.$d['id_package']; ?>" style="<?php echo $d['id_status'] == 5 ? 'background-color:#A2D9A2' : $d['styleCtrlDays']; ?>" title="">
								<td><?php echo $d['id_package']; ?></td>
								<td><?php echo $d['tracking']; ?></td>
								<td><?php echo $d['phone']; ?></td>
								<td><?php echo $d['id_location']; ?></td>
								<td><?php echo $d['c_date']; ?></td>
								<td style="font-weight: bold; color: <?php echo $d['marker']; ?>;"><?php echo $d['folio']; ?></td>
								<td><?php echo $d['receiver']; ?></td>
								<td><?php echo $d['id_status']; ?></td>
								<td style="<?php echo $d['colorErrorMessage']; ?>" > <?php echo $d['status_desc']; ?>
							<?php
							if($d['note']){?><span class="badge badge-pill badge-default" style="cursor: pointer;" title="<?php echo $d['note'];?>"><i class="fa fa-sticky-note-o" aria-hidden="true"></i> </span><?php }?>
							</td>
								<td><?php echo $d['note']; ?></td>
								<td><?php echo $d['id_contact']; ?></td>
								<td><?php echo $d['id_cat_parcel']; ?></td>
								<td><?php echo $d['parcel']; ?></td>
								<td><?php if($d['t_sms_sent']!=0){ ?>
									<?php 
									echo $d['n_date']
									?>
									<span class="badge badge-pill badge-info" style="cursor: pointer;" id="btn-details-p" title="Leer Mensaje"><?php echo $d['t_sms_sent']; ?> </span>
								<?php }?></td>
								<td>
									<?php if($d['tdt']!=0){
									 echo $d['tdt'];
									}?>
								</td>
								</td>
								<td style="text-align: center;">
									<div class="row">
										<div class="col-md-4">
										<?php if($d['id_status']==2 || $d['id_status']==5 || $d['id_status']==7){ ?>
											<span class="badge badge-pill badge-success" style="cursor: pointer;" id="btn-tbl-liberar" title="Liberar">
												<i class="fa fa-check-square-o fa-lg" aria-hidden="true"></i>
											</span>
										<?php }?>
										</div>
										<div class="col-md-4">
											<span class="badge badge-pill badge-info" style="cursor: pointer;" id="btn-records" title="Editar">
												<i class="fa fa-edit fa-lg" aria-hidden="true"></i>
											</span>
										</div>
										<div class="col-md-4">
											<?php if($d['t_evidence']!=0){ ?>
												<span class="badge badge-pill badge-warning" style="cursor: pointer;" id="btn-evidence" title="Evidencia(s)">
													<i class="fa fa-file-image-o fa-lg" aria-hidden="true"></i>
												</span>
											<?php
											}?>
										</div>
									</div>
								</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</form>
			<audio id="sound-snap" style="display: none;">
				<source src="<?php echo BASE_URL;?>/assets/snap.mp3" type="audio/mpeg">
			</audio>

		<?php endif; ?>
		<?php
			include('modal/folio.php');
			include('modal/template.php');
			include('modal/package.php');
			include('modal/release.php');
			include('modal/sync.php');
			include('modal/bot.php');
			include('modal/sms-report.php');
			include('modal/evidence.php');
			include('modal/photo-confirmed.php');
			include('modal/pull-photo.php');
			include('footer.php');
		?>
	</body>
</html>