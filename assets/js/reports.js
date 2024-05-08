$(document).ready(function() {
	let baseController = 'controllers/packageController.php';

  	let table = $('#tbl-reports').DataTable({
		"bPaginate": true,
		"lengthMenu": [[10, 50, 100, -1], [10, 50, 100, "All"]], // Definir las opciones de longitud del menú
        "pageLength": 500, // Establecer el número de registros por página predeterminado
        "bInfo" : true,
		scrollCollapse: true,
		scroller: true,
		scrollY: 450,
		scrollX: true,
		dom: 'Bfrtip',
		buttons: [
			'excel'
		],
		"columns" : [
			{title: `Id`,               name:`id_package`,       data:`id_package`},      //0
			{title: `Ubicación`,        name:`location_desc`,    data:`location_desc`},   //1
			{title: `Fecha Registro`,   name:`fecha_registro`,   data:`fecha_registro`},  //2
			{title: `Registró`,         name:`registrado_por`,   data:`registrado_por`},  //3
			{title: `Guía`,             name:`guia`,             data:`guia`},            //4
			{title: `Folio`,            name:`folio`,            data:`folio`},           //5
			{title: `Télefono`,         name:`phone`,            data:`phone`},           //6
			{title: `Destinatario`,     name:`receiver`,         data:`receiver`},        //7
			{title: `Estatus`,          name:`status_desc`,      data:`status_desc`},     //8
			{title: `Fecha Mensaje`,    name:`fecha_envio_sms`,  data:`fecha_envio_sms`}, //9
			{title: `Envió Mensaje`,    name:`sms_enviado_por`,  data:`sms_enviado_por`}, //10
			{title: `Total Mensaje`,    name:`total_sms`,        data:`total_sms`},       //11
			{title: `Fecha Entrega`,    name:`fecha_liberacion`, data:`fecha_liberacion`},//12
			{title: `Entregó`,          name:`libero`,           data:`libero`},          //13
			{title: `Nota`,             name:`note`,             data:`note`}             //14+ 1 last
		],
        'order': [[12, 'desc']]
	});

	//funcion para borrar campo de busqueda
	let clearButton = $(`<span id="clear-search" style="cursor: pointer;">&nbsp;<i class="fa fa-eraser fa-lg" aria-hidden="true"></i></span>`);
	clearButton.click(function() {
		$("#tbl-reports_filter input[type='search']").val("");
		setTimeout(function() {
			$("#tbl-reports_filter input[type='search']").trigger('mouseup').focus();
		}, 100);
	});
	$("#tbl-reports_filter label").append(clearButton);

	$(`#tbl-reports tbody`).on( `click`, `#btn-details`, function () {
		let row = table.row( $(this).closest('tr') ).data();
		loadSmsDetail(row.id_package);
	});

	async function loadSmsDetail(id_package) {
		let listSms = await getRecordsSms(id_package);
		createTableSmsSent(listSms);

		$('#modal-sms-report').modal({backdrop: 'static', keyboard: false}, 'show');
	}

	async function getRecordsSms(id_package) {
		let list = [];
		let formData =  new FormData();
		formData.append('id_package', id_package);
		formData.append('option','getRecordsSms');
		try {
			const response = await $.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false
			});
			if(response.success=='true'){
				list = response;
			}
		} catch (error) {
			console.error(error);
		}
		return list;
	}

	function createTableSmsSent(data) {
		$('#tbl-sms-sent').empty();
		let c=1;
		let phoneTitle='';
		$.each(data.dataJson, function(index, item) {
			phoneTitle = item.phone;
			let row = `<tr>
				<td><b>${c}</b></td>
				<td>${item.n_date}</td>
				<td>${item.phone}</td>
				<td>${item.contact_name}</td>
				<td>${item.user}</td>
				<td>${item.message}</td>
			</tr>`;
			$('#tbl-sms-sent').append(row);
			c++;
		});
		$('#modal-sms-report-title').html(`Mensajes Enviados ${phoneTitle}`);
	}

	$('#btn-f-erase').click(function(){
		$('#rFstatus').val(99);
		$('#rFIni').val('');
		$('#rFFin').val('');
		$('#rGuia').val('');
		$('#rFolio').val('');
		$('#rTelefono').val('');
		$('#rFIniLib').val('');
		$('#rFFinLib').val('');
		$('#rGuia').focus();
	});

	$('#rTelefono').on('input', function() {
		let input = $(this).val();
		input = input.replace(/\D/g, '').slice(0, 10); // Elimina caracteres no numéricos y limita a 10 dígitos
		$(this).val(input);
	});

	$('#rFolio').on('input', function() {
        let input = $(this).val();
        input = input.replace(/\D/g, '').slice(0, 5); // Elimina caracteres no numéricos y limita a 10 dígitos
        $(this).val(input);
    });
});