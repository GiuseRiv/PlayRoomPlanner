$(document).ready(function() {
    $('#registrationForm').on('submit', function(e) {
        e.preventDefault();
        
        // Svuota feedback precedente
        $('#regFeedback').empty();

        // Controlli Password
        const p1 = $('#regPassword').val();
        const p2 = $('#regPasswordConfirm').val();

        if (p1 !== p2) {
            $('#regFeedback').html('<div class="alert alert-danger">Le password non coincidono!</div>');
            return;
        }

        if (p1.length < 8) {
            $('#regFeedback').html('<div class="alert alert-danger">La password deve essere di almeno 8 caratteri.</div>');
            return;
        }

        // Procedi con AJAX...
        let formData = new FormData(this);
        $.ajax({
            url: 'backend/register_process.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response.status === 'success') {
                    window.location.href = 'index.php?page=login&msg=reg_ok';
                } else {
                    $('#regFeedback').html(`<div class="alert alert-danger">${response.message}</div>`);
                }
            },
            error: function(xhr) {
                let res = JSON.parse(xhr.responseText);
                $('#regFeedback').html(`<div class="alert alert-danger">${res.message}</div>`);
            }
        });
    });
});