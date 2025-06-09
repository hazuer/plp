$(document).ready(function() {

	$("#logoff").click(function(){
		swal({
			title: "Cerrar sesión",
			text: "¿Está seguro?",
			icon: "warning",
			buttons: true,
			dangerMode: true,
		})
		.then((willDelete) => {
			if (willDelete) {
				window.location.href = `${base_url}/controllers/indexController.php?option=logoff`;
			} else {
				return false;
			}
		});
	});

  	$("#home").click(function(){
		window.location.href = `${base_url}/views/packages.php`;
	});

	$('#btn-grouped').click(function(){
		window.location.href = `${base_url}/views/grouped.php`;
	});

	$('#option-location').on('change', function() {
		let formData = new FormData();
		formData.append('id_location',$('#option-location').val());
		formData.append('option','changeLocation');
		$.ajax({
			url : `${base_url}/controllers/packageController.php`,
			type: 'POST',
			data:formData,
			cache: false,
			contentType: false,
			processData: false,
		  })
		  .done(function(response) {
			window.location.reload();
		})
	});

	$('#option-location-1').click(function(){
		let formData = new FormData();
		formData.append('id_location',$(this).data('slocation'));
		let sdesc = $(this).data('slocationd');
		swal(`Nueva Ubicación ${sdesc}`, "", "success");
		formData.append('option','changeLocation');
		$.ajax({
			url : `${base_url}/controllers/packageController.php`,
			type: 'POST',
			data:formData,
			cache: false,
			contentType: false,
			processData: false,
		  })
		  .done(function(response) {
			setTimeout(function(){
			window.location.reload();
			}, 1500);
		})
	});

	$('#btn-report').click(function(){
		window.location.href = `${base_url}/views/reports.php`;
	});

	$('#btn-handler').click(function(){
		window.location.href = `${base_url}/views/handler.php`;
	});

	$('#btn-list-contact').click(function(){
		window.location.href = `${base_url}/views/contacts.php`;
	});

	$('#btn-chart').click(function(){
		window.location.href = `${base_url}/views/chart.php`;
	});

	$('#btn-map').click(function(){
		window.location.href = `${base_url}/views/map.php`;
	});

	$(function () {
		$('[data-toggle="tooltip"]').tooltip()
	})

	$('#vGuia').on('keydown', function(event) {
		if (event.key === 'Enter' || event.keyCode === 13) {
			event.preventDefault(); // Evita que se dispare un submit si está en un formulario
			const vGuia = $(this).val();

			if (!vGuia) {
				// Está vacío o solo tenía espacios
				swal("Error!", 'Ingresa numero de guía', "error");
				return; // Salir o detener ejecución
			}
			let formData = new FormData();
			formData.append('vGuia',vGuia);
			formData.append('option','checkGuia');
			$.ajax({
				url : `${base_url}/controllers/packageController.php`,
				type: 'POST',
				data:formData,
				cache: false,
				contentType: false,
				processData: false,
				beforeSend : function() {
					showSwal('Buscando Guía','Espere por favor...');
					$('.swal-button-container').hide();
				}
			})
			.done(function(response) {
				$('#vGuia').val('');
				swal.close();
				if(response.success=='true'){

					speakText(`Folio: ${response.dataJson.folio}`);
					setTimeout(function(){
						speakText(`Letra, ${response.dataJson.initial}`);
					}, 600);
					setTimeout(function(){
						speakText(`${response.dataJson.contact_name}`);
					}, 600);

					$('#mif-folio')
					.html(`${response.dataJson.folio}`)
					.css('color', response.dataJson.marker)
					.css('font-size', '45px');

					$('#mif-letra')
					.html(`${response.dataJson.initial}`)
					.css('color', response.dataJson.marker)
					.css('font-size', '45px');;

					$('#mif-nombre').html(`${response.dataJson.contact_name}`);
					let rawPhone = response.dataJson.phone;
					let formattedPhone = `${rawPhone.substring(0, 3)}-${rawPhone.substring(3, 6)}-${rawPhone.substring(6, 8)}-${rawPhone.substring(8)}`;
					$('#mif-telefono').html(formattedPhone);
				
					$('#modal-info-guia-title').html(`${response.dataJson.tracking}`);
					$('#modal-info-guia').modal({backdrop: 'static', keyboard: false}, 'show');

					setTimeout(function(){
						$('#modal-info-guia').modal('hide');
						$('#vGuia').focus();
					}, 5000);
				}else{
					swal("Error!", 'Guía no encontrada', "error");
					setTimeout(function(){
						$('#modal-info-guia').modal('hide');
						$('#vGuia').focus();
					}, 2500);
				}
			})
		}
	});

	let html5QrcodeScanner;
	$('#btn-scan-qr').click(function(){
		$('#vGuia').val('');
		let titleModal =  'Verificador Guía';
		html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", { fps: 15, qrbox : { width: 260, height: 85 } });
		html5QrcodeScanner.render(onScanSuccess);

		$('#modal-scan-qr-title').html(titleModal);
		$('#modal-scan-qr').modal({backdrop: 'static', keyboard: false}, 'show');
	});

	function onScanSuccess(decodedText, decodedResult) {
		console.log(`Scan result: ${decodedText}`, decodedResult);
		// Establecer el valor escaneado y simular Enter
		$('#vGuia').val(decodedText).trigger('input');
		$('#vGuia').trigger(jQuery.Event('keydown', { keyCode: 13, which: 13 }));

		// Detener el scanner después de un breve retraso
		setTimeout(() => {
			$('#modal-scan-qr').modal('hide');
			html5QrcodeScanner.clear();
		}, 300); // pequeño delay para evitar conflicto con otros eventos
	}

	$('#close-qr-b,#close-qr-x').click(function(){
		if (html5QrcodeScanner) {
			html5QrcodeScanner.clear().catch(error => {
				console.warn('Error al detener el escáner:', error);
			});
		}
	});

});
	function speakText(txt, rate=1) {
		const utterance = new SpeechSynthesisUtterance(txt);
		utterance.lang  = 'es-ES'; // Español
		utterance.rate  = rate; // Velocidad normal
		utterance.pitch = 1; // Tono normal
		window.speechSynthesis.speak(utterance);
	}

	const showSwal = (title='Procesando...',textDesc='Espere por favor') => {
		swal({
			title            : title,
			text             : textDesc,
			icon             : `${base_url}/assets/img/ajax-loader.gif`,
			showConfirmButton: false,
			closeOnClickOutside: false
		});
	}