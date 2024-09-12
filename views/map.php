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
$id_location = $_SESSION['uLocation'];

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
			<h3>Map <?php echo $desc_loc;?></h3>
	<?php
    $id_location = $_SESSION['uLocation'];
    $sql = "SELECT 
        p.tracking,
        p.folio,
        cc.contact_name receiver,
        UPPER(SUBSTRING(TRIM(cc.contact_name), 1, 1)) AS initial,
        p.marker 
        FROM package p 
        INNER JOIN cat_contact cc ON cc.id_contact = p.id_contact 
        WHERE p.id_location IN ($id_location) 
        AND p.id_status IN (1, 2, 5, 6, 7) 
        ORDER BY initial, p.folio
    ";

    $result = $db->select($sql);
    $groupedPackages = [];

    foreach($result as $row){
        $initial = $row['initial'];  // La primera letra del nombre
        $folio   = $row['folio'];      // El folio del paquete

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

<div class="row">
    <?php foreach ($groupedPackages as $initial => $packages): ?>
			<div class="col-6 col-md-1"></div>
			<div class="col-6 col-md-2" style="border: 1px solid black; min-height: 150px; background-color: lightblue; margin-bottom: 15px;">
				<div class="row">
				    <div class="col-4"></div>
					<div class="col-4" style="text-align:center;">
						<h3><?php echo $initial; ?></h3>
					</div>
					<div class="col-4" style="text-align:right;">
						<?php echo count($packages); ?>
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
	</body>
</html>

