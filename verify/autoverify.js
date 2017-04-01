function verify() {
	var email = getUrlParameter('email');
	var key = getUrlParameter('key');

	var json = '{"email":"' + email + '", "key":"' + key + '"}';

	$.ajax({
		url: "/verify/verify.php/",
		type: "POST",
		data: json,
		dataType: "json",
		success: function(reply) {
			document.getElementById("result").innerHTML += reply.phrase + "<br>";
		}  
	});
}

function getUrlParameter(name) {
	name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
	var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
	var results = regex.exec(location.search);
	return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}