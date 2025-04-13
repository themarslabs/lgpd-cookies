    <?php
    /*
    Plugin Name: LGPD Cookies
    Description: Gerenciador de cookies compatível com LGPD com bloqueio real
    Version: 1.2,1
    Author: Marcio Brandão
    */

    // Evitar acesso direto ao arquivo
    if (!defined('ABSPATH')) {
        exit;
    }




        // Exportação CSV
        if (isset($_POST['export_csv']) && !empty($_POST['start_date']) && !empty($_POST['end_date'])) {


            global $wpdb;
            $table_name = $wpdb->prefix . 'lgpd_cookies_history';

            $start_date = sanitize_text_field($_POST['start_date']);
            $end_date = sanitize_text_field($_POST['end_date']);

            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name WHERE date_time BETWEEN %s AND %s",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            ), ARRAY_A);

            if ($results) {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="lgpd_cookies_history_' . $start_date . '_to_' . $end_date . '.csv"');
                $output = fopen('php://output', 'w');
                fputcsv($output, array('ID', 'IP Address', 'User Agent', 'Date/Time', 'Cookies Accepted'));

                foreach ($results as $row) {
                    fputcsv($output, $row);
                }
                fclose($output);
                exit;
            }
        }



    // Criar tabela no banco de dados ao ativar o plugin
    function lgpd_cookies_create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lgpd_cookies_history';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NOT NULL,
            date_time DATETIME NOT NULL,
            cookies_accepted TEXT NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    register_activation_hook(__FILE__, 'lgpd_cookies_create_table');





    // Dentro da função lgpd_cookies_enqueue_scripts()
    function lgpd_cookies_enqueue_scripts() {
        wp_enqueue_style('lgpd-cookies-style', plugin_dir_url(__FILE__) . 'assets/css/lgpd-cookies.css');
        wp_enqueue_script('lgpd-cookies-script', plugin_dir_url(__FILE__) . 'assets/js/lgpd-cookies.js', array('jquery'), '1.0', true);
        
        //wp_enqueue_script('lgpd-google-analytics', 'https://www.googletagmanager.com/gtag/js?id=UA-XXXXX-Y', array(), null, true);
        //wp_enqueue_script('lgpd-facebook-pixel', 'https://connect.facebook.net/en_US/fbevents.js', array(), null, true);

        $settings = array(
    'title' => get_option('lgpd_title', 'Preferências de Cookies'),
            'text' => get_option('lgpd_text', 'Nós utilizamos cookies para oferecer a você uma experiência mais completa, personalizada e agradável em nosso site. Com eles, conseguimos lembrar suas preferências, entender melhor como você interage com nosso conteúdo e, assim, melhorar continuamente nossos serviços para que cada visita seja ainda mais relevante, segura e prática para você.'),
            'bg_color' => get_option('lgpd_bg_color', '#ffffff'),
            'title_color' => get_option('lgpd_title_color', '#000000'),
            'text_color' => get_option('lgpd_text_color', '#666666'),
            'btn_bg_color' => get_option('lgpd_btn_bg_color', '#007bff'),
            'btn_text_color' => get_option('lgpd_btn_text_color', '#ffffff'),
            'btn_hover_bg' => get_option('lgpd_btn_hover_bg', '#0056b3'),
            'btn_hover_text' => get_option('lgpd_btn_hover_text', '#ffffff'),
            'nonce' => wp_create_nonce('lgpd_cookies_nonce'),
            'ajax_url' => admin_url('admin-ajax.php')
            );
        wp_localize_script('lgpd-cookies-script', 'lgpdSettings', $settings);
    }
    add_action('wp_enqueue_scripts', 'lgpd_cookies_enqueue_scripts');


    // Adicionar modal ao footer
    function lgpd_cookies_add_modal() {

        if (get_option('lgpd_modal_enabled', '1') !== '1') {
            return; // Não exibe o modal se estiver desativado
        }        
        ?>
        <div id="lgpd-cookies-modal" class="lgpd-modal">
            <div class="lgpd-modal-content">
                <div class="lgpd-modal-header">
                    <h2><?php echo esc_html(get_option('lgpd_title', 'Preferências de Cookies')); ?></h2>
                    <p><?php echo esc_html(get_option('lgpd_text', 'Nós usamos cookies para melhorar sua experiência em nosso site.')); ?></p>
                </div>
                <div class="lgpd-modal-body" style="display: none;">
    <div class="cookie-preferences">
        <div class="cookie-section">
            <h3>Marketing</h3>
            <p>Esses cookies podem ser definidos em nosso site por parceiros de publicidade. Eles são utilizados para criar um perfil com base nos seus interesses e exibir anúncios mais relevantes em outros sites. Embora não armazenem informações pessoais diretamente, eles identificam de forma única o seu navegador e dispositivo. Caso não permita esses cookies, você verá anúncios menos direcionados.</p>
            <p>
                <strong>Soluções afetadas:</strong><br>
                Amazon<br>
                Google<br>
                RD Station
            </p>
            <label><input type="checkbox" id="marketing-cookies" name="cookie_types[]" value="marketing"> Permitir</label>
            <ul class="cookie-list" data-category="marketing" style="display:none !important;"></ul>
        </div>

        <div class="cookie-section">
            <h3>Não categorizado</h3>
            <p>Esses cookies ainda estão em processo de classificação e não foram atribuídos a nenhuma categoria específica.</p>
            <p>
                <strong>Soluções afetadas:</strong><br>
                <?php
                // Pega o host atual
                $host = $_SERVER['HTTP_HOST'];

                // Remove o "www." do início, se houver
                $dominio = preg_replace('/^www\./', '', $host);

                // Exibe o domínio limpo
                echo '.'.$dominio;
                ?>                            
            </p>
            <label><input type="checkbox" id="uncategorized-cookies" name="cookie_types[]" value="uncategorized"> Permitir</label>
            <ul class="cookie-list" data-category="uncategorized" style="display:none !important;"></ul>
        </div>

        <div class="cookie-section">
            <h3>Análise</h3>
            <p>Esses cookies nos ajudam a entender o número de visitas e a origem do tráfego em nosso site. Isso é essencial para melhorar o desempenho do site e torná-lo mais útil para você. Eles revelam quais páginas são mais acessadas e como os visitantes navegam pelo site. Todas as informações coletadas são anônimas. Caso você não permita esses cookies, não poderemos saber que você nos visitou e nossa capacidade de melhorar o site será limitada.</p>
            <p>
                <strong>Soluções afetadas:</strong><br>
                Google Analytics<br>
                Microsoft	
            </p>
            <label><input type="checkbox" id="analytics-cookies" name="cookie_types[]" value="analytics"> Permitir</label>
            <ul class="cookie-list" data-category="analytics" style="display:none !important;"></ul>
        </div>

        <div class="cookie-section">
            <h3>Funcional</h3>
            <p>Esses cookies permitem que o site ofereça recursos adicionais. Eles podem ser definidos por nós ou por parceiros cujos serviços integramos às nossas páginas. Ao desativá-los, algumas funcionalidades podem deixar de funcionar corretamente.</p>
            <p>
                <strong>Soluções afetadas:</strong><br>
                PHP.net
            </p>						
            <label><input type="checkbox" id="functional-cookies" name="cookie_types[]" value="functional" checked disabled> Permitir (obrigatório)</label>
            <ul class="cookie-list" data-category="functional" style="display:none !important;"></ul>
        </div>
    </div>
</div>

                <div class="lgpd-modal-footer">
                    <button id="lgpd-reject" class="lgpd-button">Rejeitar Cookies não necessários</button>
                    <button id="lgpd-preferences" class="lgpd-button">Preferências de Cookies</button>
                    <button id="lgpd-accept" class="lgpd-button">Aceitar todos os Cookies</button>
                </div>
            </div>
        </div>
        <div id="lgpd-cookies-icon" class="lgpd-icon">
            <span class="cookie-icon"><img src="/wp-content/plugins/lgpd-cookies/assets/img/icon.svg" width="32px"/></span>
        </div>
        <?php
    }
    add_action('wp_footer', 'lgpd_cookies_add_modal');




    // Salvar histórico no banco de dados via AJAX
    function lgpd_cookies_save_history() {
        check_ajax_referer('lgpd_cookies_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'lgpd_cookies_history';

        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $date_time = current_time('mysql');
        $cookies_accepted = sanitize_text_field($_POST['cookies_accepted']);

        $wpdb->insert(
            $table_name,
            array(
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'date_time' => $date_time,
                'cookies_accepted' => $cookies_accepted
            )
        );

        wp_send_json_success();
    }
    add_action('wp_ajax_lgpd_save_history', 'lgpd_cookies_save_history');
    add_action('wp_ajax_nopriv_lgpd_save_history', 'lgpd_cookies_save_history');


    // Bloquear scripts de terceiros antes do consentimento
    function lgpd_cookies_block_scripts($tag, $handle, $src) {
        $blocked_scripts = array(
            'lgpd-google-analytics' => array(
                'category' => 'analytics',
                'pattern' => '/google-analytics\.com|googletagmanager\.com/'
            ),
            'lgpd-facebook-pixel' => array(
                'category' => 'marketing',
                'pattern' => '/connect\.facebook\.net/'
            ),
            // Adicione mais padrões aqui conforme necessário
            // 'lgpd-adsense' => array('category' => 'marketing', 'pattern' => '/googlesyndication\.com/'),
        );

        foreach ($blocked_scripts as $script => $data) {
            if ($handle === $script || preg_match($data['pattern'], $src)) {
                $consent = "<script type='text/plain' data-cookie-category='{$data['category']}'>";
                $tag = str_replace('<script', $consent, $tag);
            }
        }
        return $tag;
    }
    add_filter('script_loader_tag', 'lgpd_cookies_block_scripts', 10, 3);

    // Menu de administração
    function lgpd_cookies_admin_menu() {
        add_options_page(
            'LGPD Cookies Settings',
            'LGPD Cookies',
            'manage_options',
            'lgpd-cookies',
            'lgpd_cookies_settings_page'
        );
    }
    add_action('admin_menu', 'lgpd_cookies_admin_menu');

    // Registrar configurações
    function lgpd_cookies_register_settings() {
        register_setting('lgpd_cookies_options', 'lgpd_title', 'sanitize_text_field');
        register_setting('lgpd_cookies_options', 'lgpd_text', 'sanitize_textarea_field');
        register_setting('lgpd_cookies_options', 'lgpd_bg_color', 'sanitize_hex_color');
        register_setting('lgpd_cookies_options', 'lgpd_title_color', 'sanitize_hex_color');
        register_setting('lgpd_cookies_options', 'lgpd_text_color', 'sanitize_hex_color');
        register_setting('lgpd_cookies_options', 'lgpd_btn_bg_color', 'sanitize_hex_color');
        register_setting('lgpd_cookies_options', 'lgpd_btn_text_color', 'sanitize_hex_color');
        register_setting('lgpd_cookies_options', 'lgpd_btn_hover_bg', 'sanitize_hex_color');
        register_setting('lgpd_cookies_options', 'lgpd_btn_hover_text', 'sanitize_hex_color');

        register_setting('lgpd_cookies_options', 'lgpd_modal_enabled', array(
            'type' => 'boolean',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '1'
        ));        
    }
    add_action('admin_init', 'lgpd_cookies_register_settings');

    // Página de configurações com abas
    function lgpd_cookies_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=lgpd-cookies&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Configurações</a>
                <a href="?page=lgpd-cookies&tab=history" class="nav-tab <?php echo $active_tab == 'history' ? 'nav-tab-active' : ''; ?>">Histórico</a>
            </h2>

            <?php if ($active_tab == 'settings') : ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('lgpd_cookies_options');
                    do_settings_sections('lgpd_cookies_options');
                    ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="lgpd_title">Título do Modal</label></th>
                        <td><input type="text" name="lgpd_title" id="lgpd_title" value="<?php echo esc_attr(get_option('lgpd_title', 'Preferências de Cookies')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lgpd_text">Texto do Modal</label></th>
                        <td><textarea name="lgpd_text" id="lgpd_text" rows="5" class="large-text"><?php echo esc_textarea(get_option('lgpd_text', 'Nós usamos cookies para melhorar sua experiência em nosso site.')); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lgpd_bg_color">Cor de Fundo do Modal</label></th>
                        <td><input type="text" name="lgpd_bg_color" id="lgpd_bg_color" value="<?php echo esc_attr(get_option('lgpd_bg_color', '#ffffff')); ?>" class="lgpd-color-picker"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lgpd_title_color">Cor do Título</label></th>
                        <td><input type="text" name="lgpd_title_color" id="lgpd_title_color" value="<?php echo esc_attr(get_option('lgpd_title_color', '#000000')); ?>" class="lgpd-color-picker"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lgpd_text_color">Cor do Texto</label></th>
                        <td><input type="text" name="lgpd_text_color" id="lgpd_text_color" value="<?php echo esc_attr(get_option('lgpd_text_color', '#666666')); ?>" class="lgpd-color-picker"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lgpd_btn_bg_color">Cor de Fundo dos Botões</label></th>
                        <td><input type="text" name="lgpd_btn_bg_color" id="lgpd_btn_bg_color" value="<?php echo esc_attr(get_option('lgpd_btn_bg_color', '#007bff')); ?>" class="lgpd-color-picker"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lgpd_btn_text_color">Cor do Texto dos Botões</label></th>
                        <td><input type="text" name="lgpd_btn_text_color" id="lgpd_btn_text_color" value="<?php echo esc_attr(get_option('lgpd_btn_text_color', '#ffffff')); ?>" class="lgpd-color-picker"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lgpd_btn_hover_bg">Cor de Fundo dos Botões (Hover)</label></th>
                        <td><input type="text" name="lgpd_btn_hover_bg" id="lgpd_btn_hover_bg" value="<?php echo esc_attr(get_option('lgpd_btn_hover_bg', '#0056b3')); ?>" class="lgpd-color-picker"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lgpd_btn_hover_text">Cor do Texto dos Botões (Hover)</label></th>
                        <td><input type="text" name="lgpd_btn_hover_text" id="lgpd_btn_hover_text" value="<?php echo esc_attr(get_option('lgpd_btn_hover_text', '#ffffff')); ?>" class="lgpd-color-picker"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lgpd_modal_enabled">Ativar Modal de Cookies</label></th>
                        <td>
                            <input type="checkbox" name="lgpd_modal_enabled" id="lgpd_modal_enabled" value="1" 
                                <?php checked(get_option('lgpd_modal_enabled', '1'), '1'); ?>>
                            <label for="lgpd_modal_enabled">Exibir modal de consentimento de cookies</label>
                        </td>
                    </tr>

                </table>
                    <?php submit_button('Salvar Configurações'); ?>
                </form>
            <?php elseif ($active_tab == 'history') : ?>
                <?php lgpd_cookies_history_tab(); ?>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.lgpd-color-picker').wpColorPicker();
        });
        </script>
        <?php
    }

    add_action('admin_enqueue_scripts', function() {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    });

    // Aba de histórico
    function lgpd_cookies_history_tab() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lgpd_cookies_history';


        // Últimos 20 aceites
        $history = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date_time DESC LIMIT 20", ARRAY_A);
        ?>

        <h3>Exportar Histórico</h3>
        <form method="post" >
            <label for="start_date">Data Inicial:</label>
            <input type="date" id="start_date" name="start_date" required>
            <label for="end_date">Data Final:</label>
            <input type="date" id="end_date" name="end_date" required>
            <input type="submit" name="export_csv" class="button button-primary" value="Exportar para CSV">
        </form>
    <hr style="margin:2rem 0;"/>
        <h3 >Últimos aceites</h3>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                    <th>Data/Hora</th>
                    <th>Cookies Aceitos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $entry) : ?>
                    <tr>
                        <td><?php echo esc_html($entry['id']); ?></td>
                        <td><?php echo esc_html($entry['ip_address']); ?></td>
                        <td><?php echo esc_html($entry['user_agent']); ?></td>
                        <td><?php echo esc_html($entry['date_time']); ?></td>
                        <td><?php echo esc_html($entry['cookies_accepted']); ?></td>
                    </tr>
                <?php endforeach; ?>    
            </tbody>
        </table>

        <h3>Gerenciar Histórico</h3>
        <button id="lgpd-clear-history" class="button button-secondary">Limpar Todo o Histórico</button>
        <?php
    }



    function lgpd_cookies_clear_history() {
        check_ajax_referer('lgpd_clear_history_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'lgpd_cookies_history';
        
        $result = $wpdb->query("TRUNCATE TABLE $table_name");
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Histórico limpo com sucesso!'));
        } else {
            wp_send_json_error(array('message' => 'Erro ao limpar histórico.'));
        }
    }
    add_action('wp_ajax_lgpd_clear_history', 'lgpd_cookies_clear_history');