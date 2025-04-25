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
});

const showSwal = (title='Procesando...',textDesc='Espere por favor') => {
	swal({
	  title            : title,
	  text             : textDesc,
	  icon             : `${base_url}/assets/img/ajax-loader.gif`,
	  showConfirmButton: false,
	  closeOnClickOutside: false
	});
  }