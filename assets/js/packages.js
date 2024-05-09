$(document).ready(function() {
	let baseController = 'controllers/packageController.php';

	let idLocationSelected = $('#option-location');
	let id_location        = $('#id_location');
	let id_package         = $('#id_package');
	let folio              = $('#folio');
	let action             = $('#action');
	let c_date             = $('#c_date');
	let phone              = $('#phone');
	let receiver           = $('#receiver');
	let tracking           = $('#tracking');
	let id_status          = $('#id_status');
	let divStatus          = $('#div-status');

  	let table = $('#tbl-packages').DataTable({
		"bPaginate": true,
        "lengthMenu": [[10, 50, 100, -1], [10, 50, 100, "All"]], // Definir las opciones de longitud del menú
        "pageLength": 500, // Establecer el número de registros por página predeterminado
		//"bFilter": false,
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
			{title: `id_package`,   name : `id_package`,   data : `id_package`},  //0
			{title: `Guía`,         name : `tracking`,     data : `tracking`},    //1
			{title: `Télefono`,     name : `phone`,        data : `phone`},       //2
			{title: `id_location`,  name : `id_location`,  data : `id_location`}, //3
			{title: `Fecha Registro`,        name : `c_date`,       data : `c_date`},      //4
			{title: `Folio`,        name : `folio`,        data : `folio`},       //5
			{title: `Destinatario`, name : `receiver`,     data : `receiver`},    //6
			{title: `id_status`,    name : `id_status`,    data : `id_status`},   //7
			{title: `Estatus`,      name : `status_desc`,  data : `status_desc`}, //8
			{title: `note`,         name : `note`,         data : `note`},        //9
			{title: `id_contact`,   name : `id_contact`,   data : `id_contact`}   //10 + 1 last
		],
		"columnDefs": [
			{"orderable": false,'targets': 0,'checkboxes': {'selectRow': true}},
			{ "targets": [0,3,7,9,10], "visible"   : false, "searchable": false, "orderable": false},
			{ "orderable": false,"targets": 11 }, // last
			// { "width": "40%", "targets": [1,2] }
		],
		'select': {
			'style': 'multi'
		},
		'order': [[4, 'desc']]
	});

	//funcion para borrar campo de busqueda
    let clearButton = $(`<span id="clear-search" style="cursor: pointer;">&nbsp;<i class="fa fa-eraser fa-lg" aria-hidden="true"></i></span>`);
	clearButton.click(function() {
		$("#tbl-packages_filter input[type='search']").val("");
		setTimeout(function() {
			$("#tbl-packages_filter input[type='search']").trigger('mouseup').focus();
		}, 100);
	});
    $("#tbl-packages_filter label").append(clearButton);

	$("#btn-first-package, #btn-add-package").click(function(e){
		let fechaFormateada = getCurrentDate();
		let row = {
			id_package : 0,
			phone      : '',
			id_location: idLocationSelected.val(),
			c_date     : fechaFormateada,
			id_status  : 1,
			tracking   : '',
			id_status  : 1,
			note       : '',
			id_contact : 0,
		}
		loadPackageForm(row);
	});

	function getCurrentDate(){
		let fechaActual = new Date();
		// Obteniendo cada parte de la fecha y hora
		let year     = fechaActual.getFullYear();
		let mes      = String(fechaActual.getMonth() + 1).padStart(2, '0'); // Agrega un cero al mes si es menor que 10
		let dia      = String(fechaActual.getDate()).padStart(2, '0'); // Agrega un cero al día si es menor que 10
		let horas    = String(fechaActual.getHours()).padStart(2, '0'); // Agrega un cero a las horas si es menor que 10
		let minutos  = String(fechaActual.getMinutes()).padStart(2, '0'); // Agrega un cero a los minutos si es menor que 10
		let segundos = String(fechaActual.getSeconds()).padStart(2, '0'); // Agrega un cero a los segundos si es menor que 10
		// Formateando la fecha en el formato deseado
		let dtCurrent = `${year}-${mes}-${dia} ${horas}:${minutos}:${segundos}`;
		return dtCurrent;
	}

	$(`#tbl-packages tbody`).on( `click`, `#btn-records`, function () {
		let row = table.row( $(this).closest('tr') ).data();
		loadPackageForm(row);
	});

	$(`#tbl-packages tbody`).on( `click`, `#btn-tbl-liberar`, function () {
		let  listPackageRelease=[];
		let row = table.row( $(this).closest('tr') ).data();
			swal({
			title: `Folio:${row.folio} - ${row.receiver}`,
			text: `Desea liberar la guía ${row.tracking}?`,
			icon: "info",
			buttons: true,
			dangerMode: false,
		})
		.then((weContinue) => {
		  if (weContinue) {
			let guia = row.tracking;
			listPackageRelease.push(`'${guia}'`);

			let formData = new FormData();
			formData.append('id_location',idLocationSelected.val());
			formData.append('tracking',guia);
			formData.append('listPackageRelease', JSON.stringify(listPackageRelease));
			formData.append('option','releasePackage');
			$.ajax({
				url: `${base_url}/${baseController}`,
				type       : 'POST',
				data       : formData,
				cache      : false,
				contentType: false,
				processData: false,
		})
		.done(function(response) {
			if(response.success==='true'){
				swal(guia, response.message, "success");
			}else {
				swal(guia, response.message, "warning");
			}
			$('.swal-button-container').hide();
			setTimeout(function(){
				swal.close();
				window.location.reload();
			}, 3500);

		}).fail(function(e) {
			console.log("Opps algo salio mal",e);
		});
		  } else {
			return false;
		  }
		});
	});

	async function loadPackageForm(row){
		let titleModal = '';
		$('#form-modal-package')[0].reset();
		divStatus.hide();

		id_package.val(row.id_package);
		$('#id_contact').val(row.id_contact);
		phone.val(row.phone);
		id_location.val(row.id_location);
		c_date.val(row.c_date);
		receiver.val(row.receiver);
		tracking.val(row.tracking);
		id_status.val(row.id_status);
		$('#note').val(row.note);
		action.val('new');
		$('#btn-erase').show();
		$('#phone').prop('disabled', false);
		$('#receiver').prop('disabled', false);
		$('#tracking').prop('disabled', false);

		if(row.id_package!=0){
			$('#div-keep-modal').hide();
			divStatus.show();
			folio.val(row.folio);
			titleModal=`Editar Paquete ${row.folio}`;
			action.val('update');
			$('#tracking').prop('disabled', true);

			if(row.id_status!=1){
				$('#phone').prop('disabled', true);
				$('#receiver').prop('disabled', true);
			}
			$('#btn-erase').hide();
		}else{
			updateColors(uMarker);
			$('#opcMA').prop('checked', true);
			$('#div-keep-modal').show();
			let newFolio = await getFolio('new');
			folio.val(newFolio);
			titleModal = `Nuevo Paquete ${newFolio}`;
		}

		$('#modal-package-title').html(titleModal);
		$('#modal-package').modal({backdrop: 'static', keyboard: false}, 'show');
		setTimeout(function(){
			phone.focus();
		}, 600);
	}

	async function getFolio(type) {
		let folio    = 0;
		let formData =  new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('type', type);
		formData.append('option', 'getFolio');
		try {
			const response = await $.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false
			});
			folio = response.folio;
		} catch (error) {
			console.error(error);
		}
		return folio;
	}

	$('#btn-save').click(function(){
		savePackage();
	});


	$('#btn-erase').click(function(){
		$('#id_contact').val(0);
		$('#phone').val('');
		$('#receiver').val('');
		$('#tracking').val('');
		$('#phone').focus();
	});

	//-----------------------
	$('#tracking').on('input', function() {
		let input = $(this).val().trim(); // Eliminar espacios en blanco al inicio y al final
		if (input.length === 15 && input.substr(0, 3).toUpperCase() === "JMX") {
			$('#btn-save').click();
		}
	});

	function savePackage() {
		let decodedText = $('#tracking').val();

		if(phone.val()=='' || receiver.val()=='' || tracking.val()==''){
			swal("Atención!", "* Campos requeridos", "error");
			return;
		}

		let p = phone.val().trim(); // Eliminar espacios en blanco al inicio y al final
		if (p.length!=10){
			swal("Atención!", "* El número de télefono no es válido", "error");
			return;
		}

		let t = tracking.val().trim(); // Eliminar espacios en blanco al inicio y al final

		let regex = /^JMX\d{12}$/;
		if (t.length !== 15 || !regex.test(t.toUpperCase())) {
			let mensajeError = "* Código de barras no válido:";
			if (t.length !== 15) {
				mensajeError += " Debe tener 15 caracteres";
			} else {
				mensajeError += " Formato no válido";
			}
			swal("Atención!", mensajeError, "error");
			return;
		}

		let guia = decodedText.substring(0, 3).toUpperCase() + decodedText.substring(3);

		let formData = new FormData();
		formData.append('id_package',id_package.val());
		formData.append('id_location',idLocationSelected.val());
		formData.append('folio',folio.val());
		formData.append('c_date',c_date.val());
		formData.append('phone',phone.val());
		formData.append('receiver',receiver.val());
		formData.append('id_contact',$('#id_contact').val());
		formData.append('tracking',guia);
		formData.append('id_status',id_status.val());
		formData.append('id_marcador',$('#id_marcador').val());
		formData.append('action',action.val());
		formData.append('option','savePackage');
		formData.append('note',$('#note').val());

		$.ajax({
			url        : `${base_url}/${baseController}`,
			type       : 'POST',
			data       : formData,
			cache      : false,
			contentType: false,
			processData: false,
		})
		.done(function(response) {

			if(response.success=='true'){
				uMarker = $('#id_marcador').val();
				let timex = 1500;
				if(response.message=='Paquete listo para Agrupar'){
					swal(`${response.message}`, `${response.dataJson}`, "success");
					timex = 4000;
				}else{
					swal(`${response.message}`, "", "success");
				}
				$('.swal-button-container').hide();
				$('#modal-package').modal('hide');

				if(action.val()=="update"){
					setTimeout(function(){
						swal.close();
						window.location.reload();
					}, 1500);
					return;
				}

				if(action.val()=="new"){
					if ($('#opcMA').prop('checked')) {
						setTimeout(function(){
							swal.close();
							setTimeout(function(){
								$('#btn-add-package').click();
								setTimeout(function(){
									phone.focus();
								}, 100);
							}, 300);
						}, timex);
						return;
					} else{
						let timez = 1500;
						if(response.message=='Paquete listo para Agrupar'){timez = 4000;}
						setTimeout(function(){
							swal.close();
							window.location.reload();
						}, timez);
						return;
					}
				}
			}
			if(response.success=='false'){
				swal("Atención!", `${response.message}`, "info");
				$('.swal-button-container').hide();
				setTimeout(function(){
					swal.close();
				}, 3500);
				return;
			}
		}).fail(function(e) {
			console.log("Opps algo salio mal",e);
		});
	}

	phone.on('input', function() {
        let phoneNumber = $(this).val();
		let id_location = idLocationSelected.val();
        let coincidenciasDiv = $('#coincidencias');

        input = phoneNumber.replace(/\D/g, '').slice(0, 10); // Elimina caracteres no numéricos y limita a 10 dígitos
        $(this).val(input);
        if (input.length === 10) {
			receiver.focus();
        }

		if (input.length <= 4) {
			return;
        }

        $.ajax({
            url: `${base_url}/${baseController}`, // URL ficticia de la API
            method: 'POST',
            data: { phone: phoneNumber,id_location:id_location,option:'getContact' },
            success: function(data) {
                let coincidencias = data.dataJson; // Supongamos que la respuesta contiene una lista de coincidencias
                // Limpiar el contenido del div de coincidencias
                coincidenciasDiv.empty();
				$('#id_contact').val(0);
				$('#receiver').val('');
				if (phoneNumber.length==10){
					coincidenciasDiv.hide();
					return;
				}
                // Mostrar el div de coincidencias si hay coincidencias
                if (phoneNumber.length > 0 && coincidencias.length > 0) {
                    coincidenciasDiv.show();
					let coincidenciasArray = Object.values(coincidencias);

                    // Agregar cada coincidencia como un elemento <p> al div
                    coincidenciasArray.forEach(function(coincidencia) {
						coincidenciasDiv.append(`<p data-phone="${coincidencia.phone}" data-name="${coincidencia.contact_name}" data-idcontact="${coincidencia.id_contact}">${coincidencia.phone} - ${coincidencia.contact_name}</p>`);
                    });
                } else {
                    coincidenciasDiv.hide();
                }
            },
            error: function(xhr, status, error) {
                console.error(error); // Manejo de errores
            }
        });
    });

	// Manejar la selección de una coincidencia
	$('#coincidencias').on('click', 'p', function() {
		let name        = $(this).data('name');
		let phoneNumber = $(this).data('phone');
		let id_contact = $(this).data('idcontact');
		$('#receiver').val(name);
		$('#phone').val(phoneNumber);
		$('#id_contact').val(id_contact);
		$('#coincidencias').hide();
		$('#tracking').focus();
	});


	$('#mfNumFolio').on('input', function() {
        let input = $(this).val();
        input = input.replace(/\D/g, '').slice(0, 5); // Elimina caracteres no numéricos y limita a 10 dígitos
        $(this).val(input);
    });

