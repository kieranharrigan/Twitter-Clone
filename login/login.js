function passToAdd() {
	var arr = $("#input").serializeArray();
	var json = {};

	$.each(arr, function() {
		json[this.name] = this.value;
	});

	$.ajax({
		url: "/login/index.php/",
		type: "POST",
		data: JSON.stringify(json),
		success: function(reply) {
			document.write(reply);

			var json = JSON.parse(reply);

			if(json.status.localeCompare("OK") == 0) {
				window.location.replace("/additem");
			}
			else {
				window.location.replace("/login");
			}
		}
	});
}