jQuery(document).ready(function() {
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
});
