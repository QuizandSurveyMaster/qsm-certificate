jQuery(document).ready(function($) {
// For preview button
if (!$('#wp-certificate_template-media-buttons .qsm-preview-btn').length) {
    jQuery('#wp-certificate_template-media-buttons').append(`<button class="button qsm-preview-btn"><span class="dashicons dashicons-visibility"></span>${qsm_certificate_pro_obj.preview}</button>`);
}

// For template button
if (!$('#wp-certificate_template-wrap .qsm-certificate-template-btn').length) {
    jQuery('#wp-certificate_template-wrap').append(`<button class="button qsm-certificate-template-btn"></span>${qsm_certificate_pro_obj.import_template}</button>`);
}
    if ($.fn.DataTable) {
        var table = $('#qsm-certificate-table').DataTable();
        if (table) { 
            table.destroy();
        }
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

    jQuery(document).on('click', '.qsm-certificate-expiry-check-button', function (event) {
        event.preventDefault();

        let certificate_id = jQuery(document).find('#certificate_id').val();
        var data = {
            action: 'qsm_addon_certificate_expiry_check',
            certificate_id: certificate_id,
        };

        jQuery.post(qsm_certificate_ajax_object.ajaxurl, data, function(response) {
            if (response.success) {
                jQuery(document).find('#validation_message').html('');
                const modalHTML = `
                    <div class="qsm-popup-overlay">
                        <div class="qsm-certificate-result">
                            <div class="qsm-popup-close">&times;</div>
                                <div class="qsm-certificate-details">
                                    <span class="qsm-certificate-detail-row">
                                        <span class="dashicons ${response.data.status_icon}" 
                                              style="color: white; background-color: ${response.data.status_color};"></span>
                                        <span class="qsm-certificate-value">${response.data.status_text}</span>
                                    </span>
                                    <table class="qsm-certificate-table-show">
                                        <tr>
                                            <td class="qsm-certificate-label">${response.data.translations.issued_by}</td>
                                            <td class="qsm-certificate-value">${response.data.quiz_name}</td>
                                        </tr>
                                        <tr>
                                            <td class="qsm-certificate-label">${response.data.translations.name_label}</td>
                                            <td class="qsm-certificate-value">${response.data.name}</td>
                                        </tr>
                                        <tr>
                                            <td class="qsm-certificate-label">${response.data.translations.issued_date_label}</td>
                                            <td class="qsm-certificate-value">${response.data.issued_date}</td>
                                        </tr>
                                        <tr>
                                            <td class="qsm-certificate-label">${response.data.translations.expires_label}</td>
                                            <td class="qsm-certificate-value ${response.data.expiry_date_status}">${response.data.expiry_date}</td>
                                        </tr>
                                    </table>
                                </div>
                                ${response.data.certificate_url ? `
                        <div class="qsm-certificate-pdf-preview">
                            <a href="${response.data.certificate_url}" target="_blank">${response.data.translations.preview}</a>
                        </div>
                        ` : ''}
                            </div>
                        </div>
                    </div>
                `;
             
                jQuery('body').append(modalHTML);
                
                jQuery('.qsm-popup-overlay').css('display', 'flex').hide().fadeIn();
                
                jQuery('.qsm-popup-close, .qsm-popup-overlay').on('click', function() {
                    jQuery('.qsm-popup-overlay').fadeOut(function() {
                        jQuery(this).remove();
                    });
                });
                
                jQuery('.qsm-certificate-result').on('click', function(e) {
                    e.stopPropagation();
                });
            } else {
                jQuery(document).find('#validation_message').html(response.data.message);
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

    jQuery(document).on('change', '.qsm-certificate-background', function () {
        var imageUrl = jQuery(this).val();

        if (imageUrl) {
            jQuery('#qsm-certificate-image').attr('src', imageUrl);
        } else {
            jQuery('#qsm-certificate-image').attr('src', imageUrl);
        }
    });

    jQuery(document).on('click', '.qsm-preview-btn', function (event) {
        event.preventDefault();
        jQuery('#qsm-certificate-show-popup').show();
    });

    jQuery(document).on('click', '.qsm-certificate-preview-page-template-header-right .qsm-popup__close', function (event) {
        event.preventDefault();
        jQuery('#qsm-certificate-show-popup').hide();
    });
    jQuery(document).on('click', '.qsm-certificate-template-btn', function (event) {
        event.preventDefault();
        jQuery('#qsm-certificate-page-templates').show();
    });

    jQuery(document).on('click', '.qsm-certificate-page-template-header-right .qsm-popup__close', function (event) {
        event.preventDefault();
        jQuery('#qsm-certificate-page-templates').hide(); 
    });
    jQuery(document).on('mouseenter', '.qsm-certificate-page-template-card-content', function () {
        jQuery(this).find('.qsm-certificate-page-template-card-buttons').show();
    });
    
    jQuery(document).on('mouseleave', '.qsm-certificate-page-template-card-content', function () {
        jQuery(this).find('.qsm-certificate-page-template-card-buttons').hide();
    });    

    jQuery(document).on('click', '.qsm-certificate-page-template-preview-button', function (e) {
        e.preventDefault();
        jQuery('#qsm-preview-certificate-page-templates-title').html(jQuery(this).parents('.qsm-certificate-page-template-card').find('.qsm-certificate-page-template-template-name').html());
        MicroModal.show('qsm-preview-certificate-page-templates');
        dataIndexID = qsmCertificateObject.script_tmpl[jQuery(this).attr('data-indexid')];
        let qsm_certificate_bg_preview = qsmCertificateObject.qsm_tmpl_bg_url + dataIndexID.template_preview;
        jQuery('.qsm-preview-template-image-wrapper').empty().append('<img class="qsm-preview-template-image" src="'+ qsm_certificate_bg_preview +'" alt="screenshot-default-theme.png"/>')
    });

    jQuery(document).on('click', '.qsm-certificate-result .qsm-certificate-expiry-result-close', function (event) {
        event.preventDefault();
        jQuery('.qsm-certificate-result').hide();
    });
    
    jQuery(document).on('click', '.qsm-certificate-page-template-use-button', function (e) {
        e.preventDefault();
        jQuery('#qsm-certificate-page-templates').hide();  
    });

    jQuery(document).on('click', '.qsm-certificate-page-template-use-button', function (e) { 
        let structure = jQuery(this).data('structure');
        let editor = tinymce.get('certificate_template');
        let templateValue;
        let dataIndexID;
        if (structure == 'default') {
            dataIndexID = qsmCertificateObject.script_tmpl[jQuery(this).attr('data-indexid')];
            templateValue = dataIndexID.template_content;
        }
        let updatedContent = templateValue.replace(/([^]+)/g, '<qsmvariabletag>$1</qsmvariabletag>&nbsp;');
        updatedContent = updatedContent.replace(/\\/g, '');
        editor.setContent('');
        editor.execCommand('mceInsertContent', false, updatedContent);
        let qsm_certificate_css = dataIndexID.template_css;
        let qsm_certificate_bg = qsmCertificateObject.qsm_tmpl_bg_url + dataIndexID.template_background;
        let certificate_custom_style_area = document.getElementById('certificate_font');
        let qsm_certificate_background = document.getElementById('qsm_certificate_background');
        let qsm_certificate_logo = document.getElementById('certificate_logo');
        let qsm_certificate_logo_css = document.getElementById('certificate_logo_style');
        let qsm_certificate_portrait_mode = document.getElementById('radio32');
        let qsm_certificate_landscape_mode = document.getElementById('radio33');
        let qsm_certificate_certificate_title = document.getElementById('certificate_title');
        if(dataIndexID.id == '2' || dataIndexID.id == '4'){
            qsm_certificate_portrait_mode.checked = true;
            qsm_certificate_certificate_title.value = '';
        } else {
            qsm_certificate_landscape_mode.checked = true;
            qsm_certificate_certificate_title.value = 'PERSONALITY QUIZ';
        }
        if(dataIndexID.template_logo && dataIndexID.template_logo_css){
            let qsm_cert_logo = qsmCertificateObject.qsm_tmpl_bg_url + dataIndexID.template_logo;
            let qsm_cert_logo_css = dataIndexID.template_logo_css;
            qsm_certificate_logo.value = qsm_cert_logo;
            qsm_certificate_logo_css.value = qsm_cert_logo_css;
        } else {
            qsm_certificate_logo.value = '';
        }
            certificate_custom_style_area.value = qsm_certificate_css;
            qsm_certificate_background.value = qsm_certificate_bg;
    });

});

