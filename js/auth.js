$(document).ready(function() {
    // Gestione Registrazione con Foto
    $('#registerForm').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this); // FormData è necessario per inviare file

        $.ajax({
            url: '../processes/register_process.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response.status === 'success') {
                    window.location.href = '../login_view.php?msg=reg_ok';
                }
            },
            error: function(xhr) {
                let res = JSON.parse(xhr.responseText);
                $('#regFeedback').html(`<div class="alert alert-danger">${res.message}</div>`);
            }
        });
    });

    // Gestione Login
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'processes/login_process.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                window.location.href = 'dashboard.php'; // Cambia con la tua home post-login
            },
            error: function(xhr) {
                let res = JSON.parse(xhr.responseText);
                $('#loginFeedback').html(`<div class="alert alert-danger">${res.message}</div>`);
            }
        });
    });
});