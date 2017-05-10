var max = 140;

function passToAdd() {
	var arr = $("#input").serializeArray();
	var json = {};

	$.each(arr, function() {
		if(this.name.localeCompare("media") === 0) {
			var temp = this.value.replace(/\s/g,"");
                        if(temp.localeCompare("") === 0) {
                             json[this.name] = [];
                        }
                        else {
			    json[this.name] = temp.split(",");
                        }
		}
		else {
			json[this.name] = this.value;
		}
	});

	$.ajax({
		url: "/additem/index.php/",
		type: "POST",
		data: JSON.stringify(json),
		success: function(reply) {
			document.write(reply);

			var json = JSON.parse(reply);

			if(json.status.localeCompare("error") == 0) {
				console.log("HI");
				window.history.go(-1);
			}
		}
	});
}

function updateCount() {
var remaining = max - $("#tweet")[0].value.length;
$("#rem")[0].innerHTML = remaining;
}
