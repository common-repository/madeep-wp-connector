<?php

ignore_user_abort(true);
set_time_limit(0);
require_once('Madeep-Admin.php');

class Madeep {

    private static $initiated = false;
    //private static $log = null;
    private static $js = array();
    private static $css = array();
    private static $activeLanguage = array();
    private static $multiLangPlugin = null;
    private static $madeepAdmin = null;
    private static $tables = array('hotels', 'ecoms', 'services', 'rooms', 'events', 'offers', 'config');
    private static $tablesWithPages = array('hotels', 'ecoms', 'services', 'rooms', 'events', 'offers');
    private static $pluginName = 'madeep';
    private static $pageList = array();
    private static $logFile = '/logs/logMadeepConnector.txt';

//Inizializzo il plugin
    static function init() {

        //self::$log = new log('Madeep');
        if (!self::$initiated) {
            self::init_hooks_and_files();
        }

        self::$pageList = array(
            'home' => array('Madeep', 'home'),
            'connection' => array(__('Connessione', 'madeep'), 'connection'),
            'languages' => array(__('Lingue', 'madeep'), 'languages'),
            'contents' => array(__('Contenuti', 'madeep'), 'contents'),
            'templates' => array(__('Templates & Categorie', 'madeep'), 'templates'),
            'behaviour' => array(__('Comportamento & Personalizzazione', 'madeep'), 'behaviour'),
            'debug' => array(__('Debug', 'madeep'), 'debug'),
                //'custom' => array(__('Customization', 'madeep'), 'custom'),
        );

        //self::$madeepAdmin = new MadeepAdmin();
        //self::cronUnset();
        //self::cronSet();
    }

    /**
     * Initializes WordPress hooks
     */
    static function init_hooks_and_files() {
        self::$initiated = true;
        add_action('admin_menu', array(self::class, 'load_menu'));
        add_action('admin_init', array(self::class, 'register_madeep_admin_settings'));
        add_action('wp', array(self::class, 'register_madeep_public_settings'));
        add_action('shutdown', array(self::class, 'checkSyncRequest'));

        add_action('wp_ajax_madeep_log', array(self::class, 'readLog'));
        add_action('wp_ajax_madeep_sync_hotels', array(self::class, 'saveHotels'));
        add_action('wp_ajax_madeep_sync_ecom', array(self::class, 'saveEcom'));
        add_action('wp_ajax_madeep_sync_offers', array(self::class, 'saveOffers'));
        add_action('wp_ajax_madeep_sync_services', array(self::class, 'saveServices'));
        add_action('wp_ajax_madeep_resetDB', array(self::class, 'resetDB'));
        add_action('wp_ajax_madeep_resetPages', array(self::class, 'resetPages'));
        add_action('wp_ajax_madeep_resetLog', array(self::class, 'resetLog'));
        add_action('wp_ajax_madeep_getTemplatesTranlations', array(self::class, 'getTemplatesTranlations'));
        add_action('wp_ajax_madeep_getCategoriesTranlations', array(self::class, 'getCategoriesTranlations'));
        add_action('wp_ajax_nopriv_madeep_cron_out', array(self::class, 'cronExecAjax'));
        add_action('wp_ajax_madeep_async_page_update', array(self::class, 'asyncPageUpdate'));

        add_action('wp_ajax_madeep_test_in', array(self::class, 'test_in'));
        add_action('wp_ajax_nopriv_madeep_test_out', array(self::class, 'test_out'));

        register_activation_hook(__FILE__, array(self::class, 'createDB'));
    }

    static function saveHotels() {
        $mad = new MadeepAdmin();
        $mad->saveHotels();
        echo json_encode(array('type' => 'hotels'));
        exit;
    }

    static function saveEcom() {
        $mad = new MadeepAdmin();
        $mad->saveEcom();
        echo json_encode(array('type' => 'ecoms'));
        exit;
    }

    static function saveOffers() {
        $mad = new MadeepAdmin();
        $mad->saveOffers();
        echo json_encode(array('type' => 'offers'));
        exit;
    }

    static function saveServices() {
        $mad = new MadeepAdmin();
        $mad->saveServices();
        echo json_encode(array('type' => 'services'));
        exit;
    }

    static function getLang($lang, $t) {
        $mad = new MadeepAdmin();
        return $mad->getLang($lang, $t);
    }

    static function languageCheck() {
        $mad = new MadeepAdmin();
        if ($mad->multiLangPlugin === 'wpml' && $mad->multiLangPlugin != null) {
            if (ICL_LANGUAGE_CODE !== $mad->getLang(get_option('madeep_default_language'), 0) && get_option('madeep_default_language') != '') {
                echo '<div class="update-nag notice notice-error inline">' . __('La lingua attuale e la lingua configurata come default non sono la stessa, continuando la configurazione i dati potrebbero risultare errati.', 'madeep') . '</div>';
            }
        }
    }

    static function pluginCheck() {
        $mad = new MadeepAdmin();
        return $mad->multiLangPlugin;
    }

    static function readLog() {
        header('Content-Type: application/json');
        $arr = array(
            'log' => file_get_contents(__DIR__ . self::$logFile),
            'size' => number_format(filesize(__DIR__ . self::$logFile) / 1024, 2)
        );

        preg_match_all('/Iteration \{([0-9]{0,})\} done, continue/', $arr['log'], $m);
        $arr['lastIteration'] = end($m[1]) + 1;
        $arr['lastIteration'] = 0;

        echo json_encode($arr);
        //return file_get_contents(__DIR__ . self::$logFile);
        exit;
    }

    static function resetLog() {
        $mad = new MadeepAdmin();
        $mad->clearLog();
        exit;
    }

    static function resetDB() {
        global $wpdb, $table_prefix;
        foreach (self::$tables as $key => $val) {
            $wpdb->query('TRUNCATE ' . $table_prefix . 'madeep_' . $val);
            $wpdb->query('ALTER TABLE ' . $table_prefix . 'madeep_' . $val . ' AUTO_INCREMENT = 1');
        }
        echo json_encode(array('status' => 'success'));
        exit;
    }

    static function resetPages() {
        global $wpdb, $table_prefix;
        $arr = array();
        foreach (self::$tablesWithPages as $key => $val) {
            $pages = $wpdb->get_results('SELECT id_page FROM ' . $table_prefix . 'madeep_' . $val . '');

            foreach ($pages as $key => $val) {
                $arr[] = $val->id_page;
            }
        }

        if (count($arr) > 0) {
            Madeep::resetPage($arr);
        }

        echo json_encode(array('status' => 'success'));
        exit;
    }

    static function resetPage($arr) {
        $mad = new MadeepAdmin();
        $media = array();
        foreach ($arr as $key => $id) {
            if ($mad->multiLangPlugin === 'wpml' && $mad->multiLangPlugin != null) {
                $byLangPosts = $mad->getTranslatedTemplates($id);

                foreach ($byLangPosts as $lKey => $tID) {
                    $media = array_merge($media, get_attached_media('', $tID));
                    $media[] = json_decode(json_encode(array('ID' => get_post_thumbnail_id($tID))));

                    $galleryIDs = get_post_meta($tID, 'madeep_galleryIDs');
                    $galleryIDs = explode(',', $galleryIDs[0]);
                    foreach ($galleryIDs as $key => $val) {
                        $media[] = json_decode(json_encode(array('ID' => $val)));
                    }
                    wp_delete_post($tID, true);
                }
            }
            $media = array_merge($media, get_attached_media('', $id));
            $media[] = json_decode(json_encode(array('ID' => get_post_thumbnail_id($id))));

            $galleryIDs = get_post_meta($id, 'madeep_galleryIDs');
            $galleryIDs = explode(',', $galleryIDs[0]);
            foreach ($galleryIDs as $key => $val) {
                $media[] = json_decode(json_encode(array('ID' => $val)));
            }

            wp_delete_post($id, true);

            foreach ($media as $key => $val) {
                wp_delete_attachment($val->ID, true);
            }
        }
    }

    static function getTemplatesTranlations() {
        $mad = new MadeepAdmin();
        $trid = apply_filters('wpml_element_trid', NULL, $_POST['v'], 'post_page');
        $trans = apply_filters('wpml_get_element_translations', NULL, $trid, 'post_page');

        $r = array();
        foreach ($trans as $key => $val) {
            if ($mad->getLang($mad->defaultLang) != $key) {
                $r[$key] = array('id' => $val->element_id, 'title' => $val->post_title, 'lang' => $key);
            }
        }
        echo json_encode($r);
        exit;
    }

    static function getCategoriesTranlations() {
        $mad = new MadeepAdmin();
        $trid = apply_filters('wpml_element_trid', NULL, $_POST['v'], 'tax_category');
        $trans = apply_filters('wpml_get_element_translations', NULL, $trid, 'tax_category');

        $r = array();
        foreach ($trans as $key => $val) {
            if ($mad->getLang($mad->defaultLang) != $key) {
                $r[$key] = array('id' => $val->translation_id, 'title' => $val->name, 'lang' => $val->language_code, 'd' => $val);
            }
        }
        echo json_encode($r);
        exit;
    }

    static function asyncPageUpdate($t = null, $id = null, $id_cont = null) {
        $mad = new MadeepAdmin();
        $r = array();
        $t = ($t == null) ? $_POST['madeep_t'] : $t;
        $id = ($id == null) ? $_POST['madeep_id'] : $id;
        $id_cont = ($id_cont == null) ? $_POST['madeep_id_cont'] : $id_cont;

        switch ($t) {
            case 'hotels':
                $mad->saveHotels((int) $id);
                break;
            case 'ecoms':
                $mad->saveEcom((int) $id);
                break;
            case 'offers':
                $mad->saveOffers((int) $id, (int) $id_cont);
                break;
            case 'services':
                $mad->saveServices((int) $id, (int) $id_cont);
                break;
        }

        echo json_encode($r);
        exit;
    }

//Crea tabelle
    static function createDB() {
        global $wpdb, $table_prefix;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        foreach (self::$tables as $key => $val) {
            if ($val != 'config' && $val != 'services' && $val != 'hotels' && $val != 'offers' && $val != 'ecoms') {
                if ($wpdb->get_var("show tables like '" . $table_prefix . "madeep_" . $val . "'") != $table_prefix . 'madeep_' . $val) {
                    $sql[] = "CREATE TABLE " . $table_prefix . "madeep_" . $val . " (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    id_hotel INT(11),
                    id_ecom INT(11),
                    id_service INT(11),
                    id_channel INT(11),
                    id_page INT(11),
                    name VARCHAR(255),
                    dataS text,
                    dataH text,
                    lastUpdate INT(11),
                    UNIQUE KEY id (id)
                    );";
                }
            }
        }

        $sql[] = "CREATE TABLE " . $table_prefix . "madeep_hotels (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    id_hotel INT(11),
                    id_ecom INT(11),
                    id_service INT(11),
                    id_channel INT(11),
                    id_page INT(11),
                    structureType VARCHAR(50),
                    stars INT(11),
                    name VARCHAR(255),
                    email VARCHAR(255),
                    phone VARCHAR(255),
                    address VARCHAR(255),
                    postalcode VARCHAR(255),
                    city VARCHAR(255),
                    province VARCHAR(255),
                    region VARCHAR(255),
                    nation VARCHAR(255),
                    priceFrom VARCHAR(255),
                    image VARCHAR(255),
                    imgUrl VARCHAR(255),
                    latitude VARCHAR(255),
                    longitude VARCHAR(255),
                    dataS text,
                    lastUpdate INT(11),
                    dataH text,
                    UNIQUE KEY id (id)
                    );";

        $sql[] = "CREATE TABLE " . $table_prefix . "madeep_ecoms (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    id_hotel INT(11),
                    id_ecom INT(11),
                    id_service INT(11),
                    id_channel INT(11),
                    id_page INT(11),
                    structureType VARCHAR(50),
                    stars INT(11),
                    name VARCHAR(255),
                    email VARCHAR(255),
                    phone VARCHAR(255),
                    address VARCHAR(255),
                    postalcode VARCHAR(255),
                    city VARCHAR(255),
                    province VARCHAR(255),
                    region VARCHAR(255),
                    nation VARCHAR(255),
                    priceFrom VARCHAR(255),
                    image VARCHAR(255),
                    imgUrl VARCHAR(255),
                    latitude VARCHAR(255),
                    longitude VARCHAR(255),
                    dataS text,
                    lastUpdate INT(11),
                    dataH text,
                    UNIQUE KEY id (id)
                    );";

        $sql[] = "CREATE TABLE " . $table_prefix . "madeep_services (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    id_hotel INT(11),
                    id_ecom INT(11),
                    id_service INT(11),
                    id_channel INT(11),
                    code VARCHAR(255),
                    validityFrom INT(11),
                    validityTo INT(11),
                    priceFrom INT(11),
                    imgUrl text,
                    id_page INT(11),
                    dataS text,
                    dataH text,
                    lastUpdate INT(11),
                    UNIQUE KEY id (id)
                    );";

        $sql[] = "CREATE TABLE " . $table_prefix . "madeep_offers (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    id_hotel INT(11),
                    id_ecom INT(11),
                    id_service INT(11),
                    id_channel INT(11),
                    productid VARCHAR(255),
                    priceFrom INT(11),
                    imgUrl text,
                    id_page INT(11),
                    dataS text,
                    dataH text,
                    lastUpdate INT(11),
                    UNIQUE KEY id (id)
                    );";

        $sql[] = "CREATE TABLE " . $table_prefix . "madeep_config (
		id INT(11) NOT NULL AUTO_INCREMENT,
		dataVar VARCHAR(255),
		dataVal text,
		UNIQUE KEY id (id)
		);";

        dbDelta($sql);
    }

    static function deleteDB() {
        global $wpdb, $table_prefix;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $sql = array();
        foreach (self::$tables as $key => $val) {
            if ($val != 'config') {
                if ($wpdb->get_var("show tables like '" . $table_prefix . "madeep_" . $val . "'") == $table_prefix . 'madeep_' . $val) {
                    $wpdb->query("DROP TABLE " . $table_prefix . "madeep_" . $val);
                }
            }
        }
    }

