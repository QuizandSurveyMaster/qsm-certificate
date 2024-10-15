jQuery(function ($) {
    
    $('input[name="enable_expiry"]').change(function() {
        updateExpiryFields();
    });

    jQuery(document).on('submit', '#qsm-certificate-expiry-check-form', function (event) {
        event.preventDefault();

        let certificate_id = jQuery('#qsm-certificate-expiry-check-form').find('#certificate_id').val();
        let Email = jQuery('#qsm-certificate-expiry-check-form').find('#email').val();

        var data = {
            action: 'qsm_addon_certificate_expiry_check',
            certificate_id: certificate_id,
            email: Email,
        };

        jQuery.post(qmn_ajax_object.ajaxurl, data, function (response) {
            if (response.success) {
                jQuery('#validation_message').html('<p>' + response.data.message + '</p>');
            }
        });  
    });
    function updateExpiryFields() {
        let enableExpiry = $('input[name="enable_expiry"]:checked').val();
        if (enableExpiry === '0') {
            $('.qsm-certificate-expiry-date').hide();
            $('.qsm-certificate-expiry-days').show();
        } else if (enableExpiry === '1') {
            $('.qsm-certificate-expiry-date').show();
            $('.qsm-certificate-expiry-days').hide();
        }
    }
    
});
