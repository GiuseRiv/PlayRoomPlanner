$(document).ready(function() {
    $('#registrationForm').on('submit', function(e) {
        e.preventDefault();
        
        const data = {
            nome: $('#regNome').val(),
            cognome: $('#regCognome').val(),
            email: $('#regEmail').val(),
            ruolo: $('#regRuolo').val(),
            data_nascita: $('#regData').val()
        };

        $.ajax({
            url: '../api/users.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function(response) {
                $('#regFeedback').html('<div class="alert alert-success">Registrazione completata! Reindirizzamento al login...</div>');
                setTimeout(function() {
                    window.location.href = '../index.php?msg=reg_ok';
                }, 2000);
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : "Errore durante la registrazione.";
                $('#regFeedback').html('<div class="alert alert-danger">' + errorMsg + '</div>');
            }
        });
    });
});