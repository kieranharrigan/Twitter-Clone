<html>
<head>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
</head>

<body>
	<div id="result"></div>

	<script>
		function verify() {
			var email = getUrlParameter('email');
			var key = getUrlParameter('key');

			var json = '{"email":"' + email + '", "key":"' + key + '"}';

			$.ajax({
				url: "/verify/index.php",
				type: "POST",
				data: json,
				success: function(reply) {
					document.write(reply);

					var json = JSON.parse(reply);

					if(json.status.localeCompare("OK") == 0) {
						window.location.replace("/login");
					}
				}
			});
		}

		function getUrlParameter(name) {
			name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
			var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
			var results = regex.exec(location.search);
			return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
		}

		verify();
	</script>

</body>
</html>
