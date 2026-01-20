$(document).ready(function() {
    
    
    const $pwd = $('#regPassword');
    const $pwdConf = $('#regPasswordConfirm');
    const $rulesBox = $('#passwordRules');

    
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

    
    $('#regPassword, #regPasswordConfirm').on('input focus', function() {
        $rulesBox.removeClass('d-none'); // Checklist quando si scrive
        
        const val = $pwd.val();
        const conf = $pwdConf.val();

        //Controllo Lunghezza
        updateReq('#req-length', val.length >= 8);
        
        //Controllo Maiuscola
        updateReq('#req-upper', /[A-Z]/.test(val));

        //Controllo Speciale
        updateReq('#req-special', /[!@#$%^&*(),.?":{}|<>]/.test(val));

        //Controllo Coincidenza
        if(conf.length > 0) {
            updateReq('#req-match', val === conf);
        } else {
            $('#req-match').removeClass('valid').find('i').removeClass('bi-check-circle-fill').addClass('bi-circle');
        }
    });

    
    $('#registrationForm').on('submit', function(e) {
        e.preventDefault();
        
        
        $('.form-control').removeClass('is-invalid');
        $('#globalFeedback').empty();

        let isValid = true;

        
        if ($('#fieldNome').val().trim().length < 2) {
            $('#fieldNome').addClass('is-invalid');
            isValid = false;
        }
        if ($('#fieldCognome').val().trim().length < 2) {
            $('#fieldCognome').addClass('is-invalid');
            isValid = false;
        }

        
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

        
        const p1 = $pwd.val();
        const p2 = $pwdConf.val();
        const hasUpper = /[A-Z]/.test(p1);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(p1);

        if (p1.length < 8 || !hasUpper || !hasSpecial) {
            $('#regPassword').addClass('is-invalid'); 
            isValid = false;
        }

        if (p1 !== p2) {
            $('#regPasswordConfirm').addClass('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            return; 
        }

        
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
                
                
                $('#globalFeedback').html(`<div class="alert alert-danger shadow-sm"><i class="bi bi-exclamation-triangle-fill me-2"></i>${msg}</div>`);
                window.scrollTo(0, 0);
            }
        });
    });
});