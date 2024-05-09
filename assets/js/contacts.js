$(document).ready(function() {
	let baseController = 'controllers/packageController.php';

  	let table = $('#tbl-contacts').DataTable({
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
			{title: `id_contact`,          name:`id_contact`,      data:`id_contact`},     //0
			{title: `id_location`,            name:`id_location`,  data:`id_location`}, //1
			{title: `Télefono`,     		 name:`phone`,    data:`phone`},   //2
            {title: `Nombre`,            name:`contact_name`,     data:`contact_name`},    //3
			{title: `id_contact_type`,             name:`id_contact_type`,  data:`id_contact_type`}, //4
			{title: `Tipo Contacto`,             name:`contact_type`,  data:`contact_type`}, //5
			{title: `id_contact_status`,             name:`id_contact_status`,  data:`id_contact_status`}, //6
			{title: `Estatus`,             name:`desc_estatus`,  data:`desc_estatus`}, //7+ 1 last
		],
		"columnDefs": [
			{ "targets": [0,1,4,6], "visible"   : false, "searchable": false, "orderable": false},
			{ "orderable": false,"targets": 8 }, // last
		],
        'order': [[3, 'asc']]
	});

	//funcion para borrar campo de busqueda
	let clearButton = $(`<span id="clear-search" style="cursor: pointer;">&nbsp;<i class="fa fa-eraser fa-lg" aria-hidden="true"></i></span>`);
	clearButton.click(function() {
		$("#tbl-contacts_filter input[type='search']").val("");
		setTimeout(function() {
			$("#tbl-contacts_filter input[type='search']").trigger('mouseup').focus();
		}, 100);
	});
	$("#tbl-contacts_filter label").append(clearButton);

	let idLocationSelected = $('#option-location');

	$(`#tbl-contacts tbody`).on( `click`, `#btn-tbl-edit-contact`, function () {
		let row = table.row( $(this).closest('tr') ).data();
		loadContactModal(row);
	});

	function loadContactModal(row){
		console.log(row);
		let titleModal = 'Nuevo Contacto';
		$('#mCid_contact').val(row.id_contact);
		$('#mCIdLocation').val(idLocationSelected.val());
		$('#mCPhone').val(row.phone);
		$('#mCName').val(row.contact_name);
		$('#mCContactType').val(row.id_contact_type);
		$('#mCEstatus').val(row.id_contact_status);
		
		$('#mCaction').val('new');
		$('#mCPhone').prop('disabled', false);
		if(row.id_package!=0){
			titleModal = 'Editar Contacto';
			$('#mCPhone').prop('disabled', true);
			$('#mCaction').val('update');
		}

		$('#modal-contacto-title').html(titleModal);
		$('#modal-contacto').modal({backdrop: 'static', keyboard: false}, 'show');
		setTimeout(function(){
			$('#mCPhone').focus();
		}, 600);
	}

	$(`#btn-save-contacto`).click(function(){
		if($('#mCPhone').val()=='' || $('#mCName').val()==''){
			swal("Atención!", "* Campos requeridos", "error");
			return;
		}

		console.log('continue');
		return

		let formData =  new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('mCPhone', $('#mCPhone').val());
		formData.append('mCName', $('#mCName').val());
		formData.append('mCContactType', $('#mCContactType').val());
		formData.append('mCEstatus', $('#mCEstatus').val());
		formData.append('option', 'saveContact');
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
					$('#modal-contacto').modal('hide');
					setTimeout(function(){
						swal.close();
					}, 1500);
				}
			}).fail(function(e) {
				console.log("Opps algo salio mal",e);
			});
		} catch (error) {
			console.error(error);
		}
	});
	

});