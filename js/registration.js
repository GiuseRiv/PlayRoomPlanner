$(document).ready(function() {
    $('#registrationForm').on('submit', function(e) {
        e.preventDefault();
        $('#regFeedback').empty();

        const p1 = $('#regPassword').val();
        const p2 = $('#regPasswordConfirm').val();
        
        const upperRegex = /[A-Z]/;
        const specialRegex = /[!@#$%^&*(),.?":{}|<>]/;

        if (p1 !== p2) {
            showError("Le password non coincidono.");
            return;
        }

        if (p1.length < 8) {
            showError("La password deve essere di almeno 8 caratteri.");
            return;
        }

        if (!upperRegex.test(p1)) {
            showError("La password deve contenere almeno una lettera maiuscola.");
            return;
        }

        if (!specialRegex.test(p1)) {
            showError("La password deve contenere almeno un carattere speciale.");
            return;
        }

        let formData = new FormData(this);
        const $btn = $('#btnRegister');
        $btn.prop('disabled', true).text('Registrazione in corso...');

        $.ajax({
            url: 'backend/register_process.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response.status === 'success') {
                    window.location.href = '/PlayRoomPlanner/index.php?page=login&msg=reg_ok';
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).text('Registrati');
                let msg = "Errore nel server";
                try {
                    let res = JSON.parse(xhr.responseText);
                    msg = res.message;
                } catch(e){}
                showError(msg);
            }
        });
    });

    function showError(text) {
        $('#regFeedback').html(`<div class="alert alert-danger">${text}</div>`);
        window.scrollTo(0, 0);
    }
});