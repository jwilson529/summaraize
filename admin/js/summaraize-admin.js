(function($) {
    'use strict';

    $(document).ready(function() {

        // --- Helper functions ---

        /**
         * Function to update the hidden field with the current order of points.
         */
        function updateSortedPoints() {
            var sortedPoints = [];
            $('#summaraize-points-list input[type="text"]').each(function() {
                sortedPoints.push($(this).val()); // Push even empty values
            });
            $('#summaraize_points_sorted').val(JSON.stringify(sortedPoints));
        }

        /**
         * Function to get editor data from Gutenberg or Classic editor.
         * @returns {{title: *, content: *, tags: *}}
         */
        function getEditorData() {
            var title, content, tags;
            if ($('#editor').length) {
                // Gutenberg
                title = wp.data.select('core/editor').getEditedPostAttribute('title');
                content = wp.data.select('core/editor').getEditedPostContent();
                tags = wp.data.select('core/editor').getEditedPostAttribute('tags').join(', ');
            } else {
                // Classic editor
                title = $('input#title').val();
                content = $('textarea#content').val();
                tags = $('input[name="tax_input[post_tag]"]').val();
            }
            return { title, content, tags };
        }

        /**
         * Function to auto-save field values.A
         * @param $field
         * @param value
         */
        function autoSaveField($field, value = null) {
            var fieldName, fieldValue, inputName; 
            if (typeof $field === 'string') {
                fieldName = $field;
                fieldValue = value;
                // If $field is a string, we don't have the input element to get its name
                inputName = fieldName; // Use the field name as a fallback
            } else {
                $field = $($field);
                fieldName = $field.attr('name');

                // Get the label HTML associated with the input field
                inputName = $field.closest('tr').find('th').text() || fieldName;

                if ($field.attr('type') === 'checkbox') {
                    fieldValue = [];
                    $('input[name="' + fieldName + '"]:checked').each(function() {
                        fieldValue.push($(this).val());
                    });
                } else {
                    fieldValue = $field.val();
                }
            }

            addSpinnerWithMessage($field, 'Updating ' + inputName + '...'); // Add spinner with message

            $.ajax({
                url: summaraize_admin_vars.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'summaraize_auto_save',
                    nonce: summaraize_admin_vars.summaraize_ajax_nonce,
                    post_id: summaraize_admin_vars.post_id,
                    field_name: fieldName,
                    field_value: fieldValue
                }
            })
            .done(function(response) {
                if (response.success) {
                    showNotification(response.data.message);
                    if (response.data.refresh) {
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                } else {
                    showNotification(response.data.message, 'error');
                }
            })
            .fail(function() {
                showNotification('Error saving field.', 'error');
            })
            .always(function() {
                removeSpinnerWithMessage($field); // Remove the spinner in the always() callback
            });
        }

        /**
         * Function to show notification popups.
         * @param message
         * @param type
         */
        function showNotification(message, type = 'success') {
            var $notification = $('<div class="summaraize-notification ' + type + '">' + message + '</div>');
            $('body').append($notification);
            $notification.fadeIn('fast');
            setTimeout(function() {
                $notification.fadeOut('slow', function() {
                    $notification.remove();
                });
            }, 2000);
        }

        /**
         * Add a spinner with a message below the input field.
         * @param $field
         * @param message
         */
        function addSpinnerWithMessage($field, message) {
            $field.siblings('.summaraize-spinner-container').remove();
            const spinnerContainer = $('<div class="summaraize-spinner-container"></div>');
            const spinner = $('<div class="summaraize-spinner"></div>');
            const spinnerMessage = $('<span class="summaraize-spinner-message"></span>').text(message);
            spinnerContainer.append(spinner).append(spinnerMessage);
            $field.after(spinnerContainer);
            spinnerContainer.fadeIn('fast');
        }

        /**
         * Remove the spinner and message from the input field.
         * @param $field
         */
        function removeSpinnerWithMessage($field) {
            $field.siblings('.summaraize-spinner-container').fadeOut('slow', function() {
                $(this).remove();
            });
        }

        /**
         * Toggle settings fields based on display mode.
         */
        function toggleSettingsFields() {
            var displayMode = $('#summaraize_display_position').val();
            if (displayMode === 'popup') {
                $('#summaraize_button_style, #summaraize_button_color').closest('tr').show();
                $('#summaraize_display_mode').closest('tr').hide();
            } else {
                $('#summaraize_button_style, #summaraize_button_color').closest('tr').hide();
                $('#summaraize_display_mode').closest('tr').show();
            }
        }

        /**
         * Toggle override settings fields based on view mode.
         */
        function toggleOverrideFields() {
            var displayMode = $('#summaraize_view').val();
            if (displayMode === 'popup') {
                $('.button-style-wrapper, .button-style-description, .button-color-wrapper, .button-color-description').show();
            } else {
                $('.button-style-wrapper, .button-style-description, .button-color-wrapper, .button-color-description').hide();
            }
        }

        /**
         * Debounce function to limit the rate of function execution.
         * @param func
         * @param wait
         * @returns {(function(): void)|*}
         */
        function debounce(func, wait) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        }

        // --- Event handlers ---

        // Regenerate Assistant ID
        $(document).on('click', '#summariaze_create_assistant', function(event) {
            event.preventDefault();
            $('#summaraize_assistant_id').val('');
            autoSaveField($('#summaraize_assistant_id'));
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        });

        // Remove a point from the list
        $('.remove-point').click(function() {
            var pointInputId = $(this).data('point-id');
            $('#' + pointInputId).val('');
            updateSortedPoints();
        });

        // Make points list sortable
        if ($('#summaraize-points-list').length) {
            $('#summaraize-points-list').sortable({
                handle: ".dashicons-menu",
                animation: 150,
                stop: function() {
                    updateSortedPoints();
                }
            });
        }

        // Handle tab switching
        $('.nav-tab-wrapper a').click(function(e) {
            e.preventDefault();
            $('.nav-tab-wrapper a').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('.tab-content').hide();
            $($(this).attr('href')).show();
        });

        // Set initial tab visibility
        $('#main-settings').show();
        $('#advanced-settings').hide();

        // Show/hide custom prompt field
        $('#summaraize_prompt_type').on('change', function() {
            var selectedValue = $(this).val();
            if (selectedValue === 'custom') {
                $('#summaraize_custom_prompt_row').show();
                $('#summaraize_custom_prompt_custom').css('display', 'block');
            } else {
                $('#summaraize_custom_prompt_row').hide();
                $('#summaraize_custom_prompt_custom').val('');
                autoSaveField($('#summaraize_custom_prompt_custom'));
            }
        });
        $('#summaraize_prompt_type').trigger('change');

        // Handle "Generate Top 5 Points" button click
        $(document).on('click', '#generate-summaraize-button', function(event) {
            event.preventDefault();
            var $button = $(this);
            $button.prop('disabled', true);
            var $spinner = $button.find('.summaraize-spinner');
            $spinner.css({
                display: 'inline-block',
                width: '16px',
                height: '16px',
                border: '2px solid #f3f3f3',
                borderTop: '2px solid #0073aa',
                borderRadius: '50%',
                animation: 'summaraize-spin 1s linear infinite',
                marginRight: '8px'
            });
            var editorData = getEditorData();
            $.ajax({
                    url: summaraize_admin_vars.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'summaraize_gather_content',
                        nonce: summaraize_admin_vars.summaraize_ajax_nonce,
                        title: editorData.title,
                        tags: editorData.tags || '',
                        content: editorData.content,
                    }
                })
                .done(function(response) {
                    console.log( "AJAX response:", response ); 
                    $button.prop('disabled', false);
                    $spinner.hide();
                    $button.text('Generate Top 5 Points');
                    if (response.success && response.data.points) {
                        response.data.points.forEach(function(point) {
                            var inputField = $('#summaraize_points_' + point.index);
                            if (inputField.length) {
                                inputField.val(point.text).change();
                            }
                        });
                    }
                })
                .fail(function() {
                    $button.prop('disabled', false);
                    $spinner.hide();
                    $button.text('Generate Top 5 Points');
                });
        });

        // Toggle settings fields based on display mode
        toggleSettingsFields();
        $('#summaraize_display_position').change(toggleSettingsFields);

        // Toggle override settings fields based on view mode
        toggleOverrideFields();
        $('#summaraize_view').change(toggleOverrideFields);

        // Toggle visibility of override options
        $('#summaraize_override_settings').change(function() {
            $('#summaraize_override_options').toggle($(this).is(':checked'));
        });

        // Validate API key with debounce
        const apiKeyField = $('input[name="summaraize_openai_api_key"]');
        apiKeyField.on('input paste', debounce(function() {
            const apiKey = $(this).val();
            addSpinnerWithMessage(apiKeyField, 'Validating API key...');
            $.ajax({
                    url: summaraize_admin_vars.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'summaraize_ajax_validate_openai_api_key',
                        nonce: summaraize_admin_vars.summaraize_ajax_nonce,
                        api_key: apiKey
                    }
                })
                .done(function(validationResponse) {
                    if (validationResponse.success) {
                        autoSaveField(apiKeyField);
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotification(validationResponse.data.message || 'Invalid API key.', 'error');
                    }
                })
                .fail(function() {
                    showNotification('Error validating API key.', 'error');
                })
                .always(function() {
                    removeSpinnerWithMessage(apiKeyField);
                });
        }, 500));

        // Validate Gemini API key with debounce
        const geminiApiKeyField = $('input[name="summaraize_google_gemini_api_key"]');
        geminiApiKeyField.on('input paste', debounce(function() {
            const apiKey = $(this).val();
            addSpinnerWithMessage(geminiApiKeyField, 'Validating API key...');
            $.ajax({
                url: summaraize_admin_vars.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'summaraize_ajax_validate_google_gemini_api_key', // Updated action
                    nonce: summaraize_admin_vars.summaraize_ajax_nonce,
                    api_key: apiKey
                }
            })
            .done(function(validationResponse) {
                if (validationResponse.success) {
                    autoSaveField(geminiApiKeyField);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(validationResponse.data.message || 'Invalid API key.', 'error');
                }
            })
            .fail(function() {
                showNotification('Error validating API key.', 'error');
            })
            .always(function() {
                removeSpinnerWithMessage(geminiApiKeyField);
            });
        }, 500));

        // Auto-save settings fields
        $(document).on('input change', '.summaraize-settings-field', function() {
            autoSaveField($(this));
        });

        // Initialize auto-save
        function initializeAutoSave() {
            $('.summaraize-settings-form').find('input, select, textarea').on('input change', debounce(function() {
                autoSaveField($(this));
            }, 500));
        }
        initializeAutoSave();

    });
})(jQuery);