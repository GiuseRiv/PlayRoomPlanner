$(document).ready(function() {
    
    // --- VALIDAZIONE LIVE (MENTRE SCRIVI) ---
    const $pwd = $('#regPassword');
    const $pwdConf = $('#regPasswordConfirm');
    const $rulesBox = $('#passwordRules');

    // Funzione helper per aggiornare la UI della checklist
    function updateReq(id, isValid) {
        const $el = $(id);
        const $icon = $el.find('i');
        if (isValid) {
            $el.addClass('valid');
            $icon.removeClass('bi-circle bi-x-circle').addClass('bi-check-circle-fill');
        } else {
            $el.removeClass('valid');
            $icon.removeClass('bi-check-circle-fill bi-x-circle').addClass('bi-circle');
        }
        return isValid;
    }

    // Evento INPUT su entrambi i campi password
    $('#regPassword, #regPasswordConfirm').on('input focus', function() {
        $rulesBox.removeClass('d-none'); // Mostra la checklist quando inizi a scrivere
        
        const val = $pwd.val();
        const conf = $pwdConf.val();

        // 1. Controllo Lunghezza
        updateReq('#req-length', val.length >= 8);
        
        // 2. Controllo Maiuscola
        updateReq('#req-upper', /[A-Z]/.test(val));

        // 3. Controllo Speciale
        updateReq('#req-special', /[!@#$%^&*(),.?":{}|<>]/.test(val));

        // 4. Controllo Coincidenza (solo se conferma non è vuota)
        if(conf.length > 0) {
            updateReq('#req-match', val === conf);
        } else {
            $('#req-match').removeClass('valid').find('i').removeClass('bi-check-circle-fill').addClass('bi-circle');
        }
    });

    // --- VALIDAZIONE AL SUBMIT (FINALE) ---
    $('#registrationForm').on('submit', function(e) {
        e.preventDefault();
        
        // Reset errori precedenti
        $('.form-control').removeClass('is-invalid');
        $('#globalFeedback').empty();

        let isValid = true;

        // 1. Validazione Nome/Cognome
        if ($('#fieldNome').val().trim().length < 2) {
            $('#fieldNome').addClass('is-invalid');
            isValid = false;
        }
        if ($('#fieldCognome').val().trim().length < 2) {
            $('#fieldCognome').addClass('is-invalid');
            isValid = false;
        }

        // 2. Validazione Data
        const dataVal = $('#regDataNascita').val();
        if (dataVal) {
            if (new Date(dataVal) > new Date()) {
                $('#regDataNascita').addClass('is-invalid');
                isValid = false;
            }
        } else {
            $('#regDataNascita').addClass('is-invalid');
            isValid = false;
        }

        // 3. Validazione Password Completa
        const p1 = $pwd.val();
        const p2 = $pwdConf.val();
        const hasUpper = /[A-Z]/.test(p1);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(p1);

        if (p1.length < 8 || !hasUpper || !hasSpecial) {
            $('#regPassword').addClass('is-invalid'); // Questo fa apparire il box rosso sotto
            isValid = false;
        }

        if (p1 !== p2) {
            $('#regPasswordConfirm').addClass('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            // Scrolla al primo errore se necessario, ma di solito è tutto visibile
            return; 
        }

        // --- INVIO AJAX ---
        let formData = new FormData(this);
        const $btn = $('#btnRegister');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Attendere...');

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
                let msg = "Errore di connessione col server.";
                try {
                    let res = JSON.parse(xhr.responseText);
                    msg = res.message;
                } catch(e){}
                
                // Mostra errore globale in alto (box rosso standard)
                $('#globalFeedback').html(`<div class="alert alert-danger shadow-sm"><i class="bi bi-exclamation-triangle-fill me-2"></i>${msg}</div>`);
                window.scrollTo(0, 0);
            }
        });
    });
});