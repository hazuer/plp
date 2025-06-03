<?php
// Lista de dominios permitidos
$allowed_origins = [
    'https://jmx.jtjms-mx.com',
    'https://ds.imile.com'
];

// Detecta el origen de la petición
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Verifica si está en la lista
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Manejo de preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

define( '_VALID_MOS', 1 );

date_default_timezone_set('America/Mexico_City');

require_once('../system/configuration.php');
require_once('../system/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);
$json = file_get_contents('php://input');
$request = json_decode($json, true); // Convierte a array asociativo
$_POST = array_merge($_POST, $request);


header('Content-Type: application/json; charset=utf-8');

switch ($_POST['option']) {

	case 'store':
		$id_location = $_POST['id_location'];
		$db->sqlPure("UPDATE folio SET folio = LAST_INSERT_ID(folio + 1) WHERE id_location =".$id_location);
		// Obtiene el nuevo folio de forma segura para esta conexión
		$records = $db->select("SELECT LAST_INSERT_ID() AS nuevo_folio");
		$folio = $records[0]['nuevo_folio'];
		$result   = [];
		$success  = 'false';
		$dataJson = [];
		$message  = 'Error al guardar la infomación del paquete';
		$data['id_location'] = $id_location;
		$phone               = $_POST['phone'];
		$receiver            = $_POST['receiver'];
		// Elimina los espacios al inicio y final
		$receiver = trim($receiver);
		// Reemplaza espacios múltiples entre palabras con un solo espacio
		$receiver = preg_replace('/\s+/', ' ', $receiver);
		$data['id_status']   = 1;
		$data['note']        = "";
		#$id_contact          = $_POST['id_contact'];

		try {
			// Normalizamos el nombre y el teléfono (opcional pero útil)
			$phone    = trim($phone);
			$receiver = trim($receiver);

			// Validar si ya existe el contacto
			$sqlCheck = "SELECT id_contact FROM cat_contact WHERE phone IN ('".$phone."') AND contact_name IN('".$receiver."')  AND id_location IN(".$data['id_location'].") AND id_contact_status = 1";
			$existing = $db->select($sqlCheck);

			if (empty($existing)) {
				$sqlCheckTypeContact="SELECT COUNT(id_contact_type) AS total FROM cat_contact AS cc WHERE phone = '".$phone."' AND id_contact_status = 1 AND id_contact_type IN(2)";
				$rstCheck = $db->select($sqlCheckTypeContact);
				$total = $rstCheck[0]['total'];
				$id_contact_type = ($total >= 1) ? 2 : 1;

				// Contacto nuevo, se inserta
				$contact = [
					'id_location'        => $data['id_location'],
					'phone'              => $phone,
					'contact_name'       => $receiver,
					'id_contact_type'    => $id_contact_type, // SMS
					'id_contact_status'  => 1,
					'id_contact'         => null
				];
				$id_contact = $db->insert('cat_contact', $contact);
			} else {
				// Ya existe, usamos el ID existente
				$id_contact = $existing[0]['id_contact'];
			}

			// Se asigna el contacto al dato actual
			$data['id_contact']   = $id_contact;
			$data['id_type_mode'] = 2; //automated

			if (empty($data['id_contact']) || $data['id_contact'] == 0 || $data['id_contact'] === null) {
				$success  = 'false';
				$dataJson = [];
				$message  = 'No se registro el usuario, vuelve a intentarlo';
			}else{
				$data['id_package']  = null;
				$data['folio']       = $folio;
				$data['c_date']      = date("Y-m-d H:i:s");
				$data['c_user_id']   = 1;#$_POST['c_user_id'];
				$data['tracking']    = $_POST['tracking'];
				$data['id_cat_parcel']  = $_POST['id_cat_parcel'];
				$sqlCheck = "SELECT COUNT(tracking) total FROM package WHERE tracking IN ('".$data['tracking']."')";
				$rstCheck = $db->select($sqlCheck);
				$total = $rstCheck[0]['total'];
				if($total==0){
					$data['marker']      = $_POST['id_marcador'];

					$id_location = $data['id_location'];
					$sqlCanBeAgrouped = "SELECT p.folio 
					FROM package p 
					LEFT JOIN cat_contact cc ON cc.id_contact=p.id_contact 
					LEFT JOIN cat_status cs ON cs.id_status=p.id_status 
					WHERE 1 
					AND cc.phone IN('$phone')
					AND p.id_location IN ($id_location)
					AND p.id_status IN(1,2,5,6,7) ORDER BY p.folio DESC";
					$rstCanBeAgrouped = $db->select($sqlCanBeAgrouped);
					$totalPaquetesAgrouped = count($rstCanBeAgrouped);

					$titleMsj  = 'Registrado';
					$msjFolios = "";
					if($totalPaquetesAgrouped>=1){
						$titleMsj  = 'Paquete listo para Agrupar';
						$msjFolios = $phone." - ".$receiver."\n Folios: ";
						foreach ($rstCanBeAgrouped as $resultado) {
							$msjFolios .= $resultado['folio'] . ", ";
						}
						$msjFolios = rtrim($msjFolios, ', ');
					}
					$new_id_package = $db->insert('package',$data);
					saveLog($new_id_package,1,'Nuevo registro de paquete by puppeteer');

					$success  = 'true';
					$dataJson = $msjFolios;
					$message  = $titleMsj;
				}else{
					$success  = 'false';
					$dataJson = [];
					$message  = 'El número de guía: '.$data['tracking'].' ya está registrado';
				}
			}
			$result = [
				'success'  => $success,
				'dataJson' => $dataJson,
				'message'  => $message
			];

		} catch (Exception $e) {
			$result = [
				'success'  => $success,
				'dataJson' => $dataJson,
				'message'  => $message.": ".$e->getMessage()
			];
		}
		echo json_encode($result);

	break;

}

function saveLog($id_package,$new_id_status,$desc_mov,$currentStatus=false){
	global $db;

	$old_id_status = 1;
	if($currentStatus){
		$old_id_status = getCurrentStatus($id_package);
	}

	$dataLog['id_log']        = null;
	$dataLog['datelog']       = date("Y-m-d H:i:s");
	$dataLog['id_package']    = $id_package;
	$dataLog['id_user']       = 1;
	$dataLog['new_id_status'] = $new_id_status;
	$dataLog['old_id_status'] = $old_id_status;
	$dataLog['desc_mov']      = $desc_mov;
	#$dataLog['ip']            = $ip;
	$db->insert('logger',$dataLog);
}

function getCurrentStatus($id_package){
	global $db;
	$sqlGetCurrentStatus="SELECT id_status old_id_status FROM package WHERE id_package IN ($id_package)";
	$records           = $db->select($sqlGetCurrentStatus);
	$id_status_current = $records[0]['old_id_status'];
	return $id_status_current;
}