//Aggiunge i menu
    static function load_menu() {
        add_menu_page('Madeep', 'Madeep', 'read', 'madeep', array(self::class, 'madeep_page_plugin_home'), 'data:image/svg+xml;base64,' . base64_encode(file_get_contents(Madeep_Dir . 'img/logo.svg')), 10);
        /* if (is_admin() || ( defined('WP_CLI') && WP_CLI )) {
          add_submenu_page('madeep-home', __('Configurations', 'madeep'), __('Configurations', 'madeep'), 'manage_options', 'madeep-config', array(self::class, 'madeep_plugin_options'), 1);
          } */
        if (get_option('madeep_data_type') !== false) {
            add_submenu_page('madeep', __('Connessione', 'madeep'), __('Connessione', 'madeep'), 'manage_options', 'madeep-connection', array(self::class, 'madeep_page_plugin_connection'), 1);
            add_submenu_page('madeep', __('Lingue', 'madeep'), __('Lingue', 'madeep'), 'manage_options', 'madeep-languages', array(self::class, 'madeep_page_plugin_languages'), 2);
            add_submenu_page('madeep', __('Contenuti', 'madeep'), __('Contenuti', 'madeep'), 'manage_options', 'madeep-contents', array(self::class, 'madeep_page_plugin_contents'), 3);
            add_submenu_page('madeep', __('Templates & Categorie', 'madeep'), __('Templates & Categorie', 'madeep'), 'manage_options', 'madeep-templates', array(self::class, 'madeep_page_plugin_templates'), 4);
            add_submenu_page('madeep', __('Comportamento & Personalizzazione', 'madeep'), __('Comportamento & Personalizzazione', 'madeep'), 'manage_options', 'madeep-behaviour', array(self::class, 'madeep_page_plugin_behaviour'), 5);
            add_submenu_page('madeep', __('Debug', 'madeep'), __('Debug', 'madeep'), 'manage_options', 'madeep-debug', array(self::class, 'madeep_page_plugin_debug'), 6);

            if (get_option('madeep_allow_single_sync') == 1) {
                add_filter('post_row_actions', array(self::class, 'action_post_row'), 10, 2);
            }
        }
    }

    static function action_post_row($actions) {
        $post = get_post();
        $list = self::getPageListUsed();
        $t = get_post_meta($post->ID, 'madeep_type', true);
        $id = get_post_meta($post->ID, 'madeep_id_element', true);
        $id_cont = get_post_meta($post->ID, 'madeep_id_container', true);
        if (in_array($post->ID, $list)) {
            $actions['mad_refresh'] = '<a href="#" class="mad-asyncUpdate" onClick="madAsyncUpdate(this)" data-type="' . $t . '" data-id="' . $id . '" data-id_cont="' . $id_cont . '"><img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhLS0gQ3JlYXRlZCB3aXRoIElua3NjYXBlIChodHRwOi8vd3d3Lmlua3NjYXBlLm9yZy8pIC0tPgoKPHN2ZwogICB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iCiAgIHhtbG5zOmNjPSJodHRwOi8vY3JlYXRpdmVjb21tb25zLm9yZy9ucyMiCiAgIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyIKICAgeG1sbnM6c3ZnPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICAgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIgogICB4bWxuczpzb2RpcG9kaT0iaHR0cDovL3NvZGlwb2RpLnNvdXJjZWZvcmdlLm5ldC9EVEQvc29kaXBvZGktMC5kdGQiCiAgIHhtbG5zOmlua3NjYXBlPSJodHRwOi8vd3d3Lmlua3NjYXBlLm9yZy9uYW1lc3BhY2VzL2lua3NjYXBlIgogICB3aWR0aD0iMjcuOTU0OTk4bW0iCiAgIGhlaWdodD0iMTAuODc0ODczbW0iCiAgIHZpZXdCb3g9IjAgMCAyNy45NTQ5OTggMTAuODc0ODczIgogICB2ZXJzaW9uPSIxLjEiCiAgIGlkPSJzdmc4IgogICBpbmtzY2FwZTp2ZXJzaW9uPSIwLjkyLjQgKGY4ZGNlOTEsIDIwMTktMDgtMDIpIgogICBzb2RpcG9kaTpkb2NuYW1lPSJsb2dvLnN2ZyI+CiAgPGRlZnMKICAgICBpZD0iZGVmczIiIC8+CiAgPHNvZGlwb2RpOm5hbWVkdmlldwogICAgIGlkPSJiYXNlIgogICAgIHBhZ2Vjb2xvcj0iI2ZmZmZmZiIKICAgICBib3JkZXJjb2xvcj0iIzY2NjY2NiIKICAgICBib3JkZXJvcGFjaXR5PSIxLjAiCiAgICAgaW5rc2NhcGU6cGFnZW9wYWNpdHk9IjAuMCIKICAgICBpbmtzY2FwZTpwYWdlc2hhZG93PSIyIgogICAgIGlua3NjYXBlOnpvb209IjEuOTc5ODk5IgogICAgIGlua3NjYXBlOmN4PSIxOC44Nzg5MzUiCiAgICAgaW5rc2NhcGU6Y3k9Ii00NC4yNzg3NzIiCiAgICAgaW5rc2NhcGU6ZG9jdW1lbnQtdW5pdHM9Im1tIgogICAgIGlua3NjYXBlOmN1cnJlbnQtbGF5ZXI9InN2Z2ciCiAgICAgc2hvd2dyaWQ9ImZhbHNlIgogICAgIGlua3NjYXBlOndpbmRvdy13aWR0aD0iMTMyNSIKICAgICBpbmtzY2FwZTp3aW5kb3ctaGVpZ2h0PSI3MTMiCiAgICAgaW5rc2NhcGU6d2luZG93LXg9IjM1IgogICAgIGlua3NjYXBlOndpbmRvdy15PSIyNyIKICAgICBpbmtzY2FwZTp3aW5kb3ctbWF4aW1pemVkPSIxIiAvPgogIDxtZXRhZGF0YQogICAgIGlkPSJtZXRhZGF0YTUiPgogICAgPHJkZjpSREY+CiAgICAgIDxjYzpXb3JrCiAgICAgICAgIHJkZjphYm91dD0iIj4KICAgICAgICA8ZGM6Zm9ybWF0PmltYWdlL3N2Zyt4bWw8L2RjOmZvcm1hdD4KICAgICAgICA8ZGM6dHlwZQogICAgICAgICAgIHJkZjpyZXNvdXJjZT0iaHR0cDovL3B1cmwub3JnL2RjL2RjbWl0eXBlL1N0aWxsSW1hZ2UiIC8+CiAgICAgICAgPGRjOnRpdGxlPjwvZGM6dGl0bGU+CiAgICAgIDwvY2M6V29yaz4KICAgIDwvcmRmOlJERj4KICA8L21ldGFkYXRhPgogIDxnCiAgICAgaW5rc2NhcGU6bGFiZWw9IkxpdmVsbG8gMSIKICAgICBpbmtzY2FwZTpncm91cG1vZGU9ImxheWVyIgogICAgIGlkPSJsYXllcjEiCiAgICAgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTE4Ljg5ODgxMSwtNDIuODU3OTYyKSI+CiAgICA8ZwogICAgICAgaWQ9InN2Z2ciCiAgICAgICB0cmFuc2Zvcm09Im1hdHJpeCgwLjI2NDU4MzMzLDAsMCwwLjI2NDU4MzMzLDE4Ljg5ODgxMSw0Mi44NzI0NTgpIj4KICAgICAgPHBhdGgKICAgICAgICAgZD0ibSAzNy4zMzMsMC4yMzYgYyAwLDAuMTMgLTAuMDUxLDAuMzc0IC0wLjExNCwwLjU0MiAtMC4wOTcsMC4yNTcgLTAuMDc1LDAuMjczIDAuMTQsMC4wOTggMC4xNCwtMC4xMTMgMC4zMTYsLTAuMTUxIDAuMzkxLC0wLjA4MyAwLjA3NSwwLjA2OCAwLjA4NywtMC4wMTQgMC4wMjcsLTAuMTgyIEMgMzcuNzE2LDAuNDQzIDM3LjY2NywwLjIzNyAzNy42NjcsMC4xNTMgMzcuNjY3LDAuMDY5IDM3LjU5MiwwIDM3LjUsMCAzNy40MDgsMCAzNy4zMzMsMC4xMDYgMzcuMzMzLDAuMjM2IG0gMC41LDAuMDE0IGMgMCwwLjEzOCAwLjA4MywwLjI1IDAuMTg1LDAuMjUgMC4xMDcsMCAwLjEzOCwwLjEyMyAwLjA3MywwLjI5MiAtMC4wNzUsMC4xOTkgLTAuMDU2LDAuMjM3IDAuMDYsMC4xMjIgQyAzOC4zNDMsMC43MjMgMzguMjA4LDAgMzcuOTgxLDAgMzcuOSwwIDM3LjgzMywwLjExMiAzNy44MzMsMC4yNSBtIC0xLjYwNCwxLjYxNyBjIC0wLjA0NSwwLjA3MyAtMC4wMjEsMC4xMzQgMC4wNTMsMC4xMzYgMC4wNzQsMTBlLTQgMC4wMjksMC4wNjkgLTAuMSwwLjE1MSAtMC4xOTcsMC4xMjQgLTAuMiwwLjIzMyAtMC4wMjEsMC42NjcgMC4xOTcsMC40NzUgMC4yMzMsMC40OTQgMC40MzEsMC4yMjMgMC4xODUsLTAuMjUzIDAuMTY3LC0wLjMxMiAtMC4xMzEsLTAuNDIzIC0wLjMzOSwtMC4xMjcgLTAuMzM5LC0wLjEzNSAtMC4wMjksLTAuMzcgMC4zNjUsLTAuMjc2IDAuODA1LC0wLjMzMyAwLjY1MSwtMC4wODQgLTAuMDU2LDAuMDkxIC0wLjAxOCwwLjE2NiAwLjA4NiwwLjE2NiAwLjEwNCwwIDAuMTY0LC0wLjEzMSAwLjEzMywtMC4yOTEgLTAuMDU4LC0wLjMwMSAtMC45MDksLTAuNDQgLTEuMDczLC0wLjE3NSBtIC0wLjQ4LDEuMTM1IEMgMzUuNjksMy4wOTcgMzUuNTI0LDMuMTU1IDM1LjM3OSwzLjEyOSAzNS4xMTUsMy4wODMgMzUuMDk2LDMuMTM4IDM0LjkzNyw0LjM5MiAzNC44NCw1LjE1NyAzNC44NCw1LjE1NiAzNC4yOSw1LjIwMiBjIC0wLjIzOCwwLjAxOSAtMC4zOTUsMC4wOTYgLTAuMzUsMC4xNjkgMC4wNDYsMC4wNzQgMC4xNzIsMC4wODQgMC4yOCwwLjAyMiAwLjExMywtMC4wNjUgMC4xMzgsLTAuMDQ5IDAuMDU4LDAuMDM5IC0wLjA3NywwLjA4MyAtMC4yMjcsMC4xNTEgLTAuMzM0LDAuMTUxIC0wLjEwNywwIC0wLjE5NywwLjIwNyAtMC4yMDIsMC40NTkgLTAuMDA4LDAuNDkxIDAuMTY0LDAuNjExIDAuMzE0LDAuMjE5IDAuMDUxLC0wLjEzMiAwLjAwMiwtMC4yOCAtMC4xMDcsLTAuMzI5IC0wLjEwOSwtMC4wNSAtMC4wMTEsLTAuMDcxIDAuMjE4LC0wLjA0OSAwLjIyOSwwLjAyMyAwLjM5OCwwLjEzMSAwLjM3NywwLjI0IEMgMzQuNTIsNi4yNCAzNC42MTEsNi4yOCAzNC43NjYsNi4yMjEgMzQuOTEsNi4xNjYgMzQuOTY1LDYuMDc0IDM0Ljg4OSw2LjAxOCAzNC41ODcsNS43OTcgMzQuNjksNS4zNzQgMzUuMTU0LDQuOTI5IDM1LjQ2Niw0LjYzIDM1LjYwNSw0LjM2NCAzNS41NCw0LjE4OSAzNS40NSwzLjk1MSAzNS40NzQsMy45NDYgMzUuNzIzLDQuMTQ5IDM1Ljg4LDQuMjc2IDM2LjA0Niw0LjM0MyAzNi4wOTMsNC4yOTYgMzYuMjU3LDQuMTMyIDM2LjMxMiwzLjUgMzYuMTYyLDMuNSAzNi4wNzksMy41IDM2LjA1MSwzLjM5NSAzNi4xLDMuMjY3IDM2LjIwOCwyLjk4NiAzNS45MDEsMi43NTUgMzUuNzQ5LDMuMDAyIE0gMzIuNjY3LDcuNzUgQyAzMi41MDUsNy45NDQgMzIuNTA0LDguMDUzIDMyLjY1OCw4LjI0IDMyLjksOC41MzEgMzMuMjgsOC40MSAzMy41MTcsNy45NjggMzMuNjczLDcuNjc2IDMzLjY1Niw3LjY2IDMzLjM2LDcuODE5IDMzLjE3OCw3LjkxNiAzMi45ODUsOC4wOTEgMzIuOTMsOC4yMDYgMzIuODc2LDguMzIyIDMyLjg3MSw4LjIxIDMyLjkxOSw3Ljk1OCAzMy4wMTgsNy40MzggMzIuOTYzLDcuMzkyIDMyLjY2Nyw3Ljc1IG0gLTAuNDQsMSBDIDMyLjI4LDguODg3IDMyLjI1LDkgMzIuMTYxLDkgMzIuMDczLDkgMzIsOC45MzggMzIsOC44NjEgMzIsOC43ODUgMzEuOTA0LDguNzIyIDMxLjc4Niw4LjcyMiBjIC0wLjExOCwwIC0wLjIxNSwwLjEzMiAtMC4yMTUsMC4yOTQgMCwwLjE2MSAwLjA3MSwwLjI0OSAwLjE1NywwLjE5NiAwLjA5NSwtMC4wNTggMC4wOTksLTAuMDA0IDAuMDExLDAuMTM3IC0wLjIxOSwwLjM1MSAtMC4yNTksMC44MTggLTAuMDcxLDAuODE4IDAuMDkxLDAgMC4xNDksLTAuMDU3IDAuMTMsLTAuMTI1IC0wLjA2OSwtMC4yNDIgMC4zODMsLTAuODc1IDAuNjI1LC0wLjg3NSAwLjEzNCwwIDAuMjQ0LC0wLjE1IDAuMjQ0LC0wLjMzNCAwLC0wLjE5NSAtMC4xMTEsLTAuMzMzIC0wLjI2OCwtMC4zMzMgLTAuMTcsMCAtMC4yMzMsMC4wOTIgLTAuMTcyLDAuMjUgbSAtMC4wNiwwLjgxNiBjIDAsMC4xMjcgMC4xMTIsMC4yNzUgMC4yNSwwLjMyOCAwLjE3LDAuMDY1IDAuMjUsLTAuMDA5IDAuMjUsLTAuMjMzIDAsLTAuMTggLTAuMTEzLC0wLjMyOCAtMC4yNSwtMC4zMjggLTAuMTM4LDAgLTAuMjUsMC4xMDUgLTAuMjUsMC4yMzMgbSAtMC42NDEsMS4xNTIgYyAtMC4wOTksMC4xMiAtMC4zOTksMC4yMDMgLTAuNjY1LDAuMTg1IC0wLjM1OSwtMC4wMjUgLTAuNDc5LDAuMDM4IC0wLjQ2NCwwLjI0IDAuMDIxLDAuMjkyIDAuNDEsMC42NzggMC42ODYsMC42ODIgMC4wOTIsMC4wMDIgMC4wNiwtMC4wNjUgLTAuMDcxLC0wLjE0OCBDIDMwLjc1MywxMS41MTQgMzAuNjE0LDExIDMwLjgyOCwxMSBjIDAuMDcyLDAgMC4yNDgsMC4xNjkgMC4zOSwwLjM3NSBsIDAuMjU4LDAuMzc1IC0wLjA5MSwtMC4zNzUgQyAzMS4zMzUsMTEuMTY5IDMxLjM3LDExIDMxLjQ2MywxMSBjIDAuMDkyLDAgMC4yMTEsLTAuMTEzIDAuMjY0LC0wLjI1IDAuMTE4LC0wLjMwOCAwLjAzOSwtMC4zMjEgLTAuMjAxLC0wLjAzMiBtIC0xLjE5MywxLjI5OCBjIDAsMC4xMDMgLTAuMTUyLDAuMTI2IC0wLjM3NSwwLjA1NiAtMC4zNjEsLTAuMTEyIC0wLjM2NCwtMC4xMDQgLTAuMDg1LDAuMjA4IDAuMzY4LDAuNDEzIDAuMDg0LDAuNzk4IC0wLjM5OSwwLjU0IC0wLjE2OSwtMC4wOTEgLTAuMzA3LC0wLjEyIC0wLjMwNywtMC4wNjUgMCwwLjIyOSAwLjM1NywwLjc0NSAwLjUxNSwwLjc0NSAwLjA5NCwwIDAuMTI5LC0wLjA2OCAwLjA3OCwtMC4xNTEgLTAuMDUyLC0wLjA4MyAwLjA1NiwtMC4xMTIgMC4yNCwtMC4wNjQgMC4zMzEsMC4wODcgMC4zNDUsMC4wNjEgMC4zOTIsLTAuNzQzIDAuMDEsLTAuMTYxIDAuMTI2LC0wLjMzNSAwLjI1OSwtMC4zODggMC4xOTksLTAuMDc5IDAuMTkyLC0wLjExNSAtMC4wMzgsLTAuMjAzIC0wLjE1NCwtMC4wNTkgLTAuMjgsLTAuMDMgLTAuMjgsMC4wNjUgbSAtMi4xNTIsMi40NzUgYyAtMC4yNzcsMC4xNzEgLTAuMDExLDAuMzQxIDAuNDEyLDAuMjYyIDAuMTc4LC0wLjAzNCAwLjQxNywtMC4wNjcgMC41MzIsLTAuMDc0IDAuMTE1LC0wLjAwNyAwLjIwOCwtMC4wNzYgMC4yMDgsLTAuMTU0IDAsLTAuMTgyIC0wLjg3LC0wLjIwOCAtMS4xNTIsLTAuMDM0IG0gMC4xMTEsMC40NzUgQyAyOC4xMzEsMTQuOTk4IDI4LDE1LjEzMSAyOCwxNS4yNjIgYyAwLDAuMzIgMC4xNjUsMC4zIDAuNDQyLC0wLjA1NCAwLjEyNiwtMC4xNiAwLjIyLC0wLjIxNiAwLjIwNywtMC4xMjUgLTAuMDQ5LDAuMzcyIDAuMDMsMC40MjggMC4yNjgsMC4xOTEgMC4zNzgsLTAuMzc5IDAuMTkyLC0wLjQ3IC0wLjYyNSwtMC4zMDggbSAtMS42NjgsMi43MTUgYyAtMC4xNjEsMC4wOTQgLTAuMzU3LDAuMTMxIC0wLjQzNCwwLjA4MyAtMC4wNzgsLTAuMDQ4IC0wLjIyNywwLjEwMiAtMC4zMzIsMC4zMzIgLTAuMjMxLDAuNTA4IC0wLjI0NywwLjc5MSAtMC4wMzYsMC42NjEgMC4wODUsLTAuMDUzIDAuMjAxLC0wLjAyMiAwLjI1NywwLjA2OSAwLjA1NiwwLjA5IC0wLjA5LDAuMjEyIC0wLjMyNSwwLjI3MSAtMC4yMzQsMC4wNTkgLTAuNTY3LDAuMjg2IC0wLjczOSwwLjUwNSBMIDI0LjcwMiwyMCBIIDEzLjEwMSBDIDMuNDk0LDIwIDEuNSwxOS45NjEgMS41LDE5Ljc3NiAxLjUsMTkuNDE5IDAuOTMsMTkgMC40NDUsMTkgSCAwIHYgMi4xNjcgYyAwLDEuOTUyIDAuMDI5LDIuMTY2IDAuMjkyLDIuMTY3IDAuMjIzLDAuMDAxIDAuMjk3LDAuMTUyIDAuMzE1LDAuNjQ0IDAuMDMsMC44MTggMC4zMDQsMS4zMyAwLjgxNywxLjUyNSAwLjI2OCwwLjEwMiAwLjM3LDAuMjM5IDAuMzA0LDAuNDEgLTAuMTM3LDAuMzU4IDAuMjM1LDAuNzU0IDAuNzA5LDAuNzU0IDAuMzgzLDAgMC41MTgsMC40MTggMC40MTIsMS4yNzkgLTAuMDE0LDAuMTA3IDAuMDQ5LDAuMjQxIDAuMTM5LDAuMjk3IDAuMDksMC4wNTUgMC4xMjQsMC4yMDQgMC4wNzYsMC4zMzEgLTAuMDk3LDAuMjUyIDAuNDkxLDAuNTE1IDAuNzY4LDAuMzQ0IEMgMy45MzMsMjguODU1IDQsMjkuMDQ0IDQsMjkuMzg5IGMgMCwwLjU4OCAwLjIwNiwwLjc3OCAwLjg0NywwLjc3OCAwLjI2OCwwIDAuMzIzLDAuMTI5IDAuMzM5LDAuNzkxIDAuMDEsMC40MzYgMC4wMjksMC45MDEgMC4wNDIsMS4wMzUgMC4wMTIsMC4xMzQgMC4yNjYsMC4zMTQgMC41NjQsMC4zOTkgMC40NDUsMC4xMjkgMC41NDEsMC4yMzkgMC41NDEsMC42MjUgMCwwLjQwMyAwLjA4MSwwLjQ4MyAwLjU4NCwwLjU3NyAwLjU2OCwwLjEwNyAwLjU4NCwwLjEzMSAwLjYwNCwwLjkyNSAwLjAzNCwxLjM1NSAtMC4wMzcsMS4yMzUgMC43NzEsMS4yOTQgMC4yMSwwLjAxNSAwLjI5NSwwLjE3MSAwLjMwMywwLjU2MiAwLjAyMiwxLjAwNyAwLjIxMSwxLjQxNiAwLjczNiwxLjU5OSAwLjM1NiwwLjEyNCAwLjQ3LDAuMjUgMC4zOTcsMC40NCAtMC4xNDIsMC4zNyAwLjQxOCwwLjk0OCAwLjgsMC44MjcgMC4yNSwtMC4wNzkgMC4zMDUsLTAuMDA2IDAuMzA1LDAuNDA1IDAsMC4yNzUgMC4wNjgsMC41NDMgMC4xNTEsMC41OTQgMC4wODMsMC4wNTIgMC4xMTIsMC4yNDQgMC4wNjQsMC40MjcgQyAxMC45NjIsNDAuOTk4IDExLjA0Niw0MSAyNC45ODEsNDEgSCAzOSB2IC0wLjY0NCBjIDAsLTAuNDY2IC0wLjE0LC0wLjgxOSAtMC41MDgsLTEuMjgyIC0wLjI3OSwtMC4zNTIgLTAuNjI1LC0wLjg4NyAtMC43NjksLTEuMTkgLTAuMTQzLC0wLjMwMyAtMC4zMywtMC41NTEgLTAuNDE1LC0wLjU1MSAtMC4wODQsMCAtMC4yOTIsLTAuMzIzIC0wLjQ2MSwtMC43MTggLTAuMTcsLTAuMzk2IC0wLjQzLC0wLjg0MSAtMC41NzgsLTAuOTg5IC0wLjE0OSwtMC4xNDkgLTAuNDM5LC0wLjU2MiAtMC42NDUsLTAuOTE5IC0wLjcxMywtMS4yMzggLTEuMDQ0LC0xLjc1MyAtMS42NjYsLTIuNTk4IC0wLjM0MywtMC40NjggLTAuNjI1LC0wLjk0IC0wLjYyNSwtMS4wNSAwLC0wLjExIC0wLjE3NywtMC40MTIgLTAuMzk0LC0wLjY3MSBDIDMyLjcyMywzMC4xMjkgMzIuMzg2LDI5LjYwMiAzMi4xOTEsMjkuMjE3IDMxLjk5NSwyOC44MzMgMzEuNzMyLDI4LjQ1OCAzMS42MDYsMjguMzg0IDMxLjQ4LDI4LjMxIDMxLjIxOCwyNy45MzYgMzEuMDI0LDI3LjU1MyAzMC44MywyNy4xNyAzMC41ODcsMjYuODA0IDMwLjQ4NCwyNi43NCAzMC4zODEsMjYuNjc2IDMwLjExOCwyNi4yMjEgMjkuOSwyNS43MjkgYyAtMC4yMTgsLTAuNDkzIC0wLjQ3NiwtMC44OTYgLTAuNTczLC0wLjg5NiAtMC4wOTgsMCAtMC4yOTQsLTAuMjc4IC0wLjQzNiwtMC42MTggLTAuMTQyLC0wLjM0IC0wLjUxMywtMC45MTYgLTAuODI0LC0xLjI4IEMgMjcuNzU1LDIyLjU3MSAyNy41LDIyLjE4IDI3LjUsMjIuMDY1IDI3LjUsMjEuOTUxIDI3LjM2OSwyMS43MjggMjcuMjA4LDIxLjU3IDI3LjA0OCwyMS40MTIgMjYuODM2LDIxLjA1MSAyNi43MzgsMjAuNzY3IDI2LjY0LDIwLjQ4MiAyNi4zOTcsMjAuMTc2IDI2LjE5NywyMC4wODUgMjUuODMxLDE5LjkyIDI1LjY5LDE5LjUgMjYsMTkuNSBjIDAuMDkyLDAgMC4xNDgsLTAuMDYyIDAuMTI1LC0wLjEzNyAtMC4wMjMsLTAuMDc1IDAuMTgzLC0wLjMzNSAwLjQ1OCwtMC41NzggMC4yNzUsLTAuMjQzIDAuNDA5LC0wLjQ0NCAwLjI5NywtMC40NDYgLTAuMjMsLTAuMDA2IC0wLjA5MiwtMC41MTcgMC4xOTEsLTAuNzExIDAuMjc2LC0wLjE4OSAtMC4xMTEsLTAuMTQzIC0wLjQ0NywwLjA1MyBtIC0wLjk1NywxLjk4NiBDIDI1LjU2OSwxOS44NSAyNS40MTYsMjAgMjUuMzI3LDIwIGMgLTAuMjI2LDAgLTAuMiwtMC4xMDMgMC4xMDIsLTAuNDA1IDAuMzU3LC0wLjM1NyAwLjQ1MiwtMC4zMjkgMC4yMzgsMC4wNzIiCiAgICAgICAgIGlkPSJwYXRoMCIKICAgICAgICAgaW5rc2NhcGU6Y29ubmVjdG9yLWN1cnZhdHVyZT0iMCIKICAgICAgICAgc3R5bGU9ImZpbGw6I2EwYTVhYSIgLz4KICAgICAgPHBhdGgKICAgICAgICAgZD0ibSA2Ni4xNjcsMC4yMjkgYyAwLDAuNDczIDAuNTI3LDEuNjk4IDAuOTA0LDIuMTAyIDAuMjE2LDAuMjMgMC41NSwwLjczMyAwLjc0MiwxLjExNiAwLjE5MiwwLjM4MyAwLjQyNiwwLjc0NSAwLjUyLDAuODAzIDAuMDk1LDAuMDU4IDAuMjg3LDAuMzgxIDAuNDI3LDAuNzE3IDAuMTQxLDAuMzM3IDAuMzk5LDAuNzY0IDAuNTczLDAuOTUgMC4xNzUsMC4xODYgMC40MywwLjYwNSAwLjU2NiwwLjkzMiAwLjEzNywwLjMyNyAwLjM3LDAuNjk1IDAuNTE5LDAuODE5IDAuMTQ5LDAuMTIzIDAuMzksMC40NTUgMC41MzUsMC43MzcgMC4xNDYsMC4yODEgMC40NzEsMC43NzQgMC43MjMsMS4wOTUgMC4yNTEsMC4zMjEgMC41MSwwLjc3MSAwLjU3NiwxIDAuMDY1LDAuMjI5IDAuMjgsMC42MDQgMC40NzgsMC44MzMgMC4xOTcsMC4yMyAwLjQ5OSwwLjY3OSAwLjY3MiwxIDAuMTcyLDAuMzIxIDAuNDQ1LDAuNzM0IDAuNjA2LDAuOTE3IDAuMTYxLDAuMTgzIDAuNDA5LDAuNjE1IDAuNTUxLDAuOTU4IDAuMTQxLDAuMzQ0IDAuMzQsMC42MjUgMC40NDIsMC42MjUgMC4xMDEsMCAwLjM1OSwwLjM5NyAwLjU3NCwwLjg4MiAwLjIxNCwwLjQ4NSAwLjUxNSwwLjk4NSAwLjY2NywxLjExMiAwLjE1MywwLjEyNiAwLjM4OSwwLjQ5OCAwLjUyNiwwLjgyNiAwLjEzOCwwLjMyOSAwLjQzMiwwLjc4NyAwLjY1NCwxLjAxOSAwLjIyMywwLjIzMyAwLjU0NiwwLjg1MSAwLjcxOSwxLjM3NSBsIDAuMzE0LDAuOTUyIC0wLjM4MSwwLjc1MiBjIC0wLjIwOSwwLjQxNCAtMC41NzQsMC45ODIgLTAuODEsMS4yNjMgLTAuMjM3LDAuMjgyIC0wLjQzMSwwLjYwMSAtMC40MzEsMC43MTEgMCwwLjEwOSAtMC4yMTQsMC40NiAtMC40NzUsMC43NzkgLTAuMjYyLDAuMzE4IC0wLjYxLDAuODQyIC0wLjc3NCwxLjE2MyAtMC4xNjQsMC4zMjEgLTAuNDc1LDAuNzk1IC0wLjY5MSwxLjA1NCAtMC4yMTYsMC4yNTkgLTAuMzkzLDAuNTY0IC0wLjM5MywwLjY3OCAwLDAuMTE0IC0wLjI2MywwLjUxNCAtMC41ODMsMC44ODkgLTAuMzIxLDAuMzc1IC0wLjU4NCwwLjc1OCAtMC41ODQsMC44NTEgMCwwLjA5MyAtMC4xODgsMC4zOTMgLTAuNDE5LDAuNjY3IC0wLjIzLDAuMjczIC0wLjUzOSwwLjc4NSAtMC42ODYsMS4xMzcgLTAuMTQ3LDAuMzUxIC0wLjQ1MSwwLjg2NCAtMC42NzYsMS4xNCAtMC42MDEsMC43MzUgLTEuMTUsMS41NjMgLTEuNjM2LDIuNDY2IC0wLjIzNiwwLjQ0IC0wLjUzNSwwLjg0MSAtMC42NjUsMC44OSAtMC4xMjksMC4wNSAtMC4zMiwwLjM0OCAtMC40MjQsMC42NjMgLTAuMTA0LDAuMzE1IC0wLjM2LDAuNzc3IC0wLjU2OSwxLjAyNyAtMC4yMDgsMC4yNSAtMC41NDEsMC43NzMgLTAuNzM5LDEuMTYzIEMgNjcuMzIyLDM4LjY4MSA2Ny4wODQsMzkgNjYuOTkxLDM5IGMgLTAuMTgyLDAgLTAuODI0LDEuMzc0IC0wLjgyNCwxLjc2NCAwLDAuMjAyIDEuOTUsMC4yMzYgMTMuNDE2LDAuMjM2IEMgOTEuNjY5LDQxIDkzLDQwLjk3NCA5Myw0MC43MzggYyAwLC0wLjE0NCAwLjExMiwtMC4zNzQgMC4yNSwtMC41MTIgMC4xMzgsLTAuMTM3IDAuMjUsLTAuMTg1IDAuMjUsLTAuMTA3IDAsMC4wNzkgMC4xNTgsMC4xNTUgMC4zNTEsMC4xNyAwLjI4NywwLjAyMSAwLjMzNywtMC4wNDggMC4yNzMsLTAuMzgxIC0wLjA0MywtMC4yMjUgLTAuMDEzLC0wLjQwOCAwLjA2NywtMC40MDggMC4wOCwwIDAuMTI2LC0wLjMxOSAwLjEwMiwtMC43MDggLTAuMDM3LC0wLjYwOSAwLjAwMiwtMC43MDUgMC4yNzUsLTAuNjgxIDAuMzE1LDAuMDI3IDAuOTE2LC0wLjUzNSAwLjkyNiwtMC44NjYgMC4wMDQsLTAuMDk1IDAuMjMxLC0wLjI1NyAwLjUwNSwtMC4zNiAwLjM5MSwtMC4xNDggMC40OTIsLTAuMjgxIDAuNDY1LC0wLjYwOCAtMC4wMTksLTAuMjMxIC0wLjA5NSwtMC4zODIgLTAuMTY5LC0wLjMzNyAtMC4wNzMsMC4wNDYgLTAuMDkxLDAuMTUzIC0wLjAzOCwwLjIzOCAwLjA1MywwLjA4NiAwLjAxNywwLjE1NSAtMC4wOCwwLjE1NSAtMC4yNTUsMCAtMC4yMTcsLTAuMjU5IDAuMDczLC0wLjUgMC4xMzgsLTAuMTE0IDAuMjUyLC0wLjQwNCAwLjI1NSwtMC42NDUgMC4wMDQsLTAuMjgyIDAuMTY3LC0wLjU1NCAwLjQ1OSwtMC43NjQgMC4zNTYsLTAuMjU2IDAuNDA0LC0wLjI2NSAwLjIyNCwtMC4wNDEgLTAuMiwwLjI0OCAtMC4xODksMC4yODQgMC4wOTIsMC4yODQgMC4yNTIsMCAwLjMzLC0wLjEzNCAwLjM2MywtMC42MjUgMC4wMjMsLTAuMzQ0IDAuMDUzLC0wLjc4OSAwLjA2NiwtMC45OSAwLjAxMywtMC4yIDAuMDkxLC0wLjMyMyAwLjE3MiwtMC4yNzIgMC4wODIsMC4wNSAwLjExMSwwLjIzMiAwLjA2NiwwLjQwNSAtMC4wNTksMC4yMjYgMC4wMDYsMC4zMTUgMC4yMjksMC4zMTUgMC4zNjUsMCAwLjY5MSwtMC41MjggMC42NSwtMS4wNTEgLTAuMDg1LC0xLjA2MyAtMC4wMTYsLTEuMjI4IDAuNDg4LC0xLjE1NCAwLjUyOCwwLjA3NyAwLjg2NiwtMC4yNDYgMC40OTksLTAuNDc4IC0wLjE2NiwtMC4xMDUgLTAuMTcyLC0wLjE0NiAtMC4wMjEsLTAuMTQ4IDAuMTE0LC0wLjAwMSAwLjE5OSwtMC4wNTkgMC4xODgsLTAuMTI3IC0wLjE2NywtMS4wNTcgMC4wMywtMS41NDIgMC42MjQsLTEuNTQyIDAuNTgxLDAgMC42MDEsLTAuMDE5IDAuNTA1LC0wLjQ5NSAtMC4xMTMsLTAuNTY1IDAuMjEsLTAuODUzIDAuNzksLTAuNzA0IDAuMzExLDAuMDggMC4zMjUsMC4wNTggMC4xMjIsLTAuMTg5IC0wLjE5NywtMC4yNDEgLTAuMTksLTAuMjc5IDAuMDU0LC0wLjI3OSAwLjIzNiwwIDAuMjczLC0wLjE0MiAwLjIyOSwtMC44NjggLTAuMDU0LC0wLjg2MiAtMC4wNSwtMC44NjkgMC40MzksLTAuODc1IDAuNDA5LC0wLjAwNiAwLjUxOSwtMC4wOTcgMC42NDcsLTAuNTQgMC4xMTQsLTAuMzk2IDAuMjc3LC0wLjU2IDAuNjMyLC0wLjYzOCAwLjM4MiwtMC4wODQgMC40NzcsLTAuMTk1IDAuNDc1LC0wLjU1IC0wLjAwMiwtMC4zNDkgLTAuMDM3LC0wLjM5MSAtMC4xNjQsLTAuMTk1IC0wLjA5NywwLjE1IC0wLjE2MiwwLjE3IC0wLjE2NCwwLjA1MSAtMTBlLTQsLTAuMTA5IDAuMDczLC0wLjI0NSAwLjE2NCwtMC4zMDEgMC4wOTIsLTAuMDU3IDAuMTY3LC0wLjM1NiAwLjE2NywtMC42NjYgMCwtMC41ODQgMC4zNTcsLTAuODU5IDAuODYzLC0wLjY2NSAwLjI1OCwwLjA5OSAwLjM1NSwtMS43NTcgMC4xMDUsLTIuMDA3IC0wLjA2MywtMC4wNjMgLTAuMDMzLC0wLjI0NyAwLjA2OCwtMC40MDggMC4yODYsLTAuNDU4IDAuMDM2LC0wLjg5IC0wLjQ1OSwtMC43OTYgLTAuNDgzLDAuMDkzIC0wLjUyMywwLjA1MSAtMC42MDIsLTAuNjI1IC0wLjA0OCwtMC40MDggLTAuMTUsLTAuNTE1IC0wLjU1OCwtMC41ODMgLTAuNDA5LC0wLjA2OCAtMC40OTEsLTAuMTU0IC0wLjQ0NywtMC40NjkgMC4wMywtMC4yMTEgLTAuMDE5LC0wLjQ3NCAtMC4xMDgsLTAuNTgzIC0wLjA4OSwtMC4xMDkgLTAuMTM4LC0wLjQwNSAtMC4xMSwtMC42NTcgMC4wMjksLTAuMjUyIC0wLjAyNCwtMC40NTggLTAuMTE4LC0wLjQ1OCAtMC4wOTMsMCAtMC4xMjIsMC4xMjMgLTAuMDY1LDAuMjc0IDAuMDg4LDAuMjI4IDAuMDYzLDAuMjM4IC0wLjE0NywwLjA2NCAtMC4xMzgsLTAuMTE1IC0wLjIwMywtMC4yODcgLTAuMTQ0LC0wLjM4MyAwLjA2NywtMC4xMDkgLTAuMDIyLC0wLjE0IC0wLjIzOSwtMC4wODQgLTAuMjcyLDAuMDcxIC0wLjMzMywwLjAyMyAtMC4yODUsLTAuMjIzIDAuMDMzLC0wLjE3MyAtMC4wMjksLTAuMzE2IC0wLjEzOSwtMC4zMTcgLTAuMTE5LC0wLjAwMiAtMC4xLC0wLjA2NSAwLjA1LC0wLjE2IDAuMjEyLC0wLjEzNCAwLjIxNSwtMC4xODkgMC4wMTcsLTAuMzcyIC0wLjE5MSwtMC4xNzggLTAuMjA3LC0wLjE3MyAtMC4wOTQsMC4wMzQgMC4wODcsMC4xNjEgMC4wMDcsMC4xMzcgLTAuMjI2LC0wLjA2NyAtMC4yMDIsLTAuMTc3IC0wLjM2MiwtMC4yMzIgLTAuMzYyLC0wLjEyNSAwLDAuMTA2IC0wLjA4NCwwLjE5MiAtMC4xODYsMC4xOTIgLTAuMTA0LDAgLTAuMTQ4LC0wLjE0NyAtMC4wOTksLTAuMzMzIDAuMDQ4LC0wLjE4MyAwLjAwNywtMC4zMzMgLTAuMDkyLC0wLjMzMyAtMC4wOTgsMCAtMC4xMjgsLTAuMDUxIC0wLjA2NywtMC4xMTIgMC4wNjIsLTAuMDYyIDAuMzA3LDAuMDcgMC41NDYsMC4yOTIgMC4zOTYsMC4zNjggMC40MjksMC4zNzQgMC4zNzMsMC4wNzQgLTAuMDM0LC0wLjE4MSAtMC4yNDYsLTAuNDIyIC0wLjQ3MywtMC41MzQgLTAuMzExLC0wLjE1NSAtMC40MTIsLTAuMzM5IC0wLjQxNSwtMC43NTUgLTAuMDAzLC0wLjQ5OCAtMC4wNSwtMC41NDkgLTAuNTA0LC0wLjU0OSAtMC40NzUsMCAtMC41MDIsLTAuMDM3IC0wLjU0NCwtMC43NSBDIDk5Ljk2NiwxMC4wNzcgOTkuODc0LDkuODY5IDk5LjM0MSw5Ljc1MiA5OC44NjIsOS42NDcgOTguNjA2LDkuMjY4IDk4LjcwMyw4LjgwOSA5OC43NDcsOC41OTYgOTguNjcxLDguNTY2IDk3Ljk1OCw4LjUxOSA5Ny43MjYsOC41MDQgOTcuNjY3LDguMzQ1IDk3LjY2Nyw3Ljc0IDk3LjY2Nyw3LjI3NiA5Ny42MDIsNy4wMiA5Ny41LDcuMDgzIDk3LjQwOCw3LjE0IDk3LjMzMyw3LjA3OCA5Ny4zMzMsNi45NDYgOTcuMzMzLDYuODE0IDk3LjE4NCw2LjY2NiA5Ny4wMDEsNi42MTkgOTYuNjYsNi41MjkgOTYuNjA1LDYuNDMzIDk2LjQyMiw1LjYwNSA5Ni4zNjUsNS4zNDIgOTYuMjc0LDUuMTcgOTYuMjIxLDUuMjIzIDk2LjE2OCw1LjI3NiA5NS45ODcsNS4xNTQgOTUuODE4LDQuOTUxIDk1LjY0OSw0Ljc0OSA5NS41MDksNC42NjIgOTUuNTA2LDQuNzU4IDk1LjUwMiw0Ljg1NSA5NS41OTQsNS4wMjggOTUuNzA4LDUuMTQzIDk1Ljg3NSw1LjMxMiA5NS44NTYsNS4zMyA5NS42MDksNS4yMzYgOTUuMzY5LDUuMTQ1IDk1LjMyMyw1LjAwOCA5NS40MDEsNC42MiA5NS41ODMsMy43MDggOTUuMjI4LDIuODMzIDk0LjY3NSwyLjgzMyA5NC4zMTMsMi44MzMgOTQuMjA3LDIuNzYyIDk0LjI0MSwyLjU0MiA5NC4yNjYsMi4zODEgOTQuMjAzLDIuMTk1IDk0LjEwMSwyLjEyOCA5My45NjYsMi4wMzggOTMuOTY2LDIuMDA0IDk0LjEwMiwyLjAwMyA5NC4zMTUsMiA5NC4yMTIsMS4xOTcgOTMuOTU3LDAuODc1IDkzLjg2NywwLjc2IDkzLjYxNCwwLjY2NyA5My4zOTYsMC42NjcgOTMuMTExLDAuNjY3IDkzLDAuNTczIDkzLDAuMzMzIDkzLDAuMDAzIDkyLjg4OSwwIDc5LjU4MywwIDY4LjMxNCwwIDY2LjE2NywwLjAzNyA2Ni4xNjcsMC4yMjkgbSAyNy43NzgsMS4yNiBjIDAuMDI4LDAuMTggLTAuMDM1LDAuMzggLTAuMTQsMC40NDUgQyA5My42OCwyLjAxMiA5My42NjEsMS45NzcgOTMuNzUyLDEuODMxIDkzLjg1NCwxLjY2NSA5My44MDksMS42NCA5My41NzQsMS43MyA5My40MDEsMS43OTcgOTMuMzEzLDEuNzk4IDkzLjM3OSwxLjczMiA5My40NDQsMS42NjcgOTMuNDA5LDEuNTA4IDkzLjMwMSwxLjM3OCA5My4xNDgsMS4xOTQgOTMuMTU4LDEuMTA5IDkzLjM0MywwLjk5NCA5My42MjUsMC44MiA5My44NzUsMS4wMjUgOTMuOTQ1LDEuNDg5IG0gMy4yMjIsNS4wNjkgYyAwLjQ1NywwLjI0MSAwLjU5MiwwLjIyNiAwLjQyMywtMC4wNDcgLTAuMDYsLTAuMDk4IC0wLjI3NCwtMC4xNzcgLTAuNDc1LC0wLjE3NSAtMC4zNSwwLjAwMiAtMC4zNDgsMC4wMTEgMC4wNTIsMC4yMjIiCiAgICAgICAgIGlkPSJwYXRoMiIKICAgICAgICAgaW5rc2NhcGU6Y29ubmVjdG9yLWN1cnZhdHVyZT0iMCIKICAgICAgICAgc3R5bGU9ImZpbGw6I2EwYTVhYSIKICAgICAgICAgc29kaXBvZGk6bm9kZXR5cGVzPSJjY3NjY2NjY2NjY2Njc2NzY2NjY2NjY2NzY3Nzc2NzY2NjY2NzY2Nzc3NzY3NjY3NjY2NjY2Njc2NjY2NzY2NjY3NjY2NjY3NjY2NzY2NjY2NjY2NzY3NjY2NjY3Njc2NjY2NjY2NjY2Nzc2Nzc2NjY2NzY2NjY3Njc2Njc2NjY2Njc2NjY2Nzc3NjY2NjY3NjY2NjY2NjIiAvPgogICAgICA8cGF0aAogICAgICAgICBkPSJtIDQwLjA0OSwwLjU2OSBjIDAuMDg1LDAuNTI2IDAuNTksMS4zOTMgMS43MzgsMi45ODIgMC4yNTIsMC4zNSAwLjYwOSwwLjkyIDAuNzk0LDEuMjY3IDAuNDIyLDAuNzk1IDAuODk2LDEuNTY5IDEuMzczLDIuMjQyIDAuMjA0LDAuMjg4IDAuNTM4LDAuODQyIDAuNzQyLDEuMjMyIEMgNDQuOSw4LjY4MSA0NS4xMjUsOSA0NS4xOTcsOSBjIDAuMDczLDAgMC4zNjIsMC40MzEgMC42NDUsMC45NTggMC44ODcsMS42NTcgMS40MSwyLjUxNyAxLjc3MSwyLjkxMSAwLjE5MiwwLjIxIDAuNTA5LDAuNjgxIDAuNzA0LDEuMDQ4IDAuMTk1LDAuMzY2IDAuNjQ5LDEuMTE2IDEuMDEsMS42NjYgMC4zNjEsMC41NSAwLjgxOCwxLjMgMS4wMTYsMS42NjcgMC4xOTgsMC4zNjcgMC41MzYsMC44NTQgMC43NSwxLjA4MyAxLjA0NSwxLjExOCAxLjA5Miw0LjgzNCAwLjA2Miw0LjgzNCAtMC4wNzcsMCAtMC40MDYsMC40MzEgLTAuNzMyLDAuOTU4IC0wLjMyNSwwLjUyNyAtMC43MjIsMS4xNzEgLTAuODgyLDEuNDMxIC0wLjc4NCwxLjI3MiAtMS43MjEsMi43MjkgLTEuOTQ3LDMuMDI3IC0wLjQ1MSwwLjU5OCAtMS40NTUsMi4xNTUgLTEuNzE4LDIuNjY3IC0wLjE0MiwwLjI3NSAtMC40MDEsMC42ODggLTAuNTc2LDAuOTE3IC0wLjM0NywwLjQ1NCAtMS4zMzQsMi4wMTMgLTIuMzgzLDMuNzY1IC0wLjM2NywwLjYxMyAtMC44NzMsMS4zNTcgLTEuMTI1LDEuNjU0IC0wLjI1MiwwLjI5OCAtMC40NTksMC42MDcgLTAuNDU5LDAuNjg3IDAsMC4wNzkgLTAuMjYyLDAuNTE0IC0wLjU4MywwLjk2NSAtMC4zMzUsMC40NzIgLTAuNTgzLDEuMDIxIC0wLjU4MywxLjI5MSBWIDQxIGggMTIuMjUgYyAxMS44ODksMCAxMi4yNSwtMC4wMDkgMTIuMjUsLTAuMzE0IDAsLTAuMTczIC0wLjE4OCwtMC40MzcgLTAuNDE3LC0wLjU4NyAtMC4yMjksLTAuMTUgLTAuNDE3LC0wLjM2NyAtMC40MTcsLTAuNDgyIDAsLTAuMTE1IC0wLjMsLTAuNjA5IC0wLjY2NiwtMS4wOTcgQyA2Mi44LDM4LjAzMiA2Mi41LDM3LjU1NyA2Mi41LDM3LjQ2NCBjIDAsLTAuMDk0IC0wLjIyNSwtMC40MjYgLTAuNSwtMC43MzkgLTAuMjc1LC0wLjMxNCAtMC41LC0wLjY2OSAtMC41LC0wLjc5MSAwLC0wLjEyMSAtMC4yNjIsLTAuNTUgLTAuNTgyLC0wLjk1MiAtMC4zMiwtMC40MDMgLTAuNTgyLC0wLjgyOSAtMC41ODMsLTAuOTQ4IC0xMGUtNCwtMC4xMTkgLTAuMTksLTAuNDA0IC0wLjQyLC0wLjYzNSAtMC4yMzEsLTAuMjMgLTAuNjA0LC0wLjc4NCAtMC44MzEsLTEuMjMgLTAuMjI2LC0wLjQ0NyAtMC40ODksLTAuODYgLTAuNTgzLC0wLjkxOSAtMC4wOTUsLTAuMDU4IC0wLjI4NywtMC4zNzkgLTAuNDI2LC0wLjcxMyAtMC4xNCwtMC4zMzQgLTAuMzgyLC0wLjczMSAtMC41MzksLTAuODgzIC0wLjI3NSwtMC4yNjYgLTAuODIxLC0xLjA4NiAtMS43MDMsLTIuNTU4IC0wLjIyOSwtMC4zODMgLTAuNjU3LC0xLjA2NyAtMC45NSwtMS41MjEgLTAuNTE2LC0wLjc5NiAtMC43NjEsLTEuMTU4IC0xLjYzNCwtMi40MDggLTAuNywtMS4wMDMgLTAuODIyLC0xLjM4MyAtMC44MTQsLTIuNTQyIDAuMDA2LC0wLjg5OCAwLjA3NywtMS4yMDEgMC4zNTEsLTEuNSAwLjE4OSwtMC4yMDYgMC41NDEsLTAuNzY5IDAuNzgyLC0xLjI1IEMgNTMuODA5LDE3LjM5NCA1NC4wNywxNyA1NC4xNDksMTcgYyAwLjA3OSwwIDAuMjM4LC0wLjIyNyAwLjM1MywtMC41MDUgMC4xMTUsLTAuMjc4IDAuNDIsLTAuNzcxIDAuNjc4LC0xLjA5NSAwLjI1OCwtMC4zMjQgMC41NDksLTAuODM1IDAuNjQ4LC0xLjEzNSAwLjA5OSwtMC4zIDAuMzY2LC0wLjcwNyAwLjU5MywtMC45MDQgMC4yMjcsLTAuMTk3IDAuNDEyLC0wLjQ2OSAwLjQxMiwtMC42MDQgMCwtMC4xMzUgMC4yMTUsLTAuNDkyIDAuNDc4LC0wLjc5MyAwLjI2MywtMC4zMDEgMC42MzYsLTAuODYxIDAuODMxLC0xLjI0NCAwLjE5NCwtMC4zODQgMC40MjQsLTAuNzQxIDAuNTEyLC0wLjc5NSBDIDU4Ljc0MSw5Ljg3MSA1OSw5LjQxNiA1OS4yMyw4LjkxMyA1OS40Niw4LjQxMSA1OS43Myw4IDU5LjgzLDggNTkuOTMsOCA2MC4xNTMsNy42NzEgNjAuMzI1LDcuMjY4IDYwLjQ5OCw2Ljg2NiA2MC43MjIsNi40ODUgNjAuODIzLDYuNDIzIDYwLjkyNSw2LjM2IDYxLjIzMiw1Ljg2NSA2MS41MDYsNS4zMjEgNjEuNzgsNC43NzggNjIuMDY3LDQuMzMzIDYyLjE0NCw0LjMzMyA2Mi4yMiw0LjMzMyA2Mi40MTYsNC4wMzcgNjIuNTgsMy42NzUgNjIuNzQzLDMuMzEzIDYzLjAxLDIuODczIDYzLjE3MywyLjY5OCA2My4zMzYsMi41MjMgNjMuNjMsMi4wNjYgNjMuODI2LDEuNjgyIDY0LjAyMSwxLjI5OCA2NC4yOTEsMC45NDIgNjQuNDI0LDAuODkxIDY0LjU1OCwwLjg0IDY0LjY2NywwLjYxOCA2NC42NjcsMC4zOTkgViAwIEggNTIuMzEyIDM5Ljk1NiBsIDAuMDkzLDAuNTY5IgogICAgICAgICBpZD0icGF0aDMiCiAgICAgICAgIGlua3NjYXBlOmNvbm5lY3Rvci1jdXJ2YXR1cmU9IjAiCiAgICAgICAgIHN0eWxlPSJmaWxsOiNhMGE1YWEiIC8+CiAgICAgIDxwYXRoCiAgICAgICAgIGQ9Im0gMzcuNzgxLDAuNDcyIGMgMC4wMzYsMC4yMTQgLTAuMDA0LDAuMzQ2IC0wLjA4OSwwLjI5NCAtMC4zMDIsLTAuMTg3IC0wLjU2NiwwLjM4MyAtMC40NTMsMC45ODIgMC4wNzEsMC4zODIgMC4wNDYsMC41ODUgLTAuMDc1LDAuNTg1IC0wLjEwMSwwIC0wLjEzNywtMC4wNzUgLTAuMDgxLC0wLjE2NiAwLjE1NiwtMC4yNTMgLTAuMjg5LC0wLjE5IC0wLjY2NCwwLjA5NCAtMC4zMjcsMC4yNDcgLTAuMzI3LDAuMjUxIDAuMDMzLDAuMzQ1IDAuMzA3LDAuMDggMC4zMzMsMC4xNDMgMC4xNywwLjQwNCAtMC4xMTgsMC4xODkgLTAuMjM2LDAuMjQyIC0wLjMwMSwwLjEzNiAtMC4wNjYsLTAuMTA2IC0wLjE0NywtMC4wNzEgLTAuMjA5LDAuMDkgLTAuMDU2LDAuMTQ1IC0wLjAzMywwLjI2NCAwLjA1LDAuMjY0IDAuMTUsMCAwLjA5NSwwLjYzMiAtMC4wNjksMC43OTYgQyAzNi4wNDYsNC4zNDMgMzUuODgsNC4yNzYgMzUuNzIzLDQuMTQ5IDM1LjQ3NCwzLjk0NiAzNS40NSwzLjk1MSAzNS41NCw0LjE4OSAzNS42MDUsNC4zNjQgMzUuNDY2LDQuNjMgMzUuMTU0LDQuOTI5IDM0LjY5LDUuMzc0IDM0LjU4Nyw1Ljc5NyAzNC44ODksNi4wMTggMzQuOTY1LDYuMDc0IDM0LjkxLDYuMTY2IDM0Ljc2Niw2LjIyMSAzNC42MDksNi4yODEgMzQuNTIsNi4yNCAzNC41NDQsNi4xMTkgMzQuNTY2LDYuMDA1IDM0LjQwNyw1LjkxNyAzNC4xODEsNS45MTcgMzMuOTU5LDUuOTE3IDMzLjc1OCw2LjAxMiAzMy43MzUsNi4xMjkgMzMuNzExLDYuMjQ2IDMzLjczLDYuMjY1IDMzLjc3Niw2LjE3MSAzMy44MjMsNi4wNzcgMzMuOTMyLDYgMzQuMDIsNiBjIDAuMDk1LDAgMC4wODgsMC4xMzEgLTAuMDE2LDAuMzI3IC0wLjA5NywwLjE3OSAtMC4xNjUsMC4zODUgLTAuMTUzLDAuNDU4IDAuMDczLDAuNDM2IC0wLjA0MSwwLjY5NiAtMC4yNjgsMC42MDkgLTAuMTM3LC0wLjA1MyAtMC4yNSwtMC4wMDkgLTAuMjUsMC4wOTggMCwwLjEwNyAtMC4wNjksMC4xNTEgLTAuMTU0LDAuMDk5IEMgMzMuMDA2LDcuNDg0IDMyLjc0Nyw4LjEzNiAzMi45MDEsOC4yOSAzMi45NTYsOC4zNDUgMzMsOC4zMDQgMzMsOC4yIDMzLDguMDk3IDMzLjE0OCw3LjkzMiAzMy4zMyw3LjgzNSAzMy42LDcuNjkxIDMzLjY0MSw3LjcwNSAzMy41Niw3LjkxNyAzMy40MDksOC4zMTEgMzIuOTIsOC41MiAzMi42ODksOC4yODkgMzIuNTQsOC4xNCAzMi41MTksOC4yMiAzMi42MDEsOC42MyBjIDAuMDg1LDAuNDI0IDAuMDUyLDAuNTM3IC0wLjE1OCwwLjUzNyAtMC4xNDUsMCAtMC4zNDYsMC4xNTIgLTAuNDQ1LDAuMzM4IC0wLjEwNywwLjE5OCAtMC4yNjQsMC4yODggLTAuMzgxLDAuMjE4IC0wLjEyMSwtMC4wNzIgLTAuMTA5LC0wLjAwOCAwLjAyOCwwLjE2MiAwLjE0OSwwLjE4MyAwLjE2NCwwLjI4MiAwLjA0MiwwLjI4MiAtMC4xMDMsMCAtMC4xODcsMC4xMTcgLTAuMTg3LDAuMjYgMCwwLjE0MiAwLjA3NCwwLjIxNCAwLjE2NSwwLjE1OCAwLjA5NCwtMC4wNTkgMC4xMjIsMC4wMDggMC4wNjUsMC4xNTYgQyAzMS42NzYsMTAuODg0IDMxLjU1NSwxMSAzMS40NjMsMTEgMzEuMzcsMTEgMzEuMzM4LDExLjE2OSAzMS4zOTEsMTEuMzc1IEwgMzEuNDg3LDExLjc1IDMxLjE5MywxMS4zNjUgMzAuOSwxMC45OCAzMC42NjYsMTEuNDE4IGMgLTAuMjAyLDAuMzc4IC0wLjIwMSwwLjQ2IDAuMDA4LDAuNjA2IDAuMTc4LDAuMTI0IDAuMTA5LDAuMTQzIC0wLjI1NywwLjA3MSAtMC4zODIsLTAuMDc2IC0wLjQzNSwtMC4wNTkgLTAuMjIzLDAuMDY5IDAuMTUyLDAuMDkzIDAuMjUxLDAuMzAxIDAuMjIxLDAuNDYxIC0wLjAzMSwwLjE2IC0wLjA2MiwwLjM5NCAtMC4wNjksMC41MTkgLTAuMDA4LDAuMTQ0IC0wLjEzNiwwLjE5NiAtMC4zNDYsMC4xNDEgLTAuMjAxLC0wLjA1MyAtMC4yOTIsLTAuMDIgLTAuMjI5LDAuMDgyIDAuMDY0LDAuMTAzIC0wLjAyMiwwLjEyOSAtMC4yMjIsMC4wNjUgLTAuMjI2LC0wLjA3MiAtMC4zMjcsLTAuMDI2IC0wLjMyNywwLjE0OSAwLDAuMTM5IDAuMDc4LDAuMjUyIDAuMTcyLDAuMjUyIDAuMTEsMCAwLjEwNSwwLjA2OCAtMC4wMTQsMC4xODcgLTAuMTAzLDAuMTAzIC0wLjE0OCwwLjI5MSAtMC4wOTksMC40MTcgMC4wNTEsMC4xMzMgLTAuMDI1LDAuMjM4IC0wLjE4LDAuMjUxIC0wLjE0NywwLjAxMSAtMC4zNTQsMC4wMyAtMC40NiwwLjA0MSAtMC4xMDYsMC4wMTIgLTAuMjE1LDAuMTMzIC0wLjI0MSwwLjI3MSAtMC4wMjcsMC4xMzcgLTAuMSwwLjQ3NSAtMC4xNjIsMC43NSAtMC4wNjIsMC4yNzUgLTAuMTM1LDAuNjUgLTAuMTYyLDAuODMzIC0wLjAyNiwwLjE4NCAtMC4wNTcsMC4zNzEgLTAuMDY3LDAuNDE3IC0wLjAxMSwwLjA0NiAtMC4wODksLTAuMDM2IC0wLjE3NCwtMC4xODEgLTAuMTI1LC0wLjIxNCAtMC40OSwtMC4yNzYgLTAuOTgsLTAuMTY2IC0wLjAzNSwwLjAwNyAwLjAyNSwwLjExOSAwLjEzMywwLjI0OSAwLjE0NywwLjE3NyAwLjE0OCwwLjI2NCAwLjAwMiwwLjM1NCAtMC4xNCwwLjA4NyAtMC4xMzEsMC4xNTggMC4wMzQsMC4yNjIgMC4xNjYsMC4xMDYgMC4xNzEsMC4xNDQgMC4wMTgsMC4xNDYgLTAuMTE1LDAuMDAyIC0wLjIwOSwwLjEyIC0wLjIwOSwwLjI2MyAwLDAuMTUzIC0wLjA4NSwwLjIxNiAtMC4yMDgsMC4xNTQgLTAuMTE1LC0wLjA1OCAtMC4wNzgsMC4wMDEgMC4wODIsMC4xMzIgMC4zOTksMC4zMjcgLTAuMTAxLDAuODE0IC0wLjcxMywwLjY5NCAtMC4yNTYsLTAuMDUgLTAuMzE2LC0wLjAzMiAtMC4xNjEsMC4wNDggMC4yOTEsMC4xNDkgMC40MTIsMC41NDUgMC4xNjcsMC41NDUgLTAuMzEsMCAtMC4xNjksMC40MiAwLjE5NywwLjU4NSAwLjIsMC4wOTEgMC40NDMsMC4zOTcgMC41NDEsMC42ODIgMC4wOTgsMC4yODQgMC4zMSwwLjY0NSAwLjQ3LDAuODAzIDAuMTYxLDAuMTU4IDAuMjkyLDAuMzgxIDAuMjkyLDAuNDk1IDAsMC4xMTUgMC4yNTUsMC41MDYgMC41NjcsMC44NyAwLjMxMSwwLjM2NCAwLjY4MiwwLjk0IDAuODI0LDEuMjggMC4xNDIsMC4zNCAwLjMzOCwwLjYxOCAwLjQzNiwwLjYxOCAwLjA5NywwIDAuMzU1LDAuNDAzIDAuNTczLDAuODk2IDAuMjE4LDAuNDkyIDAuNDgxLDAuOTQ3IDAuNTg0LDEuMDExIDAuMTAzLDAuMDY0IDAuMzQ2LDAuNDMgMC41NCwwLjgxMyAwLjE5NCwwLjM4MyAwLjQ1NiwwLjc1NyAwLjU4MiwwLjgzMSAwLjEyNiwwLjA3NCAwLjM4OSwwLjQ0OSAwLjU4NSwwLjgzMyAwLjE5NSwwLjM4NSAwLjUzMiwwLjkxMiAwLjc0OCwxLjE3MSAwLjIxNywwLjI1OSAwLjM5NCwwLjU2MSAwLjM5NCwwLjY3MSAwLDAuMTEgMC4yODIsMC41ODIgMC42MjUsMS4wNSAwLjYyMiwwLjg0NSAwLjk1MywxLjM2IDEuNjY2LDIuNTk4IDAuMjA2LDAuMzU3IDAuNDk2LDAuNzcgMC42NDUsMC45MTkgMC4xNDgsMC4xNDggMC40MDgsMC41OTMgMC41NzgsMC45ODkgMC4xNjksMC4zOTUgMC4zNzcsMC43MTggMC40NjEsMC43MTggMC4wODUsMCAwLjI3MiwwLjI0OCAwLjQxNSwwLjU1MSAwLjE0NCwwLjMwMyAwLjQ5LDAuODM4IDAuNzY5LDEuMTkgQyAzOC44NiwzOS41MzcgMzksMzkuODkgMzksNDAuMzU2IDM5LDQwLjk4MSAzOS4wMTcsNDEgMzkuNTgzLDQxIGMgMC41MjksMCAwLjU4NCwtMC4wNDQgMC41ODQsLTAuNDcxIDAsLTAuMjcgMC4yNDgsLTAuODE5IDAuNTgzLC0xLjI5MSAwLjMyMSwtMC40NTEgMC41ODMsLTAuODg2IDAuNTgzLC0wLjk2NSAwLC0wLjA4IDAuMjA3LC0wLjM4OSAwLjQ1OSwtMC42ODcgMC4yNTIsLTAuMjk3IDAuNzU4LC0xLjA0MSAxLjEyNSwtMS42NTQgMS4wNDksLTEuNzUyIDIuMDM2LC0zLjMxMSAyLjM4MywtMy43NjUgMC4xNzUsLTAuMjI5IDAuNDM0LC0wLjY0MiAwLjU3NiwtMC45MTcgMC4yNjMsLTAuNTEyIDEuMjY3LC0yLjA2OSAxLjcxOCwtMi42NjcgMC4yMjYsLTAuMjk4IDEuMTYzLC0xLjc1NSAxLjk0NywtMy4wMjcgMC4xNiwtMC4yNiAwLjU1NywtMC45MDQgMC44ODIsLTEuNDMxIDAuMzI2LC0wLjUyNyAwLjY1NSwtMC45NTggMC43MzIsLTAuOTU4IDEuMDMsMCAwLjk4MywtMy43MTYgLTAuMDYyLC00LjgzNCAtMC4yMTQsLTAuMjI5IC0wLjU1MiwtMC43MTYgLTAuNzUsLTEuMDgzIC0wLjE5OCwtMC4zNjcgLTAuNjU1LC0xLjExNyAtMS4wMTYsLTEuNjY3IC0wLjM2MSwtMC41NSAtMC44MTUsLTEuMyAtMS4wMSwtMS42NjYgQyA0OC4xMjIsMTMuNTUgNDcuODA1LDEzLjA3OSA0Ny42MTMsMTIuODY5IDQ3LjI1MiwxMi40NzUgNDYuNzI5LDExLjYxNSA0NS44NDIsOS45NTggNDUuNTU5LDkuNDMxIDQ1LjI3LDkgNDUuMTk3LDkgNDUuMTI1LDkgNDQuOSw4LjY4MSA0NC42OTYsOC4yOTIgNDQuNDkyLDcuOTAyIDQ0LjE1OCw3LjM0OCA0My45NTQsNy4wNiA0My40NzcsNi4zODcgNDMuMDAzLDUuNjEzIDQyLjU4MSw0LjgxOCA0Mi4zOTYsNC40NzEgNDIuMDM5LDMuOTAxIDQxLjc4NywzLjU1MSA0MC42MzksMS45NjIgNDAuMTM0LDEuMDk1IDQwLjA0OSwwLjU2OSAzOS45NTcsMC4wMDIgMzkuOTUzLDAgMzkuMDQzLDAgMzguMTU5LDAgMzguMTM0LDAuMDEzIDM4LjIzMywwLjQwNiAzOC4yODksMC42MyAzOC4yNiwwLjg1OSAzOC4xNjksMC45MTUgMzguMDc0LDAuOTc0IDM4LjA0NiwwLjkwNyAzOC4xMDMsMC43NTkgMzguMTU4LDAuNjE2IDM4LjEyNSwwLjUgMzguMDMsMC41IDM3LjkzNSwwLjUgMzcuODI2LDAuNDA2IDM3Ljc4NywwLjI5MiAzNy43NDcsMC4xNzcgMzcuNzQ1LDAuMjU4IDM3Ljc4MSwwLjQ3MiBNIDk1LjUsNC40NzQgYyAwLDAuMTg5IDAuNjQ4LDAuNzY3IDAuNzQ3LDAuNjY4IDAuMTExLC0wLjExMSAtMC4zOTksLTAuODA5IC0wLjU5LC0wLjgwOSAtMC4wODYsMCAtMC4xNTcsMC4wNjQgLTAuMTU3LDAuMTQxIE0gMzIuNjY3LDkuNjYxIGMgMCwwLjIyNCAtMC4wOCwwLjI5OCAtMC4yNSwwLjIzMyAtMC4xMzgsLTAuMDUzIC0wLjI1LC0wLjIwMSAtMC4yNSwtMC4zMjggMCwtMC4xMjggMC4xMTIsLTAuMjMzIDAuMjUsLTAuMjMzIDAuMTM3LDAgMC4yNSwwLjE0OCAwLjI1LDAuMzI4IG0gLTMuMjc2LDIuNzYzIGMgLTAuMTUsMC4zOTEgMC4wMTIsMC40MzcgMC4yNDIsMC4wNjggMC4xMjgsLTAuMjA2IDAuMTQsLTAuMzI1IDAuMDMsLTAuMzI1IC0wLjA5NSwwIC0wLjIxOCwwLjExNSAtMC4yNzIsMC4yNTcgbSAtMC4yMjQsMi41NCBjIDAsMC4wODIgLTAuNDk3LDAuNTczIC0wLjUxMywwLjUwNiAtMC4xMTEsLTAuNDY0IC0wLjA3NSwtMC41NjUgMC4xOTksLTAuNTY1IDAuMTczLDAgMC4zMTQsMC4wMjcgMC4zMTQsMC4wNTkgbSAtMy43MzgsNC42MzEgQyAyNS4xMjcsMTkuODk3IDI1LjEwMSwyMCAyNS4zMjcsMjAgYyAwLjA4OSwwIDAuMjQyLC0wLjE1IDAuMzQsLTAuMzMzIDAuMjE0LC0wLjQwMSAwLjExOSwtMC40MjkgLTAuMjM4LC0wLjA3MiIKICAgICAgICAgaWQ9InBhdGg0IgogICAgICAgICBpbmtzY2FwZTpjb25uZWN0b3ItY3VydmF0dXJlPSIwIgogICAgICAgICBzdHlsZT0iZmlsbDojYTBhNWFhIgogICAgICAgICBzb2RpcG9kaTpub2RldHlwZXM9ImNjY3NjY2NjY2Nzc2NjY2NjY3NjY3NjY2NzY3NzY2NzY3NjY2Nzc2Njc2NjY2NjY2NjY2NjY2Nzc3NjY2Njc2NjY2NjY2Njc2NjY2NzY2Njc2Nzc2Nzc3NjY3NjY3Njc2Njc3NzY3NjY2NjY2Njc2Nzc2NjY3NjY2NjY3NjY2NzY2Njc3NjY2Nzc2NjY3NjY2NzY2NzY2MiIC8+CiAgICAgIDxwYXRoCiAgICAgICAgIGQ9Im0gNjUuNTA0LDAuNDU4IGMgMC4wMDQsMC41OTQgMC41NzMsMS44NDQgMS4wOCwyLjM3NSAwLjIxOSwwLjIzIDAuNTEsMC42ODUgMC42NDgsMS4wMTQgMC4xMzcsMC4zMjggMC4zNzMsMC43IDAuNTI2LDAuODI2IDAuMTUyLDAuMTI3IDAuNDQ2LDAuNjA4IDAuNjUyLDEuMDcgMC4yMDYsMC40NjIgMC41MzYsMC45ODMgMC43MzMsMS4xNTggMC4xOTYsMC4xNzQgMC4zNTcsMC40MTYgMC4zNTcsMC41MzYgMCwwLjEyIDAuMjI1LDAuNDc0IDAuNSwwLjc4OCAwLjI3NSwwLjMxMyAwLjUsMC42NDcgMC41LDAuNzQzIDAsMC4wOTUgMC4yNTUsMC41MDcgMC41NjYsMC45MTUgMC4zMTIsMC40MDggMC43MjUsMS4wNyAwLjkxOCwxLjQ3MSAwLjM1MiwwLjczMyAwLjgzNCwxLjQ3NSAxLjU0OCwyLjM4NiAwLjIxMiwwLjI3IDAuNDgyLDAuNzE5IDAuNjAyLDAuOTk5IDAuMTE5LDAuMjggMC4zNiwwLjY2IDAuNTM0LDAuODQ1IDAuMTc0LDAuMTg2IDAuNDI4LDAuNjA1IDAuNTY0LDAuOTMyIDAuMTM3LDAuMzI2IDAuMzY2LDAuNjkyIDAuNTEsMC44MTEgMC4xNDQsMC4xMTkgMC40MDEsMC41MjYgMC41NywwLjkwMyAwLjE3LDAuMzc4IDAuNDk5LDAuODg5IDAuNzMyLDEuMTM2IDAuODQ1LDAuODk3IDAuNzY2LDEuOTQ2IC0wLjIzNSwzLjA5OCAtMC4yNjIsMC4zMDEgLTAuNDc2LDAuNjE5IC0wLjQ3NiwwLjcwNyAwLDAuMDg4IC0wLjI2MiwwLjUwMyAtMC41ODMsMC45MjQgLTAuMzIxLDAuNDIgLTAuNTgzLDAuODY1IC0wLjU4MywwLjk4OCAwLDAuMTI0IC0wLjIwMSwwLjQ2NCAtMC40NDcsMC43NTUgLTAuMjQ1LDAuMjkyIC0wLjU2NCwwLjczOCAtMC43MDgsMC45OTEgLTAuMzc0LDAuNjU3IC0xLjI1NSwxLjk5MiAtMS43NTMsMi42NTYgLTAuMjM0LDAuMzEzIC0wLjQyNiwwLjY1OSAtMC40MjYsMC43NjkgMCwwLjExIC0wLjIzMSwwLjQ5MiAtMC41MTUsMC44NDggLTAuMjgzLDAuMzU2IC0wLjYxNywwLjg5MiAtMC43NDEsMS4xOSAtMC4xMjUsMC4yOTggLTAuMzA0LDAuNTQxIC0wLjM5OSwwLjU0MSAtMC4wOTUsMCAtMC4zNiwwLjM3IC0wLjU4OSwwLjgyMiAtMC4yMjksMC40NTIgLTAuNTAxLDAuODc0IC0wLjYwNCwwLjkzOCAtMC4xMDQsMC4wNjQgLTAuMzY3LDAuNTE5IC0wLjU4NSwxLjAxMiAtMC4yMTgsMC40OTIgLTAuNDUzLDAuODk1IC0wLjUyMywwLjg5NSAtMC4wNywwIC0wLjMzMiwwLjM2NSAtMC41ODIsMC44MSAtMC4yNSwwLjQ0NiAtMC41NzcsMC45MTEgLTAuNzI2LDEuMDM1IC0wLjMyNCwwLjI2OSAtMS4wNjksMS45MDEgLTEuMDY5LDIuMzQyIDAsMC42NzMgMC41ODcsMC4zMzggMC44MjYsLTAuNDcxIDAuMTI3LC0wLjQzMiAwLjM5NSwtMC45MzggMC41OTUsLTEuMTI1IDAuMjAxLC0wLjE4OCAwLjQ5OCwtMC42MDQgMC42NjIsLTAuOTI0IDAuMTYzLC0wLjMyMSAwLjQ2OCwtMC43ODggMC42NzYsLTEuMDM4IDAuMjA5LC0wLjI1IDAuNDY0LC0wLjcxMiAwLjU2OCwtMS4wMjcgMC4xMDQsLTAuMzE1IDAuMjk1LC0wLjYxMyAwLjQyNCwtMC42NjMgMC4xMywtMC4wNDkgMC40MjksLTAuNDUgMC42NjUsLTAuODkgMC40ODYsLTAuOTAzIDEuMDM1LC0xLjczMSAxLjYzNiwtMi40NjYgMC4yMjUsLTAuMjc2IDAuNTI5LC0wLjc4OSAwLjY3NiwtMS4xNCAwLjE0NywtMC4zNTIgMC40NTYsLTAuODY0IDAuNjg2LC0xLjEzNyAwLjIzMSwtMC4yNzQgMC40MTksLTAuNTc0IDAuNDE5LC0wLjY2NyAwLC0wLjA5MyAwLjI2MywtMC40NzYgMC41ODQsLTAuODUxIDAuMzIsLTAuMzc1IDAuNTgzLC0wLjc3NSAwLjU4MywtMC44ODkgMCwtMC4xMTQgMC4xNzcsLTAuNDE5IDAuMzkzLC0wLjY3OCAwLjIxNiwtMC4yNTkgMC41MjcsLTAuNzMzIDAuNjkxLC0xLjA1NCAwLjE2NCwtMC4zMjEgMC41MTIsLTAuODQ1IDAuNzc0LC0xLjE2MyAwLjI2MSwtMC4zMTkgMC40NzUsLTAuNjcgMC40NzUsLTAuNzc5IDAsLTAuMTEgMC4xOTQsLTAuNDI5IDAuNDMxLC0wLjcxMSAwLjIzNiwtMC4yODEgMC42MDEsLTAuODQ5IDAuODEsLTEuMjYzIGwgMC4zODEsLTAuNzUyIC0wLjMxNCwtMC45NTIgQyA3Ny45NjgsMTkuNTIzIDc3LjY0NSwxOC45MDUgNzcuNDIyLDE4LjY3MiA3Ny4yLDE4LjQ0IDc2LjkwNiwxNy45ODIgNzYuNzY4LDE3LjY1MyA3Ni42MzEsMTcuMzI1IDc2LjM5NSwxNi45NTMgNzYuMjQyLDE2LjgyNyA3Ni4wOSwxNi43IDc1Ljc4OSwxNi4yIDc1LjU3NSwxNS43MTUgNzUuMzYsMTUuMjMgNzUuMTAyLDE0LjgzMyA3NS4wMDEsMTQuODMzIGMgLTAuMTAyLDAgLTAuMzAxLC0wLjI4MSAtMC40NDIsLTAuNjI1IC0wLjE0MiwtMC4zNDMgLTAuMzksLTAuNzc1IC0wLjU1MSwtMC45NTggLTAuMTYxLC0wLjE4MyAtMC40MzQsLTAuNTk2IC0wLjYwNiwtMC45MTcgLTAuMTczLC0wLjMyMSAtMC40NzUsLTAuNzcgLTAuNjcyLC0xIEMgNzIuNTMyLDExLjEwNCA3Mi4zMTcsMTAuNzI5IDcyLjI1MiwxMC41IDcyLjE4NiwxMC4yNzEgNzEuOTI3LDkuODIxIDcxLjY3Niw5LjUgNzEuNDI0LDkuMTc5IDcxLjA5OSw4LjY4NiA3MC45NTMsOC40MDUgNzAuODA4LDguMTIzIDcwLjU2Nyw3Ljc5MSA3MC40MTgsNy42NjggNzAuMjY5LDcuNTQ0IDcwLjAzNiw3LjE3NiA2OS44OTksNi44NDkgNjkuNzYzLDYuNTIyIDY5LjUwOCw2LjEwMyA2OS4zMzMsNS45MTcgNjkuMTU5LDUuNzMxIDY4LjkwMSw1LjMwNCA2OC43Niw0Ljk2NyA2OC42Miw0LjYzMSA2OC40MjgsNC4zMDggNjguMzMzLDQuMjUgNjguMjM5LDQuMTkyIDY4LjAwMywzLjgzIDY3LjgwOSwzLjQ0NyA2Ny42MTQsMy4wNjQgNjcuMjQ5LDIuNTE0IDY2Ljk5NywyLjIyNSA2Ni43NDUsMS45MzYgNjYuNDQxLDEuMzE3IDY2LjMyMSwwLjg1IDY2LjA3MiwtMC4xMjMgNjUuNDk3LC0wLjM5OSA2NS41MDQsMC40NTggbSAyNy42NjMsMC42OTkgYyAwLDAuMDg2IDAuMDc2LDAuMjA0IDAuMTY5LDAuMjYxIDAuMDkzLDAuMDU4IDAuMTI3LDAuMTc0IDAuMDc1LDAuMjU4IC0wLjA1MywwLjA4NSAwLjAzMywwLjEwNCAwLjE5MSwwLjA0NCAwLjIwOSwtMC4wODEgMC4yNDksLTAuMDUxIDAuMTUsMC4xMTEgQyA5My42NTgsMS45ODIgOTMuNjc5LDIuMDEyIDkzLjgyLDEuOTI1IDkzLjk3MSwxLjgzMiA5My45MjYsMS42OTMgOTMuNjUsMS4zOTkgOTMuMjM3LDAuOTYgOTMuMTY3LDAuOTI0IDkzLjE2NywxLjE1NyIKICAgICAgICAgaWQ9InBhdGg1IgogICAgICAgICBpbmtzY2FwZTpjb25uZWN0b3ItY3VydmF0dXJlPSIwIgogICAgICAgICBzdHlsZT0iZmlsbDojYTBhNWFhIgogICAgICAgICBzb2RpcG9kaTpub2RldHlwZXM9ImNjY2NzY3Njc2NjY2NjY3NjY2NzY3NjY2NzY2Nzc2Njc2Njc2NjY2NzY2NjY2NzY3Nzc2NzY2NjY2NjY2NzY3NjY2NjY2NjY2NjY3NjY2NjY2NjY2NjIiAvPgogICAgICA8cGF0aAogICAgICAgICBkPSJtIDY0LjY2NywwLjM5OSBjIDAsMC4yMTkgLTAuMTA5LDAuNDQxIC0wLjI0MywwLjQ5MiAtMC4xMzMsMC4wNTEgLTAuNDAzLDAuNDA3IC0wLjU5OCwwLjc5MSAtMC4xOTYsMC4zODQgLTAuNDksMC44NDEgLTAuNjUzLDEuMDE2IC0wLjE2MywwLjE3NSAtMC40MywwLjYxNSAtMC41OTMsMC45NzcgLTAuMTY0LDAuMzYyIC0wLjM2LDAuNjU4IC0wLjQzNiwwLjY1OCAtMC4wNzcsMCAtMC4zNjQsMC40NDUgLTAuNjM4LDAuOTg4IEMgNjEuMjMyLDUuODY1IDYwLjkyNSw2LjM2IDYwLjgyMyw2LjQyMyA2MC43MjIsNi40ODUgNjAuNDk4LDYuODY2IDYwLjMyNSw3LjI2OCA2MC4xNTMsNy42NzEgNTkuOTMsOCA1OS44Myw4IDU5LjczLDggNTkuNDYsOC40MTEgNTkuMjMsOC45MTMgNTksOS40MTYgNTguNzQxLDkuODcxIDU4LjY1NCw5LjkyNSBjIC0wLjA4OCwwLjA1NCAtMC4zMTgsMC40MTEgLTAuNTEyLDAuNzk1IC0wLjE5NSwwLjM4MyAtMC41NjgsMC45NDMgLTAuODMxLDEuMjQ0IC0wLjI2MywwLjMwMSAtMC40NzgsMC42NTggLTAuNDc4LDAuNzkzIDAsMC4xMzUgLTAuMTg1LDAuNDA3IC0wLjQxMiwwLjYwNCAtMC4yMjcsMC4xOTcgLTAuNDk0LDAuNjA0IC0wLjU5MywwLjkwNCAtMC4wOTksMC4zIC0wLjM5LDAuODExIC0wLjY0OCwxLjEzNSAtMC4yNTgsMC4zMjQgLTAuNTYzLDAuODE3IC0wLjY3OCwxLjA5NSBDIDU0LjM4NywxNi43NzMgNTQuMjI4LDE3IDU0LjE0OSwxNyBjIC0wLjA3OSwwIC0wLjM0LDAuMzk0IC0wLjU4MSwwLjg3NSAtMC4yNDEsMC40ODEgLTAuNTkzLDEuMDQ0IC0wLjc4MiwxLjI1IC0wLjI3NCwwLjI5OSAtMC4zNDUsMC42MDIgLTAuMzUxLDEuNSAtMC4wMDgsMS4xNTkgMC4xMTQsMS41MzkgMC44MTQsMi41NDIgMC44NzMsMS4yNSAxLjExOCwxLjYxMiAxLjYzNCwyLjQwOCAwLjI5MywwLjQ1NCAwLjcyMSwxLjEzOCAwLjk1LDEuNTIxIDAuODgyLDEuNDcyIDEuNDI4LDIuMjkyIDEuNzAzLDIuNTU4IDAuMTU3LDAuMTUyIDAuMzk5LDAuNTQ5IDAuNTM5LDAuODgzIDAuMTM5LDAuMzM0IDAuMzMxLDAuNjU1IDAuNDI2LDAuNzEzIDAuMDk0LDAuMDU5IDAuMzU3LDAuNDcyIDAuNTgzLDAuOTE5IDAuMjI3LDAuNDQ2IDAuNiwxIDAuODMxLDEuMjMgMC4yMywwLjIzMSAwLjQxOSwwLjUxNiAwLjQyLDAuNjM1IDEwZS00LDAuMTE5IDAuMjYzLDAuNTQ1IDAuNTgzLDAuOTQ4IDAuMzIsMC40MDIgMC41ODIsMC44MzEgMC41ODIsMC45NTIgMCwwLjEyMiAwLjIyNSwwLjQ3NyAwLjUsMC43OTEgMC4yNzUsMC4zMTMgMC41LDAuNjQ1IDAuNSwwLjczOSAwLDAuMDkzIDAuMywwLjU2OCAwLjY2NywxLjA1NiAwLjM2NiwwLjQ4OCAwLjY2NiwwLjk4MiAwLjY2NiwxLjA5NyAwLDAuMTE1IDAuMTg4LDAuMzMyIDAuNDE3LDAuNDgyIDAuMjI5LDAuMTUgMC40MTcsMC40MTQgMC40MTcsMC41ODcgMCwwLjE3MyAwLjExMiwwLjMxNCAwLjI1LDAuMzE0IDAuMzE4LDAgMC4zMjIsLTAuNjE0IDAuMDA4LC0xLjE2NyAtMC4xMywtMC4yMjkgLTAuNDQxLC0wLjgxIC0wLjY5MSwtMS4yOTEgLTAuMjUsLTAuNDgyIC0wLjUyMiwtMC44NzUgLTAuNjA0LC0wLjg3NSAtMC4wODIsMCAtMC4yOTQsLTAuMzI4IC0wLjQ3MSwtMC43MjggLTAuMTc3LC0wLjQwMSAtMC40MzYsLTAuODIzIC0wLjU3NiwtMC45MzkgLTAuMTM5LC0wLjExNiAtMC4zOTIsLTAuNTIgLTAuNTYyLC0wLjg5NyAtMC4xNywtMC4zNzcgLTAuNTA3LC0wLjg2OCAtMC43NDgsLTEuMDkgLTAuMjQyLC0wLjIyMyAtMC40NCwtMC40OTggLTAuNDQsLTAuNjEzIDAsLTAuMTE0IC0wLjE4OSwtMC40MzIgLTAuNDIsLTAuNzA3IC0wLjIzMSwtMC4yNzQgLTAuNTY0LC0wLjgyNCAtMC43NDEsLTEuMjIxIC0wLjE3NiwtMC4zOTcgLTAuNDc1LC0wLjg1MyAtMC42NjMsLTEuMDEyIC0wLjE4OCwtMC4xNiAtMC4zNDIsLTAuMzk4IC0wLjM0MiwtMC41MjkgMCwtMC4xMzEgLTAuMjU4LC0wLjU3NiAtMC41NzMsLTAuOTg5IC0wLjMxNSwtMC40MTIgLTAuNjksLTAuOTc3IC0wLjgzMywtMS4yNTQgLTAuMTQ0LC0wLjI3OCAtMC4zNTYsLTAuNTY1IC0wLjQ3MSwtMC42MzggLTAuMTE2LC0wLjA3MyAtMC4zOTMsLTAuNTMyIC0wLjYxNywtMS4wMTkgLTAuMjIzLC0wLjQ4NyAtMC40OTQsLTAuOTM3IC0wLjYwMSwtMSAtMC4xMDgsLTAuMDYzIC0wLjM1NywtMC40MzMgLTAuNTU0LC0wLjgyMyAtMC4xOTcsLTAuMzg5IC0wLjQyMywtMC43MDggLTAuNTAyLC0wLjcwOCAtMC4wNzksMCAtMC4zNDIsLTAuNDA1IC0wLjU4NCwtMC45MDEgLTAuMjQyLC0wLjQ5NSAtMC41NTEsLTAuOTQyIC0wLjY4NiwtMC45OTQgLTAuNDYzLC0wLjE3OCAtMC4zMTEsLTEuNDAzIDAuMjU0LC0yLjA0NyAwLjI3NSwtMC4zMTMgMC41LC0wLjY2NiAwLjUsLTAuNzg1IDAsLTAuMTE4IDAuMTc4LC0wLjM4MiAwLjM5NSwtMC41ODYgMC4yMTgsLTAuMjA1IDAuNTM2LC0wLjY4NyAwLjcwNywtMS4wNzEgMC4xNzEsLTAuMzg1IDAuNDgxLC0wLjgzNiAwLjY4OCwtMS4wMDMgMC4yMDcsLTAuMTY3IDAuMzc3LC0wLjQwMiAwLjM3NywtMC41MjMgMCwtMC4xMiAwLjMsLTAuNjY4IDAuNjY2LC0xLjIxNyAwLjM2NywtMC41NDkgMC42NjcsLTEuMDcxIDAuNjY3LC0xLjE2IDAsLTAuMDg5IDAuMTU4LC0wLjMwNCAwLjM1MSwtMC40NzkgMC4xOTQsLTAuMTc0IDAuNTI1LC0wLjY2IDAuNzM3LC0xLjA3OCAwLjIxMiwtMC40MTggMC41LC0wLjg1NCAwLjYzOCwtMC45NyAwLjEzOSwtMC4xMTUgMC4yOTgsLTAuNDE1IDAuMzUzLC0wLjY2OCAwLjA1NiwtMC4yNTIgMC4zMSwtMC42ODcgMC41NjUsLTAuOTY2IDAuMjU1LC0wLjI4IDAuNTY4LC0wLjc2MiAwLjY5NSwtMS4wNzIgMC4xMjgsLTAuMzEgMC40MDMsLTAuNyAwLjYxMywtMC44NjcgMC4yMSwtMC4xNjcgMC4zODEsLTAuNDAxIDAuMzgxLC0wLjUxOSAwLC0wLjExOSAwLjMsLTAuNjU3IDAuNjY3LC0xLjE5NyAwLjM2NiwtMC41MzkgMC42NjYsLTEuMDcxIDAuNjY2LC0xLjE4MiAwLC0wLjExMSAwLjE4LC0wLjM3MyAwLjM5OSwtMC41ODMgQyA2NC41NjcsMi42NyA2NS41NjEsMCA2NC45MTcsMCBjIC0wLjE1MywwIC0wLjI1LDAuMTU1IC0wLjI1LDAuMzk5IgogICAgICAgICBpZD0icGF0aDYiCiAgICAgICAgIGlua3NjYXBlOmNvbm5lY3Rvci1jdXJ2YXR1cmU9IjAiCiAgICAgICAgIHN0eWxlPSJmaWxsOiNhMGE1YWEiIC8+CiAgICAgIDxwYXRoCiAgICAgICAgIGQ9Im0gNjUuMTY3LDAuNDc1IGMgMCwwLjU2NyAtMS4wNDEsMi42MTggLTEuNjAyLDMuMTU3IC0wLjIxOSwwLjIxIC0wLjM5OSwwLjQ3MiAtMC4zOTksMC41ODMgMCwwLjExMSAtMC4zLDAuNjQzIC0wLjY2NiwxLjE4MiAtMC4zNjcsMC41NCAtMC42NjcsMS4wNzggLTAuNjY3LDEuMTk3IDAsMC4xMTggLTAuMTcxLDAuMzUyIC0wLjM4MSwwLjUxOSAtMC4yMSwwLjE2NyAtMC40ODUsMC41NTcgLTAuNjEzLDAuODY3IC0wLjEyNywwLjMxIC0wLjQ0LDAuNzkyIC0wLjY5NSwxLjA3MiAtMC4yNTUsMC4yNzkgLTAuNTA5LDAuNzE0IC0wLjU2NSwwLjk2NiAtMC4wNTUsMC4yNTMgLTAuMjE0LDAuNTUzIC0wLjM1MywwLjY2OCAtMC4xMzgsMC4xMTYgLTAuNDI2LDAuNTUyIC0wLjYzOCwwLjk3IC0wLjIxMiwwLjQxOCAtMC41NDMsMC45MDQgLTAuNzM3LDEuMDc4IC0wLjE5MywwLjE3NSAtMC4zNTEsMC4zOSAtMC4zNTEsMC40NzkgMCwwLjA4OSAtMC4zLDAuNjExIC0wLjY2NywxLjE2IC0wLjM2NiwwLjU0OSAtMC42NjYsMS4wOTcgLTAuNjY2LDEuMjE3IDAsMC4xMjEgLTAuMTcsMC4zNTYgLTAuMzc3LDAuNTIzIC0wLjIwNywwLjE2NyAtMC41MTcsMC42MTggLTAuNjg4LDEuMDAzIC0wLjE3MSwwLjM4NCAtMC40ODksMC44NjYgLTAuNzA3LDEuMDcxIEMgNTQuMTc4LDE4LjM5MSA1NCwxOC42NTUgNTQsMTguNzczIGMgMCwwLjExOSAtMC4yMjUsMC40NzIgLTAuNSwwLjc4NSAtMC41NjUsMC42NDQgLTAuNzE3LDEuODY5IC0wLjI1NCwyLjA0NyAwLjEzNSwwLjA1MiAwLjQ0NCwwLjQ5OSAwLjY4NiwwLjk5NCAwLjI0MiwwLjQ5NiAwLjUwNSwwLjkwMSAwLjU4NCwwLjkwMSAwLjA3OSwwIDAuMzA1LDAuMzE5IDAuNTAyLDAuNzA4IDAuMTk3LDAuMzkgMC40NDYsMC43NiAwLjU1NCwwLjgyMyAwLjEwNywwLjA2MyAwLjM3OCwwLjUxMyAwLjYwMSwxIDAuMjI0LDAuNDg3IDAuNTAxLDAuOTQ2IDAuNjE3LDEuMDE5IDAuMTE1LDAuMDczIDAuMzI3LDAuMzYgMC40NzEsMC42MzggMC4xNDMsMC4yNzcgMC41MTgsMC44NDIgMC44MzMsMS4yNTQgMC4zMTUsMC40MTMgMC41NzMsMC44NTggMC41NzMsMC45ODkgMCwwLjEzMSAwLjE1NCwwLjM2OSAwLjM0MiwwLjUyOSAwLjE4OCwwLjE1OSAwLjQ4NywwLjYxNSAwLjY2MywxLjAxMiAwLjE3NywwLjM5NyAwLjUxLDAuOTQ3IDAuNzQxLDEuMjIxIDAuMjMxLDAuMjc1IDAuNDIsMC41OTMgMC40MiwwLjcwNyAwLDAuMTE1IDAuMTk4LDAuMzkgMC40NCwwLjYxMyAwLjI0MSwwLjIyMiAwLjU3OCwwLjcxMyAwLjc0OCwxLjA5IDAuMTcsMC4zNzcgMC40MjMsMC43ODEgMC41NjIsMC44OTcgMC4xNCwwLjExNiAwLjM5OSwwLjUzOCAwLjU3NiwwLjkzOSAwLjE3NywwLjQgMC4zODksMC43MjggMC40NzEsMC43MjggMC4wODIsMCAwLjM1NCwwLjM5MyAwLjYwNCwwLjg3NSAwLjI1LDAuNDgxIDAuNTYxLDEuMDYyIDAuNjkxLDEuMjkxIDAuMTMsMC4yMyAwLjIzOCwwLjU4NiAwLjIzOSwwLjc5MiAwLjAwMiwwLjIwNiAwLjA3OCwwLjM3NSAwLjE2OSwwLjM3NSAwLjA5MiwwIDAuMTY3LC0wLjE0MSAwLjE2NywtMC4zMTMgMCwtMC40NDEgMC43NDUsLTIuMDczIDEuMDY5LC0yLjM0MiAwLjE0OSwtMC4xMjQgMC40NzYsLTAuNTg5IDAuNzI2LC0xLjAzNSAwLjI1LC0wLjQ0NSAwLjUxMiwtMC44MSAwLjU4MiwtMC44MSAwLjA3LDAgMC4zMDUsLTAuNDAzIDAuNTIzLC0wLjg5NSAwLjIxOCwtMC40OTMgMC40ODEsLTAuOTQ4IDAuNTg1LC0xLjAxMiAwLjEwMywtMC4wNjQgMC4zNzUsLTAuNDg2IDAuNjA0LC0wLjkzOCAwLjIyOSwtMC40NTIgMC40OTQsLTAuODIyIDAuNTg5LC0wLjgyMiAwLjA5NSwwIDAuMjc0LC0wLjI0MyAwLjM5OSwtMC41NDEgMC4xMjQsLTAuMjk4IDAuNDU4LC0wLjgzNCAwLjc0MSwtMS4xOSAwLjI4NCwtMC4zNTYgMC41MTUsLTAuNzM4IDAuNTE1LC0wLjg0OCAwLC0wLjExIDAuMTkyLC0wLjQ1NiAwLjQyNiwtMC43NjkgMC40OTgsLTAuNjY0IDEuMzc5LC0xLjk5OSAxLjc1MywtMi42NTYgMC4xNDQsLTAuMjUzIDAuNDYzLC0wLjY5OSAwLjcwOCwtMC45OTEgMC4yNDYsLTAuMjkxIDAuNDQ3LC0wLjYzMSAwLjQ0NywtMC43NTUgMCwtMC4xMjMgMC4yNjIsLTAuNTY4IDAuNTgzLC0wLjk4OCAwLjMyMSwtMC40MjEgMC41ODMsLTAuODM2IDAuNTgzLC0wLjkyNCAwLC0wLjA4OCAwLjIxNCwtMC40MDYgMC40NzYsLTAuNzA3IDEuMDAxLC0xLjE1MiAxLjA4LC0yLjIwMSAwLjIzNSwtMy4wOTggLTAuMjMzLC0wLjI0NyAtMC41NjIsLTAuNzU4IC0wLjczMiwtMS4xMzYgLTAuMTY5LC0wLjM3NyAtMC40MjYsLTAuNzg0IC0wLjU3LC0wLjkwMyAtMC4xNDQsLTAuMTE5IC0wLjM3MywtMC40ODUgLTAuNTEsLTAuODExIC0wLjEzNiwtMC4zMjcgLTAuMzksLTAuNzQ2IC0wLjU2NCwtMC45MzIgLTAuMTc0LC0wLjE4NSAtMC40MTUsLTAuNTY1IC0wLjUzNCwtMC44NDUgLTAuMTIsLTAuMjggLTAuMzksLTAuNzI5IC0wLjYwMiwtMC45OTkgQyA3Mi44MTgsMTIuODI5IDcyLjMzNiwxMi4wODcgNzEuOTg0LDExLjM1NCA3MS43OTEsMTAuOTUzIDcxLjM3OCwxMC4yOTEgNzEuMDY2LDkuODgzIDcwLjc1NSw5LjQ3NSA3MC41LDkuMDYzIDcwLjUsOC45NjggNzAuNSw4Ljg3MiA3MC4yNzUsOC41MzggNzAsOC4yMjUgNjkuNzI1LDcuOTExIDY5LjUsNy41NTcgNjkuNSw3LjQzNyA2OS41LDcuMzE3IDY5LjMzOSw3LjA3NSA2OS4xNDMsNi45MDEgNjguOTQ2LDYuNzI2IDY4LjYxNiw2LjIwNSA2OC40MSw1Ljc0MyA2OC4yMDQsNS4yODEgNjcuOTEsNC44IDY3Ljc1OCw0LjY3MyA2Ny42MDUsNC41NDcgNjcuMzY5LDQuMTc1IDY3LjIzMiwzLjg0NyA2Ny4wOTQsMy41MTggNjYuODAzLDMuMDYzIDY2LjU4NCwyLjgzMyA2Ni4wNzcsMi4zMDIgNjUuNTA4LDEuMDUyIDY1LjUwNCwwLjQ1OCA2NS41MDIsMC4yMDYgNjUuNDI1LDAgNjUuMzMzLDAgNjUuMjQyLDAgNjUuMTY3LDAuMjE0IDY1LjE2NywwLjQ3NSIKICAgICAgICAgaWQ9InBhdGg3IgogICAgICAgICBpbmtzY2FwZTpjb25uZWN0b3ItY3VydmF0dXJlPSIwIgogICAgICAgICBzdHlsZT0iZmlsbDojYTBhNWFhIiAvPgogICAgPC9nPgogIDwvZz4KPC9zdmc+Cg==" style="height:9px" title="' . Madeep_Name . '" /> ' . esc_html__('Sincronizza', 'madeep') . '</a>';
        }
        self::addJs('madeepJs', Madeep_Url . 'js/app.js');
        return $actions;
    }

    static function getPageListUsed() {
        global $wpdb, $table_prefix;

        $mad = new MadeepAdmin();
        $mad->debug = false;
        $query = array();
        $pagesList = array();
        foreach (self::$tablesWithPages as $key => $val) {
            $query[] = 'SELECT id_page FROM ' . $table_prefix . 'madeep_' . $val;
        }
        if (count($query) > 0) {
            $pages = $wpdb->get_results(implode(' UNION ', $query));
        }

        foreach ($pages as $key => $val) {
            $pagesList[] = $val->id_page;
            if ($mad->multiLangPlugin === 'wpml') {
                $tPages = $mad->getTranslatedPages($val->id_page);
                foreach ($tPages as $tKey => $tVal) {
                    $pagesList[] = $tVal;
                }
            }
        }

        return $pagesList;
    }

    static function register_madeep_settings() {
        register_setting('madeep-settings-group-connection', 'madeep_username');
        register_setting('madeep-settings-group-connection', 'madeep_password');
        register_setting('madeep-settings-group-connection', 'madeep_data_type');

        register_setting('madeep-settings-group-templates', 'madeep_category_hotels');
        register_setting('madeep-settings-group-templates', 'madeep_category_ecoms');
        register_setting('madeep-settings-group-templates', 'madeep_category_services');
        register_setting('madeep-settings-group-templates', 'madeep_category_offers');

        register_setting('madeep-settings-group-languages', 'madeep_default_language');
        register_setting('madeep-settings-group-languages', 'madeep_active_languages');
        register_setting('madeep-settings-group-languages', 'madeep_active_multilanguages');

        register_setting('madeep-settings-group-templates', 'madeep_template_hotels');
        register_setting('madeep-settings-group-templates', 'madeep_template_ecoms');
        register_setting('madeep-settings-group-templates', 'madeep_template_services');
        register_setting('madeep-settings-group-templates', 'madeep_template_offers');


        register_setting('madeep-settings-group-custom', 'madeep_google_api_key');
        register_setting('madeep-settings-group-custom', 'madeep_post_template_css');
        register_setting('madeep-settings-group-custom', 'madeep_post_template_js');

        register_setting('madeep-settings-group-contents', 'madeep_enable_write');
        register_setting('madeep-settings-group-contents', 'madeep_write_hotels_page');
        register_setting('madeep-settings-group-contents', 'madeep_write_ecoms_page');
        register_setting('madeep-settings-group-contents', 'madeep_write_services_page');
        register_setting('madeep-settings-group-contents', 'madeep_write_offers_page');

        register_setting('madeep-settings-group-contents', 'madeep_download_gallery');

        register_setting('madeep-settings-group-debug', 'madeep_debug_mode');
        register_setting('madeep-settings-group-debug', 'madeep_debug_quantity');

        register_setting('madeep-settings-group-behaviour', 'madeep_allow_single_sync');
        register_setting('madeep-settings-group-behaviour', 'madeep_allow_lang_tag');
        register_setting('madeep-settings-group-behaviour', 'madeep_allow_filters_tag');
        register_setting('madeep-settings-group-behaviour', 'madeep_allow_structure_tag');

        register_setting('madeep-settings-group-times', 'madeep_time_last_update_hotels');
        register_setting('madeep-settings-group-times', 'madeep_time_last_update_hotels_pages');
        register_setting('madeep-settings-group-times', 'madeep_time_last_update_hotels_list');
        register_setting('madeep-settings-group-times', 'madeep_time_last_update_ecoms');
        register_setting('madeep-settings-group-times', 'madeep_time_last_update_ecoms_pages');
        register_setting('madeep-settings-group-times', 'madeep_time_last_update_ecoms_list');
        register_setting('madeep-settings-group-times', 'madeep_time_last_update_services');
        register_setting('madeep-settings-group-times', 'madeep_time_last_update_services_pages');
        register_setting('madeep-settings-group-times', 'madeep_time_last_update_services_list');
        register_setting('madeep-settings-group-times', 'madeep_time_last_update_offers');
        register_setting('madeep-settings-group-times', 'madeep_time_last_update_offers_pages');
        register_setting('madeep-settings-group-times', 'madeep_time_last_update_offers_list');
    }

