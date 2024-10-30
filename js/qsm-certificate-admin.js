jQuery(document).ready(function($) {
    if ($.fn.DataTable) {
        $('#qsm-certificate-table').DataTable({
            paging: true,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, qsm_certificate_obj.length_menu]],
            language: {
                paginate: { previous: "<", next: ">" },
                lengthMenu: qsm_certificate_obj.lengthMenu,
                info: qsm_certificate_obj.info,
                search: qsm_certificate_obj.search
            },
            order: [[2, "asc"]],
            columnDefs: [
                { targets: [2, 3], orderable: true, type: 'date-eu' },
                { targets: [0, 1, 4], orderable: false }
            ]
        });
    }
                  
        
    // Handle single file deletion
    $('.qsm-delete-file').on('click', function() {
        var filename = $(this).data('filename');
        if (confirm(qsm_certificate_obj.delete_confirm)) {
            var row = $(this).closest('tr');
            $.post(ajaxurl, {
                action: 'delete_certificate',
                file_name: filename
            }, function(response) {
                if (response.success) {
                    row.fadeOut(function() {
                        $(this).remove(); 
                    });
                } else {
                    alert(response.data);
                }
            });
        }
    });

        // Handle select all functionality
        $('#qsm-select-all-certificate').click(function() {
            $('input[name="certificates[]"]').prop('checked', this.checked);
        });
    
        // Handle bulk delete submission
        $('#qsm-certificate-form').on('submit', function(e) {
            e.preventDefault();
    
            var certificates = [];
            $('input[name="certificates[]"]:checked').each(function() {
                certificates.push($(this).val());
            });
    
            if (certificates.length === 0) {
                alert(qsm_certificate_obj.no_certificate_selected);
                return;
            }
            if(confirm(qsm_certificate_obj.bulk_delete_confirm)){
                var data = {
                action: 'bulk_delete_certificates',
                certificates: certificates,
                bulk_delete_certificates_nonce: $('#bulk_delete_certificates_nonce').val()
            };
        }
    
            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload(); 
                } else {
                    alert(response.data);
                }
            });
        });

    
    $('input[name="enable_expiry"]').change(function() {
        qsmUpdateExpiryFields();
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
    function qsmUpdateExpiryFields() {
        let enableExpiry = $('input[name="enable_expiry"]:checked').val();
        if (enableExpiry === '0') {
            $('.qsm-certificate-expiry-date').hide();
            $('.qsm-certificate-expiry-days').show();
        } else if (enableExpiry === '1') {
            $('.qsm-certificate-expiry-date').show();
            $('.qsm-certificate-expiry-days').hide();
        } else if (enableExpiry === '2') {
            $('.qsm-certificate-expiry-date').hide();
            $('.qsm-certificate-expiry-days').hide();
        }
    }
    qsmUpdateExpiryFields();
    
});

