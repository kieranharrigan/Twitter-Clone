var max = 140;

function passToAdd() {
	var arr = $("#input").serializeArray();
	var json = {};

	$.each(arr, function() {
		json[this.name] = this.value;
	});

	$.ajax({
		url: "/additem/index.php/",
		type: "POST",
		data: JSON.stringify(json),
		success: function(reply) {
			document.write(reply);
		}
	});
}

function updateCount() {
var remaining = max - $("#tweet")[0].value.length;
$("#rem")[0].innerHTML = remaining;
}
