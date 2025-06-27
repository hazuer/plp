<?php
session_start();
define( '_VALID_MOS', 1 );

require_once('../system/configuration.php');
require_once('../system/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);

require_once('../system/session_cookies.php');
$id_location = $_SESSION['uLocation'];

$cTelefono = $_POST['cTelefono'] ?? null;
$cNombre   = $_POST['cNombre'] ?? null;

$andTelefono = '';
if (!empty($cTelefono)) {
    $andTelefono = " AND c.phone LIKE '%$cTelefono%'";
}

$andNombre = '';
if (!empty($cNombre)) {
    $andNombre = " AND c.contact_name LIKE '%$cNombre%'";
}

$sql="SELECT 
c.id_contact,
c.id_location,
c.phone,
c.contact_name,
c.id_contact_type,
ct.contact_type,
c.id_contact_status,
CASE 
	WHEN c.id_contact_status = 1 THEN 'Activo' 
	WHEN c.id_contact_status = 2 THEN 'Inactivo' 
	END AS desc_estatus,
c.c_date,
CASE 
	c.id_type_mode WHEN 1 THEN 'Manual'
	WHEN 2 THEN 'Automático'
END AS tipo_modo,
c.id_type_mode 
FROM cat_contact c 
INNER JOIN cat_contact_type ct ON ct.id_contact_type = c.id_contact_type 
WHERE 
c.id_location IN ($id_location) 
$andTelefono 
$andNombre 
ORDER BY c.c_date DESC LIMIT 200";
$contacts = $db->select($sql);
?>
<!doctype html>
<html lang = "en">
	<head>
		<?php include '../views/header.php'; ?>
		<script src="<?php echo BASE_URL;?>/assets/js/contacts.js?version=<?php echo time(); ?>"></script>
	</head>
	<body>
		<div class="main">
			<?php
				include '../views/navTop.php';
			?>
			<h3 id="lbl-title-location">Lista de Contactos <?php echo $desc_loc;?></h3>

			<form id="frm-contacts" action="<?php echo BASE_URL;?>/views/contacts.php" method="POST">

				<div class="row">
					<div class="col-md-2">
						<div class="form-group">
							<label for="cTelefono"><b>Télefono:</b></label>
							<input type="text" class="form-control" name="cTelefono" id="cTelefono" value="<?php echo $cTelefono; ?>" autocomplete="off">
						</div>
					</div>
					<div class="col-md-2">
						<div class="form-group">
							<label for="cNombre"><b>Nombre:</b></label>
							<input type="text" class="form-control" name="cNombre" id="cNombre" value="<?php echo $cNombre; ?>" autocomplete="off">
						</div>
					</div>
					<div class="col-md-1"><br>
						<div class="form-group">
							<button id="btn-filter-contact" type="submit" class="btn btn-success" title="Filtrar" data-dismiss="modal">Filtrar</button>
						</div>
					</div>
					<div class="col-md-1"><br>
						<button id="btn-c-erase" type="button" class="btn btn-default" title="Borrar">Borrar</button>
					</div>

				</div>
			</form>
			<hr>
			<div class="row">
				<div class="col-md-12 row justify-content-end">
					<div class="btn-group" role="group" aria-label="Basic example">
						<button id="btn-add-contact" type="button" class="btn-success btn-sm" title="Nuevo Contacto">
							<i class="fa fa-user-plus" aria-hidden="true"></i>
						</button>
					</div>
				</div>
			</div>
			<table id="tbl-contacts" class="table table-striped table-bordered nowrap table-hover" cellspacing="0" style="width:100%">
				<thead>
					<tr>
						<th>id_contact</th>
						<th>id_location</th>
						<th>phone</th>
						<th>contact_name</th>
						<th>id_contact_type</th>
						<th>contact_type</th>
						<th>c_date</th>
						<th>tipo_modo</th>
						<th>id_type_mode</th>
						<th>id_contact_status</th>
						<th>desc_estatus</th>
						<th>Opciones</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($contacts as $d): ?>
						<tr>
						<td><?php echo $d['id_contact']; ?></td>
						<td><?php echo $d['id_location']; ?></td>
						<td><?php echo $d['phone']; ?></td>
						<td><?php echo $d['contact_name']; ?></td>
						<td><?php echo $d['id_contact_type']; ?></td>
						<td><?php echo $d['contact_type']; ?></td>
						<td><?php echo $d['c_date']; ?></td>
						<td><?php echo $d['tipo_modo']; ?></td>
						<td><?php echo $d['id_type_mode']; ?></td>
						<td><?php echo $d['id_contact_status']; ?></td>
						<td><?php echo $d['desc_estatus']; ?></td>
						<td style="text-align: center;">
							<div class="row">
								<div class="col-md-12">
									<span class="badge badge-pill badge-success" style="cursor: pointer;" id="btn-tbl-edit-contact" title="Editar">
										<i class="fa fa-edit fa-lg" aria-hidden="true"></i>
									</span>
								</div>
							</div>
						</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		include('modal/contact.php');
		include('footer.php');
		?>
	</body>
</html>