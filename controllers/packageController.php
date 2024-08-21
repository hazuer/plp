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
		$newOrCurrent = ($type=='new')? 1: 0;
		$sqlMax="SELECT MAX(folio) + $newOrCurrent AS nuevo_folio FROM folio WHERE id_location IN ($id_location)";
		$records = $db->select($sqlMax);
		$folio = $records[0]['nuevo_folio'];
		$upQr['folio']  = $folio;
		$db->update('folio',$upQr," `id_location` = $id_location");
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
		$data['id_status']   = $_POST['id_status'];
		$data['note']        = $_POST['note'];
		$id_contact          = $_POST['id_contact'];

		$action              = $_POST['action'];
		try {
			if($id_contact==0){ //new case when the full number was entered and the user was not selected
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
			$data['id_contact']  = $id_contact;

			switch ($action) {
				case 'update':
					if($data['id_status'] == 4 ){
						$data['d_date']     = date("Y-m-d H:i:s");
						$data['d_user_id']  = $_SESSION["uId"];
					}
					$id       = $_POST['id_package'];
					$success  = 'true';
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
						$sqlCheck = "SELECT COUNT(tracking) total FROM package WHERE tracking IN ('".$data['tracking']."')";
						$rstCheck = $db->select($sqlCheck);
						$total = $rstCheck[0]['total'];
						if($total==0){
							$data['marker']      = $_POST['id_marcador'];
							$_SESSION["uMarker"] = $data['marker'];
							setcookie('uMarker', $data['marker'], time() + 3600, '/');

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
							$db->insert('package',$data);

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
		try {
			$success  = 'true';
			$dataJson = $db->update('folio',$data," `id_location` = $id_location");
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

	case 'getContact':
		$result   = [];
		$success  = 'false';
		$dataJson = [];
		$message  = 'Error al obtener la información de contactos';

		$phone       = $_POST['phone'];
		$id_location = $_POST['id_location'];
		try {
			$success  = 'true';
			$sqlContact = "SELECT id_contact,contact_name,phone FROM cat_contact WHERE phone LIKE '%$phone%' AND id_location IN($id_location) AND id_contact_status IN(1) ORDER BY contact_name ASC LIMIT 10";
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
			n.message 
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
		case 3:
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
			GROUP BY cc.phone`
			const data = await db.processDBQueryUsingPool(sql)
			const rst = JSON.parse(JSON.stringify(data))
			ids = rst[0] ? rst[0].ids : 0;
			let idContactType = rst[0] ? rst[0].id_contact_type : 0;
			let folioGuias = rst[0] ? rst[0].folioGuias : 0;
			let fullMessage = `${iconBot} ${message}`;
			if(ids!=0){
				fullMessage = `${iconBot} ${message} \n*(Folio)-Guía:*\n${folioGuias}`;
			}
	
			let sid ="";
			let newStatusPackage = 1;
			let id_contact_type = 3;
			try {
				if(idContactType==2){ //WhatsApp
					const chatId = "521"+number+ "@c.us";
					await client.sendMessage(chatId, fullMessage);
					sid =`Mensaje enviado con éxito a, ${number} WhatsApp`
					newStatusPackage = 2
					if(id_estatus==5){
						newStatusPackage=5;
					}
					id_contact_type=2;
				}else{
					const number_details = await client.getNumberId(number); // get mobile number details
					if (number_details) {
						await client.sendMessage(number_details._serialized, fullMessage); // send message
						sid =`Mensaje enviado con éxito a, ${number}`
						newStatusPackage = 2
						if(id_estatus==5){
							newStatusPackage=5;
						}
						if(ids!=0){
							const sqlUpdateTypeContact = `UPDATE cat_contact 
							SET id_contact_type=2 
							WHERE id_location=${id_location} AND phone=\'${number}\' AND id_contact_type=1`
							await db.processDBQueryUsingPool(sqlUpdateTypeContact)
						}
					} else {
						sid = `${number}, Número de móvil no registrado`
						newStatusPackage = 6
					}
					if (i < numbers.length - 1) {
						await sleep(2000); // tiempo de espera en segundos entre cada envío
					}
				}
			} catch (error) {
				sid =`Ocurrió un error al procesar el número, ${number}`
				newStatusPackage = 6
			}
			console.log(`${i + 1} - ${sid}`);
			if(ids!=0){
				const listIds = ids.split(",");
				const nDate = moment().tz("America/Mexico_City").format("YYYY-MM-DD HH:mm:ss");
				for (let i = 0; i < listIds.length; i++) {
					const id_package = listIds[i];
					const sqlSaveNotification = `INSERT INTO notification 
					(id_location,n_date,n_user_id,message,id_contact_type,sid,id_package) 
					VALUES 
					(${id_location},\'${nDate}\',${n_user_id},\'${message} \n*(Folio)-Guía:*\n${folioGuias}\',${id_contact_type},\'${sid}\',${id_package})`
					await db.processDBQueryUsingPool(sqlSaveNotification)
	
					const sqlUpdatePackage = `UPDATE package SET 
					n_date = \'${nDate}\', n_user_id = \'${n_user_id}\', id_status=${newStatusPackage} 
					WHERE id_package IN (${id_package})`
					await db.processDBQueryUsingPool(sqlUpdatePackage)
				}
			}
		}
		console.log("Proceso finalizado...");
	  } else {
		console.log("Proceso de envío de mensajes cancelado");
	  }
	  rl.close();
	});

});
client.initialize();
function sleep(ms) {
	return new Promise(resolve => setTimeout(resolve, ms));
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
		$idsx    = $_POST['idsx'];
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
			$sql = "SELECT 
			p.id_package,
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
			AND p.id_status IN(1,2,6,7)";
			$packages = $db->select($sql);
			foreach($packages as $d){
				$waybillNo   = $d['tracking'];
				$phoneVerify = $d['last_four_digits'];
				$phone       = $d['phone'];
				$receiver    = $d['receiver'];
				$folio       = $d['folio'];

				$url = "https://official.jtjms-mx.com/official/logisticsTracking/v3/getDetailByWaybillNo?waybillNo=".$waybillNo."&langType=ES&phoneVerify=".$phoneVerify;
				#$url ="https://official.jtjms-mx.com/official/logisticsTracking/v3/getDetailByWaybillNo?waybillNo=JMX300064944499&langType=ES&phoneVerify=2464";

				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($curl);

				$commonValues = [
					'guia'     => $waybillNo,
					'phone'    => $phone,
					'receiver' => $receiver,
					'folio'    => $folio
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
								'desc_status' => 'Liberado en J&T pero no en el sistema interno'
							],$commonValues);
						} elseif ($lastStatus === '派件中') {
							$arrayRst[$waybillNo] = array_merge([
								'status'      => 'Ok',
								'desc_status' => 'Entrega en curso'
							],$commonValues);
						} else {
							$arrayRst[$waybillNo] = array_merge([
								'status'      => 'Verificar',
								'desc_status' => 'El estatus del paquete no pudo ser determinado'
							],$commonValues);
						}
					} else {
						$arrayRst[$waybillNo] = array_merge([
							'status'      => 'Verificar',
							'desc_status' => 'Sin detalles'
						],$commonValues);
					}
				}

				curl_close($curl);
			}
			$result = [
				'success'      => 'true',
				'trackingList' => $arrayRst,
				'message'      => 'ok'
			];
			echo json_encode($result);
		break;

		case 'ocurre':
			if(isset($_SESSION['uLocation'])){
				$_SESSION['uLocation'] = $_SESSION['uLocation'];
			}else{
				$_SESSION['uLocation'] = $_SESSION['uLocationDefault'];
			}
			$id_location = $_SESSION['uLocation'];
			$typeLocation='tlaqui';
			if($id_location==2){$typeLocation='zaca';}

			$type_mode = $_POST['type_mode'];
			$nameTypeMode='ocurre';
			$listEstatus='1, 2, 4, 6, 7'; //except entregado y confirmado
			if($type_mode=='auto'){
				$nameTypeMode='auto_servicio';
				$listEstatus='1, 2, 3, 4, 5, 6, 7'; // al estatus
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
			WHERE
				1
				AND p.id_location IN ($id_location)
				AND p.id_status IN ($listEstatus)
				AND p.c_date BETWEEN '".date('Y-m-d')." 00:00:00' AND '".date('Y-m-d')." 23:59:59'
			ORDER BY p.id_package DESC";
			$codigos = $db->select($sql);

			$archivos = array();
			// Iterar sobre cada código y generar el código de barras correspondiente
			$c=1;
			foreach ($codigos as $data) {
				$codigo= $data['tracking'];
				// Nombre del archivo para guardar el código de barras
				$nombreImagen = $c.'_'.$codigo . '.png';

				// Llamar a la función barcode() para generar el código de barras con un tamaño más grande
				barcode($nombreImagen, $codigo, 80, 'horizontal', 'code128', true, 1);

			   # aca agegar el nombreImagen al array archivos
			   array_push($archivos,$nombreImagen);
			   $c++;
			}

			$nameOcurre= $nameTypeMode.'_'.$typeLocation.'_'.date('Y-m-d');
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

}