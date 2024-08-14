<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

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
			<h3>Porcentaje de Entrega Últimos 3 Meses <?php echo $desc_loc;?></h3>
			<hr>
		
	<?php 
// Obtener el día actual del mes
$current_day = date('j');

// Obtener el último día del mes actual con el día actual del mes
$last_day_of_month = date('t'); // Último día del mes actual
$end_date = date('Y-m-' . min($current_day, $last_day_of_month)); // Ajusta al día actual del mes o al último día del mes si es mayor

// Convertir las fechas a objetos DateTime
#$start = new DateTime(date('Y-m-01')); // Primer día del mes actual
// Crear un objeto DateTime con la fecha actual
$start = new DateTime();
// Restar tres meses a la fecha actual
$start->modify('-3 months');
// Opcionalmente, establecer el primer día del mes resultante
$start->modify('first day of this month');
// Formatear la fecha para mostrarla o usarla en el formato que necesites
$last3months=$start->format('Y-m-d'); 

$start = new DateTime(date($last3months)); // Primer día del mes actual
$end = new DateTime($end_date); // Último día del mes actual con el día actual del mes


$c=0;
// Bucle desde la fecha de inicio hasta la fecha de fin
				
    ?>
    <?php
for ($date = $start; $date <= $end; $date->modify('+1 day')) {
    $c++;
    
    // Formatear la fecha para la consulta
    $current_date = $date->format('Y-m-d');
    $start_datetime = $current_date . ' 00:00:00';
    $end_datetime = $current_date . ' 23:59:59';

    // Consulta SQL
    $sql = "SELECT 
                p.id_status, 
                s.status_desc, 
                COUNT(*) AS count,
                SUM(CASE 
                    WHEN DATE(p.c_date) = DATE(p.d_date) THEN 1 
                    ELSE 0 
                END) AS same_day_delivery,
                SUM(CASE 
                    WHEN DATE(p.c_date) != DATE(p.d_date) THEN 1 
                    ELSE 0 
                END) AS different_day_delivery
            FROM 
                package p
            JOIN 
                cat_status s ON p.id_status = s.id_status
            WHERE 
                p.c_date BETWEEN '$start_datetime' AND '$end_datetime'
                AND p.id_location IN ($id_location)
            GROUP BY 
                p.id_status, s.status_desc
            ORDER BY 
                p.id_status";

    // Ejecutar la consulta
    $result = $db->select($sql);
   
    // Verificar si hay resultados y mostrarlos
    if (count($result) > 0) {
        $total_count = 0;

    // Recorrer el arreglo y sumar los valores de 'count'
    foreach ($result as $item) {
        $total_count += intval($item['count']); // Convertir a entero antes de sumar
    }
        
        echo "<table class='table table-striped table-bordered nowrap table-hover' cellspacing='0' style='width:100%'>
        <tr><td colspan='6'>Paquetes registrados el día $current_date, Total:<b> ".$total_count." (100%)</b></td></tr>";
             foreach ($result as $row) {
                 $p= round(((100/$total_count)*$row["count"]),1);
                 if($row["id_status"]==3){
                     $pmd=round(((100/$row["count"])*$row["same_day_delivery"]),1);
                     $pdd=round(((100/$row["count"])*$row["different_day_delivery"]),1);
                        echo "<tr>
                        <td>".$row["status_desc"]."(s)</td>
                        <td>".$row["count"]." (".$p."%)</td>
                        <td>Entregados mismo día</td>
                        <td>".$row["same_day_delivery"]." (".$pmd."%)</td>
                        <td>Entregados después del día </td>
                        <td>".$row["different_day_delivery"]." (".$pdd."%)</td></tr>";
                 }else{
                         echo "<tr><td>".$row["status_desc"]."(s)</td><td>".$row["count"]." (".$p."%)</tr>";
                 }

}echo "</table>";
       
    } else {
         echo "<table class='table table-striped table-bordered nowrap table-hover' cellspacing='0' style='width:100%'>
        <tr><td>Día $current_date, sin registro de paquetes</td></tr>
        </table>";
    }
}


// Obtener el día actual del mes
$diaHoy = date('j');

// Obtener el último día del mes actual con el día actual del mes
$ultimoDiaMes = date('t'); // Último día del mes actual
$ultimoD = date('Y-m-' . min($diaHoy, $ultimoDiaMes)); // Ajusta al día actual del mes o al último día del mes si es mayor

// Convertir las fechas a objetos DateTime
#$diaInicial = new DateTime(date('Y-m-01')); // Primer día del mes actual
$diaInicial = new DateTime(date('2024-03-01')); // Primer día del mes actual
$diaFinal = new DateTime($ultimoD); // Último día del mes actual con el día actual del mes


$fini = $diaInicial->format('Y-m-d');
$f1 = $fini . ' 00:00:00';

$ffin = $diaFinal->format('Y-m-d');
$f2 = $ffin . ' 23:59:59';


$sql2="SELECT 
    p.id_status, 
    s.status_desc, 
    COUNT(*) AS count 
FROM 
    package p 
JOIN 
    cat_status s ON p.id_status = s.id_status 
WHERE 
    p.c_date BETWEEN '$f1' AND '$f2' 
    AND p.id_location IN ($id_location) 
GROUP BY  
    p.id_status, s.status_desc 
ORDER BY 
    p.id_status";
 $rst=$db->select($sql2);
 
 
        $tpm = 0;

    // Recorrer el arreglo y sumar los valores de 'count'
    foreach ($rst as $item) {
        $tpm += intval($item['count']); // Convertir a entero antes de sumar
    }

   // Crear la tabla HTML
echo "<table class='table table-striped table-bordered nowrap table-hover' cellspacing='0' style='width:100%'>";
echo "<tr><th colspan='3'>Resumen del mes de ".date('F').", Total: ".$tpm." paquetes ".$desc_loc."</th></tr>";
echo "<tr><th>Estatus</th><th>Total</th><th>Porcentaje</th></tr>";


// Recorrer el arreglo y generar las filas de la tabla
foreach ($rst as $row) {
    
    $p= round(((100/$tpm)*$row["count"]),2);
    echo "<tr>";
    echo "<td>" . $row["status_desc"] . "</td>";
    echo "<td>" . $row["count"] . "</td>";
    echo "<td>" . $p . "%</td>";
    echo "</tr>";
}

echo "</table>";

					?>
		</div>

	</body>
</html>