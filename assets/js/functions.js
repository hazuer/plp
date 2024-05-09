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

	$('#btn-report').click(function(){
		window.location.href = `${base_url}/views/reports.php`;
	});

	$('#btn-inspect').click(function(){
		window.location.href = `${base_url}/views/inspect.php`;
	});

	$('#btn-list-contact').click(function(){
		window.location.href = `${base_url}/views/contacts.php`;
	});
});


const showSwal = () => {
	swal({
	  title            : "Procesando...",
	  text             : "Espere por favor",
	  icon             : `${base_url}/assets/img/ajax-loader.gif`,
	  showConfirmButton: false,
	  allowOutsideClick: false
	});
  }