// ----------------------------------------------------

	$('#btn-folio').click(function(){
		loadModalFolio();
	});

	$('#mfModo').on('change', function() {
		let id_mode = $('#mfModo').val();
		if(id_mode==1){
			$('#mfNumFolio').val(0);
			$('#mfNumFolio').prop('disabled', true);
		}else{
			$('#mfNumFolio').val('');
			$('#mfNumFolio').prop('disabled', false);
			setTimeout(function(){
				$('#mfNumFolio').focus();
			}, 250);
		}
	});

	async function loadModalFolio() {
		let foliActual= await getFolio('current');
		$('#mfFolioActual').val(foliActual);
		$('#mfIdLocation').val(idLocationSelected.val());
		$('#mfModo').val(1);
		$('#mfNumFolio').val(0);
		$('#mfNumFolio').prop('disabled', true);
		$('#modal-folio-title').html('Control de Folios');
		$('#modal-folio').modal({backdrop: 'static', keyboard: false}, 'show');
	}

	$(`#btn-save-folio`).click(function(){
		if($('#mfNumFolio').val()==''){
			swal("Atención!", "* Campos requeridos", "error");
			return;
		}

		let formData =  new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('mfNumFolio', $('#mfNumFolio').val());
		formData.append('option', 'saveFolio');
		try {
			$.ajax({
				url        : `${base_url}/${baseController}`,
				type       : 'POST',
				data       : formData,
				cache      : false,
				contentType: false,
				processData: false,
			})
			.done(function(response) {
				if(response.success=='true'){
					swal(`${response.message}`, "", "success");
					$('.swal-button-container').hide();
					$('#modal-folio').modal('hide');
					setTimeout(function(){
						swal.close();
						window.location.reload();
					}, 1500);
				}
			}).fail(function(e) {
				console.log("Opps algo salio mal",e);
			});
		} catch (error) {
			console.error(error);
		}
	});

