<?php
session_start();
#error_reporting(E_ALL);
#ini_set('display_errors', '1');

define( '_VALID_MOS', 1 );

date_default_timezone_set('America/Mexico_City');

require_once('../system/configuration.php');
require_once('../system/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);

header('Content-Type: application/json; charset=utf-8');

switch ($_POST['option']) {

	case 'getFolio':
		$id_location = $_POST['id_location'];
		$type = $_POST['type'];
		$increment = ($type == 'new') ? 1 : 0;
		/*$sqlMax="SELECT MAX(folio) + $newOrCurrent AS nuevo_folio FROM folio WHERE id_location IN ($id_location)";
		$records = $db->select($sqlMax);
		$folio = $records[0]['nuevo_folio'];
		$upQr['folio']  = $folio;
		$db->update('folio',$upQr," `id_location` = $id_location");*/
		if ($increment) {
			// Incrementa folio en 1 y guarda el nuevo valor en sesión segura
			$db->sqlPure("UPDATE folio SET folio = LAST_INSERT_ID(folio + 1) WHERE id_location = $id_location");
			// Obtiene el nuevo folio de forma segura para esta conexión
			$records = $db->select("SELECT LAST_INSERT_ID() AS nuevo_folio");
			$folio = $records[0]['nuevo_folio'];
		} else {
			// Si no es 'new', solo lee el folio actual
			$records = $db->select("SELECT folio AS nuevo_folio FROM folio WHERE id_location = $id_location");
			$folio = $records[0]['nuevo_folio'];
		}
		$result = [
			'success' => 'true',
			'folio'   => $folio,
			'message' => 'ok'
		];
		echo json_encode($result);
	break;

	case 'changeLocation':
		$id_location           = $_POST['id_location'];
		$_SESSION['uLocation'] = $id_location;
		setcookie('uLocation', $id_location, time() + 3600, '/');
		$result = [
			'success'  => 'true',
			'dataJson' => $id_location,
			'message'  => 'ok'
		];
		echo json_encode($result);
	break;

	case 'savePackage':
		$result   = [];
		$success  = 'false';
		$dataJson = [];
		$message  = 'Error al guardar la infomación del paquete';
		$data['id_location'] = $_POST['id_location'];
		$phone               = $_POST['phone'];
		$receiver            = $_POST['receiver'];
		// Elimina los espacios al inicio y final
		$phone    = trim($phone);
		$receiver = trim($receiver);
		// Reemplaza espacios múltiples entre palabras con un solo espacio
		$receiver = preg_replace('/\s+/', ' ', $receiver);
		$data['id_status']   = $_POST['id_status'];
		$data['note']        = $_POST['note'];
		$id_contact          = $_POST['id_contact'];

		$action              = $_POST['action'];
		try {
			/*if($id_contact==0){ //new case when the full number was entered and the user was not selected
				$contact['id_location']       = $data['id_location'];
				$contact['phone']             = $phone;
				$contact['contact_name']      = $receiver;
				$contact['id_contact_type']   = 1; //SMS
				$contact['id_contact_status'] = 1;
				$contact['id_contact']  = null;
				$id_contact = $db->insert('cat_contact',$contact);
			}else{
				$sqlContactCheck = "SELECT COUNT(phone) tContact FROM cat_contact 
				WHERE phone IN ('$phone') AND contact_name IN('$receiver') AND id_location IN(".$data['id_location'].")";
				$rstContactCheck = $db->select($sqlContactCheck);
				$tContact = $rstContactCheck[0]['tContact'];
				if($tContact==0){ //check if it is a variant of the name with the same phone number
					$contact['id_location']       = $data['id_location'];
					$contact['phone']             = $phone;
					$contact['contact_name']      = $receiver;
					$contact['id_contact_type']   = 1; //SMS
					$contact['id_contact_status'] = 1;
					$contact['id_contact']  = null;
					$id_contact = $db->insert('cat_contact',$contact);
				}
			}

			//normal process of assigning contact
			$data['id_contact']  = $id_contact;*/
	

			// Validar si ya existe el contacto
			$sqlCheck = "SELECT id_contact FROM cat_contact WHERE phone IN ('".$phone."') AND contact_name IN('".$receiver."')  AND id_location IN(".$data['id_location'].") AND id_contact_status = 1";
			$existing = $db->select($sqlCheck, [$phone, $receiver, $data['id_location']]);

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
			$data['id_contact'] = $id_contact;

			switch ($action) {
				case 'update':
					$id       = $_POST['id_package'];
					$errorLoadImg=null;
					if (isset($_FILES['evidence'])) {
						$pathLocation = '';
						switch ($data['id_location']) {
							case 1:
								$pathLocation = 'tlaquiltenango';
							break;
							default:
								$pathLocation = 'zacatepec';
							break;
						}

						$file = $_FILES['evidence'];
						$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp','application/pdf'];
						$fileType = mime_content_type($file['tmp_name']);

						if (in_array($fileType, $allowedTypes)) {
							$uploadDir        = '../evidence/'.$pathLocation.'/';
							$fileExtension    = pathinfo($file['name'], PATHINFO_EXTENSION);
							$originalFileName = pathinfo($file['name'], PATHINFO_FILENAME);
							$prefixIdPackage  = $id;
							$newFileName      = $prefixIdPackage . '_' . uniqid() .'_'. $originalFileName . '.' . $fileExtension;
							$uploadFile       = $uploadDir . $newFileName;

							if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
								$evidence['id_package']  = $id;
								$evidence['id_user']     = $_SESSION["uId"];;
								$evidence['path']        = $uploadFile;
								$evidence['id_location'] = $data['id_location'];
								$db->insert('evidence',$evidence);
								saveLog($id,$data['id_status'],'Carga de evidencia '.$newFileName,true);
							} else {
								$errorLoadImg = "Hubo un error al subir la imagen";
							}
						} else {
							$errorLoadImg = "Solo se permiten archivos de imagen (JPG, PNG, GIF)";
						}
					}
					if(isset($errorLoadImg)){
						$resultLoadImg = [
							'success'  => 'false',
							'dataJson' => [],
							'message'  => $errorLoadImg
						];
						echo json_encode($resultLoadImg);
						die();
					}

					if($data['id_status'] == 4 ){
						$data['d_date']     = date("Y-m-d H:i:s");
						$data['d_user_id']  = $_SESSION["uId"];
					}
					$success  = 'true';
					saveLog($id,$data['id_status'],'Cambio de Estatus/Modificación',true);
					$dataJson = $db->update('package',$data," `id_package` = $id");
					$message  = 'Actualizado';
				break;
				case 'new':
					if (empty($data['id_contact']) || $data['id_contact'] == 0 || $data['id_contact'] === null) {
						$success  = 'false';
						$dataJson = [];
						$message  = 'No se registro el usuario, vuelve a intentarlo';
					}else{
						$data['id_package']  = null;
						$data['folio']       = $_POST['folio'];
						$data['c_date']      = date("Y-m-d H:i:s");
						$data['c_user_id']   = $_SESSION["uId"];
						$data['tracking']    = $_POST['tracking'];
						$data['id_cat_parcel']  = $_POST['id_cat_parcel'];
						$sqlCheck = "SELECT COUNT(tracking) total FROM package WHERE tracking IN ('".$data['tracking']."')";
						$rstCheck = $db->select($sqlCheck);
						$total = $rstCheck[0]['total'];
						if($total==0){
							$data['marker']      = $_POST['id_marcador'];
							$_SESSION["uMarker"] = $data['marker'];
							setcookie('uMarker', $data['marker'], time() + 3600, '/');

							$_SESSION["uIdCatParcel"] = $_POST['id_cat_parcel'];
							setcookie('uIdCatParcel', $_POST['id_cat_parcel'], time() + 3600, '/');

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
							saveLog($new_id_package,1,'Nuevo registro de paquete');

							$success  = 'true';
							$dataJson = $msjFolios;
							$message  = $titleMsj;
						}else{
							$success  = 'false';
							$dataJson = [];
							$message  = 'El número de guía: '.$data['tracking'].' ya está registrado';
						}
					}

				break;
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

	case 'saveFolio':
		$result   = [];
		$success  = 'false';
		$dataJson = [];
		$message  = 'Error al guardar el folio';

		$id_location      = $_POST['id_location'];
		$data['folio']    = $_POST['mfNumFolio'];
		$data['voice']    = $_POST['mfVoice'];
		try {
			$success  = 'true';
			$dataJson = $db->update('folio',$data," `id_location` = $id_location");
			$message  = 'Actualizado';
			$result = [
				'success'  => $success,
				'dataJson' => $dataJson,
				'message'  => $message
			];
			$_SESSION["uVoice"] = $data['voice'];
		} catch (Exception $e) {
			$result = [
				'success'  => $success,
				'dataJson' => $dataJson,
				'message'  => $message.": ".$e->getMessage()
			];
		}
		echo json_encode($result);
	break;

	case 'getContact':
		$result   = [];
		$success  = 'false';
		$dataJson = [];
		$message  = 'Error al obtener la información de contactos';

		$phone       = $_POST['phone'];
		$id_location = $_POST['id_location'];
		$idParcel    = $_POST['idParcel'];
		$criteriaPhone = ($idParcel==1 || $idParcel==3) ? "'%$phone%'" : "'%$phone'";
		$limit = ($idParcel==1 || $idParcel==3) ? 10 : 20;


		try {
			$success  = 'true';
			$sqlContact = "SELECT id_contact,contact_name,phone FROM cat_contact WHERE phone LIKE ".$criteriaPhone." AND id_location IN($id_location) AND id_contact_status IN(1) ORDER BY contact_name ASC LIMIT ".$limit;
			$dataJson = $db->select($sqlContact);
			$message  = 'ok';
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

	case 'saveContact':
		$result   = [];
		$success  = 'false';
		$dataJson = [];
		$message  = 'Error al guardar el contacto';

		 $data['contact_name']      = $_POST['mCName'];
		 $data['id_contact_type']   = $_POST['mCContactType'];
		 $data['id_contact_status'] = $_POST['mCEstatus'];

		 $action = $_POST['action'];

		 try {
			switch ($action) {
				case 'update':
					$id       = $_POST['id_contact'];
					$success  = 'true';
					$dataJson = $db->update('cat_contact',$data," `id_contact` = $id");
					$message  = 'Contacto Actualizado';
				break;
				case 'new':
					$data['id_location']       = $_POST['id_location'];
					$data['phone']             = $_POST['mCPhone'];
					$data['id_contact']  = null;
					$success  = 'true';
					$dataJson = $db->insert('cat_contact',$data);
					$message  = 'Contacto Registrado';
				break;
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

	case 'releasePackage':
		$result   = [];
		$success  = 'false';
		$dataJson = [];
		$message  = 'Error liberar el paquete';
		$id_location = $_POST['id_location'];
		$tracking    = $_POST['tracking'];
		$desc_mov    = $_POST['desc_mov'];
		$jsonPakage = $_POST['listPackageRelease'];
		try {

			$sql="SELECT id_status
		   	FROM package
		   	WHERE tracking IN ('$tracking')
			AND id_location IN ($id_location)
			LIMIT 1";
			$checkRelease = $db->select($sql);
			if(count($checkRelease)==0){
				$success  = 'false';
				$message  = 'Paquete no encontrado';
			}else{
				$idEstatus = $checkRelease[0]['id_status'];
				switch ($idEstatus) {
					case 1:
					case 6:
						$success  = 'false';
						$message  = 'No es posible liberar un paquete sin contactar al destinatario';
						break;
					case 4:
						$success  = 'false';
						$message  = 'El paquete ya no esta disponible';
						break;
					case 3:
						$success  = 'false';
						$message  = 'El paquete ya fue entregado';
						break;
					case 2:
					case 5:
					case 7:
						$success  = 'true';
						$message  = 'Paquete Liberado';

						$data['id_status']  = 3; //Liberado
						$data['d_date']     = date("Y-m-d H:i:s");
						$data['d_user_id']  = $_SESSION["uId"];
						if (!empty($_POST['imgEvidence'])) {
							switch ($id_location) {
								case 1:
									$pathLocation = 'tlaquiltenango';
								break;
								default:
									$pathLocation = 'zacatepec';
								break;
							}
							$imageData = $_POST['imgEvidence'];
							$imageData = str_replace('data:image/png;base64,', '', $imageData);
							$imageData = str_replace(' ', '+', $imageData);
							$decodedImage = base64_decode($imageData);
							$nameFile = $tracking.'_'. uniqid(). '.png';
							$filePath = '../evidence/'.$pathLocation.'/'.$nameFile;
							file_put_contents($filePath, $decodedImage);
							saveLogByTracking($tracking,$data['id_status'],'Evidencia de entrega '.$nameFile,true);
							$sqlGetIdPackage   ="SELECT id_package FROM package WHERE tracking IN ('$tracking')";
							$records           = $db->select($sqlGetIdPackage);
							$id_pkg = $records[0]['id_package'];

							$evidence['id_package']  = $id_pkg;
							$evidence['id_user']     = $_SESSION["uId"];;
							$evidence['path']        = $filePath;
							$evidence['id_location'] = $id_location;
							$db->insert('evidence',$evidence);
						}
						saveLogByTracking($tracking,$data['id_status'],$desc_mov,true);
						$rst = $db->update('package',$data," `tracking` = '$tracking'");
						$listPackageRelease   = json_decode($jsonPakage, true);
						$inList = implode(", ", $listPackageRelease);
						$sql ="SELECT DISTINCT 
						p.tracking,
						cc.phone,
						cc.contact_name receiver,
						p.folio 
						FROM package p 
						INNER JOIN cat_contact cc ON cc.id_contact=p.id_contact 
						WHERE tracking IN($inList) AND p.id_location IN($id_location) 
						AND id_status IN (3)";
						$records = $db->select($sql);
						$dataJson = $records;
						break;
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

	case 'getRecordsSms':
		try {
		$result   = [];
		$success  = 'false';
		$dataJson = [];
		$message  = 'Error al consultar mensajes enviados';
		$id_package   = $_POST['id_package'];
		$sql="SELECT 
			n.n_date,
			cc.phone,
			cc.contact_name,
			un.user,
			n.message,
			sid 
			FROM 
				notification n 
			INNER JOIN users un ON un.id = n.n_user_id 
			INNER JOIN package p  ON p.id_package = n.id_package 
			INNER JOIN cat_contact cc ON cc.id_contact = p.id_contact 
			WHERE 
			n.id_package IN($id_package) 
			ORDER  BY n.n_date DESC";
		$success  = 'true';
		$dataJson = $db->select($sql);
		$message  = 'ok';
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

	case 'getRecordsEvidence':
		try {
		$result   = [];
		$success  = 'false';
		$dataJson = [];
		$message  = 'Error al consultar evidencias';
		$id_package   = $_POST['id_package'];
		$sql="SELECT 
		e.date_e,
		e.`path`,
		p.tracking,
		u.`user` 
		FROM 
		evidence e 
		INNER JOIN users u ON u.id  = e.id_user  
		INNER JOIN package p  ON p.id_package = e.id_package 
		WHERE
			e.id_package IN($id_package) 
		ORDER BY e.id_evidence DESC ";
		$success  = 'true';
		$dataJson = $db->select($sql);
		$message  = 'ok';
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

	case 'getRecordsHistory':
		try {
		$result   = [];
		$success  = 'false';
		$dataJson = [];
		$message  = 'Error al consultar el historial';
		$id_package   = $_POST['id_package'];
		$sql="SELECT 
			l.datelog,
			u.user name_user,
			ns.status_desc new_status,
			os.status_desc old_status,
			l.desc_mov 
			FROM logger l 
			INNER JOIN users u ON u.id = l.id_user 
			INNER JOIN cat_status ns ON ns.id_status = l.new_id_status 
			INNER JOIN cat_status os ON os.id_status = l.old_id_status 
			WHERE 
			l.id_package IN($id_package) 
			ORDER BY l.id_log DESC";
		$success  = 'true';
		$dataJson = $db->select($sql);
		$message  = 'ok';
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

	case 'saveTemplate':
	$result   = [];
		$success  = 'false';
		$dataJson = [];
		$message  = 'Error al guardar el folio';

		$id_location      = $_POST['id_location'];
		$data['template']    = $_POST['mTTemplate'];
		try {
			$success  = 'true';
			$dataJson = $db->update('cat_template',$data," `id_location` = $id_location");
			$message  = 'Actualizado';
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

	case 'bot':
		$result   = [];
		$success  = 'false';
		$dataJson = [];
		$message  = 'Error al enviar los mensajes';

		$id_location   = $_POST['id_location'];
		$idContactType = $_POST['idContactType'];
		$idEstatus     = $_POST['idEstatus'];
		$messagebot    = $_POST['messagebot'];
		$mbIdCatParcel    = $_POST['mbIdCatParcel'];
		$idParceIn   = ($mbIdCatParcel==99) ? '1,2,3': $mbIdCatParcel;
		$plb  = $_POST['phonelistbot'];
		$lineas = explode("\n", $plb);

		// Iterar sobre cada línea y limpiarla (eliminar espacios y comillas)
		$numeros_de_telefono = [];
		foreach ($lineas as $linea) {
			$numero = trim(str_replace('"', '', $linea));
			if (!empty($numero)) {
				$numeros_de_telefono[] = '"' . $numero . '"';
			}
		}

		// Unir los números de teléfono en un solo string con comas
		$phonelistbot = implode(",", $numeros_de_telefono);

		$nameFile = "chat_bot";
		$jsfile_content = '
console.log("    ____           __  __                               ____           ");
console.log("   / __ )__  __   / / / /___ _____  __  ______ _____   /  _/____ ____ _");
console.log("  / __  / / / /  / /_/ / __ `/_  / / / / / _  \\/ ___/   / // __  \\/ __ `/");
console.log(" / /_/ / /_/ /  / __  / /_/ / / /_/ /_/ /  __/ /     _/ // / / / /_/ / ");
console.log("/_____/\\___, /  /_/ /_/\\___,_/ /___/\\___,_/\\____/_/     /___/_/ /_/\\___,  (.)");
console.log("      /____/                                                  /____/   ");
const qrcode = require("qrcode-terminal");
const moment = require("moment-timezone");
const { Client } = require("whatsapp-web.js");
const Database = require("./database.js")
const readline = require("readline");
const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});
const client = new Client();
client.on("qr", (qr) => {
	qrcode.generate(qr, { small: true });
});
client.on("ready", async () => {
	console.log("Client is ready!");
	let db = new Database("false")
	const id_location = '.$id_location.';
	const id_estatus = '.$idEstatus.';
	const n_user_id='.$_SESSION["uId"].'
	const numbers = ['.$phonelistbot.'];
	const message = `'.$messagebot.'`;
	const idParceIn = `'.$idParceIn.'`;
	let iconBot= ``;
	let tipoMessage =``;
	switch (id_estatus) {
		case 1:
			iconBot= `🤖 `;
			tipoMessage =`Nuevo`;
		break;
		case 2:
			iconBot= `🔔 `;
			tipoMessage =`Recordatorio Mensajes Enviados`;
		break;
		case 5:
			iconBot= `📢 `;
			tipoMessage =`Recordatorio Paquetes Confirmados`;
		break;
	}
	// Mostrar números del arreglo en pantalla
	console.log("--------------------------------------");
	console.log(`Formato del mensaje: ${tipoMessage}`);
	console.log(message);
	console.log("--------------------------------------");
	console.log("Números de teléfono a los que se enviará el mensaje:");
	numbers.forEach((number, index) => {
	  console.log(`${index + 1}. ${number}`);
	});
	// Solicitar al usuario si desea continuar
	rl.question("Desea continuar? (s/n): ",  async (answer) => {
	  if (answer.toLowerCase() === "s") {
		let ids =  0;
		for (let i = 0; i < numbers.length; i++) {
			const number = numbers[i];
			const sql =`SELECT 
			cc.phone,
			cc.id_contact_type,
			GROUP_CONCAT(p.id_package) AS ids,
			GROUP_CONCAT(\'*(\',p.folio,\')-\',p.tracking,\'*\' SEPARATOR \'\n\') AS folioGuias 
			FROM package p 
			INNER JOIN cat_contact cc ON cc.id_contact=p.id_contact 
			WHERE 
			p.id_location IN (${id_location}) 
			AND p.id_status IN (${id_estatus}) 
			AND cc.phone IN(${number}) 
			AND p.id_cat_parcel IN(${idParceIn}) 
			GROUP BY cc.phone`
			const data        = await db.processDBQueryUsingPool(sql)
			const rst         = JSON.parse(JSON.stringify(data))
			ids               = rst[0] ? rst[0].ids : 0;
			let idContactType = rst[0] ? rst[0].id_contact_type : 0;
			let folioGuias    = rst[0] ? rst[0].folioGuias : 0;
			let fullMessage   = `${message}`;
			let sid = "";
			if(ids!=0){
				let registros = folioGuias ? folioGuias.split(\'\n\').filter(Boolean) : [];
				let totalRegistros = registros.length;
				fullMessage = `${message} \n*Total:${totalRegistros}*\n*(Folio)-Guía:*\n${folioGuias}`;
	
				let newStatusPackage = 1;
				let id_contact_type  = 3;
				let logWhats      = null;

				if(idContactType==2){ //WhatsApp
					const chatId = "521"+number+ "@c.us";
					let rst = await sendMessageWhats(client, chatId, fullMessage, iconBot);
					sid = `${rst.descMsj} ::Whatsapp Registrado`;
					logWhats = rst.details;
					newStatusPackage = rst.status ? (id_estatus == 5 ? 5 : 2) : 6;
					id_contact_type  = 2;
				}else{
					const number_details = await client.getNumberId(number); // get mobile number details
					if (number_details) {
						let rst = await sendMessageWhats(client, number_details._serialized, fullMessage,iconBot);
						sid =`${rst.descMsj}`;
						logWhats = rst.details;
						newStatusPackage = rst.status ? (id_estatus == 5 ? 5 : 2) : 6;
						// if(ids!=0){
							const lastMessage = moment().tz("America/Mexico_City").format("YYYY-MM-DD HH:mm:ss");
							const sqlUpdateTypeContact = `UPDATE cat_contact 
							SET id_contact_type=2, lastMessage=\'${lastMessage}\'
							WHERE id_location=${id_location} AND phone=\'${number}\' AND id_contact_type=1`
							await db.processDBQueryUsingPool(sqlUpdateTypeContact)
						// }
					} else {
						sid = `${number}, Número de móvil no registrado`
						logWhats = sid;
						newStatusPackage = 6
					}
					//if (i < numbers.length - 1) {
						//await sleep(1000); // tiempo de espera en segundos entre cada envío
					//}
				}

			//if(ids!=0){
				const listIds = ids.split(",");
				const nDate = moment().tz("America/Mexico_City").format("YYYY-MM-DD HH:mm:ss");
				let fullLog=`${sid}, ${logWhats}`;
				for (let i = 0; i < listIds.length; i++) {
					const id_package = listIds[i];
					const sqlSaveNotification = `INSERT INTO notification 
					(id_location,n_date,n_user_id,message,id_contact_type,sid,id_package) 
					VALUES 
					(${id_location},\'${nDate}\',${n_user_id},\'${fullMessage}\',${id_contact_type},\'${fullLog}\',${id_package})`
					await db.processDBQueryUsingPool(sqlSaveNotification);

					const sqlLogger = `INSERT INTO logger 
					(datelog, id_package, id_user, new_id_status, old_id_status, desc_mov) 
					VALUES 
					(\'${nDate}\', ${id_package}, ${n_user_id}, ${newStatusPackage}, ${id_estatus}, \'Envío de Mensaje WhatsApp\')`
					await db.processDBQueryUsingPool(sqlLogger)

					const sqlUpdatePackage = `UPDATE package SET 
					n_date = \'${nDate}\', n_user_id = \'${n_user_id}\', id_status=${newStatusPackage} 
					WHERE id_package IN (${id_package})`
					await db.processDBQueryUsingPool(sqlUpdatePackage)
				}
			}else{
				sid = ` Número ${number} sin guías para procesar, mensaje no enviado`;
			}

			console.log(`${i + 1} - ${sid}`);
			// Delay aleatorio entre 2 y 6 segundos entre mensajes
			await randomSleep(3000, 8000);

			// Cada 20 mensajes, pausa larga de 1 a 3 minutos
			await pauseEveryN(i + 1, 20, Math.floor(Math.random() * (180000 - 60000 + 1)) + 60000);
		}
		console.log("Proceso finalizado...");
	  } else {
		console.log("Proceso de envío de mensajes cancelado");
	  }
	  rl.close();
	});

});
client.initialize();
async function randomSleep(minMs, maxMs) {
    const ms = Math.floor(Math.random() * (maxMs - minMs + 1)) + minMs;
    console.log(`⏳ Esperando ${ms / 1000} segundos antes del siguiente mensaje...`);
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function pauseEveryN(count, n, pauseMs) {
    if (count > 0 && count % n === 0) {
        console.log(`⏸ Pausa larga de ${(pauseMs / 1000 / 60).toFixed(2)} minutos después de ${count} mensajes...`);
        await sleep(pauseMs);
    }
}
function sleep(ms) {
	return new Promise(resolve => setTimeout(resolve, ms));
}
async function sendMessageWhats(client, chatId, fullMessage, iconBot) {
	let rstSent = null;
    try {
        // Intentar enviar el mensaje
        const response = await client.sendMessage(chatId, `${iconBot} ${fullMessage}`);
        // Si el response tiene el campo id, consideramos que el mensaje fue enviado
        if (response && response.id) {
			let details = `success id:${response.id.id}, phone:${response.to}, timestamp:${response.timestamp}`;
			rstSent = {
				status:true,
				descMsj:`Mensaje enviado exitosamente a ${response.to}`,
				details:details
			}
        } else {
            // Si no hay un id en el response, consideramos que el envío falló
			rstSent = {
				status:false,
				descMsj:`Error en el envío, no se recibió un ID del mensaje`,
				details:`No se recibio respuesta del servicio`
			}
        }
    } catch (error) {
		rstSent = {
			status:false,
			descMsj:`Error al enviar mensaje a ${chatId}`,
			details:`error:${error}`
		}
    }
	return rstSent
}';
		$init = array(
			"nameFile" => $nameFile,
		);
		require_once('../nodejs/NodeJs.php');
		$nodeFile = new NodeJs($init);
		$path_file = NODE_PATH_FILE;
		$nodeFile->createContentFileJs($path_file, $jsfile_content);
		//$nodeFile->getContentFile(true); # true:continue
		$nodeJsPath = $nodeFile->getFullPathFile();

		$logNameFile = "log-".date("Y-m-d H-i-s").".txt";
		$txtLog  = new NodeJs($init);
		$allData = "idEstatus:".$idEstatus."\n"."messagebot:".$messagebot."\n"."idParceIn:".$idParceIn."\n"."phonelistbot:".$phonelistbot;
		$txtLog->createLog($logNameFile,$path_file."logs/", $allData);

		//handler emergency

		$nombreArchivo = '../views/modal/handler.php';
	$contenidoHTML='<div class="col-md-6">
	<div class="form-group">
		<div class="form-group">
		<textarea class="form-control" id="msjbt" name="msjbt" rows="4" readonly="">'.$messagebot.'</textarea>
		</div>
	</div>
</div>
<div class="col-md-6">
	<div class="form-group">
		<div class="form-group">
		<input type="hidden" class="form-control" name="idlocbt" id="idlocbt" value="'.$id_location.'" autocomplete="off" >
		</div>
	</div>
</div>
<div class="col-md-6">
	<div class="form-group">
		<div class="form-group">
		<input type="hidden" class="form-control" name="uidbt" id="uidbt" value="'.$_SESSION["uId"].'" autocomplete="off" >
		</div>
	</div>
</div>';
		foreach ($lineas as $telefono) {
			$contenidoHTML .="<a href='#' class='mensaje'  data-phone='$telefono'>Enviar mensaje a $telefono</a> <br>";
		}

		// Intenta abrir el archivo para escritura
		if ($archivo = fopen($nombreArchivo, 'w')) {
			// Escribe el contenido en el archivo
			fwrite($archivo, $contenidoHTML);
			// Cierra el archivo
			fclose($archivo);
			#echo "El archivo $nombreArchivo ha sido creado con éxito.";
		} else {
			#echo "No se pudo crear el archivo $nombreArchivo.";
		}

		$result = [
			'success'  => true,
			'dataJson' => $nodeJsPath,
			'message'  => 'Chatbot creado .!'
		];

		echo json_encode($result);
	break;

	case 'pullRealise':
		$result   = [];
		$success  = 'false';
		$dataJson = [];
		$message  = 'Error liberar el pull de paquetes';
		$id_location = $_POST['id_location'];
		$idsx        = $_POST['idsx'];
		$desc_mov    = $_POST['desc_mov'];
		$listIds = explode(",", $idsx);
		$totPaqPorLiberar = count($listIds);
		try {
			$sql="SELECT p.id_status,p.folio,cs.status_desc 
		   	FROM package p 
		   	INNER JOIN cat_status cs ON cs.id_status=p.id_status 
		   	WHERE p.id_package IN ($idsx) 
			AND p.id_location IN ($id_location) 
			AND p.id_status IN (2,5,7)";
			$checkRelease = $db->select($sql);
			$totalPaqueteDisponibles = count($checkRelease);

			if($totPaqPorLiberar==$totalPaqueteDisponibles){
				$success="true";
				$message="$totPaqPorLiberar Paquetes Liberados";
				$data['id_status']  = 3; //Liberado
				$data['d_date']     = date("Y-m-d H:i:s");
				$data['d_user_id']  = $_SESSION["uId"];

				$pathLocation = null;
				$nameFile     = null;
				$filePath     = null;
				switch ($id_location) {
					case 1:
						$pathLocation = 'tlaquiltenango';
					break;
					default:
						$pathLocation = 'zacatepec';
					break;
				}
				if (!empty($_POST['imgEvidence'])) {
					$imageData = $_POST['imgEvidence'];
					$imageData = str_replace('data:image/png;base64,', '', $imageData);
					$imageData = str_replace(' ', '+', $imageData);
					$decodedImage = base64_decode($imageData);
					$nameFile = 'Pull_Evidence_'. uniqid(). '.png';
					$filePath = '../evidence/'.$pathLocation.'/'.$nameFile;
					file_put_contents($filePath, $decodedImage);
				}
				foreach ($listIds as $i => $idpkg) {
					if (!empty($_POST['imgEvidence'])) {
						saveLog($idpkg,$data['id_status'],'Evidencia de entrega '.$nameFile,true);
						$evidence['id_package']  = $idpkg;
						$evidence['id_user']     = $_SESSION["uId"];;
						$evidence['path']        = $filePath;
						$evidence['id_location'] = $id_location;
						$db->insert('evidence',$evidence);
					}
					saveLog($idpkg,$data['id_status'],$desc_mov,true);
				}
				$rst = $db->update('package',$data," `id_package` IN ($idsx)");
			}else{
				$sql="SELECT p.id_status,p.folio,cs.status_desc 
				FROM package p 
				INNER JOIN cat_status cs ON cs.id_status=p.id_status 
				WHERE p.id_package IN ($idsx) 
				AND p.id_location IN ($id_location) 
				AND p.id_status NOT IN (2,5,7)";
				$noAvailable = $db->select($sql);
				$success="error";
				$mensaje="No es posible liberar el grupo de paquetes, por favor verifica el estatus de los paquetes:\n";
				foreach ($noAvailable as $resultado) {
					$mensaje .= "Folio:" . $resultado['folio'] . ", Estatus:" . $resultado['status_desc'] . "\n";
				}
				$message = $mensaje. "\nNota:Solo paquetes con estatus:mensaje enviado, contactado o confirmado pueden ser liberados.";
			}

			$result = [
				'success'  => $success,
				'dataJson' => [],
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

		case 'mensajeManual':
			$result   = [];
			$success  = 'false';
			$dataJson = [];
			$message  = 'Error envio de mensaje';
			$id_location   = $_POST['id_location'];
			$uidbt    = $_POST['uidbt'];
			$msjbt    = $_POST['msjbt'];
			$telefono = $_POST['telefono'];
			try {
				$sql="SELECT 
				cc.phone,
				GROUP_CONCAT(p.id_package) AS ids,
				GROUP_CONCAT('*(',p.folio,')-',p.tracking,'*' SEPARATOR '\n') AS folioGuias 
				FROM package p 
				INNER JOIN cat_contact cc ON cc.id_contact=p.id_contact 
				WHERE 
				p.id_location IN ($id_location) 
				AND p.id_status IN (1) 
				AND cc.phone IN ($telefono)
				GROUP BY cc.phone";
				$rst = $db->select($sql);
				$exist = count($rst);
				$txtFolios='';
				if($exist!=0){
					$success="true";
					$ids = $rst[0]['ids'];
					$folioGuias = $rst[0]['folioGuias'];
					$txtFolios="\n*(Folio)-Guía:*\n$folioGuias";
					$fullMesage= $msjbt." ".$txtFolios;

					$listIds = explode(",", $ids);
					foreach ($listIds as $id_package) {
						$sid ="Mensaje enviado con éxito a, $telefono";
						$nDate = date("Y-m-d H:i:s");
						$data['id_location']  = $id_location;
						$data['n_date']      = $nDate;
						$data['n_user_id']   = $uidbt;
						$data['message']  = $fullMesage;
						$data['id_contact_type']  = 2;
						$data['sid']  = $sid;
						$data['id_package']  = $id_package;
						$db->insert('notification',$data);

						$upData['n_date']    = $nDate;
						$upData['n_user_id'] = $uidbt;
						$upData['id_status'] = 2;
						saveLog($id_package,$upData['id_status'],'Envío de Mensaje Manual',true);
						$db->update('package',$upData," `id_package` IN($id_package)");
					}

				}

				$result = [
					'success'  => $success,
					'dataJson' => [],
					'message'  => $txtFolios
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
		
		case 'chekout':
			$arrayRst = [];
			$id_location = $_POST['id_location'];
			$idParcel = $_POST['idParcel'];
			$parcelIn   = ($idParcel==99) ? " AND p.id_cat_parcel IN(1,2,3) ": "AND p.id_cat_parcel IN(".$idParcel.")";
			$sql = "SELECT 
			p.id_package,
			p.id_cat_parcel,
			p.tracking,
			RIGHT(cc.phone, 4) AS last_four_digits,
			cc.phone,
			cc.contact_name receiver,
			p.folio 
			FROM package p 
			LEFT JOIN cat_contact cc ON cc.id_contact=p.id_contact 
			LEFT JOIN cat_status cs ON cs.id_status=p.id_status 
			WHERE 1 
			AND p.id_location IN ($id_location) 
			AND p.id_status IN(1,2,6,7)
			$parcelIn";
			$packages = $db->select($sql);
			foreach($packages as $d){
				if($d['id_cat_parcel']==1){
					jtCheckServiceTracking($d,$arrayRst);
				}else if($d['id_cat_parcel']==2){
					imileCheckServiceTracking($d,$arrayRst);
				}else if($d['id_cat_parcel']==3){
					cnmexCheckServiceTracking($d,$arrayRst);
				}
			}
			$result = [
				'success'      => 'true',
				'trackingList' => $arrayRst,
				'message'      => 'ok'
			];
			echo json_encode($result);
		break;

		case 'createBarcode':

			$id_location  = $_POST['id_location'];
			$idParcel   = $_POST['idParcel'];
			$parcelIn   = ($idParcel==99) ? " AND p.id_cat_parcel IN(1,2,3) ": "AND p.id_cat_parcel IN(".$idParcel.")";
			$labelParcel = "";
			switch ($idParcel) {
				case 1:
					$labelParcel = "jt";
					break;
				case 2:
					$labelParcel = "imile";
					break;
				case 3:
					$labelParcel = "cnmex";
					break;
				case 99:
					$labelParcel = "todas";
					break;
			}

			$fechaAutoIni  = $_POST['fechaAuto'];
			$fechaAutoFin      = explode(" ", $fechaAutoIni)[0];
			$typeLocation ='tlaqui';
			if($id_location==2){$typeLocation='zaca';}

			$tat  = $_POST['textAreatracking'];
			$lineasTat = explode("\n", $tat);

			// Iterar sobre cada línea y limpiarla (eliminar espacios y comillas)
			$numeros_de_guia = [];
			foreach ($lineasTat as $linea) {
				$numero = trim(str_replace('"', '', $linea));
				if (!empty($numero)) {
					$numeros_de_guia[] = "'" . $numero . "'";
				}
			}
			// Unir los números de teléfono en un solo string con comas
			$textAreatracking = implode(",", $numeros_de_guia);

			$nameTypeMode = '';
			$listEstatus  = '';
			$dateBetween  = '';
			switch ($_POST['type_mode']) {
				case 'auto':
					$nameTypeMode='auto_servicio';
					#$listEstatus='1, 2, 3, 4, 5, 6, 7'; // al estatus
					$listEstatus = "";
					$dateBetween = "AND p.c_date BETWEEN '".$fechaAutoIni.":00' AND '".$fechaAutoFin." 23:59:59' ";
					break;
				case 'ocurre':
					$nameTypeMode = 'ocurre';
					//$listEstatus  = '1, 2, 7'; // Nuevo / Mensaje Enviado / Contactado
					$listEstatus  = " AND p.id_status IN ('1', '2', '7') ";
					#$dateBetween = "AND p.c_date BETWEEN '".date('Y-m-d')." 00:00:00' AND '".date('Y-m-d')." 23:59:59' ";
					$dateBetween  = "";
					break;
				case 'anomalia':
					$nameTypeMode = 'anomalia';
					#$listEstatus = '6'; //Mensaje de error
					$listEstatus  = " AND p.id_status IN ('6') ";
					$dateBetween  = "";
					break;
				case 'manual':
					$nameTypeMode='manual';
					$listEstatus = "";
					$dateBetween = "AND p.tracking IN (".$textAreatracking.") ";
					break;
			}

			$result = [
				'success'   => false,
				'message'   => 'No se pudo abrir el archivo ZIP'
			];

			// Incluir la biblioteca PHPBarcode
			require_once('barcode.php');

			$sql ="SELECT 
				p.tracking 
			FROM 
				package p 
			LEFT JOIN cat_contact cc ON cc.id_contact = p.id_contact 
			LEFT JOIN cat_status cs ON cs.id_status = p.id_status 
			LEFT JOIN users uc ON uc.id = p.c_user_id 
			LEFT JOIN cat_location cl ON cl.id_location = p.id_location 
			LEFT JOIN users un ON un.id = p.n_user_id 
			LEFT JOIN users ud ON ud.id = p.d_user_id 
			WHERE 1 
				AND p.id_location IN ($id_location) 
				$listEstatus 
				$dateBetween 
				$parcelIn 
			ORDER BY p.id_package DESC";
			$codigos = $db->select($sql);
			if(count($codigos)==0){
				$result = [
					'success'   => 'false',
					'zip'       => '',
					'message'   => 'Sin registros para crear códigos de barras'
				];
				echo json_encode($result);
				return;
			}

			$archivos = array();
			// Iterar sobre cada código y generar el código de barras correspondiente
			$c=1;
			foreach ($codigos as $data) {
				$codigo = $data['tracking'];
				// Nombre del archivo para guardar el código de barras
				$nombreImagen = $c.'_'.$codigo . '.png';

				// Llamar a la función barcode() para generar el código de barras con un tamaño más grande
				barcode($nombreImagen, $codigo, 80, 'horizontal', 'code128', true, 1);

			   # aca agegar el nombreImagen al array archivos
			   array_push($archivos,$nombreImagen);
			   $c++;
			}

			$nameOcurre= $nameTypeMode.'_'.$typeLocation.'_'.$labelParcel.'_T'.count($codigos).'_'.date('Y-m-d');
			// Nombre del archivo ZIP
			$zipFilename = $nameOcurre.'.zip';

			// Crear una instancia de ZipArchive
			$zip = new ZipArchive();

			// Abrir el archivo ZIP para escritura
			if ($zip->open($zipFilename, ZipArchive::CREATE) === TRUE) {
				// Agregar cada archivo al archivo ZIP
				foreach ($archivos as $archivo) {
					// Crear un objeto SplFileInfo para el archivo
					$fileInfo = new SplFileInfo($archivo);
					// Agregar el archivo al ZIP usando el nombre base como nombre interno
					$zip->addFile($archivo, $fileInfo->getBasename());
				}
				// Cerrar el archivo ZIP
				$zip->close();

				$result = [
					'success'   => 'true',
					'zip'       => $zipFilename,
					'message'   => 'ok'
				];
			}

			foreach ($archivos as $archivo) {
				unlink($archivo);
			}

			echo json_encode($result);
		break;

		case 'deleteZip':
			$zipFile=$_POST['zipFile'];
			unlink($zipFile);
		break;

		case 'check-tracking':
			$tracking = isset($_POST['tracking']) ? htmlspecialchars($_POST['tracking']) : '';

			$result   = [];
			$success  = 'false';
			$message  = 'No se encontró el número de guía especificado';
			$sql = "SELECT tracking, CASE 
			WHEN id_location = 1 THEN 'Tlaquiltenango' 
			WHEN id_location = 2 THEN 'Zacatepec' 
			END AS ubicacion 
			FROM package 
			WHERE 
			tracking IN ('$tracking')
			AND id_status NOT IN(3,4,5)
			LIMIT 1";
			$rst      = $db->select($sql);
			$total    = COUNT($rst);
			if($total >= 1){
				$location = $rst[0]['ubicacion'];
				$success  = 'true';
				$message  = "Felicitaciones, tu paquete está listo en la sucursal de $location";
			}
			$result = [
				'success'  => $success,
				'message'  => $message
			];
			echo json_encode($result);
		break;

		case 'pullConfirm':
			$result   = [];
			$success  = 'false';
			$dataJson = [];
			$message  = 'Error confirmar el pull de paquetes';
			$id_location = $_POST['id_location'];
			$idsx    = $_POST['idsx'];
			$listIds = explode(",", $idsx);
			$totPaqPorConfirmar = count($listIds);
			try {
				$sql="SELECT p.id_package,p.id_status,p.folio,cs.status_desc,note 
				   FROM package p 
				   INNER JOIN cat_status cs ON cs.id_status=p.id_status 
				   WHERE p.id_package IN ($idsx) 
				AND p.id_location IN ($id_location) 
				AND p.id_status IN (2,7)";
				$checkRelease = $db->select($sql);
				$totalPaqueteDisponibles = count($checkRelease);

				if($totPaqPorConfirmar==$totalPaqueteDisponibles){
					$success="true";
					$message="$totPaqPorConfirmar Paquetes Confirmados";
					$data['id_status']  = 5; //Confirmado
					foreach ($listIds as $i => $idpkg) {
						saveLog($idpkg,$data['id_status'],'Confirmación de Paquete por Selección',true);
					}
					$rst = $db->update('package',$data," `id_package` IN ($idsx)");
					foreach ($checkRelease as $rdata) {
						$separator = ($rdata['note']=='') ?  '':', ';
						$dUpN['note'] = $rdata['note'].$separator.'Confirmó '.$_SESSION["uName"].' el día '.date("Y-m-d H:i");
						$idnotex=$rdata['id_package'];
						$db->update('package',$dUpN," `id_package` IN ($idnotex)");
					}
				}else{
					$sql="SELECT p.id_status,p.folio,cs.status_desc 
					FROM package p 
					INNER JOIN cat_status cs ON cs.id_status=p.id_status 
					WHERE p.id_package IN ($idsx) 
					AND p.id_location IN ($id_location) 
					AND p.id_status NOT IN (2,7)";
					$noAvailable = $db->select($sql);
					$success="error";
					$mensaje="No es posible confirmar el grupo de paquetes, por favor verifica el estatus de los paquetes:\n";
					foreach ($noAvailable as $resultado) {
						$mensaje .= "Folio:" . $resultado['folio'] . ", Estatus:" . $resultado['status_desc'] . "\n";
					}
					$message = $mensaje. "\nNota:Solo paquetes con estatus:mensaje enviado o contactado pueden ser confirmados.";
				}

				$result = [
					'success'  => $success,
					'dataJson' => [],
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

			case 'revertStatus':
				$result   = [];
				$success  = 'false';
				$dataJson = [];
				$message  = 'Error al guardar cambiar el estatus';
				$id_package      = $_POST['id_package'];

				try {
					$success  = 'true';
					$data['id_status']  = 7;
					saveLog($id_package,$data['id_status'],'Activacion de Guía, se cambia a Contactado',true);
					$dataJson = $db->update('package',$data," `id_package` = $id_package");
					$message  = 'Guía Actualizada';
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

			case 'checkGuia':
				$result   = [];
				$success  = 'false';
				$dataJson = [];
				$message  = 'Guía no encontrada';
				$result = [
						'success'  => $success,
						'dataJson' => $dataJson,
						'message'  => $message
					];
				$vGuia      = $_POST['vGuia'];
				$sqlCheckGuia="SELECT
				p.tracking, cc.contact_name, cc.phone,p.folio,p.marker,UPPER(SUBSTRING(TRIM(REPLACE(
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
							'É', 'E')), 1, 1)) AS initial
				FROM
				package p
				left join cat_contact cc on cc.id_contact =p.id_contact
				WHERE
				p.tracking IN('".$vGuia."')";
				$records           = $db->select($sqlCheckGuia,true);
				if(count($records)>=1){
					$result = [
						'success'  => 'true',
						'dataJson' => $records[0],
						'message'  => ''
					];
				}

				echo json_encode($result);
			break;

			case 'puppeteer':
				$result   = [];
				$success  = 'false';
				$dataJson = [];
				$message  = 'Error al crear puppeteer';

				$id_location   = $_POST['id_location'];
				$mpptIdCatParcel    = $_POST['mpptIdCatParcel'];
				$mpptIdMarcador = $_POST['mpptIdMarcador'];
				$ptlb  = $_POST['mpptListTracking'];
				$lineas = explode("\n", $ptlb);

				$numeros_guias = [];
				foreach ($lineas as $linea) {
					$numero = trim(str_replace('"', '', $linea));
					if (!empty($numero)) {
						$numeros_guias[] = '"'. $numero . '"';
					}
				}
				$mpptListTracking = implode(",", $numeros_guias);
				$nameFile = "puppeteer";
				$jsfile_content ='
// List of tracking numbers to process
const trackingNumbers = [
'.$mpptListTracking.'
];
// Array to store all results
const resultados = [];
// Endpoint function
async function enviarDatos(resultado) {
    try {
        const endpoint = "https://paqueterialospinos.com/controllers/puppeteer.php";
        console.log(`📤 Enviando datos de ${resultado.tracking} al endpoint paqueterialospinos`);
        const response = await page.evaluate(async (url, data) => {
            const response = await fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        }, endpoint, resultado);
        console.log("✅ Respuesta del servidor:", response);
        return response; // <--- return full object
    } catch (error) {
        console.error("❌ Error al enviar datos:", error);
        return { success: "false", message: "Error de red o excepción" };
    }
}
let contador = 0;
const totalElementos = trackingNumbers.length;
for (const trackingNumber of trackingNumbers) {
    contador++;
    const resultado = {
		option:"store",
		id_location:'.$id_location.',
		phone:"",
		receiver:"",
		id_contact:'.$_SESSION["uId"].',
		tracking:trackingNumber,
		id_cat_parcel:'.$mpptIdCatParcel.',
		id_marcador:"'.$mpptIdMarcador.'",
		estado:""
	};
    try {
        await page.goto("https://jmx.jtjms-mx.com/app/serviceQualityIndex/recordSheet?title=Orden%20de%20registro&moduleCode=");
        await page.waitForTimeout(2300);
        try {
            await page.waitForSelector(`input[placeholder="Por favor, ingrese"]`, { timeout: 2000 });
        } catch {
            console.log("No se encontró el input en español, recargando...");
            await page.reload();
            await page.waitForSelector(`input[placeholder="Por favor, ingrese"]`, { timeout: 3000 });
        }
        const input = await page.$(`input[placeholder="Por favor, ingrese"]`);
        await input.click();
        await page.evaluate((inputElement, text) => {
            inputElement.value = text;
            const event = new Event("input", { bubbles: true });
            inputElement.dispatchEvent(event);
        }, input, trackingNumber);
		console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
        console.log(`:::::: Procesando ${contador} de ${totalElementos} ::::::`);
		console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
        const currentValue = await page.evaluate(el => el.value, input);
        if (currentValue !== trackingNumber) {
            throw new Error("Error al pegar el texto");
        }
        console.log("✅ Texto pegado correctamente");
        // Wait and click "Información básica" tab
        await page.waitForTimeout(600);
        await page.waitForSelector("#tab-base.el-tabs__item", { timeout: 500 });
        await page.click("#tab-base.el-tabs__item");
        console.log(`✅ Pestaña "Información básica" clickeada`);
        await page.waitForTimeout(800);
        // Click all info icons
        try {
            await page.waitForSelector(".iconfuwuzhiliang-mingwen", { timeout: 800 });
            const icons = await page.$$(".iconfuwuzhiliang-mingwen");
            console.log(`🔍 Íconos encontrados: ${icons.length}`);
            for (let i = 0; i < icons.length; i++) {
                try {
                    await icons[i].hover();
                    await icons[i].click();
                    await page.waitForTimeout(200);
                    console.log(`✅ Ícono ${i + 1} clickeado`);
                } catch (error) {
                    console.warn(`⚠️ Error al hacer clic en ícono ${i + 1}:`, error.message);
                }
            }
        } catch (error) {
            console.error("❌ No se encontraron íconos:", error.message);
        }
        await page.waitForTimeout(100);
        // Extract receiver information
        await page.waitForSelector(".item .row", { timeout: 2800 });
        const [nameR, telR] = await page.evaluate(() => {
            const rows = Array.from(document.querySelectorAll(".item .row"));
            const nameRow = rows.find(row => row.textContent.includes("Nombre del receptor:"));
            const telRow = rows.find(row => row.textContent.includes("Teléfono del destinatario:"));
            const nameR = nameRow ? nameRow.querySelector("span").textContent.trim() : "";
            let telR = telRow ? telRow.querySelector("span").textContent.trim() : "";
            telR = telR.slice(-10);
            return [nameR, telR];
        });
        // Validación de datos antes del envío
		let datosValidos = true;
		// 1. Validar que el nombre no esté vacío
		if (!nameR || nameR.trim() === "") {
			console.log("❌ Nombre del receptor está vacío - No se enviará al endpoint");
			datosValidos = false;
			resultado.estado = "Falló: Nombre receptor vacío";
		}
		// 2. Validar que el teléfono no contenga asteriscos
		if (telR.includes("*")) {
			console.log("❌ Teléfono contiene asteriscos - No se enviará al endpoint");
			datosValidos = false;
			resultado.estado = "Falló: Teléfono con asteriscos";
		}
		// 3. Validar formato del teléfono (10 dígitos)
		if (!/^\d{10}$/.test(telR)) {
			console.log("❌ Teléfono no tiene 10 dígitos - No se enviará al endpoint");
			datosValidos = false;
			resultado.estado = "Falló: Teléfono inválido";
		}
		// Guardar datos en el objeto resultado (aunque no sean válidos)
		resultado.receiver = nameR;
		resultado.phone = telR;
		// Solo enviar si pasa todas las validaciones
		if (datosValidos) {
			console.log(`✅ Datos válidos: ${nameR} | ${telR}`);
			try {
				const respuestaServidor = await enviarDatos(resultado);
				if (respuestaServidor.success === "true") {
					resultado.estado = "Registrado";
				} else {
					const msg = respuestaServidor.message || "Sin mensaje del servidor";
					resultado.estado = "Falló: " + msg.replace(/["\']/g, "");
				}
			} catch (error) {
				resultado.estado = "Falló: Error de conexión";
				console.error("Error al enviar datos:", error);
			}
		} else {
			console.log(`⏸️ Datos no enviados: ${nameR} | ${telR} - Motivo: ${resultado.estado}`);
		}
    } catch (error) {
        console.error(`❌ Error al procesar ${trackingNumber}:`, error.message);
        resultado.estado = `error: ${error.message}`;
    } finally {
        resultados.push(resultado);
        await page.waitForTimeout(1000);
    }
}
await page.waitForTimeout(500);
console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
console.log("📊 FIN DEL PROCESO:");
// Filtrar y contar resultados
const guiasRegistradas = resultados.filter(r => r.estado === "Registrado");
const guiasConError = resultados.filter(r => r.estado !== "Registrado" && r.estado.includes("Falló")); // Asegura que solo cuente los fallos reales
// Mostrar resumen
console.log(`📦 Total procesado: ${resultados.length}`);
console.log(`✅ Guías registradas correctamente: ${guiasRegistradas.length}`);
if (guiasConError.length > 0) {
	console.log(`❌ Guías con errores: ${guiasConError.length}`);
    console.log("\n🔍 Detalle de errores:");
    guiasConError.forEach((resultado, index) => {
        console.log(`\n${index + 1}. Guía: ${resultado.tracking}`);
        console.log(`   Estado: ${resultado.estado}`);
        console.log(`   Receptor: ${resultado.receiver || "No disponible"}`);
        console.log(`   Teléfono: ${resultado.phone || "No disponible"}`);
    });
}
';
			$init = array(
				"nameFile" => $nameFile,
			);
				require_once('../nodejs/NodeJs.php');
				$nodeFile = new NodeJs($init);
				$path_file = NODE_PATH_FILE;
				$nodeFile->createContentFileJs($path_file, $jsfile_content);
				//$nodeFile->getContentFile(true); # true:continue
				$nodeJsPath = $nodeFile->getFullPathFile();

				$logNameFile = "puppeteer-".date("Y-m-d H-i-s").".txt";
				$txtLog  = new NodeJs($init);
				$allData = "id_location:".$id_location."\n"."id_marcador:".$mpptIdMarcador."\n"."id_cat_parcel:".$mpptIdCatParcel."\n"."mpptListTracking:".$mpptListTracking;
				$txtLog->createLog($logNameFile,$path_file."logs/", $allData);
				$result = [
					'success'  => true,
					'dataJson' => $nodeJsPath,
					'message'  => 'Puppeteer creado .!'
				];
				echo json_encode($result);
			break;

}

function cnmexCheckServiceTracking($d,&$arrayRst){
	$waybillNo   = $d['tracking'];
	$phoneVerify = $d['last_four_digits'];
	$phone       = $d['phone'];
	$receiver    = $d['receiver'];
	$folio       = $d['folio'];

	$url = "https://global.cainiao.com/global/detail.json?mailNos=".$waybillNo."&lang=en-US&language=en-US";

	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($curl);
	curl_close($curl);

	$commonValues = [
		'guia'     => $waybillNo,
		'phone'    => $phone,
		'receiver' => $receiver,
		'folio'    => $folio,
		'parcel'   => 'CNMEX'
	];

	if ($response === false) {
		$arrayRst[$waybillNo] = array_merge([
			'status'      => 'Verificar',
			'desc_status' => 'Error al realizar la solicitud, intenta nuevamente'
		],$commonValues);
	} else {
		$jsonDecode = json_decode($response, true);
		if ($jsonDecode && isset($jsonDecode['module']['detailList']) && count($jsonDecode['module']['detailList']) > 0) {
			$details = $jsonDecode['module']['detailList'];
			$lastScanTime = '';
			$lastStatus = '';
			foreach ($details as $detail) {
				$scanTime = strtotime($detail['time']);
				if ($scanTime > strtotime($lastScanTime)) {
					$lastScanTime = $detail['time'];
					$lastStatus   = $detail['desc'];
				}
			}

			// Determinar el estatus final
			if ($lastStatus === 'Package delivered') {
				$arrayRst[$waybillNo] = array_merge([
					'status'      => 'Verificar',
					'desc_status' => 'Liberado en CNMEX pero no en el sistema interno',
					'scanTime'    => $lastScanTime
				],$commonValues);
			} elseif ($lastStatus === 'Delivery') { //TODO
				$arrayRst[$waybillNo] = array_merge([
					'status'      => 'Ok',
					'desc_status' => 'Entrega en curso',
					'scanTime'    => ''
				],$commonValues);
			} else {
				$arrayRst[$waybillNo] = array_merge([
					'status'      => 'Verificar',
					'desc_status' => 'El estatus del paquete no pudo ser determinado',
					'scanTime'    => ''
				],$commonValues);
			}
		} else {
			$arrayRst[$waybillNo] = array_merge([
				'status'      => 'Verificar',
				'desc_status' => 'Sin detalles',
				'scanTime'    => ''
			],$commonValues);
		}
	}
}

function imileCheckServiceTracking($d,&$arrayRst){
	$waybillNo   = $d['tracking'];
	$phoneVerify = $d['last_four_digits'];
	$phone       = $d['phone'];
	$receiver    = $d['receiver'];
	$folio       = $d['folio'];

	$url = "https://www.imile.com/saastms/mobileWeb/track/query?waybillNo=".$waybillNo;

	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($curl);
	curl_close($curl);

	$commonValues = [
		'guia'     => $waybillNo,
		'phone'    => $phone,
		'receiver' => $receiver,
		'folio'    => $folio,
		'parcel'   => 'IMILE'
	];

	if ($response === false) {
		$arrayRst[$waybillNo] = array_merge([
			'status'      => 'Verificar',
			'desc_status' => 'Error al realizar la solicitud, intenta nuevamente'
		],$commonValues);
	} else {
		$jsonDecode = json_decode($response, true);
		if ($jsonDecode && isset($jsonDecode['resultObject']['trackInfos']) && count($jsonDecode['resultObject']['trackInfos']) > 0) {
			$details = $jsonDecode['resultObject']['trackInfos'];
			$lastScanTime = '';
			$lastStatus = '';
			foreach ($details as $detail) {
				$scanTime = strtotime($detail['time']);
				if ($scanTime > strtotime($lastScanTime)) {
					$lastScanTime = $detail['time'];
					$lastStatus   = $detail['trackStageTx'];
				}
			}

			// Determinar el estatus final
			if ($lastStatus === 'Delivered') {
				$arrayRst[$waybillNo] = array_merge([
					'status'      => 'Verificar',
					'desc_status' => 'Liberado en IMILE pero no en el sistema interno',
					'scanTime'    => $lastScanTime
				],$commonValues);
			} elseif ($lastStatus === 'Delivery') {
				$arrayRst[$waybillNo] = array_merge([
					'status'      => 'Ok',
					'desc_status' => 'Entrega en curso',
					'scanTime'    => ''
				],$commonValues);
			} else {
				$arrayRst[$waybillNo] = array_merge([
					'status'      => 'Verificar',
					'desc_status' => 'El estatus del paquete no pudo ser determinado',
					'scanTime'    => ''
				],$commonValues);
			}
		} else {
			$arrayRst[$waybillNo] = array_merge([
				'status'      => 'Verificar',
				'desc_status' => 'Sin detalles',
				'scanTime'    => ''
			],$commonValues);
		}
	}
}

function jtCheckServiceTracking($d,&$arrayRst){
	$waybillNo   = $d['tracking'];
	$phoneVerify = $d['last_four_digits'];
	$phone       = $d['phone'];
	$receiver    = $d['receiver'];
	$folio       = $d['folio'];

	$url = "https://official.jtjms-mx.com/official/logisticsTracking/v3/getDetailByWaybillNo?waybillNo=".$waybillNo."&langType=ES&phoneVerify=".$phoneVerify;

	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($curl);
	curl_close($curl);

	$commonValues = [
		'guia'     => $waybillNo,
		'phone'    => $phone,
		'receiver' => $receiver,
		'folio'    => $folio,
		'parcel'   => 'J&T'
	];

	if ($response === false) {
		$arrayRst[$waybillNo] = array_merge([
			'status'      => 'Verificar',
			'desc_status' => 'Error al realizar la solicitud, intenta nuevamente'
		],$commonValues);
	} else {
		$jsonDecode = json_decode($response, true);
		if ($jsonDecode && isset($jsonDecode['data']['details']) && count($jsonDecode['data']['details']) > 0) {
			$details = $jsonDecode['data']['details'];
			$lastScanTime = '';
			$lastStatus = '';
			foreach ($details as $detail) {
				$scanTime = strtotime($detail['scanTime']);
				if ($scanTime > strtotime($lastScanTime)) {
					$lastScanTime = $detail['scanTime'];
					$lastStatus   = $detail['status'];
				}
			}

			// Determinar el estatus final
			if ($lastStatus === '已签收') {
				$arrayRst[$waybillNo] = array_merge([
					'status'      => 'Verificar',
					'desc_status' => 'Liberado en J&T pero no en el sistema interno',
					'scanTime'    => $lastScanTime
				],$commonValues);
			} elseif ($lastStatus === '运送中') {
				$arrayRst[$waybillNo] = array_merge([
					'status'      => 'Ok',
					'desc_status' => 'En tránsito',
					'scanTime'    => ''
				],$commonValues);
			} elseif ($lastStatus === '派件中') {
				$arrayRst[$waybillNo] = array_merge([
					'status'      => 'Ok',
					'desc_status' => 'Entrega en curso',
					'scanTime'    => ''
				],$commonValues);
			}else {
				$arrayRst[$waybillNo] = array_merge([
					'status'      => 'Verificar',
					'desc_status' => 'El estatus del paquete no pudo ser determinado',
					'scanTime'    => ''
				],$commonValues);
			}
		} else {
			$arrayRst[$waybillNo] = array_merge([
				'status'      => 'Verificar',
				'desc_status' => 'Sin detalles',
				'scanTime'    => ''
			],$commonValues);
		}
	}
	//return $arrayRst;

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
	$dataLog['id_user']       = $_SESSION["uId"];
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

function saveLogByTracking($tracking,$id_status,$desc_mov,$flag){
	global $db;
	$sqlGetIdPackage   ="SELECT id_package FROM package WHERE tracking IN ('$tracking')";
	$records           = $db->select($sqlGetIdPackage);
	$id_package = $records[0]['id_package'];
	saveLog($id_package,$id_status,$desc_mov,$flag);
}