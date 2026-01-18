$(document).ready(function() {
    $('#registrationForm').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);

        $.ajax({
            url: '../backend/register_process.php', // Percorso corretto partendo da index.php
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response.status === 'success') {
                    window.location.href = '../index.php?page=login&msg=reg_ok';
                } else {
                    $('#regFeedback').html(`<div class="alert alert-danger">${response.message}</div>`);
                }
            },
            error: function(xhr) {
                let msg = "Errore nel server";
                try {
                    let res = JSON.parse(xhr.responseText);
                    msg = res.message;
                } catch(e){}
                $('#regFeedback').html(`<div class="alert alert-danger">${msg}</div>`);
            }
        });
    });
});