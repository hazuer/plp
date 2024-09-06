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
			{ "targets": [3,7,9,10], "visible"   : false, "searchable": false, "orderable": false},
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
			formData.append('desc_mov','Liberación de Paquete Manual');
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
					$('audio#togroup')[0].play();
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
				$('audio#wrong')[0].play();
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
			formData.append('desc_mov','Liberación de Paquete Escáner Modal');
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
		$('#modal-bot-title').html('Chatbot Envío de Mensajes 🤖');
		$('#mBEstatus').val(1);
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
		formData.append('idEstatus', $('#mBEstatus').val());
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

		$('#modal-sync-package-title').html('Sincronizar J&T y Paquetes Liberados');
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
					text: "Autoservicio",
					value: "opcion1",
				},
				opcion2: {
					text: "Ocurre",
					value: "opcion2",
				},
				opcion3: {
					text: "Anomalia",
					value: "opcion3",
				}
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
				case "opcion3":
						createBarCode('anomalia');
					break;
			}
		});
	});

	function createBarCode(mode) {
		let formData =  new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('type_mode', mode);
		formData.append('option', 'createBarcode');
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

	$("#confirmg").click(function(e){
		//let rowsConfirm     = '';
		let rows_selected   = table.column(0).checkboxes.selected();
		let tRows           = 0;
		let isValid         = true;
		let noValidTracking = [];
		let phoneUser       = [];
		let userName        = [];
		let folios        = [];

		$.each(rows_selected, function(index, rowId){
			tRows++;
			 // Obtener el índice de la fila
			 let rowIndex = table.row('#row_id_' + rowId).index();
			 // Obtener el valor de la columna 7 para la fila actual usando el índice
			 let rowData = table.row(rowIndex).data(); // Obtener los datos de la fila
			 let status = rowData.id_status; // Obtener el valor de la columna 7
			// Verificar si el estatus es 2 o 7
			if (status !== '2' && status !== '7') {
				isValid = false; // Marcar como inválido si no cumple con el criterio 
				noValidTracking.push(rowData.tracking); // Agregar el tracking no válido al array
			}
			phoneUser.push(rowData.phone);
			userName.push(rowData.receiver);
			folios.push(rowData.folio);
		});

		if (tRows === 0) {
			swal("Error al confirmar!", "Debes seleccionar las guías para confirmar", "error");
			return false;
		}

		if (!isValid) {
			let noValidTrackingList = noValidTracking.join(',');
			swal("Error al confirmar!", "Solo se permite confirmar paquetes con estatus:\nMensaje Enviado\nContactado\n\nGuías no válidas para confirmar:\n" + noValidTrackingList, "error");
			return false;
		}

		// same phone 7341287415
		if (!allPhonesEqual(phoneUser)) {
			swal("Error al confirmar!", "Todos los paquetes deben tener el mismo número de teléfono para confirmar", "error");
			return false;
		}

		let rowsConfirm = rows_selected.join(",");
		//console.log('continue:::',rowsConfirm);
		let tpaquetes = tRows;
		let tphone    = phoneUser[0];
		let tname     = userName[0];
		let tids      = rowsConfirm;
		// Ordenar el arreglo de forma ascendente
		folios.sort(function(a, b) {
			return a - b;
		});
		let lsFolios = folios.join(',');
		swal({
			title: `Confirmar Paquetes 👍`,
			text: `Total:${tpaquetes} Paquetes\nTélefono:${tphone}\nDesinatario:${tname}\nFolios:${lsFolios}\n\nEstá seguro ?`,
			icon: "info",
			buttons: true,
			dangerMode: false,
		})
		.then((weContinue) => {
		  if (weContinue) {

			let formData = new FormData();
			formData.append('id_location', idLocationSelected.val());
			formData.append('idsx', tids);
			formData.append('option', 'pullConfirm');
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
					if(response.success==='true'){
						swal('Éxito', response.message, "success");
						setTimeout(function(){
							swal.close();
							window.location.reload();
						}, 3500);
					}else {
						swal('Atención', response.message, "warning");
					}
					$('.swal-button-container').hide();
				});
			} catch (error) {
				console.log("Opps algo salio mal",error);
			}
		  } else {
			return false;
		  }
		});

		e.preventDefault();
	});

	function allPhonesEqual(phoneArray) {
		if (phoneArray.length === 0) {
			return true; // Si el array está vacío, consideramos que todos son iguales (o podrías manejar esto como un caso especial)
		}
		let firstPhone = phoneArray[0]; // Obtener el primer número de teléfono
		// Comparar cada número de teléfono con el primero
		return phoneArray.every(phone => phone === firstPhone);
	}

	$("#releaseg").click(function(e){
		let rows_selected   = table.column(0).checkboxes.selected();
		let tRows           = 0;
		let isValid         = true;
		let noValidTracking = [];
		let phoneUser       = [];
		let userName        = [];
		let folios          = [];

		$.each(rows_selected, function(index, rowId){
			tRows++;
			 let rowIndex = table.row('#row_id_' + rowId).index();
			 let rowData = table.row(rowIndex).data(); // Obtener los datos de la fila
			 let status = rowData.id_status;
			// Verificar si el estatus es 2 o 7
			//console.log(rowId,status);
			if (status !== '2' && status !== '5' && status !== '7') {
				isValid = false; // Marcar como inválido si no cumple con el criterio 
				noValidTracking.push(rowData.tracking); // Agregar el tracking no válido al array
			}
			phoneUser.push(rowData.phone);
			userName.push(rowData.receiver);
			folios.push(rowData.folio);
		});

		if (tRows === 0) {
			swal("Error al liberar!", "Debes seleccionar las guías para liberar", "error");
			return false;
		}

		if (!isValid) {
			let noValidTrackingList = noValidTracking.join(',');
			swal("Error al liberar!", "Solo se permite liberar paquetes con estatus:\nMensaje Enviado\nContactado\nConfirmado\n\nGuías no válidas para liberar:\n" + noValidTrackingList, "error");
			return false;
		}

		// same phone 7341287415
		if (!allPhonesEqual(phoneUser)) {
			swal("Error al liberar!", "Todos los paquetes deben tener el mismo número de teléfono para liberar", "error");
			return false;
		}

		let rowsRelease = rows_selected.join(",");
		let tpaquetes = tRows;
		let tphone    = phoneUser[0];
		let tname     = userName[0];
		let tids      = rowsRelease;
		// Ordenar el arreglo de forma ascendente
		folios.sort(function(a, b) {
			return a - b;
		});
		let lsFolios = folios.join(',');
		swal({
			title: `Liberar Paquetes 📦`,
			text: `Total:${tpaquetes} Paquetes\nTélefono:${tphone}\nDesinatario:${tname}\nFolios:${lsFolios}\n\nEstá seguro ?`,
			icon: "info",
			buttons: true,
			dangerMode: false,
		})
		.then((weContinue) => {
		  if (weContinue) {

			let formData = new FormData();
			formData.append('id_location', idLocationSelected.val());
			formData.append('idsx', tids);
			formData.append('option', 'pullRealise');
			formData.append('desc_mov', 'Liberación de Paquete por Selección');
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
					if(response.success==='true'){
						swal('Éxito', response.message, "success");
						setTimeout(function(){
							swal.close();
							window.location.reload();
						}, 3500);
					}else {
						swal('Atención', response.message, "warning");
					}
					$('.swal-button-container').hide();
				});
			} catch (error) {
				console.log("Opps algo salio mal",error);
			}
		  } else {
			return false;
		  }
		});

		e.preventDefault();
	});

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