//Registra le variabili per Admin
    static function register_madeep_admin_settings() {
        /*
         * Variabili
         */
        self::register_madeep_settings();

        add_action('madeep_refresh_cache', array(self::class, 'cronExec'));

        if (get_option('madeep_sync_key') == '') {
            self::genKey();
        }
    }

//Registra le variabili per Admin
    static function register_madeep_public_settings() {

        /*
         * Variabili
         */
        self::register_madeep_settings();

        if (get_option('madeep_sync_key') == '') {
            self::genKey();
        }

        /*
         * Widget
         *
          register_widget( 'madeep_book_now');
          add_action( 'widgets_init', 'madeep_book_now_widget' );

          /*
         * ShortCodes
         */
        /* add_shortcode('MadeepGallery', array(self::class, 'genGallery'));
          add_shortcode('MadeepMap', array(self::class, 'genMap')); */
        add_shortcode('MadeepStructureData', array(self::class, 'genStructureData'));
        add_shortcode('MadeepEcomServicesList', array(self::class, 'genEcomServicesList'));
        /* add_shortcode('MadeepOffersList', array(self::class, 'genOffersList'));
          add_shortcode('MadeepHotelList', array(self::class, 'genHotelList'));
          add_shortcode('MadeepEcomList', array(self::class, 'genEcomList')); */

        /*
          add_shortcode('MadeepQr', array(self::class, 'genEcomList'));
          add_shortcode('MadeepBookNow', array(self::class, 'genEcomList'));
         */

        /*
         * CUSTOM JS & CSS
         */
        add_action('wp_print_footer_scripts', array(self::class, 'addCustomCssAndJs'));

        /* add_action('wp_enqueue_scripts', array(self::class, 'print_script'));
          add_action('wp_enqueue_style', array(self::class, 'print_css')); */

        self::addCss('madeep-base', Madeep_Url . 'css/madeep.css');


        //add_action('madeep_refresh_cache', array(self::class, 'cronExec'));
        //add_action('wp_ajax_nopriv_madeep_sync', array(self::class, 'ajaxSync'));
    }

    /*
     * PAGINE
     */