// ----------------------------------------------------
	$('#mCPhone').on('input', function() {
		let input = $(this).val();
		input = input.replace(/\D/g, '').slice(0, 10); // Elimina caracteres no numéricos y limita a 10 dígitos
		$(this).val(input);

		if (input.length === 10) {
			$('#mCName').focus();
		}
	});

	$('#btn-send-messages').click(function(){
		selectMessages();
	});

	async function selectMessages() {
		let listPackage = await getPackageNewSms();
		let tmsj = listPackage.dataJson;
		if(tmsj.length==0){
			swal("Estás al día!", "No hay mensajes para enviar", "success");
			$('.swal-button-container').hide();
			$('#btn-save-messages').hide();
			setTimeout(function(){
				swal.close();
				$('#modal-messages').modal('hide');
			}, 2500);
			return;
		}

		generateTable(listPackage);

		$('#mMIdLocation').val(idLocationSelected.val());
		$('#mMContactType').val(1);
		$('#mMEstatus').val(1);
		let msj=`${templateMsj}`;
		$('#mMMessage').val(msj);
		$('#modal-messages-title').html('Envío de Mensajes');
		$('#modal-messages').modal({backdrop: 'static', keyboard: false}, 'show');
		setTimeout(function(){
			$('#mMMessage').focus();
		}, 600);
	}

	async function getPackageNewSms() {
		let list = [];
		let formData =  new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('IdContactType', $('#mMContactType').val());
		formData.append('idStatus', $('#mMEstatus').val());
		formData.append('option','getPackageNewSms');
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

	// Función para procesar el JSON y generar filas de tabla
	function generateTable(data) {
		// Limpiar la tabla
		$('#tbl-list-package-sms').empty();
		$('#btn-save-messages').show();

		// Iterar sobre los datos del JSON y generar filas de tabla
		let c=1;
		$.each(data.dataJson, function(index, item) {
			let row = `<tr>
				<td><b>${c}</b></td>
				<td>${item.phone}</td>
				<td>${item.main_name}</td>
				<td>${item.folios}</td>
				<td>${item.total_p}</td>
				<td style="text-align:center">
				<span class="badge badge-pill badge-info btn-idx" title="Ver" style="cursor: pointer;" data-phone="${item.phone}" data-name="${item.main_name}" data-trackings="${item.trackings}" data-ids="${item.ids}"><i class="fa fa-eye fa-lg" aria-hidden="true"></i></span>
				</td>
			</tr>`;
			$('#tbl-list-package-sms').append(row);
			c++;
		});

		$('#tbl-list-package-sms').on('click', '.btn-idx', function() {
			let name = $(this).data('name');
			let trackings = $(this).data('trackings');
			swal(`${name}`,trackings, "success");
			$('.swal-button-container').hide();
		});
	}

	$('#btn-save-messages').click(function(){
		swal({
				title: "Enviar Mensajes",
				text: "Está seguro?",
				icon: "info",
				buttons: true,
				dangerMode: false,
			})
			.then((weContinue) => {
			  if (weContinue) {
				enviarNotificaciones();
			  } else {
				return false;
			  }
			});
	});


async function enviarNotificaciones() {
	// Array para almacenar los ids de las filas seleccionadas
	let arrayNotification = [];
	// Iterar sobre las filas de la tabla
	$('#tbl-list-package-sms tr').each(function(index, row) {
		// Obtener el id de la fila actual
		let phonex = $(row).find('.btn-idx').data('phone');
		let idsx = $(row).find('.btn-idx').data('ids');
		let trackingsx = $(row).find('.btn-idx').data('trackings');
		arrayNotification.push({phone:phonex,
			ids:idsx,
			lstrakings:trackingsx
		});
	});

	let sentCount = 0;
    const totalNotifications = arrayNotification.length;
    swal({
        title: `Enviando mensajes 1 de ${totalNotifications}`,
        text: 'Procesando, espere por favor ...',
        icon: 'info',
        buttons: false
    });

    for (let i = 0; i < totalNotifications; i++) {
        const item = arrayNotification[i];
		let txt = $('#mMMessage').val();
		let txtguias= `${txt} Guías listas para recoger: ${item.lstrakings}`;

		let formData = new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('idContactType', $('#mCContactType').val());
		formData.append('message', txtguias);
		formData.append('ids',item.ids);
		formData.append('phone',item.phone);
		formData.append('option', 'sendMessages');
		try {
			const response = await $.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				contentType: false,
				processData: false,
			});
			if(response.success==='true'){
				sentCount++;
                swal({
                    title: `Enviando mensajes ${sentCount} de ${totalNotifications}`,
					text: 'Procesando, espere por favor ...',
                    icon: 'info',
                    buttons: false
                });

				if (sentCount === totalNotifications) {
					$('#modal-messages').modal('hide');
					swal({
						title: 'Se han enviado todos los mensajes',
						text: 'Operación finalizada',
						icon: 'success',
						buttons: false
					});
					setTimeout(function(){
						swal.close();
						window.location.reload();
					}, 5500);
				}
			}
		} catch (error) {
			console.log("Opps algo salio mal",error);

		}
    }
}

	//------------------------------------------ release
	let  listPackageRelease=[];

	$('#btn-release-package').click(function(){
		listPackage = [];
		$('#form-modal-release-package')[0].reset();
		$('#mrp-id_location').val(idLocationSelected.val());
		let fechaFormateada = getCurrentDate();
		$('#mrp-date-release').val(fechaFormateada);
		$('#tablaPaquetes').hide();

		$('#modal-release-package-title').html('Entrega de Paquetes');
		$('#modal-release-package').modal({backdrop: 'static', keyboard: false}, 'show');
		setTimeout(function(){
			$('#mrp-tracking').focus();
		}, 600);
	});

	/*$('#btn-mrp-scan').click(function(){
		loadReaderScan()
	});*/

	$('#close-mrp-x,#close-mrp-b').click(function(){
		window.location.reload();
	});

	$('#btn-mrp-save').click(function(){
		saveAndReleasePakage();
	});

	function saveAndReleasePakage(){
		try {
			let tracking = $('#mrp-tracking').val();
			let t = $('#mrp-tracking').val().trim(); // Eliminar espacios en blanco al inicio y al final

			let regex = /^JMX\d{12}$/;
			if (t.length !== 15 || !regex.test(t.toUpperCase())) {
				let mensajeError = "* Código de barras no válido:";
				if (t.length !== 15) {
					mensajeError += " Debe tener 15 caracteres";
				} else {
					mensajeError += " Formato no válido";
				}
				swal("Atención!", mensajeError, "error");
				return;
			}

			let guia = tracking.substring(0, 3).toUpperCase() + tracking.substring(3);
			listPackageRelease.push(`'${guia}'`);


			let formData = new FormData();
			formData.append('id_location',idLocationSelected.val());
			formData.append('tracking',guia);
			formData.append('listPackageRelease', JSON.stringify(listPackageRelease));
			formData.append('option','releasePackage');
			$.ajax({
				url: `${base_url}/${baseController}`,
				type       : 'POST',
				data       : formData,
				cache      : false,
				contentType: false,
				processData: false,
			})
			.done(function(response) {
				$('#mrp-tracking').val('');
				if(response.success==='true'){
					if (response.dataJson.length > 0) {
						$('#tablaPaquetes').show();
						$('#tablaPaquetes tbody').empty();
						$.each(response.dataJson, function(index, item) {
							let row = `<tr>
								<td>${item.tracking}</td>
								<td>${item.phone}</td>
								<td>${item.receiver}</td>
								<td>${item.folio}</td>
							</tr>`;
							$('#tablaPaquetes tbody').append(row);
						});
					}
					swal(guia, response.message, "success");
				}else {
					let index = listPackageRelease.indexOf(`'${guia}'`);
					if (index !== -1) {
						listPackageRelease.splice(index, 1);
					}
					swal(guia, response.message, "warning");
				}
				$('.swal-button-container').hide();
				setTimeout(function(){
					swal.close();
					$('#mrp-tracking').focus();
				}, 2500);

			}).fail(function(e) {
				console.log("Opps algo salio mal",e);
			});

		} catch (error) {
			console.log("Opps algo salio mal",error);
		}
	}

	//-----------------------
	$('#mrp-tracking').on('input', function() {
		let input = $(this).val().trim();
		if (input.length === 15 && input.substr(0, 3).toUpperCase() === "JMX") {
			$('#btn-mrp-save').click();
		}
	});

	//--------------
	$('#btn-template').click(function(){
		loadModalTemplate();
	});
	async function loadModalTemplate() {
		$('#mTTemplate').val(templateMsj);
		$('#mTIdLocation').val(idLocationSelected.val());
		$('#modal-template-title').html('Plantilla de Mensajes');
		$('#modal-template').modal({backdrop: 'static', keyboard: false}, 'show');
		setTimeout(function(){
			$('#mTTemplate').focus();
		}, 600);
	}

	$('#btn-save-template').click(function(){
		if($('#mTTemplate').val()==''){
			swal("Atención!", "* Campos requeridos", "error");
			return;
		}
		
		let formData =  new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('mTTemplate', $('#mTTemplate').val());
		formData.append('option', 'saveTemplate');
		try {
			$.ajax({
				url        : `${base_url}/${baseController}`,
				type       : 'POST',
				data       : formData,
				cache      : false,
				contentType: false,
				processData: false,
			})
			.done(function(response) {
				if(response.success=='true'){
					swal(`${response.message}`, "", "success");
					$('.swal-button-container').hide();
					$('#modal-template').modal('hide');
					setTimeout(function(){
						swal.close();
						window.location.reload();
					}, 1500);
				}
			}).fail(function(e) {
				console.log("Opps algo salio mal",e);
			});
		} catch (error) {
			console.error(error);
		}
	});


	$('#mBListTelefonos').on('keypress', function(event) {
        var tecla = event.which;
        // Permitir solo números y comas (código ASCII: 44 para la coma y del 48 al 57 para los números)
        if ((tecla != 44 && tecla < 48) || (tecla > 57)) {
            event.preventDefault();
        }
    });

	$('#btn-bot').click(function(){
		$('#mBListTelefonos').val('');
		$('#modal-bot-title').html('Crear Chatbot 🤖');
		$('#mBIdLocation').val(idLocationSelected.val());
		$('#modal-bot').modal({backdrop: 'static', keyboard: false}, 'show');
		let msj=`${templateMsj}`;
		$('#mBMessage').val(msj);
		setTimeout(function(){
			$('#mBListTelefonos').focus();
		}, 600);
	});

	$('#btn-bot-command').click(function(){

		if($('#mBListTelefonos').val()==''){
			swal("Atención!", "* Campos requeridos", "error");
			return;
		}

		let formData = new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('idContactType', 2);
		formData.append('messagebot', $('#mBMessage').val());
		formData.append('phonelistbot', $('#mBListTelefonos').val());
		formData.append('option', 'bot');
		try {
			$.ajax({
				url        : `${base_url}/${baseController}`,
				type       : 'POST',
				data       : formData,
				cache      : false,
				contentType: false,
				processData: false,
			})
			.done(function(response) {
				$('#modal-bot').modal('hide');
				console.log(response);
				swal(`🤖`,`${response.message}`, "success");
				$('.swal-button-container').hide();
				setTimeout(function(){
					swal.close();
				}, 3500);
			});
		} catch (error) {
			console.log("Opps algo salio mal",error);

		}
	});

	$(`#tbl-packages tbody`).on( `click`, `#btn-details-p`, function () {
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


	$('#btn-sync').click(async function(){
		showSwal();
		$('.swal-button-container').hide();
		let result = await chekout();
	
		// Iterar sobre el trackingList y agregar las filas correspondientes
		var trackingList = result.trackingList;
		let t=0;
		for (var guia in trackingList) {
			if (trackingList.hasOwnProperty(guia)) {
				var data = trackingList[guia];
				if (data.status === 'Verificar') {
					t++;
					addRowToTable(data.guia, data.phone, data.receiver, data.folio,data.desc_status);
				}
			}
		}
		if(t==0){
			swal("Éxito!", `Estás al día`, "success");
			$('.swal-button-container').hide();
			setTimeout(function(){
				swal.close();
			}, 3500);
			return;
		}

		swal.close();
		$('#form-modal-sync-package')[0].reset();
		$('#msyncp-id_location').val(idLocationSelected.val());
		let fechaFormateada = getCurrentDate();
		$('#msyncp-date-release').val(fechaFormateada);

		$('#modal-sync-package-title').html('Synchronize J&T and Released Packages');
		$('#modal-sync-package').modal({backdrop: 'static', keyboard: false}, 'show');
	});

	function addRowToTable(guia, telefono, destinatario, folio,desc_action) {
		var table = document.getElementById("tbl-sync").getElementsByTagName('tbody')[0];
		var newRow = table.insertRow(table.rows.length);
		var cellGuia = newRow.insertCell(0);
		var cellTelefono = newRow.insertCell(1);
		var cellDestinatario = newRow.insertCell(2);
		var cellFolio = newRow.insertCell(3);
		var cellDescAtion = newRow.insertCell(4);
		cellGuia.innerHTML = guia;
		cellTelefono.innerHTML = telefono;
		cellDestinatario.innerHTML = destinatario;
		cellFolio.innerHTML = folio;
		cellDescAtion.innerHTML = desc_action;
	}

	async function chekout() {
		let result   = '';
		let formData =  new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('option', 'chekout');
		try {
			const response = await $.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false
			});
			result = response;
		} catch (error) {
			console.error(error);
		}
		return result;
	}

	updateColors(uMarker);

	document.getElementById("id_marcador").addEventListener("change", function() {
		let selectedColor = this.value;
		updateColors(selectedColor);
	});

	$('#btn-ocurre').click(function(){
		swal({
			title: "Crear Códigos de Barras",
			text: "¿Que Opción Deseas Generar?",
			icon: "info",
			buttons: {
				opcion1: {
					text: "Punto de Autoservicio",
					value: "opcion1",
				},
				opcion2: {
					text: "Modo Ocurre",
					value: "opcion2",
				},
			},
			dangerMode: false,
		})
		.then((value) => {
			switch (value) {
				case "opcion1":
						createBarCode('auto');
					break;
				case "opcion2":
						createBarCode('ocurre');
					break;
			}
		});
	});

	function createBarCode(mode) {
		let formData =  new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('type_mode', mode);
		formData.append('option', 'ocurre');
		try {
			$.ajax({
				url        : `${base_url}/${baseController}`,
				type       : 'POST',
				data       : formData,
				cache      : false,
				contentType: false,
				processData: false,
				beforeSend : function() {
					showSwal();
					$('.swal-button-container').hide();
				}
			})
			.done(function(response) {
				swal.close();
				if (response.success=='true') {
					// Crear un enlace temporal
					let link = document.createElement('a');
					link.href =`${base_url}/controllers/${response.zip}`,
					link.download = response.zip; // Nombre del archivo ZIP
					document.body.appendChild(link);
					// Simular el clic en el enlace para iniciar la descarga
					link.click();
					// Eliminar el enlace temporal del DOM
					document.body.removeChild(link);
					swal("Éxito!", `Descarga finalizada`, "success");
				$('.swal-button-container').hide();
				setTimeout(function(){
					swal.close();
					let formData = new FormData();
					formData.append('zipFile',`${response.zip}`);
					formData.append('option','deleteZip');
					$.ajax({
						url        : `${base_url}/${baseController}`,
						type       : 'POST',
						data       : formData,
						cache      : false,
						contentType: false,
						processData: false,
					})
				}, 2500);
				} else {
					console.error('Error al generar el archivo ZIP:', response.message);
				}
			}).fail(function(e) {
				console.log("Opps algo salio mal",e);
			});
		} catch (error) {
			console.error(error);
		}
}
});

function updateColors(selectedColor) {
    let select = document.getElementById("id_marcador");

    // Establecer el color seleccionado como seleccionado en el select
    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].value === selectedColor) {
            select.selectedIndex = i;
            break;
        }
    }

    // Actualizar los colores de las opciones
    for (let i = 0; i < select.options.length; i++) {
        let option = select.options[i];
        option.style.backgroundColor = option.value;
        option.style.color = option.value === selectedColor ? 'black' : 'white';
    }

    // Establecer el color de fondo del select
    select.style.backgroundColor = selectedColor;
    select.style.color = 'white'; // Cambiar el color del texto para que sea visible
}

