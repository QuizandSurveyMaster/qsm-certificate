jQuery(document).ready(function($) {
    // For preview button
    if (!$('#wp-certificate_template-media-buttons .qsm-preview-btn').length) {
        let preview = typeof qsm_certificate_pro_obj !== 'undefined' ? qsm_certificate_pro_obj.preview : '';
        jQuery('#wp-certificate_template-media-buttons').append(`<button class="button qsm-preview-btn"><span class="dashicons dashicons-visibility"></span>${preview}</button>`);
    }

    // For template button
    if (!$('#wp-certificate_template-wrap .qsm-certificate-template-btn').length) {
        let import_template = typeof qsm_certificate_pro_obj !== 'undefined' ? qsm_certificate_pro_obj.import_template : '';
        let save_template = typeof qsm_certificate_pro_obj !== 'undefined' ? qsm_certificate_pro_obj.save_template : '';
        jQuery('#wp-certificate_template-wrap').append(`<button class="button qsm-certificate-template-btn"></span>${import_template}</button>`);
        jQuery('#wp-certificate_template-wrap').append(`<button class="button qsm-certificate-save-template-button"></span>${save_template}</button>`);
    }

    $('input[name="enable_expiry"]').change(function() {
        qsm_certificate_update_expiry_fields();
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
    
    function qsm_toggle_certificate_rows() {
        var $c = jQuery('input[name="enableCertificates"]:checked');
        if (!$c.length) return;
        var d = $c.val() === '1', $f = $c.closest('form');
        $f.find('table.form-table:first tr').show().slice(1)
          .css('opacity', d ? 0.5 : 1)
          .find('input,select,textarea,button').prop('readonly', d);
        $f.find('button[type="submit"],input[type="submit"]').prop('readonly', false);
        var ed = window.tinymce && tinymce.get('certificate_template');
        if (ed) { try { ed.setMode(d ? 'readonly' : 'design'); } catch(e){}
        }
        if (typeof qsm_certificate_update_expiry_fields === 'function') qsm_certificate_update_expiry_fields();
    }

    jQuery(document).on('change', 'input[name="enableCertificates"]', qsm_toggle_certificate_rows);

    qsm_toggle_certificate_rows();

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
    jQuery(document).on('click', '.qsm-certificate-template-btn', function (e) {
        e.preventDefault();
        var $p = jQuery('#qsm-certificate-page-templates').show(),
            type = $p.find('main[id$="-page-templates-content"]').data('type') || 'certificate',
            $links = $p.find('.qsm-' + type + '-page-tmpl-header-links'),
            $page = $p.find('.qsm-' + type + '-page-template-container'),
            $my = $p.find('.qsm-' + type + '-my-template-container');
        $links.removeClass('active').filter('[data-tab="page"]').addClass('active');
        $page.show();
        $my.hide();
        $links.off('click.qsmTabs').on('click.qsmTabs', function (ev) {
            ev.preventDefault();
            var tab = jQuery(this).data('tab');
            $links.removeClass('active');
            jQuery(this).addClass('active');
            if (tab === 'page') { $my.hide(); $page.show(); } else { $page.hide(); $my.show(); }
        });
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

    jQuery(document).on('change', 'input[name="qsm_certificate_expiry_shortcode"]', function () {
        var $el = jQuery('.qsm-certificate-expiry-shortcode-notloop');
        $el.css('visibility', jQuery(this).is(':checked') ? 'visible' : 'hidden');
        if ($el.data('first')) jQuery('#qsm-certificate-expiry-layout').val('grid');
    });

    jQuery(document).on('click', '.qsm-certificate-expiry-shortcode-notloop .qsm-certificate-expiry-shortcode-info', function () {
        var $container = jQuery(this).closest('.qsm-certificate-expiry-shortcode-notloop');
        var shortcode = $container.find('.qsm-certificate-expiry-shortcode-print').text().trim();
        $container.find('.certificate-copy-msg').hide();
        $container.find('.certificate-copy-success').fadeIn();
        copyToClipboard(shortcode);
        setTimeout(function () {
            $container.find('.certificate-copy-success').hide();
            $container.find('.certificate-copy-msg').fadeIn();
        }, 1000);
    });

    function copyToClipboard(text) {
        var textarea = document.createElement('textarea');
        document.body.appendChild(textarea);
        textarea.value = text;
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }

});

jQuery(document).ready(function() {
    jQuery('input[name="qsm-template-action"]').on('change', function() {
        const isNew = jQuery(this).val() === 'new';
        jQuery('#qsm-template-name-row').toggle(isNew);
        jQuery('#qsm-template-select-row').toggle(!isNew);
        jQuery('#qsm-certificate-template-name').prop('required', isNew);
        jQuery('#qsm-certificate-template-select').prop('required', !isNew);
        const submitText = window.qsm_certificate_template_obj || {};
        jQuery('#qsm-template-name-update-popup-btn').text(isNew ? submitText.save_template : submitText.update_template);
    });
    jQuery('input[name="qsm-template-action"]:checked').trigger('change');

    jQuery('.qsm-certificate-save-template-button').on('click', function(event) {
        event.preventDefault();
        event.stopPropagation();
        MicroModal.show('qsm-template-name-update-popup');
        jQuery('#qsm-template-name-update-popup-btn').off('click').on('click', function(event) {
            event.preventDefault();
            var $btn = jQuery(this);
            if ($btn.prop('disabled')) return;
            $btn.prop('disabled', true);
            var actionType = jQuery('input[name="qsm-template-action"]:checked').val();
            var template_name = jQuery('#qsm-certificate-template-name').val();
            var template_id = jQuery('#qsm-certificate-template-select').val();
            // Client-side validation
            if (actionType === 'new') {
                jQuery('#qsm-certificate-template-name').css('border', '');
                if (!template_name || !template_name.trim()) {
                    jQuery('#qsm-certificate-template-name').css('border', '1px solid #dc2626').focus();
                    $btn.prop('disabled', false);
                    return;
                }
            } else if (actionType === 'update') {
                jQuery('#qsm-certificate-template-select').css('border', '');
                if (!template_id) {
                    jQuery('#qsm-certificate-template-select').css('border', '1px solid #dc2626').focus();
                    $btn.prop('disabled', false);
                    return;
                }
            }
            var $cert_data = {
                size: jQuery('input[name="certificateSize"]:checked').val() || 'Portrait',
                font: jQuery('#certificate_font').val()?.trim() || '',
                title: jQuery('#certificate_title').val()?.trim() || '',
                logo: jQuery('#certificate_logo').val()?.trim() || '',
                dpi: jQuery('#certificate_dpi').val()?.trim() || '',
                logoStyle: jQuery('#certificate_logo_style').val()?.trim() || '',
                background: jQuery('#qsm_certificate_background').val()?.trim() || '',
                content: tinymce.get('certificate_template')?.getContent({ format: 'raw' }) || jQuery('#certificate_template').val(),            
            };
            var data = {
                action: 'qsm_addon_certificate_save_template',
                quiz_id: qsm_certificate_pro_obj.quiz_id,
                template_name: template_name,
                cert_data: $cert_data,
                template_action: actionType,
                template_id: template_id
            };
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        MicroModal.close('qsm-template-name-update-popup');
                        const res = response.data || {};
                        try { if (window.qsm_certificate_template_obj && qsm_certificate_template_obj.certificate_data_by_id && res.template_id && res.cert_data) { qsm_certificate_template_obj.certificate_data_by_id[res.template_id] = res.cert_data; } } catch(_) {}

                        const id = parseInt(res.template_id, 10);
                        const name = res.template_name || template_name || '';
                        const created = res.creation_date || '';

                        // Reflect in the Update select dropdown
                        const $tplSelect = jQuery('#qsm-certificate-template-select');
                        if ($tplSelect.length && id) {
                            const $opt = $tplSelect.find('option[value="' + id + '"]');
                            if ($opt.length) {
                                $opt.text(name).prop('selected', true);
                            } else {
                                jQuery('<option>', { value: id, text: name }).appendTo($tplSelect).prop('selected', true);
                            }
                        }
                        const $tbody = jQuery('.qsm-my-templates-table-body');
                        if (!$tbody.length) return;

                        $tbody.find('tr.qsm-no-templates-row').remove();
                        if (!$tbody.find('tr.qsm-templates-header').length && !$tbody.find('th').length) {
                            const t = qsm_certificate_template_obj;
                            $tbody.append('<tr class="qsm-templates-header"><th>' + t.template_name + '<\/th><th>' + t.template_id + '<\/th><th>' + t.created_date + '<\/th><th>' + t.actions + '<\/th><\/tr>');
                        }

                        const $existing = $tbody.find('button.qsm-use-my-template[data-template-id="' + id + '"]').closest('tr');
                        if ($existing.length) {
                            const $tds = $existing.find('td');
                            if ($tds.length >= 3) { jQuery($tds[0]).text(name); jQuery($tds[1]).text(id); jQuery($tds[2]).text(created); }
                        } else {
                            const t = qsm_certificate_template_obj;
                            const actionsHtml = '<div class="qsm-table-icons">\
                                <button type="button" class="qsm-use-my-template" data-template-id="' + id + '"><img src="' + t.import_template_svg + '" alt="Import Icon"><\/button>\
                                <button type="button" class="qsm-delete-template" data-template-id="' + id + '"><img src="' + t.delete_template_svg + '" alt="Delete Icon"><\/button>\
                            <\/div>';
                            const $row = jQuery('<tr><td><\/td><td><\/td><td><\/td><td><\/td><\/tr>');
                            $row.find('td').eq(0).text(name);
                            $row.find('td').eq(1).text(id);
                            $row.find('td').eq(2).text(created);
                            $row.find('td').eq(3).html(actionsHtml);
                            $tbody.append($row);
                        }
                    } else {
                        alert(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false);
                    alert(error);
                }
            });
        });
        jQuery('#qsm-template-name-update-popup-close').on('click', function(event) {
            event.preventDefault();
            MicroModal.close('qsm-template-name-update-popup');
        });
    });

    jQuery(document).off('click', '.qsm-use-my-template').on('click', '.qsm-use-my-template', function (e) {
        e.preventDefault();
        let template_data = qsm_certificate_template_obj.certificate_data_by_id;
        let template_id = jQuery(this).attr('data-template-id');
        let template_data_by_id = template_data[template_id];
        jQuery('#qsm_certificate_background').val(template_data_by_id.background || '');
        jQuery('#certificate_font').val(template_data_by_id.font || '');
        jQuery('#certificate_logo').val(template_data_by_id.logo || '');
        jQuery('#certificate_logo_style').val(template_data_by_id.logoStyle || '');
        jQuery('#certificate_title').val(template_data_by_id.title || '');
        jQuery('input[name="certificateSize"][value="' + (template_data_by_id.size === 'Landscape' ? 'Landscape' : 'Portrait') + '"]').prop('checked', true);
        const editor = (typeof tinymce !== 'undefined') && tinymce.get('certificate_template');
        editor ? editor.setContent(template_data_by_id.content || '') : jQuery('#certificate_template').val(template_data_by_id.content || '');
    });

    jQuery(document)
        .off('click.qsmDeleteTpl', '.qsm-delete-template')
        .on('click.qsmDeleteTpl', '.qsm-delete-template', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        const $btn = jQuery(this);
        if ($btn.prop('disabled')) return;
        const id = parseInt($btn.data('template-id'), 10);
        if (!id) return alert('Invalid template ID.');
        if (!confirm(qsm_certificate_pro_obj.tmpl_confirm_msg)) return;

        $btn.prop('disabled', true);
        jQuery.post(ajaxurl, { action: 'qsm_addon_certificate_delete_template', template_id: id })
            .done(function (res) {
                if (res && res.success) {
                    $btn.closest('tr').fadeOut(200, function(){ jQuery(this).remove(); });
                } else {
                    alert((res && res.data) ? res.data : qsm_certificate_pro_obj.failed_msg);
                    $btn.prop('disabled', false);
                }
            })
            .fail(function () { alert(qsm_certificate_pro_obj.server_error_msg); $btn.prop('disabled', false); });
    });
    jQuery('.qsm-certificate-validate-parent').on( 'click', '.qsm-certificate-validate-license-button', function ( event ) {
        event.preventDefault();
        var license_message = jQuery('.qsm-certificate-validate-license-message');
        jQuery("#qsm-certificate-license-message").show();
        if( jQuery('.qsm-certificate-validate-license-input').val() != "") {
          license_message.html(qsm_certificate_obj.please_wait);
          var data = {
            action: 'qsm_certificate_validate_license',
            input: jQuery('.qsm-certificate-validate-license-input').val(),
          };
          jQuery.post( ajaxurl, data, function ( response ) {
            response = jQuery.parseJSON(response);
            license_message.html(response.message);
            if(response.validate == 1 || response.validate == 2) {
              response.validate == 1 ? jQuery("#qsm-certificate-license-message").css({'color': '#00a32a'}) : "";
              setTimeout(function() {
                location.reload();
              }, 2000);
            } else {
              license_message.html(qsm_certificate_obj.invalid);
            }
          });
        }else{
          license_message.html(qsm_certificate_obj.license_msg);
          jQuery('.qsm-certificate-validate-license-input').focus();
        }
    });
    jQuery('.qsm-certificate-licence-toggle').on("click", function( event ) {
      event.preventDefault();
      if ( parseInt(jQuery(this).attr('data-toggle')) === 0 ) {
        jQuery(document).find('#qsm-certificate-license-entry').show();
        jQuery(document).find('.qsm-license-content .qsm-license-info').hide();
      } else {
        jQuery(document).find('.qsm-license-content .qsm-license-info').show();
        jQuery(document).find('#qsm-certificate-license-entry').hide();
      }
    });
    jQuery(document).on( 'click', '#qsm-certificate-validate-btn', function ( event ) {
      event.preventDefault();
      var license_message = jQuery('#qsm-certificate-license-entry .qsm-validate-msg');
      license_message.html(qsm_license_validate_obj.hold);
      if( jQuery('#qsm-certificate-license-entry input[name="license_key"]').val() != "") {
        var data = {
          action: 'qsm_certificate_validate_license',
          input: jQuery('#qsm-certificate-license-entry input[name="license_key"]').val(),
          update_status: 'yes',
        };
        jQuery.post( ajaxurl, data, function ( response ) {
          response = jQuery.parseJSON(response);
          if(response.validate == 1 || response.validate == 2) {
            setTimeout(function() {
              location.reload();
            }, 2000);
          } else {
            license_message.html(qsm_license_validate_obj.invalid);
          }
        });
      } else {
        license_message.html(qsm_license_validate_obj.empty);
        jQuery('#qsm-certificate-license-entry input[name="license_key"]').focus();
      }
    });
});

