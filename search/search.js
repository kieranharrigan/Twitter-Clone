function passToAdd() {
	var arr = $("#input").serializeArray();
	var json = {};

	$.each(arr, function() {
                if(this.name.localeCompare("following") === 0) {
                    json[this.name] = true;
                }
                else {
		    json[this.name] = this.value;
                }
	});

	$.ajax({
		url: "/search/index.php/",
		type: "POST",
		data: JSON.stringify(json),
		success: function(reply) {
			document.write(reply);
		}
	});
}
