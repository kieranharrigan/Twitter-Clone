function passToAdd() {
	var arr = $("#input").serializeArray();
	var json = {};

	$.each(arr, function() {
		json[this.name] = this.value;
	});

	$.ajax({
		url: "/adduser/index.php/",
		type: "POST",
		data: JSON.stringify(json),
		success: function(reply) {
			console.log("HERE");
			document.write(reply);
console.log("PLS");
			var json = JSON.parse(reply);
			console.log("WHY");

			if(json.status.localeCompare("OK") == 0) {
				window.location.replace("/additem");
			}
			else {
				window.location.replace("/adduser");
			}
		}
	});
}
