<?php
session_start();
#error_reporting(E_ALL);
#ini_set('display_errors', '1');

define( '_VALID_MOS', 1 );
date_default_timezone_set('America/Mexico_City');

require_once('../system/configuration.php');
require_once('../system/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);

require_once('../system/session_cookies.php');
if($_SESSION["uId"] !=1){
	header('Location: '.BASE_URL.'/admin');
	die();
}

?>
<!doctype html>
<html lang = "en">
	<head>
		<?php include '../views/header.php'; ?>
	</head>
	<body>
		<div class="main">
			<?php
			include '../views/navTop.php';
			$id_location = $_SESSION['uLocation'];
			$sql = "SELECT 
				p.tracking,
				p.folio,
				cc.contact_name receiver,
				UPPER(SUBSTRING(TRIM(REPLACE(
					REPLACE(
						REPLACE(
							REPLACE(
								REPLACE(
									REPLACE(
										REPLACE(
											REPLACE(cc.contact_name, 'á', 'a'),
										'é', 'e'),
									'í', 'i'),
								'ó', 'o'),
							'ú', 'u'),
						'Á', 'A'),
					'Ñ', 'N'),
				'É', 'E')), 1, 1)) AS initial,
				p.marker 
				FROM package p 
				INNER JOIN cat_contact cc ON cc.id_contact = p.id_contact 
				WHERE p.id_location IN ($id_location) 
				AND p.id_status IN (1, 2, 5, 6, 7, 8) 
				ORDER BY initial, p.folio
			";

			$result = $db->select($sql);
			$groupedPackages = [];
			// Contadores
			$countJMX1   = 0;
			$countCN1    = 0;
			$countImile1 = 0;

			foreach($result as $row){
				$initial = $row['initial'];  // La primera letra del nombre
				$folio   = $row['folio'];      // El folio del paquete

				// Recorrer el array y contar los que comienzan con "JMX"
				if (strpos($row['tracking'], 'JMX') === 0) {
					$countJMX1++;
				}else if(strpos($row['tracking'], 'CNMEX') === 0) {
					$countCN1++;
				} else {
					$countImile1++;
				}
				// Agrupar los paquetes por inicial
				if (!isset($groupedPackages[$initial])) {
					$groupedPackages[$initial] = [];
				}

				// Dentro de cada inicial, agrupar por folio
				$groupedPackages[$initial][] = [
					'tracking' => $row['tracking'],
					'folio'    => $folio,
					'receiver' => $row['receiver'],
					'marker'   => $row['marker']
				];
			}
			?>
			<h3 id="lbl-title-location">Map <?php echo $desc_loc;?></h3>
			<span style="font-size:18px;">
			<?php 
			echo "<b>Total: ".count($result)." Paquetes</b> <br>";
			if($countJMX1>0){echo "J&T:".$countJMX1." | "; }
			if($countCN1>0){echo "CNMEX:".$countCN1." | "; }
			if($countImile1>0){echo " IMILE:".$countImile1; }
			?></span>
			<div class="row">
			<?php foreach ($groupedPackages as $initial => $packages): ?>
				<div class="col-6 col-md-1"></div>
				<div class="col-6 col-md-2" style="border: 1px solid black; min-height: 150px; background-color: lightblue; margin-bottom: 15px;">
					<div class="row">
						<div class="col-8" style="text-align:right;">
							<span style="font-size:28px;"><?php echo $initial."-".count($packages); ?></span>
						</div>
						<div class="col-4" style="text-align:right;">
							<?php
							// Contadores
							$countJMX   = 0;
							$countCN    = 0;
							$countImile = 0;

							// Recorrer el array y contar los que comienzan con "JMX"
							foreach ($packages as $item) {
								if (strpos($item['tracking'], 'JMX') === 0) {
									$countJMX++;
								} else if (strpos($item['tracking'], 'CNMEX') === 0) {
									$countCN++;
								} else {
									$countImile++;
								}
							}
							if($countJMX>0){echo "JT:".$countJMX."<br>"; }
							if($countCN>0){echo "CN:".$countCN."<br>"; }
							if($countImile>0){echo " IM:".$countImile; }
							?>
						</div>
					</div>
					<div class="row">
						<?php foreach ($packages as $package): ?>
							<div class="col-md-3" data-toggle="tooltip" data-placement="top" title="<?php echo $package['tracking'];?>-<?php echo $package['receiver'];?>">
								<span style="color:<?php echo $package['marker'];?>">
									<b><?php echo $package['folio']; ?></b>
								</span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
			</div>
		</div>
	<?php
    include('footer.php');
    ?>
	</body>
</html>