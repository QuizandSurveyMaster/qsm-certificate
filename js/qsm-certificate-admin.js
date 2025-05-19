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
        qsm_certificate_update_expiry_fields();
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
                                            <td class="qsm-certificate-label" ${response.data.label_width}>${response.data.translations.issued_by}</td>
                                            <td class="qsm-certificate-value">${response.data.quiz_name}</td>
                                        </tr>
                                        <tr>
                                            <td class="qsm-certificate-label" ${response.data.label_width}>${response.data.translations.name_label}</td>
                                            <td class="qsm-certificate-value">${response.data.name}</td>
                                        </tr>
                                        <tr>
                                            <td class="qsm-certificate-label" ${response.data.label_width}>${response.data.translations.issued_date_label}</td>
                                            <td class="qsm-certificate-value">${response.data.issued_date}</td>
                                        </tr>
                                        <tr>
                                            <td class="qsm-certificate-label" ${response.data.label_width}>${response.data.translations.expires_label}</td>
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
    function qsm_certificate_update_expiry_fields() {
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
    qsm_certificate_update_expiry_fields();

    jQuery(document).on('change', '.qsm-certificate-background', function (e) {
        e.preventDefault();
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
        const $cert = {
            size: $('input[name="certificateSize"]:checked').val() || 'Portrait',
            font: $('#certificate_font').val()?.trim() || '',
            title: $('#certificate_title').val()?.trim() || '',
            logo: $('#certificate_logo').val()?.trim() || '',
            logoStyle: $('#certificate_logo_style').val()?.trim() || '',
            background: $('#qsm_certificate_background').val()?.trim() || '',
            content: tinymce.get('certificate_template')?.getContent({ format: 'raw' }) || $('#certificate_template').val()
        };

        const [A4_WIDTH, A4_HEIGHT] = [Math.round(8.26 * 100), Math.round(11.69 * 100)];
        const [width, height] = $cert.size === 'Landscape' ? [A4_HEIGHT, A4_WIDTH] : [A4_WIDTH, A4_HEIGHT];

        const html = `
            <style>
                ${!$cert.font.includes('dejavusans') ? $cert.font : 'body { font-family: "DejaVu Sans", sans-serif; text-align: left; }'}
            </style>
            <div style="background-image: url('${$cert.background}'); background-size: cover; position: relative; width: ${width}px; height: ${height}px; padding: 1px; margin: auto;">
                ${$cert.logo ? `<div style="${$cert.logoStyle}"><img src="${$cert.logo}" alt="Logo"></div>` : ''}
                ${$cert.title ? `<h1 style="text-align: center; margin: ${$cert.size === 'Landscape' ? '120px 0 50px' : '60px 0 20px'}; font-weight: 700;">${$cert.title}</h1>` : ''}
                <div style="text-align: center; vertical-align: middle; justify-content: center;">${$cert.content}</div>
            </div>
        `;

        $('#qsm-certificate-show-changes').html(html);
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
        let templateValue;
        let dataIndexID;
        if (structure == 'default') {
            dataIndexID = qsmCertificateObject.script_tmpl[jQuery(this).attr('data-indexid')];
            templateValue = dataIndexID.template_content;
        }
        let updatedContent = templateValue.replace(/([^]+)/g, '<qsmvariabletag>$1</qsmvariabletag>&nbsp;');
        updatedContent = updatedContent.replace(/\\/g, '');

        // Try to get the editor instance
        let editor = tinymce.get('certificate_template');

        // If editor exists, use it directly
        if (editor) {
            editor.setContent('');
            editor.execCommand('mceInsertContent', false, updatedContent);
        } else {
            // If editor doesn't exist, try to initialize it
            tinymce.init({
                selector: '#certificate_template',
                setup: function(editor) {
                    editor.on('init', function() {
                        editor.setContent('');
                        editor.execCommand('mceInsertContent', false, updatedContent);
                    });
                }
            });
        }

        let qsm_certificate_css = dataIndexID.template_css;
        let qsm_certificate_bg = qsmCertificateObject.qsm_tmpl_bg_url + dataIndexID.template_background;
        let certificate_custom_style_area = jQuery('#certificate_font');
        let qsm_certificate_background = jQuery('#qsm_certificate_background');
        let qsm_certificate_logo = jQuery('#certificate_logo');
        let qsm_certificate_logo_css = jQuery('#certificate_logo_style');
        let qsm_certificate_portrait_mode = jQuery('#radio32');
        let qsm_certificate_landscape_mode = jQuery('#radio33');
        let qsm_certificate_certificate_title = jQuery('#certificate_title');
        let qsm_certificate_preview_bg_img = jQuery('#qsm-certificate-image');

        if(dataIndexID.id == '2' || dataIndexID.id == '4'){
            qsm_certificate_portrait_mode.prop('checked', true);
            qsm_certificate_certificate_title.val('');
        } else {
            qsm_certificate_landscape_mode.prop('checked', true);
            qsm_certificate_certificate_title.val('PERSONALITY QUIZ');
        }
        if(dataIndexID.template_logo && dataIndexID.template_logo_css){
            let qsm_cert_logo = qsmCertificateObject.qsm_tmpl_bg_url + dataIndexID.template_logo;
            let qsm_cert_logo_css = dataIndexID.template_logo_css;
            qsm_certificate_logo.val(qsm_cert_logo);
            qsm_certificate_logo_css.val(qsm_cert_logo_css);
        } else {
            qsm_certificate_logo.val('');
        }
        certificate_custom_style_area.val(qsm_certificate_css);
        qsm_certificate_background.val(qsm_certificate_bg);
        qsm_certificate_preview_bg_img.attr('src', qsm_certificate_bg);
    });

});