//Pagina delle opzioni
    static function madeep_plugin_options() {

        /* echo get_option('madeep_username') . '#';
          echo get_option('madeep_password') . '#'; */

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        include('pages/madeep-options.php');
    }

//Pagina base
    static function madeep_page_plugin_home() {

        include('pages/madeep-options.php');
    }

    static function madeep_page_plugin_connection() {
        $_GET['tab'] = 'connection';
        include('pages/madeep-options.php');
    }

    static function madeep_page_plugin_languages() {
        $_GET['tab'] = 'languages';
        include('pages/madeep-options.php');
    }

    static function madeep_page_plugin_contents() {
        $_GET['tab'] = 'contents';
        include('pages/madeep-options.php');
    }

    static function madeep_page_plugin_templates() {
        $_GET['tab'] = 'templates';
        include('pages/madeep-options.php');
    }

    static function madeep_page_plugin_debug() {
        $_GET['tab'] = 'debug';
        include('pages/madeep-options.php');
    }

    static function madeep_page_plugin_behaviour() {
        $_GET['tab'] = 'behaviour';
        include('pages/madeep-options.php');
    }

    /*
     * POST
     */

    static function genGallery($attr) {

        $images = explode('|', $attr['images']);
        if (!is_array($images)) {
            $images = array($images);
        }
        $html = '';
        if (count($images) > 0) {
            //self::addJs('jquery-3', '//cdn.jsdelivr.net/npm/jquery@3.4.1/dist/jquery.min.js');
            self::addCss('fancybox-css', Madeep_Url . 'css/jquery.fancybox.min.css');
            self::addJs('fancybox-js', Madeep_Url . 'js/jquery.fancybox.min.js');
            self::addCss('madeep-gallery', Madeep_Url . 'css/gallery.css');

            $html = '<div id="madeepGallery" style="width:100%;">';
            foreach ($images as $key => $val) {
                $html .= '<a data-fancybox="gallery" class="madeepGalleryImg" href="' . $val . '" style="background-image:url(' . $val . ')"></a>';
            }
            $html .= '</div>';
        }
        return $html;
    }

    static function genMap($attr) {

        $attr['location'] = explode(',', $attr['location']);
        $html = '';
        if (strlen(trim($attr['location'][0])) > 0 && strlen(trim($attr['location'][1])) > 0) {
            self::addJs('madeep-maps', Madeep_Url . 'js/map.js');
            self::addJs('google-maps', '//maps.googleapis.com/maps/api/js?key=' . get_option('madeep_google_api_key') . '&callback=madeepMap');

            $html = '<div class="madeepHotelMap" style="width:100%;height: 350px;" data-lat="' . $attr['location'][0] . '" data-lon="' . $attr['location'][1] . '" ';
            if (isset($attr['text'])) {
                $html .= ' data-text="' . $attr['text'] . '" ';
            }
            $html .= '></div>';
        }
        return $html;
    }

    /* static function genEcomServicesList($attr) {
      global $wpdb, $table_prefix;
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      $table = $table_prefix . 'madeep_services';

      $template = self::$madeepAdmin->getTemplate('services-list');

      $html = '';

      $attr['id'] = explode(',', $attr['id']);
      if (!is_array($attr['id'])) {
      $attr['id'] = array($attr['id']);
      }
      if (!isset($attr['lang'])) {
      $attr['lang'] = get_option('madeep_default_language');
      }
      $lang = $attr['lang'];

      $q = '';
      if (count($attr['id']) > 0) {
      $q = ' WHERE id_ecom IN (' . implode(',', $attr['id']) . ')';
      }

      $queryCheck = 'SELECT * FROM ' . $table . $q;
      //echo $queryCheck;
      $queryCheckR = $wpdb->get_results($queryCheck);

      //print_r($queryCheckR);

      foreach ($queryCheckR as $key => $val) {

      $hotelData = array();

      $queryEcom = $wpdb->get_results('SELECT * FROM ' . $table_prefix . 'madeep_ecoms WHERE id_ecom = ' . $val->id_ecom . ' LIMIT 1');
      $queryEcom[0]->dataS = unserialize(base64_decode($queryEcom[0]->dataS));

      $hotelData['ecomName'] = $queryEcom[0]->dataS->Name;
      $hotelData['ecomPageLink'] = esc_url(get_page_link($queryEcom[0]->id_page));


      $val->dataS = unserialize(base64_decode($val->dataS));
      $data = $val->dataS;

      $hotelData = array();
      $hotelData['texts'] = self::$madeepAdmin->arr2langarr($data->ServDetails->Details);
      //print_r($data);
      $hotelData['id'] = $data->id;
      $hotelData['id_page'] = $val->id_page;
      $hotelData['name'] = self::$madeepAdmin->strToLang($hotelData['texts'], 'nameDetail');
      $hotelData['image'] = str_replace(array('<![CDATA[', ']]>'), '', $data->ImgUrl);
      $hotelData['gallery'] = $data->Gallery;
      $hotelData['serviceUrl'] = esc_url(get_page_link($val->id_page));
      if (isset($data->Places->Place)) {
      $hotelData['longitude'] = $data->Places->Place->lng;
      $hotelData['latitude'] = $data->Places->Place->lat;
      $hotelData['address'] = $data->Places->Place->address;
      $hotelData['postalcode'] = $data->Places->Place->zipcode;
      $hotelData['city'] = $data->Places->Place->city;
      $hotelData['province'] = $data->Places->Place->province;
      $hotelData['region'] = $data->Places->Place->region;
      $hotelData['nation'] = $data->Places->Place->nation;
      }

      if (self::$madeepAdmin->multiLangPlugin != null) {
      $hotelData['name'] = $hotelData['texts'][666][self::$madeepAdmin->getLang($lang, 1)]['value'];
      $html .= self::$madeepAdmin->genServicesListRow($hotelData, $template['html'], self::$madeepAdmin->getLang($lang));
      } else {

      $hotelData['name'] = $hotelData['texts'][666][self::$madeepAdmin->getLang(get_option('madeep_default_language'), 1)]['value'];
      $html .= self::$madeepAdmin->genServicesListRow($hotelData, $template['html'], get_option('madeep_default_language'));
      }
      }

      return $html;
      }

      static function genOffersList($attr) {
      global $wpdb, $table_prefix;
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      $table = $table_prefix . 'madeep_offers';

      $template = self::$madeepAdmin->getTemplate('offers-list');

      $html = '';

      $attr['id'] = explode(',', $attr['id']);
      if (!is_array($attr['id'])) {
      $attr['id'] = array($attr['id']);
      }
      if (!isset($attr['lang'])) {
      $attr['lang'] = get_option('madeep_default_language');
      }
      $lang = $attr['lang'];

      $q = '';
      if (count($attr['id']) > 0) {
      $q = ' WHERE id_hotel IN (' . implode(',', $attr['id']) . ')';
      }

      $queryCheck = 'SELECT * FROM ' . $table . $q;
      //echo $queryCheck;
      $queryCheckR = $wpdb->get_results($queryCheck);

      //print_r($queryCheckR);

      foreach ($queryCheckR as $key => $val) {

      $hotelData = array();

      $queryEcom = $wpdb->get_results('SELECT * FROM ' . $table_prefix . 'madeep_offers WHERE id_hotel = ' . $val->id_hotel . ' LIMIT 1');
      $queryEcom[0]->dataS = unserialize(base64_decode($queryEcom[0]->dataS));

      $hotelData['offerName'] = $queryEcom[0]->dataS->Name;
      $hotelData['offerPageLink'] = esc_url(get_page_link($queryEcom[0]->id_page));


      $val->dataS = unserialize(base64_decode($val->dataS));
      $data = $val->dataS;

      $hotelData = array();
      $hotelData['texts'] = self::$madeepAdmin->arr2langarr($data->ServDetails->Details);
      //print_r($data);
      $hotelData['id'] = $data->id;
      $hotelData['id_page'] = $val->id_page;
      $hotelData['name'] = self::$madeepAdmin->strToLang($hotelData['texts'], 'nameDetail');
      $hotelData['image'] = str_replace(array('<![CDATA[', ']]>'), '', $data->ImgUrl);
      $hotelData['gallery'] = $data->Gallery;
      $hotelData['serviceUrl'] = esc_url(get_page_link($val->id_page));
      if (isset($data->Places->Place)) {
      $hotelData['longitude'] = $data->Places->Place->lng;
      $hotelData['latitude'] = $data->Places->Place->lat;
      $hotelData['address'] = $data->Places->Place->address;
      $hotelData['postalcode'] = $data->Places->Place->zipcode;
      $hotelData['city'] = $data->Places->Place->city;
      $hotelData['province'] = $data->Places->Place->province;
      $hotelData['region'] = $data->Places->Place->region;
      $hotelData['nation'] = $data->Places->Place->nation;
      }

      if (self::$madeepAdmin->multiLangPlugin != null) {
      $hotelData['name'] = $hotelData['texts'][666][self::$madeepAdmin->getLang($lang, 1)]['value'];
      $html .= self::$madeepAdmin->genOffersListRow($hotelData, $template['html'], self::$madeepAdmin->getLang($lang));
      } else {

      $hotelData['name'] = $hotelData['texts'][666][self::$madeepAdmin->getLang(get_option('madeep_default_language'), 1)]['value'];
      $html .= self::$madeepAdmin->genOffersListRow($hotelData, $template['html'], get_option('madeep_default_language'));
      }
      }

      return $html;
      } */

    /* static function genHotelList($attr) {
      global $wpdb, $table_prefix;
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      $table = $table_prefix . 'madeep_hotels';

      $template = self::$madeepAdmin->getTemplate('services-list');

      $html = '';

      $attr['id'] = explode(',', $attr['id']);
      if (!is_array($attr['id'])) {
      $attr['id'] = array($attr['id']);
      }
      if (!isset($attr['lang'])) {
      $attr['lang'] = get_option('madeep_default_language');
      }

      $q = '';
      if (count($attr['id']) > 0) {
      $q = ' WHERE id_hotel IN (' . implode(',', $attr['id']) . ')';
      }

      $lang = $attr['lang'];

      $queryCheck = 'SELECT * FROM ' . $table . $q;
      //echo $queryCheck;
      $queryCheckR = $wpdb->get_results($queryCheck);

      //print_r($queryCheckR);

      foreach ($hotelList as $key => $val) {

      $hotelData = array();
      $val->dataS = unserialize(base64_decode($val->dataS));
      $data = $val->dataS;

      $hotelData['id'] = $data->id;
      $hotelData['id_page'] = $val->id_page;
      $hotelData['name'] = $data->Name;
      $hotelData['image'] = $data->Image;
      $hotelData['email'] = $data->Email;
      $hotelData['phone'] = $data->Phone;
      $hotelData['fax'] = $data->Fax;
      $hotelData['price'] = $data->PriceFrom;
      $hotelData['stars'] = $data->Stars;
      $hotelData['address'] = $data->Address;
      $hotelData['postalcode'] = $data->PostalCode;
      $hotelData['city'] = $data->City;
      $hotelData['province'] = $data->Province;
      $hotelData['region'] = $data->Region;
      $hotelData['nation'] = $data->Nation;
      $hotelData['texts'] = self::$madeepAdmin->arr2langarr($data->HotelDetails->Details);
      $hotelData['hotelUrl'] = esc_url(get_page_link($val->id_page));

      if (self::$madeepAdmin->multiLangPlugin != null) {
      foreach (self::$madeepAdmin->activeLanguage as $keyL => $valL) {
      if (!isset($htmlLang[self::$madeepAdmin->getLang($valL)])) {
      $htmlLang[self::$madeepAdmin->getLang($valL)] = '';
      }
      $htmlLang[self::$madeepAdmin->getLang($valL)] .= self::$madeepAdmin->genHotelListRow($hotelData, $template['html'], self::$madeepAdmin->getLang($valL));
      }
      } else {
      $htmlLang[get_option('madeep_default_language')] .= self::$madeepAdmin->genHotelListRow($hotelData, $template['html'], get_option('madeep_default_language'));
      }
      }

      if (self::$madeepAdmin->multiLangPlugin != null) {
      if (self::$madeepAdmin->multiLangPlugin == 'wp-multilang') {
      $return = '';
      foreach ($htmlLang as $key => $val) {
      $return .= '[:' . $this->getLang($key) . ']' . $val;
      }
      $html = $template['style'] . $return . '[:]';
      }
      } else {
      $html = $template['style'] . $htmlLang[get_option('madeep_default_language')];
      }
      return $html;
      } */

    /* static function genEcomList($attr) {
      global $wpdb, $table_prefix;
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      $table = $table_prefix . 'madeep_ecoms';

      $template = self::$madeepAdmin->getTemplate('services-list');

      $html = '';

      $attr['id'] = explode(',', $attr['id']);
      if (!is_array($attr['id'])) {
      $attr['id'] = array($attr['id']);
      }
      if (!isset($attr['lang'])) {
      $attr['lang'] = get_option('madeep_default_language');
      }

      $q = '';
      if (count($attr['id']) > 0) {
      $q = ' WHERE id_ecom IN (' . implode(',', $attr['id']) . ')';
      }

      $lang = $attr['lang'];

      $queryCheck = 'SELECT * FROM ' . $table . $q;
      //echo $queryCheck;
      $queryCheckR = $wpdb->get_results($queryCheck);

      //print_r($queryCheckR);

      foreach ($hotelList as $key => $val) {

      $hotelData = array();
      $val->dataS = unserialize(base64_decode($val->dataS));
      $data = $val->dataS;

      $hotelData = array();
      $hotelData['texts'] = self::$madeepAdmin->arr2langarr($data->EcomDetails->Details);

      $hotelData['id'] = $data->id;
      $hotelData['id_page'] = $val->id_page;
      $hotelData['name'] = $data->Name;
      $hotelData['image'] = '';
      if (isset($data->ImgUrl)) {
      $hotelData['image'] = str_replace(array('<![CDATA[', ']]>'), '', $data->ImgUrl);
      }
      if (strlen(trim($hotelData['image'])) == 0) {
      if (isset($data->Gallery)) {
      if (isset($data->Gallery->GImage)) {
      $hotelData['image'] = str_replace(array('<![CDATA[', ']]>'), '', $data->Gallery->GImage[0]);
      }
      }
      }
      $hotelData['gallery'] = $data->Gallery;
      $hotelData['longitude'] = (isset($data->Lng)) ? $data->Lng : '';
      $hotelData['latitude'] = (isset($data->Lat)) ? $data->Lat : '';
      $hotelData['address'] = (isset($data->Address)) ? $data->Address : '';
      $hotelData['city'] = (isset($data->City)) ? $data->City : '';
      $hotelData['province'] = (isset($data->Province)) ? $data->Province : '';
      $hotelData['region'] = (isset($data->Region)) ? $data->Region : '';
      $hotelData['nation'] = (isset($data->Nation)) ? $data->Nation : '';
      $hotelData['ecomUrl'] = esc_url(get_page_link($val->id_page));

      if (self::$madeepAdmin->multiLangPlugin != null) {
      foreach (self::$madeepAdmin->activeLanguage as $keyL => $valL) {
      if (!isset($htmlLang[self::$madeepAdmin->getLang($valL)])) {
      $htmlLang[self::$madeepAdmin->getLang($valL)] = '';
      }
      $htmlLang[self::$madeepAdmin->getLang($valL)] .= self::$madeepAdmin->genEcomListRow($hotelData, $template['html'], self::$madeepAdmin->getLang($valL));
      }
      } else {
      $htmlLang[get_option('madeep_default_language')] .= self::$madeepAdmin->genEcomListRow($hotelData, $template['html'], get_option('madeep_default_language'));
      }
      }

      if (self::$madeepAdmin->multiLangPlugin != null) {
      if (self::$madeepAdmin->multiLangPlugin == 'wp-multilang') {
      $return = '';
      foreach ($htmlLang as $key => $val) {
      $return .= '[:' . self::$madeepAdmin->getLang($key) . ']' . $val;
      }
      $html = $template['style'] . $return . '[:]';
      }
      } else {
      $html = $template['style'] . $htmlLang[get_option('madeep_default_language')];
      }
      return $html;
      }

     */

    static function genStructureData($attr) {

        $html = '';
        if (isset($attr['id'])) {
            $html .= '<input type="hidden" name="madeep-structure-id" value="' . $attr['id'] . '" />';
        }
        if (isset($attr['stars'])) {
            $html .= '<input type="hidden" name="madeep-structure-stars" value="' . $attr['stars'] . '" />';
        }
        if (isset($attr['price'])) {
            $html .= '<input type="hidden" name="madeep-structure-price" value="' . $attr['price'] . '" />';
        }

        return $html;
    }

    static function tabMenu() {
        $actualPage = (isset($_GET['tab'])) ? sanitize_text_field($_GET['tab']) : 'home';
        $pageList = (get_option('madeep_data_type') !== false) ? self::$pageList : array(self::$pageList['connection']);
        $r = '<nav class="nav-tab-wrapper">';
        foreach ($pageList as $key => $val) {
            $r .= '<a href="?page=' . self::$pluginName . (($key !== "home") ? '-' . $key : '') . '" class="nav-tab ' . (($actualPage === $key) ? 'nav-tab-active' : '') . '">' . $val[0] . '</a>';
        }
        $r .= '</nav>';
        return $r;
    }

    static function tabContent() {
        $r = 'home';
        $actualPage = (isset($_GET['tab'])) ? sanitize_text_field($_GET['tab']) : 'home';
        $actualPage = (get_option('madeep_data_type') !== false) ? $actualPage : 'connection';

        if (isset(self::$pageList[$actualPage])) {
            $r = $actualPage;
        }

        return $r;
    }

    static function varGroup() {
        $actualPage = (isset($_GET['tab'])) ? sanitize_text_field($_GET['tab']) : 'home';
        $actualPage = (get_option('madeep_data_type') !== false) ? $actualPage : 'connection';

        if (isset(self::$pageList[$actualPage])) {
            return 'madeep-settings-group-' . $actualPage;
        } else {
            return false;
        }
    }

    static function addJs($handle, $src = '', $deps = array(), $ver = null, $in_footer = true) {
        if (wp_register_script($handle, $src, $deps, $ver, $in_footer)) {
            wp_enqueue_script($handle);
        } else {
            //echo '<script>console.error("Unable to register JS: ' . $handle . '");</script>';
        }
    }

    static function addCss($handle, $src = '', $deps = array(), $ver = null, $media = 'all') {
        if (wp_register_style($handle, $src, $deps, $ver, $media)) {
            wp_enqueue_style($handle);
        } else {
            //echo '<script>console.error("Unable to register CSS: ' . $handle . '");</script>';
        }
    }

    static function addCustomCssAndJs() {
        echo '<script>' . get_option('madeep_post_template_js') . '</script>';
        echo '<style>' . get_option('madeep_post_template_css') . '</style>';
    }

    static function cronSet($t) {
        //print_r(wp_next_scheduled('madeep_refresh_cache'));

        if (!wp_next_scheduled('madeep_refresh_cache')) {
            switch ($t) {
                case 0:
                    $c = 'twicedaily';
                    break;
                case 1:
                    $c = 'daily';
                    break;
                case 2:
                    $c = 'weekly';
                    break;
                default:
                    $c = 'daily';
            }

            wp_schedule_event(time(), $c, 'madeep_refresh_cache');
        }
    }

    static function cronUnset() {
        wp_clear_scheduled_hook('madeep_refresh_cache');
    }

    static function cronExec() {
        file_get_contents(admin_url('admin-ajax.php') . '?action=madeep_cron_out&key=' . get_option('madeep_sync_key'));
    }

    static function cronExecAjax() {
        if ($_GET['key'] == get_option('madeep_sync_key')) {
            $mad = new MadeepAdmin();
            if (!isset($_GET['type']) && !isset($_GET['id'])) {
                $mad->cron();
            } else {
                self::asyncPageUpdate(esc_attr($_GET['type']), esc_attr((int) $_GET['id']), esc_attr((int) $_GET['id_cont']));
            }
        }
    }

    static function cronCheck() {
        $url = get_admin_url() . 'options-general.php?page=madeep&action=';

        if ((int) wp_next_scheduled('madeep_refresh_cache') > 0) {
            echo __('Prossima sincronizzazione', 'madeep') . ': ' . date('d/m/Y h:i:s', wp_next_scheduled('madeep_refresh_cache')) . ' (' . wp_get_schedule('madeep_refresh_cache') . ') [<a href="' . $url . 'unsetCron">Turn Off</a>]';
        } else {
            echo __('Nessuna sincronizzazione prevista', 'madeep') . '<br/>' . __('Attiva', 'madeep') . ': [<a href="' . $url . 'setCron&t=0">' . __('2 volte al giorno', 'madeep') . '</a>] [<a href="' . $url . 'setCron&t=1">' . __('Giornaliero', 'madeep') . '</a>] [<a href="' . $url . 'setCron&t=2">' . __('Settimanale', 'madeep') . '</a>]';
        }

        //echo 'Next sync: ' . (((int) wp_next_scheduled('madeep_refresh_cache') > 0) ? date('d/m/Y h:i:s', wp_next_scheduled('madeep_refresh_cache')) . ' (' . wp_get_schedule('madeep_refresh_cache') . ') [<a href="' . $url . 'unsetCron">Turn Off</a>]' : 'Not scheluded [<a href="' . $url . 'setCron">Turn On</a>]');
    }

    static function checkSyncRequest() {
        if (isset($_GET['key'])) {
            if ($_GET['key'] == get_option('madeep_sync_key') && $_GET['madeepAction'] == 'sync') {
                $mad = new MadeepAdmin();
                $mad->cron();
            }
        }
    }

    static function genKey() {
        $return = md5(uniqid(rand(), true));
        update_option('madeep_sync_key', $return);
        return $return;
    }

    /*
      Link aggiuntivi dopo la descrizione
     */

    static function rowMeta($links, $file) {
        if (plugin_basename(__FILE__) == $file) {
            $row_meta = array(
                    //'docs' => '<a href="' . esc_url('https://www.madeep.com/') . '" target="_blank" aria-label="' . esc_attr__('Plugin Additional Links', 'madeep') . '" style="color:green;">' . esc_html__('Docs', 'madeep') . '</a>'
            );

            return array_merge($links, $row_meta);
        }
        return (array) $links;
    }

    /*
      Link aggiuntivi vicino al disattiva
     */

    static function pluginSettingsLink($links) {
        $url = get_admin_url() . 'options-general.php?page=madeep';
        $settings_link = '<a href="' . $url . '">' . __('Settings', 'madeep') . '</a>';
        $links[] = $settings_link;
        return $links;
    }

    static function test_in() {
        
    }

    static function test_out() {
        
    }

}
