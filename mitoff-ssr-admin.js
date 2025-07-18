jQuery(document).ready(function($) {
    
    let searchResults = null;
    
    // Gestione del form di ricerca
    $('#ssr-form').on('submit', function(e) {
        e.preventDefault();
        performSearch();
    });
    
    // Gestione del pulsante sostituzione
    $('#replace-btn').on('click', function() {
        performReplace();
    });
    
    // Gestione dei pulsanti esempio
    $('.ssr-use-example').on('click', function() {
        const searchText = $(this).data('search');
        const replaceText = $(this).data('replace');
        const useRegex = $(this).data('regex') === 1;
        
        // Decodifica HTML entities
        const decodedSearch = $('<div>').html(searchText).text();
        const decodedReplace = $('<div>').html(replaceText).text();
        
        // Popola i campi
        $('#search_text').val(decodedSearch);
        $('#replace_text').val(decodedReplace);
        $('#use_regex').prop('checked', useRegex);
        
        // Feedback visivo
        $(this).text('✅ Esempio caricato!').prop('disabled', true);
        setTimeout(() => {
            $(this).text('Usa questo esempio').prop('disabled', false);
        }, 2000);
        
        // Scorri al form
        $('html, body').animate({
            scrollTop: $('#ssr-form').offset().top - 100
        }, 500);
    });
    
    // Caricamento meta_key dinamico
    $('#database_table').on('change', function() {
        const table = $(this).val();
        $('#ssr-meta-key-row').remove();
        if (!table) return;
        // Mostra loading per meta_key
        const loadingRow = `<tr id="ssr-meta-key-row"><th scope="row">Meta Key:</th><td><span id="ssr-meta-key-loading">Caricamento...</span></td></tr>`;
        // Inserisci DOPO la riga della tabella database, non alla fine
        $('#database_table').closest('tr').after(loadingRow);
        $.ajax({
            url: ssr_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ssr_get_meta_keys',
                database_table: table,
                nonce: ssr_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.meta_keys && response.data.meta_keys.length > 0) {
                    let select = `<select id="meta_key" name="meta_key" class="regular-text"><option value="">-- Tutti --</option>`;
                    response.data.meta_keys.forEach(function(key) {
                        select += `<option value="${escapeHtml(key)}">${escapeHtml(key)}</option>`;
                    });
                    select += '</select>';
                    $('#ssr-meta-key-row td').html(select + '<p class="description">Filtra per meta_key (opzionale)</p>');
                } else {
                    $('#ssr-meta-key-row').remove();
                }
            },
            error: function() {
                $('#ssr-meta-key-row').remove();
            }
        });
    });

    // Trigger iniziale se già selezionato
    if ($('#database_table').val()) {
        $('#database_table').trigger('change');
    }
    
    /**
     * Esegue la ricerca
     */
    function performSearch() {
        const searchText = $('#search_text').val().trim();
        const replaceText = $('#replace_text').val().trim();
        const useRegex = $('#use_regex').is(':checked');
        const databaseTable = $('#database_table').val();
        const metaKey = $('#meta_key').length ? $('#meta_key').val() : '';
        
        if (!searchText) {
            alert('Il pattern di ricerca è obbligatorio!');
            return;
        }
        
        // Mostra loading
        showLoading();
        hideResults();
        
        // AJAX request
        $.ajax({
            url: ssr_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ssr_search',
                search_text: searchText,
                replace_text: replaceText,
                use_regex: useRegex ? '1' : '0',
                database_table: databaseTable,
                meta_key: metaKey,
                nonce: ssr_ajax.nonce
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    searchResults = response.data;
                    displaySearchResults(response.data);
                } else {
                    alert('Errore: ' + response.data);
                }
            },
            error: function() {
                hideLoading();
                alert('Errore di comunicazione con il server');
            }
        });
    }
    
    /**
     * Esegue la sostituzione
     */
    function performReplace() {
        if (!searchResults) {
            alert('Devi prima eseguire una ricerca!');
            return;
        }
        
        if (!confirm('Sei sicuro di voler procedere con la sostituzione? Questa operazione è irreversibile!')) {
            return;
        }
        
        // Mostra loading
        showLoading();
        $('#replace-btn').prop('disabled', true);
        
        // AJAX request
        $.ajax({
            url: ssr_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ssr_replace',
                search_text: searchResults.search_text,
                replace_text: searchResults.replace_text,
                use_regex: searchResults.use_regex ? '1' : '0',
                database_table: searchResults.database_table,
                meta_key: $('#meta_key').length ? $('#meta_key').val() : '',
                nonce: ssr_ajax.nonce
            },
            success: function(response) {
                hideLoading();
                $('#replace-btn').prop('disabled', false);
                
                if (response.success) {
                    displayReplaceResults(response.data);
                    $('#replace-btn').hide();
                } else {
                    alert('Errore: ' + response.data);
                }
            },
            error: function() {
                hideLoading();
                $('#replace-btn').prop('disabled', false);
                alert('Errore di comunicazione con il server');
            }
        });
    }
    
    /**
     * Mostra i risultati della ricerca
     */
    function displaySearchResults(data) {
        const $results = $('#ssr-results');
        const $summary = $('#search-summary');
        const $details = $('#search-details');
        
        // Per ricerca e sostituzione, non fare escape se non è regex
        const searchTextDisplay = data.use_regex ? escapeHtml(data.search_text) : data.search_text;
        const replaceTextDisplay = data.use_regex ? escapeHtml(data.replace_text) : data.replace_text;
        
        // Summary
        $summary.html(`
            <div class="ssr-summary-box">
                <h3>📊 Riepilogo ricerca</h3>
                <p><strong>Tabella database:</strong> <code>${escapeHtml(data.database_table)}</code></p>
                <p><strong>Pattern cercato:</strong> <code>${searchTextDisplay}</code></p>
                <p><strong>Sostituirà con:</strong> <code>${replaceTextDisplay}</code></p>
                <p><strong>Modalità regex:</strong> ${data.use_regex ? 'Sì' : 'No'}</p>
                <p><strong>Record trovati:</strong> ${data.total_records}</p>
                <p><strong>Occorrenze totali:</strong> ${data.total_occurrences}</p>
                ${data.debug_info ? `
                <hr style="margin: 15px 0;">
                <h4>🔍 Debug Info</h4>
                <p><strong>Testo originale:</strong> <code>${data.debug_info.original_search_text}</code></p>
                <p><strong>Pattern SQL LIKE:</strong> <code>${data.debug_info.escaped_like_pattern}</code></p>
                <p><strong>Risultati query SQL:</strong> ${data.debug_info.sql_results_count}</p>
                <details>
                    <summary>Query SQL completa</summary>
                    <pre style="background: #f0f0f0; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px;">${escapeHtml(data.debug_info.sql_query)}</pre>
                </details>
                ` : ''}
            </div>
        `);
        
        // Details
        if (data.matches.length > 0) {
            let detailsHtml = '<h4>📋 Dettagli dei record trovati:</h4>';
            detailsHtml += '<div class="ssr-table-container">';
            detailsHtml += '<table class="wp-list-table widefat fixed striped">';
            detailsHtml += '<thead><tr>';
            
            // Header dinamico basato sulla struttura della tabella
            const structure = data.table_structure;
            detailsHtml += `<th>${escapeHtml(structure.primary_key)}</th>`;
            
            structure.display_fields.forEach(function(field) {
                if (field !== structure.primary_key) {
                    let fieldLabel = field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    detailsHtml += `<th>${escapeHtml(fieldLabel)}</th>`;
                }
            });
            
            if (data.matches.some(match => match.post_title)) {
                detailsHtml += '<th>Titolo Post</th>';
            }
            
            detailsHtml += '<th>Occorrenze</th>';
            detailsHtml += '</tr></thead><tbody>';
            
            data.matches.forEach(function(match) {
                detailsHtml += '<tr>';
                detailsHtml += `<td>${match.primary_id}</td>`;
                
                structure.display_fields.forEach(function(field) {
                    if (field !== structure.primary_key && match.hasOwnProperty(field)) {
                        let value = match[field];
                        if (field.includes('key') || field.includes('name')) {
                            value = `<code>${escapeHtml(value)}</code>`;
                        } else {
                            value = escapeHtml(value || 'N/A');
                        }
                        detailsHtml += `<td>${value}</td>`;
                    }
                });
                
                if (match.post_title) {
                    detailsHtml += `<td>${escapeHtml(match.post_title)}</td>`;
                }
                
                detailsHtml += `<td><span class="ssr-count">${match.occurrences}</span></td>`;
                detailsHtml += '</tr>';
            });
            
            detailsHtml += '</tbody></table></div>';
            $details.html(detailsHtml);
            
            // Mostra il pulsante sostituisci
            $('#replace-btn').show();
        } else {
            $details.html('<p class="ssr-no-results">🚫 Nessun risultato trovato</p>');
            $('#replace-btn').hide();
        }
        
        $results.show();
        
        // Scroll alla sezione risultati
        $('html, body').animate({
            scrollTop: $results.offset().top - 100
        }, 500);
    }
    
    /**
     * Mostra i risultati della sostituzione
     */
    function displayReplaceResults(data) {
        const $results = $('#ssr-replace-results');
        const $summary = $('#replace-summary');
        const $details = $('#replace-details');
        
        // Summary
        $summary.html(`
            <div class="ssr-summary-box ssr-success">
                <h3>✅ Sostituzione completata!</h3>
                <p><strong>Record aggiornati:</strong> ${data.updated_records}</p>
                <p><strong>Sostituzioni totali:</strong> ${data.total_replacements}</p>
            </div>
        `);
        
        // Details
        if (data.details.length > 0) {
            let detailsHtml = '<h4>📋 Dettagli delle sostituzioni:</h4>';
            detailsHtml += '<div class="ssr-table-container">';
            detailsHtml += '<table class="wp-list-table widefat fixed striped">';
            detailsHtml += '<thead><tr>';
            
            // Header dinamico basato sui campi disponibili
            const firstDetail = data.details[0];
            const fields = Object.keys(firstDetail).filter(key => key !== 'replacements');
            
            fields.forEach(function(field) {
                let fieldLabel = field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                detailsHtml += `<th>${escapeHtml(fieldLabel)}</th>`;
            });
            
            detailsHtml += '<th>Sostituzioni</th>';
            detailsHtml += '</tr></thead><tbody>';
            
            data.details.forEach(function(detail) {
                detailsHtml += '<tr>';
                
                fields.forEach(function(field) {
                    if (detail.hasOwnProperty(field)) {
                        let value = detail[field];
                        if (field.includes('key') || field.includes('name')) {
                            value = `<code>${escapeHtml(value)}</code>`;
                        } else {
                            value = escapeHtml(value || 'N/A');
                        }
                        detailsHtml += `<td>${value}</td>`;
                    }
                });
                
                detailsHtml += `<td><span class="ssr-count ssr-success-count">${detail.replacements}</span></td>`;
                detailsHtml += '</tr>';
            });
            
            detailsHtml += '</tbody></table></div>';
            $details.html(detailsHtml);
        }
        
        $results.show();
        
        // Scroll alla sezione risultati
        $('html, body').animate({
            scrollTop: $results.offset().top - 100
        }, 500);
    }
    
    /**
     * Mostra il loading
     */
    function showLoading() {
        $('#ssr-loading').show();
        $('#search-btn').prop('disabled', true).text('⏳ Elaborazione...');
    }
    
    /**
     * Nasconde il loading
     */
    function hideLoading() {
        $('#ssr-loading').hide();
        $('#search-btn').prop('disabled', false).text('🔍 Cerca');
    }
    
    /**
     * Nasconde i risultati
     */
    function hideResults() {
        $('#ssr-results').hide();
        $('#ssr-replace-results').hide();
    }
    
    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
}); 