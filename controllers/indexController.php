<?php
session_start();
# ini_set('display_errors',1);
# error_reporting(E_ALL);

define( '_VALID_MOS', 1 );

require_once('../system/configuration.php');
require_once('../system/DB.php');

$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);

switch ($_REQUEST['option']) {
	case 'login':
		if(empty($_POST['username']) || empty($_POST['password'])){
			header('Location: '.BASE_URL);
			die();
		}else{
			header('Content-Type: application/json; charset=utf-8');

			$result = ['success'=>'false'];
			try {
			    $u = $_POST['username'];
			    $p = $_POST['password'];
			    $sql ="SELECT * FROM users 
				WHERE 1 
				AND user = '$u' 
				AND password = md5('$p') 
				AND status IN(1)
				LIMIT 1";
				$user = $db->select($sql);
				if(isset($user[0]['id'])) {
					$_SESSION["uId"]       = $user[0]['id'];
					$_SESSION["uName"]     = $u;
					$_SESSION["uLocationDefault"] = $user[0]['id_location_default'];
					$_SESSION["uActive"]   = true;
					$_SESSION["uMarker"]   = 'black';
					$result                = ['success' => 'true'];

					// Duración de la cookie en segundos (8 horas)
					$cookieDuration = 8 * 60 * 60;
					// Iterar sobre $_SESSION y establecer cookies para cada elemento
					foreach ($_SESSION as $key => $value) {
						// Establecer una cookie con el nombre de la variable de sesión y su valor
						setcookie($key, $value, time() + $cookieDuration, "/"); // Caduca en 8 horas

					}
					setcookie('uLocation', $_SESSION["uLocationDefault"], time() + $cookieDuration, "/"); // Caduca en 8 horas
				}

				echo json_encode($result);
				die();
			} catch (Exception $e) {
				echo json_encode( 'Exception caught: ',  $e->getMessage(), "\n");
			}
		}
	break;

	case 'logoff':
		session_unset();
		session_destroy();
		//destro cookies
		setcookie('uId', '', time() - 3600, '/');
		setcookie('uName', '', time() - 3600, '/');
		setcookie('uLocationDefault', '', time() - 3600, '/');
		setcookie('uLocation', '', time() - 3600, '/');
		setcookie('uActive', '', time() - 3600, '/');
		setcookie('uMarker', '', time() - 3600, '/');

		header('Location: '.BASE_URL.'/admin');
		die();
	break;

	default:
		header('Location: '.BASE_URL);
		die();
	break;
}

?>