function passToAdd() {
	var arr = $("#input").serializeArray();
	var json = {};

	$.each(arr, function() {
                if(this.name.localeCompare("follow") === 0) {
                    json[this.name] = false;
                }
                else {
		    json[this.name] = this.value;
                }
	});

	$.ajax({
		url: "/follow/index.php/",
		type: "POST",
		data: JSON.stringify(json),
		success: function(reply) {
			document.write(reply);
		}
	});
}
