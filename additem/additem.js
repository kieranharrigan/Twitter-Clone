var max = 140;

function passToAdd() {
	var arr = $("#input").serializeArray();
	var json = {};

	$.each(arr, function() {
		if(this.name.localeCompare("media") === 0) {
			this.value.replace(/\s/g,"");
			json[this.name] = this.value.split(",");
		}
		else {
			json[this.name] = this.value;
		}
	});

	console.log(json);
	console.log(JSON.stringify(json));

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
