<?php
session_start();
define( '_VALID_MOS', 1 );

require_once('../system/configuration.php');
require_once('../system/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);

require_once('../system/session_cookies.php');
$id_location = $_SESSION['uLocation'];

$sql="SELECT 
		cc.phone,
		IF((SELECT count(cc1.contact_name) FROM cat_contact cc1 WHERE cc1.phone = cc.phone AND cc1.id_location IN($id_location) AND cc1.id_contact_status IN (1))=1,
			(SELECT cc2.contact_name FROM cat_contact cc2 WHERE cc2.phone = cc.phone AND cc2.id_location IN($id_location) AND cc2.id_contact_status IN (1) limit 1),
			CONCAT((SELECT cc3.contact_name FROM cat_contact cc3 WHERE cc3.phone = cc.phone AND cc3.id_location IN($id_location) AND cc3.id_contact_status IN (1) LIMIT 1),' <b>+',(SELECT count(cc4.contact_name) FROM cat_contact cc4 WHERE cc4.phone = cc.phone AND cc4.id_location IN($id_location) AND cc4.id_contact_status IN (1))-1,'</b>')
		) AS main_name,
		COUNT(p.tracking) AS total_p,
		GROUP_CONCAT(p.tracking) AS trackings,
		GROUP_CONCAT(p.folio) AS folios,
		GROUP_CONCAT(p.id_package) AS ids 
		FROM package p 
		INNER JOIN cat_contact cc ON cc.id_contact=p.id_contact 
		WHERE 
		p.id_location IN ($id_location) 
		AND p.id_status IN (1,2,5,7) 
		GROUP BY cc.phone,main_name 
		ORDER BY total_p DESC";
$packages = $db->select($sql);
?>
<!doctype html>
<html lang = "en">
	<head>
		<?php include '../views/header.php'; ?>
		<script src="<?php echo BASE_URL;?>/assets/js/grouped.js"></script>
	</head>
	<body>
		<div class="main">
			<?php
				include '../views/navTop.php';
			?>
			<h3>Usuarios y Paquetes Agrupados <?php echo $desc_loc;?></h3>
			<table id="tbl-inspect" class="table table-striped table-bordered nowrap table-hover" cellspacing="0" style="width:100%">
				<thead>
					<tr>
						<th>phone</th>
						<th>main_name</th>
						<th>total_p</th>
						<th>folios</th>
						<th>trackings</th>
						<th>ids</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($packages as $d): ?>
						<tr>
						<td><?php echo $d['phone']; ?></td>
						<td><?php echo $d['main_name']; ?></td>
						<td>
						<?php 
							// Dividir el string en un array
							$array = explode(',', $d['trackings']);
							// Contadores
							$countJMX = 0;
							$countImile = 0;

							// Recorrer el array y contar los que comienzan con "JMX" y los demás
							foreach ($array as $item) {
								if (strpos($item, 'JMX') === 0) {
									$countJMX++;
								} else {
									$countImile++;
								}
							}
							?>
							<span class="badge badge-pill badge-info btn-pull-realise" style="cursor: pointer;" title="Liberar Paquetes" data-tpaquetes="<?php echo $d['total_p']; ?>" data-tphone="<?php echo $d['phone']; ?>" data-tname="<?php echo $d['main_name']; ?>" data-tids="<?php echo $d['ids']; ?>" data-tjt="<?php echo $countJMX; ?>" data-timile="<?php echo $countImile; ?>">
								<?php if($countJMX>0){echo "JT:".$countJMX.","; }?> <?php if($countImile>0){echo " Imile:".$countImile.","; }?> TOTAL: <?php echo $d['total_p']; ?>
							</span>

						</td>
						<td><?php
							$folios_array = explode(",", $d['folios']);
							rsort($folios_array);
							$texto_folios_ordenados = implode(",", $folios_array);
							echo $texto_folios_ordenados; // Mostrar los folios ordenados
						?></td>
						<td><?php 
							$guias_array = explode(",", $d['trackings']);
							rsort($guias_array);
							$texto_guias_ordenados = implode(",", $guias_array);
							echo $texto_guias_ordenados; // Mostrar los folios ordenados
						?></td>
						<td><?php echo $d['ids']; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		include('modal/sms-report.php');
		include('footer.php');
		?>
	</body>
</html>