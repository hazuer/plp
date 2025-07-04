<?php
session_start();
define( '_VALID_MOS', 1 );
date_default_timezone_set('America/Mexico_City');

require_once('../system/configuration.php');
require_once('../system/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);

require_once('../system/session_cookies.php');

$tat  = $_POST['txt-a-guides'] ?? 0;
$lineasTat = explode("\n", $tat);
$titltModule = "";
$packages  = [];
$rowEmpty  = ["id_package"    => "0" ,
	"location_desc" => "NA" ,
	"c_date"        => "" ,
	"registro"      => "" ,
	//"tracking"      => $linea ,
	"phone"         => "" ,
	"folio"         => "" ,
	"marker"        => "black" ,
	"receiver"      => "" ,
	"status_desc"   => "" ,
	"n_date"        => "" ,
	"sms_by_user"   => "" ,
	"t_sms_sent"    => "" ,
	"d_date"        => "" ,
	"user_libera"   => "" ,
	"note"          => "Sin registro" ,
	"t_evidence"    => "0" ,
	"parcel_desc"   => "" ,
	"t_pk_delivery" => "" ,
	"contact_type"  => "" ,
	"tipo_modo"     => "" ,
	"v_date"        => "" ,
	"user_rotulo"   => ""
];

if($tat==0){ //pre load
	$titltModule = "Registros Pre-registrados";
	$sql = "SELECT 
	p.id_package,
	cl.location_desc,
	p.c_date c_date,
	uc.user registro,
	p.tracking,
	cc.phone,
	p.folio,
	p.marker,
	cc.contact_name receiver,
	'Pre-registrado' status_desc,
	'' n_date,
	'' sms_by_user,
	'' t_sms_sent,
	p.d_date,
	'' user_libera,
	'Pendiente por rotular' note,
	'0' t_evidence,
	cp.parcel parcel_desc,
	'' t_pk_delivery,
	cct.contact_type,
	'Automático' tipo_modo,
	p.v_date,
	'' user_rotulo,
	p.address 
	FROM 
		package_tmp p 
	LEFT JOIN cat_contact cc ON cc.id_contact = p.id_contact 
	LEFT JOIN users uc ON uc.id = p.c_user_id 
	LEFT JOIN cat_location cl ON cl.id_location = p.id_location 
	LEFT JOIN cat_parcel cp ON cp.id_cat_parcel = p.id_cat_parcel 
	LEFT JOIN cat_contact_type cct ON cct.id_contact_type = cc.id_contact_type";
	$packages = $db->select($sql);
}else{ // audit report
	$titltModule = "Reporte de Auditoria";
	foreach ($lineasTat as $linea) {
		if (!empty($linea)) {
			$slt = "SELECT 
			id_package,tracking
			FROM package 
			WHERE tracking = '".$linea."'
			LIMIT 1";
			$rstR = $db->select($slt);
			if(count($rstR)==1){
			$sql = "SELECT
				p.id_package,
				cl.location_desc,
				p.c_date c_date,
				uc.user registro,
				p.tracking,
				cc.phone,
				p.folio,
				p.marker,
				cc.contact_name receiver,
				cs.status_desc,
				DATE_FORMAT(p.n_date, '%Y-%m-%d') n_date,
				un.user sms_by_user,
				(SELECT
					count(n.id_notification)
				FROM
					notification n
				WHERE
					n.id_package in(p.id_package)) t_sms_sent,
				p.d_date,
				ud.user user_libera,
				p.note,
				(SELECT
					count(e.id_evidence)
				FROM
					evidence e
				WHERE
					e.id_package IN(p.id_package)) t_evidence,
				cp.parcel parcel_desc,
				(SELECT
					COUNT(pk.id_package)
				FROM
					package pk
				LEFT JOIN cat_contact cpk ON
					cpk.id_contact = pk.id_contact
				WHERE
					cpk.phone = cc.phone
					AND pk.id_status IN (3)) AS t_pk_delivery,
				cct.contact_type,
				CASE
					p.id_type_mode WHEN 1 THEN 'Manual'
					WHEN 2 THEN 'Automático'
				END AS tipo_modo,
				p.v_date,
				uv.user user_rotulo,
				p.address 
			FROM
				package p
			LEFT JOIN cat_contact cc ON cc.id_contact = p.id_contact
			LEFT JOIN cat_status cs ON cs.id_status = p.id_status
			LEFT JOIN users uc ON uc.id = p.c_user_id
			LEFT JOIN cat_location cl ON cl.id_location = p.id_location
			LEFT JOIN users un ON un.id = p.n_user_id
			LEFT JOIN users ud ON ud.id = p.d_user_id
			LEFT JOIN cat_parcel cp ON cp.id_cat_parcel = p.id_cat_parcel
			LEFT JOIN cat_contact_type cct ON cct.id_contact_type = cc.id_contact_type
			LEFT JOIN users uv ON uv.id = p.v_user_id
			WHERE
				1
				AND p.tracking IN('$linea')";
				$rst = $db->select($sql);
				$row = $rst[0];
			}else{
				$rowEmpty["tracking"]=$linea ;
				$row = $rowEmpty;
			}
			array_push($packages,$row);
		}
	}
}
?>
<!doctype html>
<html lang = "en">
	<head>
		<?php include '../views/header.php'; ?>
		<script src="<?php echo BASE_URL;?>/assets/js/reports.js?version=<?php echo time(); ?>"></script>
	</head>
	<body>
		<div class="main">
			<?php
				include '../views/navTop.php';
			?>
			<h3 id="lbl-title-location"><?php echo $titltModule;?></h3>
			<form id="frm-reports" action="<?php echo BASE_URL;?>/views/adminReport.php" method="POST">
				<div class="row">
					<div class="col-md-8">
						<div class="form-group">
							<label for="txt-a-guides"><b>Pega guías (Excel) para auditoría:</b></label>
							<textarea class="form-control" id="txt-a-guides" name="txt-a-guides" rows="4" ></textarea>
						</div>
					</div>
					<div class="col-md-2"><br>
						<div class="form-group">
							<button id="btn-admin-filt" type="submit" class="btn btn-success" title="Filtrar" data-dismiss="modal">Filtrar</button>
						</div>
					</div>
					<div class="col-md-2"><br>
						<div class="form-group">
							<button id="btn-pre" type="button" class="btn btn-primary" title="Pre-registrados" data-dismiss="modal">Pre-registro</button>
						</div>
					</div>
				</div>
			</form>
			<hr>
			</div>
			<div class="col-md-12 px-4">
			<table id="tbl-reports" class="table table-striped table-bordered nowrap table-hover" cellspacing="0" style="width:100%">
				<thead>
					<tr>
						<th>id_package</th>
						<th>location_desc</th>
						<th>parcel_desc
						<th>fecha_registro</th>
						<th>registrado_por</th>
						<th>guia</th>
						<th>folio</th>
						<th>phone</th>
						<th>receiver</th>
						<th>status_desc</th>
						<th>fecha_envio_sms</th>
						<th>sms_enviado_por</th>
						<th>total_sms</th>
						<th>fecha_liberacion</th>
						<th>libero</th>
						<th>note</th>
						<th>evidence</th>
						<th>t_pk_delivery</th>
						<th>contact_type</th>
						<th>tipo_modo</th>
						<th>v_date</th>
						<th>user_rotulo</th>
						<th>address</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($packages as $d):
						$folioColor = "<span style='font-weight: bold; color:".$d['marker']."'>".$d['folio']."</span>";
						?>
						<tr>
						<td title="Ver Historial" id="id-logger" style="cursor: pointer; text-decoration: underline;"><?php echo $d['id_package']; ?></td>
						<td><?php echo $d['location_desc']; ?></td>
						<td><?php echo $d['parcel_desc']; ?></td>
						<td><?php echo $d['c_date']; ?></td>
						<td><?php echo $d['registro']; ?></td>
						<td><?php echo $d['tracking']; ?></td>
						<td><?php echo $folioColor; ?></td>
						<td><?php echo $d['contact_type']; ?></td>
						<td><?php echo $d['phone']; ?></td>
						<td><?php echo $d['receiver']; ?></td>
						<td><?php echo $d['status_desc']; ?></td>
						<td><?php echo $d['n_date']; ?></td>
						<td><?php echo $d['sms_by_user']; ?></td>
						<td>
							<?php if($d['t_sms_sent']==0){ echo "0";}else{ ?>
								<span class="badge badge-pill badge-info" style="cursor: pointer;" id="btn-details" title="Ver"><?php echo $d['t_sms_sent']; ?></span>
							<?php
							} ?>
						</td>
						<td><?php echo $d['d_date']; ?></td>
						<td><?php echo $d['user_libera']; ?></td>
						<td><?php echo $d['note']; ?></td>
						<td>
							<?php if($d['t_evidence']!=0){ ?>
								<span class="badge badge-pill badge-warning" style="cursor: pointer;" id="btn-evidence" title="Evidencia(s)">
									<?php echo $d['t_evidence']; ?> <i class="fa fa-file-image-o fa-lg" aria-hidden="true"></i>
								</span>
							<?php
							} ?>
						</td>
						<td><?php echo $d['t_pk_delivery']; ?></td>
						<td><?php echo $d['tipo_modo']; ?></td>
						<td><?php echo $d['v_date']; ?></td>
						<td><?php echo $d['user_rotulo']; ?></td>
						<td><?php echo $d['address']; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		include('modal/sms-report.php');
		include('modal/logger.php');
		include('modal/evidence.php');
		include('footer.php');
		?>
	</body>
</html>