/**
 * Certificate Report: one list of results with a certificate status.
 *
 * Generate (row or bulk) runs through a queue that sends one AJAX request at a
 * time, so the server renders one PDF at a time. Delete (row or bulk) uses the
 * existing delete_certificate / bulk_delete_certificates handlers.
 */
jQuery(function ($) {
    var $table = $('#qsm-certificate-report-table');
    if (!$table.length || typeof qsm_certificate_obj === 'undefined') {
        return;
    }
    var obj = qsm_certificate_obj;
    var dt = null;
    if ($.fn.DataTable) {
        dt = $table.DataTable({
            paging: true,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, obj.length_menu]],
            language: {
                paginate: { previous: "<", next: ">" },
                lengthMenu: obj.lengthMenu,
                info: obj.info,
                search: obj.search
            },
            // Columns: 0 checkbox, 1 result, 2 quiz, 3 user, 4 submitted, 5 status,
            // 6 generated, 7 expiry, 8 certificate ID, 9 action.
            order: [[1, "desc"]],
            columnDefs: [
                { targets: [0, 9], orderable: false },
                { targets: 0, width: '40px' }
            ],
            autoWidth: false
        });
    }

    var queue = [];
    var running = false;
    var stopRequested = false;
    var total = 0, done = 0, ok = 0, failed = 0;
    var failedIds = []; // failed and not yet re-queued, kept across runs
    var DELAY_MS = 300; // breathing room between PDFs on shared hosting
    var $box = $('#qsm-cert-queue');

    function fmt(str) {
        var args = Array.prototype.slice.call(arguments, 1);
        var i = 0;
        return str.replace(/%(\d+\$)?d/g, function (m, pos) {
            return pos ? args[parseInt(pos, 10) - 1] : args[i++];
        });
    }
    function rows() {
        return dt ? $(dt.rows().nodes()) : $table.find('tbody tr');
    }
    function rowFor(id) {
        return rows().filter('[data-result-id="' + id + '"]');
    }
    function refresh($row) {
        if (dt && $row.length) {
            dt.row($row[0]).invalidate('dom');
        }
    }
    function setRow(id, status, actionHtml) {
        var $row = rowFor(id);
        $row.find('.qsm-cert-status').text(status);
        if (actionHtml !== undefined) {
            $row.find('.qsm-cert-action').html(actionHtml);
        }
    }
    function actionIcons(url, file) {
        var $wrap = $('<div class="qsm-table-icons"></div>');
        $('<a target="_blank" class="qsm-view-file"></a>').attr({ href: url, title: obj.gen_view })
            .append($('<img>').attr({ src: obj.view_icon, alt: obj.gen_view })).appendTo($wrap);
        $('<button type="button" class="qsm-cert-delete"></button>').attr({ 'data-filename': file, title: 'Delete' })
            .append($('<img>').attr({ src: obj.delete_icon, alt: 'Delete' })).appendTo($wrap);
        return $wrap;
    }
    function generateButton(id, label) {
        return $('<button type="button" class="button button-small qsm-cert-generate"></button>')
            .attr('data-result-id', id).text(label || obj.generate);
    }
    function markGenerated(id, data) {
        var $row = rowFor(id);
        $row.attr({ 'data-status': 'generated', 'data-filename': data.file }).addClass('qsm-cert-done');
        $row.find('.qsm-cert-cb').prop('checked', false);
        $row.find('.qsm-cert-generated').text(data.generated);
        $row.find('.qsm-cert-expiry').text(data.expiry);
        setRow(id, obj.gen_generated, actionIcons(data.url, data.file));
        refresh($row);
    }
    function markNotGenerated($row) {
        var id = $row.attr('data-result-id');
        if (!id) {
            // A PDF with no current result: nothing left to show.
            if (dt) { dt.row($row[0]).remove().draw(false); } else { $row.remove(); }
            return;
        }
        $row.attr('data-status', 'not-generated').removeAttr('data-filename').removeClass('qsm-cert-done');
        $row.find('.qsm-cert-generated, .qsm-cert-expiry, .qsm-cert-id').html('&mdash;');
        setRow(id, obj.not_generated, generateButton(id));
        refresh($row);
    }
    function showProgress(text) {
        $box.show().find('.qsm-cert-queue-text').text(text);
    }
    function updateRetryButton() {
        var $retry = $box.find('.qsm-cert-queue-retry');
        if (!running && failedIds.length) {
            $retry.text(fmt(obj.gen_retry_all, failedIds.length)).show();
        } else {
            $retry.hide();
        }
    }
    function restoreButton(id) {
        setRow(id, rowFor(id).find('.qsm-cert-status').text(), generateButton(id, obj.gen_retry));
    }
    function finish() {
        running = false;
        $box.find('.spinner').removeClass('is-active');
        $box.find('.qsm-cert-queue-stop').hide();
        updateRetryButton();
        $box.removeClass('notice-info').addClass(failed ? 'notice-warning' : 'notice-success');
        showProgress((stopRequested ? obj.gen_stopped + ' ' : '') + fmt(obj.gen_done, ok, failed));
        queue.forEach(function (id) { setRow(id, obj.gen_stopped); restoreButton(id); });
        queue = [];
        $('#qsm-cert-bulk-apply').prop('disabled', false);
    }
    function next() {
        if (stopRequested || !queue.length) {
            finish();
            return;
        }
        var id = queue.shift();
        done++;
        showProgress(fmt(obj.gen_progress, done, total));
        setRow(id, obj.gen_running, '');
        $.post(ajaxurl, { action: 'qsm_certificate_generate_single', nonce: obj.generate_nonce, result_id: id })
            .done(function (res) {
                if (res && res.success && res.data && res.data.url) {
                    ok++;
                    markGenerated(id, res.data);
                } else {
                    failed++;
                    failedIds.push(id);
                    setRow(id, obj.gen_failed + (res && res.data && res.data.message ? ': ' + res.data.message : ''));
                    restoreButton(id);
                }
            })
            .fail(function (xhr) {
                failed++;
                failedIds.push(id);
                var msg = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message;
                setRow(id, obj.gen_failed + (msg ? ': ' + msg : ' (HTTP ' + (xhr ? xhr.status : '?') + ')'));
                restoreButton(id);
            })
            .always(function () {
                setTimeout(next, DELAY_MS);
            });
    }
    function enqueue(ids) {
        ids = ids.filter(function (id) { return queue.indexOf(id) === -1; });
        if (!ids.length) {
            return;
        }
        ids.forEach(function (id) { queue.push(id); setRow(id, obj.gen_queued, ''); });
        failedIds = failedIds.filter(function (id) { return ids.indexOf(id) === -1; });
        if (!running) {
            running = true;
            stopRequested = false;
            total = ids.length; done = 0; ok = 0; failed = 0;
            $box.removeClass('notice-success notice-warning').addClass('notice-info');
            $box.find('.spinner').addClass('is-active');
            $box.find('.qsm-cert-queue-stop').show();
            $('#qsm-cert-bulk-apply').prop('disabled', true);
            next();
        } else {
            total += ids.length;
        }
        updateRetryButton();
    }
    function checkedRows() {
        return rows().filter(function () { return $(this).find('.qsm-cert-cb').prop('checked'); });
    }

    // Row: generate.
    $table.on('click', '.qsm-cert-generate', function () {
        enqueue([String($(this).data('result-id'))]);
    });

    // Row: delete (same AJAX handler and confirm as before).
    $table.on('click', '.qsm-cert-delete', function (e) {
        e.preventDefault();
        var $row = $(this).closest('tr');
        if (!confirm(obj.delete_confirm)) {
            return;
        }
        $row.addClass('processing');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_certificate',
                file_name: $(this).data('filename'),
                security: $('#bulk_delete_certificates_nonce').val()
            },
            dataType: 'json',
            complete: function () { $row.removeClass('processing'); },
            success: function (response) {
                if (response.success) {
                    markNotGenerated($row);
                } else {
                    alert(response.data);
                }
            },
            error: function () { alert(obj.delete_error); }
        });
    });

    // Select all = every row matching the current search, across all pages.
    $('#qsm-cert-select-all').on('change', function () {
        var nodes = dt ? dt.rows({ search: 'applied' }).nodes() : $table.find('tbody tr');
        $(nodes).find('.qsm-cert-cb').prop('checked', this.checked);
    });

    // Bulk actions.
    $('#qsm-cert-bulk-apply').on('click', function () {
        var action = $('#qsm-cert-bulk-action').val();
        var $checked = checkedRows();

        if ('generate' === action) {
            var ids = $checked.filter('[data-status="not-generated"][data-result-id]').map(function () {
                return String($(this).attr('data-result-id'));
            }).get();
            if (!ids.length) {
                alert(obj.gen_none_selected);
                return;
            }
            if (!confirm(fmt(obj.gen_confirm, ids.length))) {
                return;
            }
            $('#qsm-cert-select-all').prop('checked', false);
            enqueue(ids);
        } else if ('delete' === action) {
            var files = $checked.filter('[data-filename]').map(function () {
                return $(this).attr('data-filename');
            }).get();
            if (!files.length) {
                alert(obj.no_certificate_selected);
                return;
            }
            if (!confirm(obj.bulk_delete_confirm)) {
                return;
            }
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bulk_delete_certificates',
                    certificates: files,
                    bulk_delete_certificates_nonce: $('#bulk_delete_certificates_nonce').val()
                },
                dataType: 'json',
                complete: function () { $btn.prop('disabled', false); },
                success: function (response) {
                    alert(response.data);
                    if (response.success) {
                        location.reload();
                    }
                },
                error: function () { alert(obj.bulk_delete_error); }
            });
        } else {
            alert(obj.no_action_selected);
        }
    });

    // Re-queue every failed certificate (same one-at-a-time queue).
    $box.on('click', '.qsm-cert-queue-retry', function () {
        enqueue(failedIds.slice());
    });

    $box.on('click', '.qsm-cert-queue-stop', function () {
        stopRequested = true; // the request in flight finishes, nothing new starts
    });

    $(window).on('beforeunload', function () {
        if (running) {
            return obj.gen_leave;
        }
    });
});
