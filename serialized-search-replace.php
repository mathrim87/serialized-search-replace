<?php
/**
 * Plugin Name: Serialized Search & Replace
 * Description: Plugin per cercare e sostituire testo in dati serializzati nella tabella postmeta
 * Version: 1.1.7
 * Author: mitoff
 * Text Domain: serialized-search-replace
 * Domain Path: /languages
 * Update URI: https://github.com/mathrim87/serialized-search-replace
 */

// Impedisce l'accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

define('SSR_VERSION', '1.1.7');
define('SSR_PLUGIN_FILE', __FILE__);
define('SSR_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once SSR_PLUGIN_DIR . 'salus/salus-admin-menu.php';
if ( is_admin() ) {
	require_once SSR_PLUGIN_DIR . 'salus/class-ssr-update-checker.php';
	SSR_Update_Checker::init();
}

class SerializedSearchReplace {

    const BATCH_SIZE = 200;
    const PCRE_BACKTRACK_LIMIT = 100000;
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 99);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_ssr_search', array($this, 'ajax_search'));
        add_action('wp_ajax_ssr_replace', array($this, 'ajax_replace'));
        add_action('wp_ajax_ssr_get_meta_keys', array($this, 'ajax_get_meta_keys'));
    }
    
    /**
     * Aggiunge la voce di menu nell'admin
     */
    public function add_admin_menu() {
        Salus_Admin_Menu::register_submenu(
            'Serialized Search & Replace',
            'Search & Replace',
            'manage_options',
            'serialized-search-replace',
            array($this, 'admin_page'),
            'serialized-search-replace'
        );
    }
    
    /**
     * Carica CSS e JavaScript
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'salus_page_serialized-search-replace') {
            return;
        }
        
        $js_url = plugin_dir_url(__FILE__) . 'assets/mitoff-ssr-admin.js';
        $css_url = plugin_dir_url(__FILE__) . 'assets/mitoff-ssr-admin.css';
        
        wp_enqueue_script('mitoff-ssr-admin', $js_url, array('jquery'), SSR_VERSION, true);
        wp_enqueue_style('mitoff-ssr-admin', $css_url, array(), SSR_VERSION);
        
        wp_localize_script('mitoff-ssr-admin', 'ssr_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ssr_nonce')
        ));
    }
    
    /**
     * Pagina di amministrazione
     */
    public function admin_page() {
        global $wpdb;
        
        // Ottieni lista delle tabelle del database
        $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
        $table_options = array();
        foreach ($tables as $table) {
            $table_name = $table[0];
            // Mostra solo tabelle che potrebbero contenere dati serializzati
            if (strpos($table_name, 'meta') !== false || strpos($table_name, 'options') !== false) {
                $table_options[] = $table_name;
            }
        }
        ?>
        <div class="wrap">
            <h1>
                🔍 Serialized Search & Replace
                <span class="salus-plugin-version" style="font-size: 0.8em; color: #646970; font-weight: normal;">
                    - v<?php echo esc_html( SSR_VERSION ); ?>
                </span>
            </h1>
            <div class="ssr-container">
                <div class="ssr-warning">
                    <p><strong>⚠️ ATTENZIONE:</strong> Fai sempre un backup del database prima di procedere con le sostituzioni!</p>
                </div>
                
                <!-- Sezione Esempi -->
                <div class="ssr-examples">
                    <h2>📚 Esempi di utilizzo con Regex</h2>
                    <div class="ssr-examples-grid">
                        <div class="ssr-example-card">
                            <h3>🔧 Riparare tag BR malformati</h3>
                            <p><strong>Cerca:</strong> <code>(?&lt;!&lt;)br /(?!&gt;)</code></p>
                            <p><strong>Sostituisci:</strong> <code>&lt;br /&gt;</code></p>
                            <p><em>Trova "br /" che non è già dentro "&lt;br /&gt;"</em></p>
                            <button type="button" class="button ssr-use-example" data-search="(?&lt;!&lt;)br /(?!&gt;)" data-replace="&lt;br /&gt;" data-regex="1">Usa questo esempio</button>
                        </div>
                        
                        <div class="ssr-example-card">
                            <h3>🔗 Aggiornare URL HTTP a HTTPS</h3>
                            <p><strong>Cerca:</strong> <code>http://(?!.*https://)</code></p>
                            <p><strong>Sostituisci:</strong> <code>https://</code></p>
                            <p><em>Converte URL HTTP in HTTPS (senza duplicare)</em></p>
                            <button type="button" class="button ssr-use-example" data-search="http://(?!.*https://)" data-replace="https://" data-regex="1">Usa questo esempio</button>
                        </div>
                        
                        <div class="ssr-example-card">
                            <h3>📝 Sostituire testo semplice</h3>
                            <p><strong>Cerca:</strong> <code>vecchio-dominio.com</code></p>
                            <p><strong>Sostituisci:</strong> <code>nuovo-dominio.com</code></p>
                            <p><em>Sostituzione diretta senza regex</em></p>
                            <button type="button" class="button ssr-use-example" data-search="vecchio-dominio.com" data-replace="nuovo-dominio.com" data-regex="0">Usa questo esempio</button>
                        </div>
                        
                        <div class="ssr-example-card">
                            <h3>🎨 Rimuovere attributi style inline</h3>
                            <p><strong>Cerca:</strong> <code>style="[^"]*"</code></p>
                            <p><strong>Sostituisci:</strong> <code></code> (vuoto)</p>
                            <p><em>Rimuove tutti gli attributi style inline</em></p>
                            <button type="button" class="button ssr-use-example" data-search="style=\"[^\"]*\"" data-replace="" data-regex="1">Usa questo esempio</button>
                        </div>
                    </div>
                </div>
                
                <form id="ssr-form" class="ssr-form">
                    <h3>🛠️ Configurazione ricerca e sostituzione</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="database_table">Tabella database:</label>
                            </th>
                            <td>
                                <select id="database_table" name="database_table" class="regular-text">
                                    <?php foreach (
                                        $table_options as $table): ?>
                                        <option value="<?php echo esc_attr($table); ?>" <?php selected($table, $wpdb->postmeta); ?>>
                                            <?php echo esc_html($table); ?>
                                            <?php if ($table === $wpdb->postmeta): ?> (predefinita)<?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">Seleziona la tabella in cui cercare i dati serializzati</p>
                            </td>
                        </tr>
                        <!-- Placeholder per meta_key, verrà popolato da JS se disponibile -->
                        <tr id="ssr-meta-key-row" style="display:none;">
                            <th scope="row">Meta Key:</th>
                            <td>
                                <span id="ssr-meta-key-loading">Caricamento...</span>
                                <p class="description">Filtra per meta_key (opzionale, solo se disponibile per la tabella selezionata)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="search_text">Pattern di ricerca:</label>
                            </th>
                            <td>
                                <input type="text" id="search_text" name="search_text" class="regular-text" placeholder="es: (?&lt;!&lt;)br /(?!&gt;)" required />
                                <p class="description">Inserisci il testo o pattern regex da cercare nei dati serializzati</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="replace_text">Testo sostitutivo:</label>
                            </th>
                            <td>
                                <input type="text" id="replace_text" name="replace_text" class="regular-text" placeholder="es: &lt;br /&gt;" />
                                <p class="description">Inserisci il testo sostitutivo (può essere vuoto per rimuovere)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="use_regex">Modalità espressione regolare:</label>
                            </th>
                            <td>
                                <input type="checkbox" id="use_regex" name="use_regex" value="1" checked />
                                <label for="use_regex">Usa espressione regolare (PCRE)</label>
                                <p class="description">Se disabilitato, verrà fatta una ricerca letterale del testo</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary" id="search-btn">
                            🔍 Cerca
                        </button>
                    </p>
                </form>
                
                <div id="ssr-results" class="ssr-results" style="display: none;">
                    <h2>Risultati della ricerca</h2>
                    <div id="search-summary" class="ssr-summary"></div>
                    <div id="search-details" class="ssr-details"></div>
                    
                    <div class="ssr-actions">
                        <button type="button" class="button button-secondary" id="replace-btn" style="display: none;">
                            🔄 Procedi con la sostituzione
                        </button>
                    </div>
                </div>
                
                <div id="ssr-replace-results" class="ssr-replace-results" style="display: none;">
                    <h2>Report sostituzione</h2>
                    <div id="replace-summary" class="ssr-summary"></div>
                    <div id="replace-details" class="ssr-details"></div>
                </div>
                
                <div id="ssr-loading" class="ssr-loading" style="display: none;">
                    <p>⏳ Elaborazione in corso...</p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Ricerca
     */
    public function ajax_search() {
        check_ajax_referer('ssr_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Accesso negato');
        }

        $params = $this->parse_request_params();

        if (empty($params['search_text'])) {
            wp_send_json_error('Il pattern di ricerca non può essere vuoto');
        }

        global $wpdb;

        if (!$this->is_valid_table($params['database_table'])) {
            wp_send_json_error('Tabella database non valida');
        }

        if ($params['use_regex']) {
            $regex_check = $this->validate_regex_pattern($params['search_text']);
            if (is_wp_error($regex_check)) {
                wp_send_json_error($regex_check->get_error_message());
            }
        }

        try {
            $table_structure = $this->get_table_structure($params['database_table']);
            if (!$table_structure) {
                wp_send_json_error('Impossibile determinare la struttura della tabella');
            }

            $sql = $this->build_search_query(
                $params['database_table'],
                $table_structure,
                $params['search_text'],
                $params['meta_key'],
                $params['use_regex'],
                $params['batch_size'],
                $params['offset']
            );

            if (is_wp_error($sql)) {
                wp_send_json_error($sql->get_error_message());
            }

            $debug_info = array(
                'original_search_text' => $params['search_text'],
                'sql_query'            => $sql,
            );

            $results = $wpdb->get_results($sql);
            $debug_info['sql_results_count'] = count($results);

            $matches           = array();
            $batch_occurrences = 0;

            foreach ($results as $row) {
                $serialized_field  = $table_structure['serialized_field'];
                $unserialized_data = $this->safe_unserialize($row->{$serialized_field});

                if ($unserialized_data === false) {
                    continue;
                }

                $occurrences = $this->count_occurrences_in_data(
                    $unserialized_data,
                    $params['search_text'],
                    $params['use_regex']
                );

                if ($occurrences > 0) {
                    $match_data = array(
                        'primary_id'  => $row->{$table_structure['primary_key']},
                        'occurrences' => $occurrences,
                    );

                    foreach ($table_structure['display_fields'] as $field) {
                        if (isset($row->{$field})) {
                            $match_data[$field] = $row->{$field};
                        }
                    }

                    if (isset($row->post_id)) {
                        $match_data['post_title'] = get_the_title($row->post_id);
                    }

                    $matches[] = $match_data;
                    $batch_occurrences += $occurrences;
                }
            }

            $batch_size = $params['batch_size'];
            $has_more   = count($results) === $batch_size;

            wp_send_json_success(array(
                'matches'           => $matches,
                'batch_matches'     => count($matches),
                'batch_occurrences' => $batch_occurrences,
                'has_more'          => $has_more,
                'next_offset'       => $params['offset'] + $batch_size,
                'search_text'       => $params['search_text'],
                'replace_text'      => $params['replace_text'],
                'use_regex'         => $params['use_regex'],
                'database_table'    => $params['database_table'],
                'table_structure'   => $table_structure,
                'debug_info'        => $debug_info,
            ));

        } catch (Exception $e) {
            wp_send_json_error('Errore durante la ricerca: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Sostituzione
     */
    public function ajax_replace() {
        check_ajax_referer('ssr_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Accesso negato');
        }

        $params = $this->parse_request_params();

        if (empty($params['search_text'])) {
            wp_send_json_error('Il pattern di ricerca non può essere vuoto');
        }

        global $wpdb;

        if (!$this->is_valid_table($params['database_table'])) {
            wp_send_json_error('Tabella database non valida');
        }

        if ($params['use_regex']) {
            $regex_check = $this->validate_regex_pattern($params['search_text']);
            if (is_wp_error($regex_check)) {
                wp_send_json_error($regex_check->get_error_message());
            }
        }

        try {
            $table_structure = $this->get_table_structure($params['database_table']);
            if (!$table_structure) {
                wp_send_json_error('Impossibile determinare la struttura della tabella');
            }

            $sql = $this->build_search_query(
                $params['database_table'],
                $table_structure,
                $params['search_text'],
                $params['meta_key'],
                $params['use_regex'],
                $params['batch_size'],
                $params['offset']
            );

            if (is_wp_error($sql)) {
                wp_send_json_error($sql->get_error_message());
            }

            $results             = $wpdb->get_results($sql);
            $updated_records     = 0;
            $total_replacements  = 0;
            $details             = array();

            foreach ($results as $row) {
                $serialized_field  = $table_structure['serialized_field'];
                $unserialized_data = $this->safe_unserialize($row->{$serialized_field});

                if ($unserialized_data === false) {
                    continue;
                }

                $replacements = $this->replace_in_data(
                    $unserialized_data,
                    $params['search_text'],
                    $params['replace_text'],
                    $params['use_regex']
                );

                if ($replacements > 0) {
                    $new_serialized_data = serialize($unserialized_data);

                    $update_result = $wpdb->update(
                        $params['database_table'],
                        array($serialized_field => $new_serialized_data),
                        array($table_structure['primary_key'] => $row->{$table_structure['primary_key']}),
                        array('%s'),
                        array('%d')
                    );

                    if ($update_result !== false) {
                        $updated_records++;
                        $total_replacements += $replacements;

                        $detail_data = array(
                            'primary_id'   => $row->{$table_structure['primary_key']},
                            'replacements' => $replacements,
                        );

                        foreach ($table_structure['display_fields'] as $field) {
                            if (isset($row->{$field})) {
                                $detail_data[$field] = $row->{$field};
                            }
                        }

                        if (isset($row->post_id)) {
                            $detail_data['post_title'] = get_the_title($row->post_id);
                        }

                        $details[] = $detail_data;
                    }
                }
            }

            $batch_size = $params['batch_size'];
            $has_more   = count($results) === $batch_size;

            wp_send_json_success(array(
                'updated_records'    => $updated_records,
                'total_replacements' => $total_replacements,
                'details'            => $details,
                'has_more'           => $has_more,
                'next_offset'        => $params['offset'] + $batch_size,
            ));

        } catch (Exception $e) {
            wp_send_json_error('Errore durante la sostituzione: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Restituisce i meta_key disponibili per la tabella selezionata
     */
    public function ajax_get_meta_keys() {
        check_ajax_referer('ssr_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Accesso negato');
        }
        $database_table = sanitize_text_field($_POST['database_table']);
        global $wpdb;
        if (!$this->is_valid_table($database_table)) {
            wp_send_json_error('Tabella database non valida');
        }
        // Cerca se esiste il campo meta_key
        $columns = $wpdb->get_col($wpdb->prepare(
            "SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $database_table
        ));
        if (!in_array('meta_key', $columns)) {
            wp_send_json_success(array('meta_keys' => array()));
        }
        $meta_keys = $wpdb->get_col("SELECT DISTINCT meta_key FROM `$database_table` ORDER BY meta_key ASC");
        wp_send_json_success(array('meta_keys' => $meta_keys));
    }
    
    /**
     * Legge e normalizza i parametri comuni delle richieste AJAX.
     */
    private function parse_request_params() {
        $batch_size = isset($_POST['batch_size']) ? (int) $_POST['batch_size'] : self::BATCH_SIZE;
        $batch_size = max(1, min(self::BATCH_SIZE, $batch_size));

        return array(
            'use_regex'      => isset($_POST['use_regex']) && $_POST['use_regex'] === '1',
            'search_text'    => trim(stripslashes($_POST['search_text'])),
            'replace_text'   => trim(stripslashes($_POST['replace_text'])),
            'database_table' => sanitize_text_field($_POST['database_table']),
            'meta_key'       => isset($_POST['meta_key']) ? sanitize_text_field($_POST['meta_key']) : '',
            'offset'         => isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0,
            'batch_size'     => $batch_size,
        );
    }

    /**
     * Deserializza in modo sicuro (nessuna istanziazione di oggetti PHP).
     */
    private function safe_unserialize($raw) {
        if (!is_string($raw) || !is_serialized($raw)) {
            return false;
        }

        return @unserialize(trim($raw), array('allowed_classes' => false));
    }

    /**
     * Valida la sintassi di un pattern regex prima dell'uso.
     */
    private function validate_regex_pattern($pattern) {
        if ($pattern === '') {
            return new WP_Error('ssr_empty_regex', 'Il pattern regex non può essere vuoto.');
        }

        set_error_handler(static function () {}, E_WARNING);
        $result = @preg_match('/' . str_replace('/', '\/', $pattern) . '/', '');
        restore_error_handler();

        if ($result === false) {
            return new WP_Error('ssr_invalid_regex', 'Sintassi regex non valida.');
        }

        return true;
    }

    /**
     * Esegue operazioni PCRE con limiti di backtrack/recursion ridotti.
     */
    private function run_pcre($callback) {
        $prev_backtrack  = ini_get('pcre.backtrack_limit');
        $prev_recursion  = ini_get('pcre.recursion_limit');

        ini_set('pcre.backtrack_limit', (string) self::PCRE_BACKTRACK_LIMIT);
        ini_set('pcre.recursion_limit', '10000');

        try {
            return $callback();
        } finally {
            ini_set('pcre.backtrack_limit', $prev_backtrack);
            ini_set('pcre.recursion_limit', $prev_recursion);
        }
    }

    /**
     * Conta le occorrenze in modo ricorsivo
     */
    private function count_occurrences_in_data($data, $search_text, $use_regex) {
        $count = 0;

        if (is_array($data)) {
            foreach ($data as $value) {
                $count += $this->count_occurrences_in_data($value, $search_text, $use_regex);
            }
        } elseif (is_string($data)) {
            if ($use_regex) {
                $count = $this->run_pcre(function () use ($search_text, $data) {
                    $result = preg_match_all('/' . str_replace('/', '\/', $search_text) . '/', $data);
                    return ($result === false) ? 0 : $result;
                });
            } else {
                $count = substr_count($data, $search_text);
            }
        }

        return $count;
    }

    /**
     * Sostituisce i dati in modo ricorsivo
     */
    private function replace_in_data(&$data, $search_text, $replace_text, $use_regex) {
        $replacements = 0;

        if (is_array($data)) {
            foreach ($data as &$value) {
                $replacements += $this->replace_in_data($value, $search_text, $replace_text, $use_regex);
            }
            unset($value);
        } elseif (is_string($data)) {
            if ($use_regex) {
                $count = 0;
                $new_data = $this->run_pcre(function () use ($search_text, $replace_text, $data, &$count) {
                    return preg_replace(
                        '/' . str_replace('/', '\/', $search_text) . '/',
                        $replace_text,
                        $data,
                        -1,
                        $count
                    );
                });

                if ($new_data !== null) {
                    $data         = $new_data;
                    $replacements = $count;
                }
            } else {
                $data = str_replace($search_text, $replace_text, $data, $count);
                $replacements = $count;
            }
        }

        return $replacements;
    }
    
    /**
     * Valida che la tabella sia sicura da usare
     */
    private function is_valid_table($table_name) {
        global $wpdb;
        
        // Lista delle tabelle consentite (solo quelle con possibili dati serializzati)
        $allowed_patterns = array('meta', 'options', 'postmeta', 'usermeta', 'termmeta');
        
        foreach ($allowed_patterns as $pattern) {
            if (strpos($table_name, $pattern) !== false) {
                // Verifica che la tabella esista effettivamente
                $table_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                    DB_NAME,
                    $table_name
                ));
                
                return $table_exists > 0;
            }
        }
        
        return false;
    }
    
    /**
     * Determina la struttura della tabella
     */
    private function get_table_structure($table_name) {
        global $wpdb;
        
        // Ottieni le colonne della tabella
        $columns = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.columns WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $table_name
        ));
        
        if (!$columns) {
            return false;
        }
        
        $column_names = array_column($columns, 'COLUMN_NAME');
        
        // Strutture predefinite per tabelle comuni
        $known_structures = array(
            'postmeta' => array(
                'primary_key' => 'meta_id',
                'serialized_field' => 'meta_value',
                'display_fields' => array('meta_id', 'post_id', 'meta_key'),
                'search_fields' => array('meta_value')
            ),
            'usermeta' => array(
                'primary_key' => 'umeta_id',
                'serialized_field' => 'meta_value',
                'display_fields' => array('umeta_id', 'user_id', 'meta_key'),
                'search_fields' => array('meta_value')
            ),
            'termmeta' => array(
                'primary_key' => 'meta_id',
                'serialized_field' => 'meta_value',
                'display_fields' => array('meta_id', 'term_id', 'meta_key'),
                'search_fields' => array('meta_value')
            ),
            'options' => array(
                'primary_key' => 'option_id',
                'serialized_field' => 'option_value',
                'display_fields' => array('option_id', 'option_name'),
                'search_fields' => array('option_value')
            )
        );
        
        // Cerca una struttura conosciuta
        foreach ($known_structures as $pattern => $structure) {
            if (strpos($table_name, $pattern) !== false) {
                // Verifica che i campi esistano nella tabella
                $valid_structure = true;
                foreach (array_merge(array($structure['primary_key'], $structure['serialized_field']), $structure['display_fields']) as $field) {
                    if (!in_array($field, $column_names)) {
                        $valid_structure = false;
                        break;
                    }
                }
                
                if ($valid_structure) {
                    return $structure;
                }
            }
        }
        
        // Struttura generica per tabelle sconosciute
        $primary_key = null;
        $serialized_field = null;
        
        // Cerca chiave primaria
        foreach ($column_names as $col) {
            if (strpos($col, 'id') !== false && ($col === 'id' || strpos($col, '_id') !== false)) {
                $primary_key = $col;
                break;
            }
        }
        
        // Cerca campo serializzato (value, content, data, etc.)
        $serialized_candidates = array('value', 'content', 'data', 'meta_value', 'option_value');
        foreach ($serialized_candidates as $candidate) {
            if (in_array($candidate, $column_names)) {
                $serialized_field = $candidate;
                break;
            }
        }
        
        if ($primary_key && $serialized_field) {
            return array(
                'primary_key' => $primary_key,
                'serialized_field' => $serialized_field,
                'display_fields' => array_slice($column_names, 0, 4), // Prime 4 colonne
                'search_fields' => array($serialized_field)
            );
        }
        
        return false;
    }
    
    /**
     * Costruisce la query di ricerca dinamica
     */
    private function build_search_query($table_name, $table_structure, $search_text, $meta_key = '', $use_regex = false, $limit = self::BATCH_SIZE, $offset = 0) {
        global $wpdb;

        $select_fields = array();
        $select_fields[] = $table_structure['primary_key'];
        $select_fields[] = $table_structure['serialized_field'];
        foreach ($table_structure['display_fields'] as $field) {
            if (!in_array($field, $select_fields, true)) {
                $select_fields[] = $field;
            }
        }
        $select_clause = implode(', ', $select_fields);

        if ($use_regex) {
            $core_pattern = $this->extract_core_pattern($search_text);
            if ($core_pattern === null) {
                if (empty($meta_key)) {
                    return new WP_Error(
                        'ssr_broad_pattern',
                        'Pattern regex troppo generico per la scansione SQL: seleziona una meta_key o semplifica il pattern.'
                    );
                }
                $core_pattern = $meta_key;
            }
            $like_pattern = '%' . $wpdb->esc_like($core_pattern) . '%';
        } else {
            $like_pattern = '%' . $wpdb->esc_like($search_text) . '%';
        }

        $field = $table_structure['serialized_field'];
        $pk    = $table_structure['primary_key'];

        $sql = "SELECT {$select_clause} FROM `{$table_name}` ";
        $sql .= "WHERE `{$field}` LIKE %s ";
        $sql .= "AND (`{$field}` LIKE 'a:%%' OR `{$field}` LIKE 'O:%%' OR `{$field}` LIKE 's:%%') ";

        $prepare_args = array($like_pattern);

        if (!empty($meta_key) && in_array('meta_key', $table_structure['display_fields'], true)) {
            $sql .= "AND `meta_key` = %s ";
            $prepare_args[] = $meta_key;
        }

        $sql .= "ORDER BY `{$pk}` ASC LIMIT %d OFFSET %d";
        $prepare_args[] = (int) $limit;
        $prepare_args[] = (int) $offset;

        return $wpdb->prepare($sql, ...$prepare_args);
    }

    /**
     * Estrae la parte "core" di un pattern regex per la ricerca SQL
     *
     * @return string|null Stringa utilizzabile per LIKE, oppure null se troppo generica.
     */
    private function extract_core_pattern($regex_pattern) {
        $core = preg_replace('/\(\?\<[!=].*?\)/', '', $regex_pattern);
        $core = preg_replace('/\(\?\![^)]*\)/', '', $core);
        $core = preg_replace('/\(\?\=[^)]*\)/', '', $core);
        $core = preg_replace('/[+*?{][^}]*}?/', '', $core);
        $core = str_replace(array('^', '$', '\\b', '\\s', '\\d', '\\w'), '', $core);
        $core = preg_replace('/[()]/', '', $core);
        $core = trim($core);

        if ($core !== '' && strlen($core) >= 2) {
            return $core;
        }

        if (strpos($regex_pattern, 'strong') !== false) {
            return 'strong';
        }
        if (strpos($regex_pattern, 'br') !== false) {
            return 'br';
        }

        return null;
    }
}


// Inizializza il plugin
new SerializedSearchReplace();