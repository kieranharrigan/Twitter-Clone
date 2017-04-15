function passToDB() {
        var form = $("#input")[0];
        var formData = new FormData(form);

        $.ajax({
                url: "/addmedia/index.php/",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function(reply) {
                        document.write(reply);
                }
        });
}
