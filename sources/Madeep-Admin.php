<?php

set_time_limit(0);
require_once( ABSPATH . 'wp-admin/includes/image.php' );

class MadeepAdmin {

    //private $log = null;
    private $endpoint = 'http://back-services.com/int/portals/lists.php?wsdl';
    private $user = null;
    private $pass = null;
    public $multiLangPlugin = null;
    public $defaultLang = 'ita';
    private $logFile = '/logs/logMadeepConnector.txt';
    private $shutDown = '/logs/shutdownMadeepConnector.txt';
    private $saveLastResponse = true;
    private $lastResponseLocation = '/logs/lastResponse.xml';
    private $logFileIndex = null;
    public $languageList = array(
        'ita' => array('it', 'ita', 'italian', 'italiano'),
        'eng' => array('en', 'eng', 'english', 'english'),
        'esp' => array('es', 'esp', 'spanish', 'espanol'),
        'por' => array('pt', 'por', 'portuguese', 'portugues'),
        'bra' => array('br', 'bra', 'brazilian portuguese', 'portugues brasileiro'),
        'fra' => array('fr', 'fra', 'french', 'francaise'),
        'deu' => array('de', 'deu', 'deutsch', 'deutsche'),
        'rus' => array('ru', 'rus', 'russian', 'pусский'),
        'cin' => array('ci', 'cin', 'chinese', '中文'),
        'jap' => array('ja', 'jap', 'japanese', '日本語'),
        'ara' => array('ar', 'ara', 'arabic', 'العربية'),
        'fin' => array('fi', 'fin', 'finnish', 'suomi'),
        'dan' => array('dk', 'dan', 'danish', 'dansk'),
        'swe' => array('sw', 'swe', 'swedish', 'svenska'),
        'nor' => array('no', 'nor', 'norwegian', 'norsk'),
    );
    public $activeLanguage = array();
    public $detailsDefinition = array(
        'nameDetail' => array('NAME_eng', 'NAME_ita'),
        'shortdesc' => array('SHORTDESC_eng', 'SHORTDESC_ita'),
        'longdesc' => array('LONGDESC_eng', 'LONGDESC_ita'),
        'description' => array('Description', 'Descrizione'),
        'information' => array('Information', 'Informazioni'),
        'burl' => array('burl'),
    );
    private $structureType = array(
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        20 => 'residence',
        21 => 'appartments',
        22 => 'bed_and_breakfast',
        23 => 'farmhouse',
        24 => 'room_rental',
        25 => 'refuge',
        26 => 'hostel',
        27 => 'vacation_house',
        28 => 'camping',
        29 => 'chalet',
        30 => 'touristic_village',
        31 => 'villa',
    );
    private $lang = array();
    private $writePages = false;
    private $writePagesList = array(
        "madeep_write_hotels_page" => false,
        "madeep_write_ecoms_page" => false,
        "madeep_write_services_page" => false,
        "madeep_write_offers_page" => false
    );
    private $downloadGalleryImages = false;
    private $pageIdNotToClean = array();
    //DEBUG
    public $debug = true;
    private $c = 0;
    private $cMax = 0;

    function __construct() {
        if (strlen(trim(esc_attr(get_option('madeep_username')))) == 0 || strlen(trim(esc_attr(get_option('madeep_password')))) == 0) {
            //die(__('No user or password defined', 'madeep-plugin'));
        } else {

            $this->debug = (esc_attr(get_option('madeep_debug_mode')) == 1) ? true : false;
            $this->cMax = (esc_attr(get_option('madeep_debug_mode')) == 1) ? (int) (((int) esc_attr(get_option('madeep_debug_quantity')) * 1) - 1) : 0;

            $this->createLog();
            $this->user = esc_attr(get_option('madeep_username'));
            $this->pass = esc_attr(get_option('madeep_password'));
            if (strlen(trim(esc_attr(get_option('madeep_default_language')))) > 0) {
                $this->defaultLang = esc_attr(get_option('madeep_default_language'));
            }
            $this->activeLanguage = $this->getDataActiveLanguage();
            if (!is_array($this->activeLanguage) || count($this->activeLanguage) == 0) {
                $this->activeLanguage = array(get_option('madeep_default_language'));
            }

            if (get_option('madeep_enable_write') == 1) {
                $this->writePages = true;
                foreach ($this->writePagesList as $key => $val) {
                    $this->writePagesList[$key] = (bool) get_option($key);
                }
            }

            $this->downloadGalleryImages = (get_option('madeep_download_gallery') == 1) ? true : false;

            $this->checkMultilanguagePlugins();
            $this->manageTranslationHooks();
            $this->register_madeep_settings();

            $this->loadLangs();

            register_shutdown_function(array($this, 'callShutdown'));
        }
    }

    private function callShutdown() {
        $last_error = error_get_last();
        if ($last_error['type'] === E_ERROR) {
            $this->logAddRow(__FUNCTION__, 'Unexpected Shutdown', __LINE__);
            $this->logAddRow(__FUNCTION__, '###################', __LINE__);
            $this->logAddRow(__FUNCTION__, json_encode($last_error), __LINE__);
            $this->logAddRow(__FUNCTION__, '###################', __LINE__);
        }
    }

    private function register_madeep_settings() {
        
    }

    private function manageTranslationHooks() {
        global $wpml_post_translations, $sitepress;

        if ($this->multiLangPlugin === 'wpml' && $this->multiLangPlugin != null) {

            $this->logAddRow(__FUNCTION__, 'Remove WPML save_post action', __LINE__);
            remove_action('save_post', array($wpml_post_translations, 'save_post_actions'), 100);
            
            $this->logAddRow(__FUNCTION__, 'Remove WPML get_term filter', __LINE__);
            remove_filter('get_term', array($sitepress, 'get_term_adjust_id'), 1);
        }
    }

    private function createLog() {
        if ($this->debug) {
            $this->logFileIndex = fopen(__DIR__ . $this->logFile, 'a+');
        }
    }

    public function logAddRow($f, $m, $l) {
        if ($this->debug) {
            fwrite($this->logFileIndex, '[' . $f . '][' . date('Y-m-d H:i:s') . '][' . $l . ']' . $m . '
');
        }
    }

    public function readLog() {
        if ($this->debug) {
            return file_get_contents(__DIR__ . $this->logFile);
        } else {
            return '';
        }
    }

    public function clearLog() {
        if ($this->debug) {
            fopen(__DIR__ . $this->logFile, 'w+');
        }
    }

    public function shutItDown() {
        fopen(__DIR__ . $this->shutDown, 'w+');
    }

    public function turnItUp() {
        unlink(__DIR__ . $this->shutDown);
    }

    public function checkShutDown() {
        if (file_exists(__DIR__ . $this->shutDown)) {
            $this->logAddRow(__FUNCTION__, 'Stopped cause "Force stop" is active', __LINE__);
            die();
        }
    }

    private function call($data) {
        ini_set("soap.wsdl_cache_enabled", WSDL_CACHE_NONE);
        $client = new SoapClient($this->endpoint, array('trace' => 1, 'encoding' => 'UTF-8'));

        $return = $client->{$data['call']}($data['data']);
        $xmlCall = $client->__getLastRequest();
        $xmlReturn = $client->__getLastResponse();
        $r = $return;

        $this->saveLastResponse($xmlReturn);

        foreach ($data['return'] as $i => $val) {
            if ($data['return'][count($data['return']) - 1] == $val) {
                if (!is_array($r->{$val})) {
                    $r = array($r->{$val});
                } else {
                    $r = $r->{$val};
                }
            } else {
                $r = $r->{$val};
            }
        }

        if (!is_array($r)) {
            $r = array();
        }

        return $r;
    }

    private function saveLastResponse($r) {
        if ($this->saveLastResponse) {
            $fp = fopen(__DIR__ . $this->lastResponseLocation, 'w+');
            fwrite($fp, $r);
            fclose($fp);
        } else {
            $fp = fopen(__DIR__ . $this->lastResponseLocation, 'w+');
            fclose($fp);
        }
    }

    /*
     * Hotel
     */

    public function getHotelList() {
        if (get_option('madeep_data_type') == 'hotel' || get_option('madeep_data_type') == 'ecom') {
            return array();
        } else {
            $this->logAddRow(__FUNCTION__, 'Getting hotel list', __LINE__);
            $list = $this->call(
                    array(
                        'call' => 'listHotels',
                        'return' => array('Hotels', 'Hotel'),
                        'data' => array(
                            'channelid' => $this->user,
                            'portalCode' => $this->pass,
                            'hotelDetails' => 'yes'
                        )
                    )
            );
            return $list;
        }
    }

    public function getHotelRooms($id) {
        if (get_option('madeep_data_type') == 'hotel') {
            $list = $this->call(
                    array(
                        'call' => 'listRooms',
                        'data' => array(
                            'hotelid' => $this->user,
                            'stars' => 5,
                            'channelid' => 1,
                            'portalCode' => $this->pass,
                        )
                    )
            );
        } else {
            $list = $this->call(
                    array(
                        'call' => 'listRooms',
                        'data' => array(
                            'hotelid' => $id,
                            'stars' => 5,
                            'channelid' => $this->user,
                            'portalCode' => $this->pass,
                        )
                    )
            );
        }
        return $list;
    }

    public function getHotelOffers($id) {

        if (get_option('madeep_data_type') == 'hotel') {

            $this->logAddRow(__FUNCTION__, 'Getting offers list', __LINE__);
            $list = $this->call(
                    array(
                        'call' => 'listOffers',
                        'return' => array('Offers', 'Offer'),
                        'data' => array(
                            'hotelid' => $this->user,
                            'channelid' => 1,
                            'portalCode' => $this->pass,
                        )
                    )
            );
        } else if (get_option('madeep_data_type') == 'canale') {
            $this->logAddRow(__FUNCTION__, 'Getting offers list', __LINE__);
            $list = $this->call(
                    array(
                        'call' => 'listOffers',
                        'return' => array('Offers', 'Offer'),
                        'data' => array(
                            'hotelid' => $id,
                            'channelid' => $this->user,
                            'portalCode' => $this->pass,
                        )
                    )
            );
        } else {
            $list = array();
        }
        return $list;
    }

    /*
     * Ecommerce
     */

    public function getEcomList() {
        if (get_option('madeep_data_type') == 'hotel' || get_option('madeep_data_type') == 'ecom') {
            $list = array();
        } else {
            $list = $this->call(
                    array(
                        'call' => 'listEcom',
                        'return' => array('Ecoms', 'Ecom'),
                        'data' => array(
                            'channelid' => $this->user,
                            'portalCode' => $this->pass,
                            'ecomDetails' => 'yes'
                        )
                    )
            );
        }
        return $list;
    }

    public function getEcomServices() {
        if (get_option('madeep_data_type') == 'hotel') {
            $list = array();
        } else {
            $list = $this->call(
                    array(
                        'call' => 'listServices',
                        'return' => array('Services', 'Service'),
                        'data' => array(
                            'channelid' => $this->user,
                            'portalCode' => $this->pass,
                            'servDetails' => 'yes',
                            'ecomid' => ''
                        )
                    )
            );
        }
        return $list;
    }

    public function getCategories() {
        $list = $this->call(
                array(
                    'call' => 'listCats',
                    'data' => array(
                        'channelid' => $this->user,
                        'portalCode' => $this->pass,
                        'servDetails' => 'yes'
                    )
                )
        );

        return $list;
    }

    public function getEvents() {
        $list = $this->call(
                array(
                    'call' => 'listEvents',
                    'return' => array('Events', 'Event'),
                    'data' => array(
                        'channelid' => $this->user,
                        'portalCode' => $this->pass,
                        'servDetails' => 'yes'
                    )
                )
        );

        return $list;
    }

    /*
     * Saving
     */

    public function saveHotels($justThis = 0) {
        global $wpdb, $table_prefix;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $list = $this->getHotelList();
        //print_r($list);
        //die();
        $table = $table_prefix . 'madeep_hotels';
        $table = $this->getTable('hotels');

        $this->logAddRow(__FUNCTION__, 'Found ' . count($list) . ' hotels', __LINE__);
        $this->logAddRow(__FUNCTION__, 'One element mode {' . (((int) $justThis > 0) ? 'ON' : 'OFF') . '}', __LINE__);

        foreach ($list as $key => $val) {

            if ($justThis > 0 && $justThis != $val->id) {
                continue;
            }

            if ($val->Active == 'si') {

                $this->logAddRow(__FUNCTION__, 'Hotel {' . $val->Name . '} is active, continue', __LINE__);

                $serialized = serialize($val);
                $queryCheck = 'SELECT id, id_page FROM ' . $table . ' WHERE id_hotel = ' . (int) $val->id;
                $queryCheckR = $wpdb->get_results($queryCheck);

                if ((int) count($queryCheckR) === 0) {
                    $query = $wpdb->query('INSERT INTO ' . $table . ' SET 
                                            id_hotel = ' . (int) $val->id . ',
                                            id_ecom = "",
                                            name = "' . $val->Name . '",
                                            id_channel = "' . $this->user . '",
                                            id_page = "",
                                            stars = "' . $val->Stars . '",
                                            structureType = "' . $this->structureType[$val->Stars] . '",
                                            email = "' . $val->Email . '",
                                            phone = "' . $val->Phone . '",
                                            address = "' . $val->Address . '",
                                            postalcode = "' . $val->PostalCode . '",
                                            city = "' . $val->City . '",
                                            province = "' . $val->Province . '",
                                            region = "' . $val->Region . '",
                                            nation = "' . $val->Nation . '",
                                            priceFrom = "' . $val->PriceFrom . '",
                                            image = "' . $val->Image . '",
                                            latitude = "' . $val->Latitude . '",
                                            longitude = "' . $val->Longitude . '",
                                            dataS = "' . base64_encode($serialized) . '",
                                            dataH = "' . md5($serialized) . '",
                                            lastUpdate = ' . time());

                    $val->status = 'new';
                    $val->id_page = 0;
                } else if ((int) count($queryCheckR) === 1) {
                    $query = $wpdb->query('UPDATE ' . $table . ' SET 
                                            id_hotel = ' . (int) $val->id . ',
                                            id_ecom = "",
                                            name = "' . $val->Name . '",
                                            id_channel = "' . $this->user . '",
                                            stars = "' . $val->Stars . '",
                                            structureType = "' . $this->structureType[$val->Stars] . '",
                                            email = "' . $val->Email . '",
                                            phone = "' . $val->Phone . '",
                                            address = "' . $val->Address . '",
                                            postalcode = "' . $val->PostalCode . '",
                                            city = "' . $val->City . '",
                                            province = "' . $val->Province . '",
                                            region = "' . $val->Region . '",
                                            nation = "' . $val->Nation . '",
                                            priceFrom = "' . $val->PriceFrom . '",
                                            image = "' . $val->Image . '",
                                            latitude = "' . $val->Latitude . '",
                                            longitude = "' . $val->Longitude . '",
                                            dataS = "' . base64_encode($serialized) . '",
                                            dataH = "' . md5($serialized) . '",
                                            lastUpdate = ' . time() . ' WHERE id_hotel = ' . (int) $val->id);

                    $queryCheck = 'SELECT id_page FROM ' . $table . ' WHERE id_hotel = ' . (int) $val->id;
                    $queryCheckR = $wpdb->get_results($queryCheck);
                    $val->status = 'mod';
                    $val->id_page = (int) $queryCheckR[0]->id_page;
                } else {
                    $pages = array();
                    foreach ($queryCheckR as $qKEy => $qVal) {
                        wp_delete_post($qVal->id_page, true);
                    }
                    $query = $wpdb->query('INSERT INTO ' . $table . ' SET 
                                            id_hotel = ' . (int) $val->id . ',
                                            id_ecom = "",
                                            name = "' . $val->Name . '",
                                            id_channel = "' . $this->user . '",
                                            id_page = "",
                                            stars = "' . $val->Stars . '",
                                            structureType = "' . $this->structureType[$val->Stars] . '",
                                            email = "' . $val->Email . '",
                                            phone = "' . $val->Phone . '",
                                            address = "' . $val->Address . '",
                                            postalcode = "' . $val->PostalCode . '",
                                            city = "' . $val->City . '",
                                            province = "' . $val->Province . '",
                                            region = "' . $val->Region . '",
                                            nation = "' . $val->Nation . '",
                                            priceFrom = "' . $val->PriceFrom . '",
                                            image = "' . $val->Image . '",
                                            latitude = "' . $val->Latitude . '",
                                            longitude = "' . $val->Longitude . '",
                                            dataS = "' . base64_encode($serialized) . '",
                                            dataH = "' . md5($serialized) . '",
                                            lastUpdate = ' . time());

                    $val->status = 'new';
                    $val->id_page = 0;
                }
                if ($this->writePages) {
                    $val->id_page = $this->genPage($val, 'hotels');
                }
                if ($this->c >= $this->cMax && $this->debug && $justThis == 0) {
                    $this->logAddRow(__FUNCTION__, 'Iteration {' . $this->c . '} done, continue...', __LINE__);
                    $this->logAddRow(__FUNCTION__, 'Max debug iterations {' . ($this->cMax + 1) . '} reached, stopping... Have a nice debug!', __LINE__);
                    break;
                } else {
                    $this->logAddRow(__FUNCTION__, 'Iteration {' . $this->c . '} done, continue...', __LINE__);
                    $this->c = $this->c + 1;
                }
            } else {
                $this->logAddRow(__FUNCTION__, 'Hotel {' . $val->Name . '} is inactive, continue', __LINE__);
            }
        }

        if ($justThis == 0) {
            $this->postToDraft('hotels');
        }
        update_option('madeep_time_last_update_hotels', time());
    }

    public function saveEcom($justThis = 0) {
        global $wpdb, $table_prefix;
        if (get_option('madeep_data_type') == 'ecom' || get_option('madeep_data_type') == 'canale') {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $list = $this->getEcomList();
            $table = $table_prefix . 'madeep_ecoms';

            $this->logAddRow(__FUNCTION__, 'Found ' . count($list) . ' ecoms', __LINE__);
            $this->logAddRow(__FUNCTION__, 'One element mode {' . (($justThis > 0) ? 'ON' : 'OFF') . '}', __LINE__);
            foreach ($list as $key => $val) {

                if ($justThis > 0 && $justThis != $val->id) {
                    continue;
                }

                $serialized = serialize($val);
                $queryCheck = 'SELECT id, id_page FROM ' . $table . ' WHERE id_ecom = ' . (int) $val->id;
                $queryCheckR = $wpdb->get_results($queryCheck);

                echo count($queryCheckR) . '<br/>';
                if ((int) count($queryCheckR) === 0) {
                    $query = $wpdb->query('INSERT INTO ' . $table . ' SET 
                                            id_hotel = "",
                                            id_ecom = ' . (int) $val->id . ',
                                            id_channel = "' . $this->user . '",
                                            name = "' . $val->Name . '",
                                            email = "' . $val->Email . '",
                                            phone = "' . $val->Phone . '",
                                            address = "' . $val->Address . '",
                                            postalcode = "' . $val->PostalCode . '",
                                            city = "' . $val->City . '",
                                            province = "' . $val->Province . '",
                                            region = "' . $val->Region . '",
                                            nation = "' . $val->Nation . '",
                                            latitude = "' . $val->Latitude . '",
                                            longitude = "' . $val->Longitude . '",
                                            id_page = "",
                                            dataS = "' . base64_encode($serialized) . '",
                                            dataH = "' . md5($serialized) . '",
                                            lastUpdate = ' . time());

                    $val->status = 'new';
                    $val->id_page = 0;
                } else if ((int) count($queryCheckR) === 1) {
                    $query = $wpdb->query('UPDATE ' . $table . ' SET 
                                            id_hotel = "",
                                            id_ecom = ' . (int) $val->id . ',
                                            id_channel = "' . $this->user . '",
                                            name = "' . $val->Name . '",
                                            email = "' . $val->Email . '",
                                            phone = "' . $val->Phone . '",
                                            address = "' . $val->Address . '",
                                            postalcode = "' . $val->PostalCode . '",
                                            city = "' . $val->City . '",
                                            province = "' . $val->Province . '",
                                            region = "' . $val->Region . '",
                                            nation = "' . $val->Nation . '",
                                            latitude = "' . $val->Latitude . '",
                                            longitude = "' . $val->Longitude . '",
                                            dataS = "' . base64_encode($serialized) . '",
                                            dataH = "' . md5($serialized) . '",
                                            lastUpdate = ' . time() . ' WHERE id_ecom = ' . (int) $val->id);

                    $queryCheck = 'SELECT id_page FROM ' . $table . ' WHERE id_ecom = ' . (int) $val->id;
                    $queryCheckR = $wpdb->get_results($queryCheck);
                    $val->status = 'mod';
                    $val->id_page = (int) $queryCheckR[0]->id_page;
                } else {
                    $pages = array();
                    foreach ($queryCheckR as $qKEy => $qVal) {
                        wp_delete_post($qVal->id_page, true);
                    }
                    $query = $wpdb->query('INSERT INTO ' . $table . ' SET 
                                            id_hotel = "",
                                            id_ecom = ' . (int) $val->id . ',
                                            id_channel = "' . $this->user . '",
                                            name = "' . $val->Name . '",
                                            id_page = "",
                                            dataS = "' . base64_encode($serialized) . '",
                                            dataH = "' . md5($serialized) . '",
                                            lastUpdate = ' . time());

                    $val->status = 'new';
                    $val->id_page = 0;
                }
                if ($this->writePages) {
                    $val->id_page = $this->genPage($val, 'ecoms');
                }

                if ($this->c >= $this->cMax && $this->debug && $justThis == 0) {
                    $this->logAddRow(__FUNCTION__, 'Iteration {' . $this->c . '} done, continue...', __LINE__);
                    $this->logAddRow(__FUNCTION__, 'Max debug iterations {' . ($this->cMax + 1) . '} reached, stopping... Have a nice debug!', __LINE__);
                    break;
                } else {
                    $this->logAddRow(__FUNCTION__, 'Iteration {' . $this->c . '} done, continue...', __LINE__);
                    $this->c = $this->c + 1;
                }
            }

            if ($justThis == 0) {
                $this->postToDraft('ecoms');
            }
            update_option('madeep_time_last_update_ecom', time());
        }
    }

    public function saveOffers($justThis = 0, $inThat = 0) {
        global $wpdb, $table_prefix;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        if (get_option('madeep_data_type') == 'hotel') {
            $this->logAddRow(__FUNCTION__, 'Getting offers list from hotel', __LINE__);
            $this->logAddRow(__FUNCTION__, 'Getting offers list for hotel {' . $this->user . '}', __LINE__);
            if ($inThat > 0 && $inThat == $this->user) {
                $list = $this->getHotelOffers($this->user);
                $this->_saveOffers($list, $force, $this->user, $justThis, $inThat);
            } else if ($inThat == 0) {
                $list = $this->getHotelOffers($this->user);
                $this->_saveOffers($list, $force, $this->user, $justThis, $inThat);
            }
        } else if (get_option('madeep_data_type') == 'ecom') {
            
        } else {

            $this->logAddRow(__FUNCTION__, 'Getting offers list from channel', __LINE__);

            $table = $table_prefix . 'madeep_offers';
            $tableHotels = $table_prefix . 'madeep_hotels';

            $queryhotels = 'SELECT id_hotel as id FROM ' . $tableHotels . ' ORDER BY id';
            $queryHotelsR = $wpdb->get_results($queryhotels);

            foreach ($queryHotelsR as $keyH => $valH) {
                if ($inThat > 0 && $inThat != $valH->id) {
                    continue;
                }

                if ($this->c >= $this->cMax && $this->debug) {
                    $this->logAddRow(__FUNCTION__, 'Iteration {' . $this->c . '} done, continue...', __LINE__);
                    $this->logAddRow(__FUNCTION__, 'Max debug iterations {' . ($this->cMax + 1) . '} reached, stopping... Have a nice debug!', __LINE__);
                    break;
                }

                $this->logAddRow(__FUNCTION__, 'Getting offers list for hotel {' . $valH->id . '}', __LINE__);
                $list = $this->getHotelOffers($valH->id);
                $this->_saveOffers($list, $force, $valH->id, $justThis, $inThat);
            }
        }
    }

    public function _saveOffers($list, $force = false, $idHotel = 0, $justThis = 0, $inThat = 0) {
        global $wpdb, $table_prefix;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $table = $table_prefix . 'madeep_offers';
        $tableHotels = $table_prefix . 'madeep_hotels';

        $this->logAddRow(__FUNCTION__, 'Found ' . count($list) . ' offers for ' . $idHotel, __LINE__);
        $this->logAddRow(__FUNCTION__, 'One element mode {' . (($justThis > 0) ? 'ON' : 'OFF') . '}', __LINE__);
        if ($justThis > 0) {
            $this->logAddRow(__FUNCTION__, 'jT: ' . (int) $justThis . ', iT: ' . (int) $inThat, __LINE__);
        }
        foreach ($list as $key => $val) {

            if ($justThis > 0 && $justThis != $val->Productid) {
                continue;
            }

            $serialized = serialize($val);
            $queryCheck = 'SELECT id, id_page FROM ' . $table . ' WHERE id_hotel = ' . (int) $val->Hotelid . ' AND productid = "' . $val->Productid . '"';
            $queryCheckR = $wpdb->get_results($queryCheck);
            if ((int) count($queryCheckR) == 0) {

                $query = $wpdb->query('INSERT INTO ' . $table . ' SET 
                                            id_hotel = "' . (int) $idHotel . '",
                                            id_ecom = "",
                                            id_channel = "' . $this->user . '",
                                            productid = "' . $val->Productid . '",
                                            priceFrom = "' . $val->PriceFrom . '",
                                            imgUrl = "' . $val->ImgUrl . '",
                                            id_page = "",
                                            dataS = "' . base64_encode($serialized) . '",
                                            dataH = "' . md5($serialized) . '",
                                            lastUpdate = ' . time());

                $tableHotels = $table_prefix . 'madeep_hotels';
                $queryhotels = 'SELECT name,id_page FROM ' . $tableHotels . ' WHERE id_hotel = ' . (int) $idHotel;
                $queryHotelsR = $wpdb->get_results($queryhotels);
                $val->hotelName = $queryHotelsR[0]->name;
                $val->hotelUrl = get_page_link($queryHotelsR[0]->id_page);
                $val->status = 'new';
                $val->id_page = 0;
            } else if ((int) count($queryCheckR) === 1) {
                $query = $wpdb->query('UPDATE ' . $table . ' SET 
                                            id_hotel = "' . (int) $idHotel . '",
                                            id_ecom = "",
                                            id_channel = "' . $this->user . '",
                                            productid = "' . $val->Productid . '",
                                            priceFrom = "' . $val->PriceFrom . '",
                                            imgUrl = "' . $val->ImgUrl . '",
                                            dataS = "' . base64_encode($serialized) . '",
                                            dataH = "' . md5($serialized) . '",
                                            lastUpdate = ' . time() . ' WHERE id_hotel = ' . (int) $idHotel . ' AND productid = ' . (int) $val->Productid);

                $tableHotels = $table_prefix . 'madeep_hotels';
                $queryhotels = 'SELECT name,id_page FROM ' . $tableHotels . ' WHERE id_hotel = ' . (int) $idHotel;
                $queryHotelsR = $wpdb->get_results($queryhotels);
                $val->hotelName = $queryHotelsR[0]->name;
                $val->hotelUrl = get_page_link($queryHotelsR[0]->id_page);

                $queryCheck = 'SELECT id_page FROM ' . $table . ' WHERE id_hotel = ' . (int) $idHotel . ' AND productid = ' . (int) $val->Productid;
                $queryCheckR = $wpdb->get_results($queryCheck);
                $val->status = 'mod';
                $val->id_page = (int) $queryCheckR[0]->id_page;
            } else {
                $pages = array();
                foreach ($queryCheckR as $qKEy => $qVal) {
                    wp_delete_post($qVal->id_page, true);
                }
                $queryCheck = $wpdb->query('DELETE FROM ' . $table . ' WHERE id_hotel = ' . (int) $idHotel . ' AND productid = ' . (int) $val->Productid);
                $query = $wpdb->query('INSERT INTO ' . $table . ' SET 
                                            id_hotel = "' . (int) $idHotel . '",
                                            id_ecom = "",
                                            id_channel = "' . $this->user . '",
                                            productid = "' . $val->Productid . '",
                                            priceFrom = "' . $val->PriceFrom . '",
                                            imgUrl = "' . $val->ImgUrl . '",
                                            id_page = "",
                                            dataS = "' . base64_encode($serialized) . '",
                                            dataH = "' . md5($serialized) . '",
                                            lastUpdate = ' . time());

                $tableHotels = $table_prefix . 'madeep_hotels';
                $queryhotels = 'SELECT name,id_page FROM ' . $tableHotels . ' WHERE id_hotel = ' . (int) $idHotel;
                $queryHotelsR = $wpdb->get_results($queryhotels);
                $val->hotelName = $queryHotelsR[0]->name;
                $val->hotelUrl = get_page_link($queryHotelsR[0]->id_page);
                $val->status = 'new';
                $val->id_page = 0;
            }
            if ($this->writePages && (int) $val->Hotelid > 0) {
                $val->id_page = $this->genPage($val, 'offers');
            }

            if ($this->c >= $this->cMax && $this->debug && $justThis == 0) {
                $this->logAddRow(__FUNCTION__, 'Iteration {' . $this->c . '} done, continue...', __LINE__);
                $this->logAddRow(__FUNCTION__, 'Max debug iterations {' . ($this->cMax + 1) . '} reached, stopping... Have a nice debug!', __LINE__);
                break;
            } else {
                $this->logAddRow(__FUNCTION__, 'Iteration {' . $this->c . '} done, continue...', __LINE__);
                $this->c = $this->c + 1;
            }
        }
        if ($justThis == 0) {
            $this->postToDraft('offers');
        }
        update_option('madeep_time_last_update_offers', time());
    }

    private function checkPagePostExists($id) {
        $valid = !is_null(get_post($id));
        if ($valid) {
            return $id;
        } else {
            return 0;
        }
    }

    private function getWPMultilangTemplate($t, $l) {
        if (strpos($t, '[:') !== false) {
            $this->logAddRow(__FUNCTION__, 'WPMultiLang tag found', __LINE__);
            if (strpos($t, '[:' . $l . ']') !== false) {
                $this->logAddRow(__FUNCTION__, 'WPMultiLang tag in lang {' . $l . '} found', __LINE__);
                $s = strpos($t, '[:' . $l . ']');
                $e = strpos($t, '[:', $s + 5);
                $t = substr($t, $s + 5, $e);
            } else {
                $s = strpos($t, '[:');
                $e = strpos($t, '[:', $s + 5);
                $t = substr($t, $s + 5, $e);
            }
            return $t;
        } else {
            return $t;
        }
    }

    private function genContentWPMultilang($arr, $t) {
        $template = $this->getTemplate($t);
        $style = $template['style'];
        $template = $template['html'];
        $html = '';

        foreach ($this->activeLanguage as $key => $val) {
            $this->logAddRow(__FUNCTION__, 'Generating template in {' . $this->getLang($val) . '} for wp-multilang', __LINE__);
            //$this->logAddRow(__FUNCTION__, 'Original template:', __LINE__);
            //$this->logAddRow(__FUNCTION__, $template, __LINE__);
            $templateLang = $this->getWPMultilangTemplate($template, $this->getLang($val));
            //$this->logAddRow(__FUNCTION__, 'Lang template:', __LINE__);
            //$this->logAddRow(__FUNCTION__, $this->getWPMultilangTemplate($template, $this->getLang($val)), __LINE__);
            //$templateLang = preg_replace('(\[\:[a-z]{0,2}\])', '', $templateLang);
            $services = '';
            $gallery = '';
            $map = '';

            if (strpos($templateLang, '%map%') !== false) {
                $map = $this->genMap(array('lat' => $arr['latitude'], 'lon' => $arr['longitude']), array('text' => $this->genAddress($arr)));
            }

            $from = array('%map%', '%services%');
            $to = array($map, $services);

            $templateLang = str_replace($from, $to, $templateLang);
            $templateLang = $this->replacePlaceholder($arr, $templateLang, $this->getLang($val, 1));
            $templateLang = $this->replaceLangs($templateLang, $this->getLang($val, 1));

            $html .= '[:' . $this->getLang($val) . ']' . $this->genPageData($arr) . $style . $templateLang;
        }
        $this->logAddRow(__FUNCTION__, 'Resulting page:', __LINE__);
        $this->logAddRow(__FUNCTION__, $html . '[:]', __LINE__);
        return $html . '[:]';
    }

    private function genContent($arr, $t = 'hotel', $lang = '') {

        if ($this->multiLangPlugin === 'wp-multilang' && $this->multiLangPlugin != null) {
            $this->logAddRow(__FUNCTION__, 'Found wp-multilang plugin', __LINE__);
            $html = $this->genContentWPMultilang($arr, $t);
        } else {
            $template = $this->getTemplate($t, $lang);
            $style = $template['style'];
            $template = $template['html'];
            $html = '';
            $lang = ($lang != '') ? $lang : $this->defaultLang;

            $templateLang = $template;
            $services = '';
            $gallery = '';
            $map = '';

            $gallery = $arr['galleryIDs'];
            if (strpos($templateLang, '%map%') !== false) {
                $map = $this->genMap(array('lat' => $arr['latitude'], 'lon' => $arr['longitude']), array('text' => $this->genAddress($arr)));
            }

            $from = array('%map%', '%services%');
            $to = array($map, $services);
            $templateLang = str_replace($from, $to, $templateLang);
            $templateLang = $this->replacePlaceholder($arr, $templateLang, $lang);
            $templateLang = $this->replaceLangs($templateLang, $this->getLang($lang, 1));

            $html .= $this->genPageData($arr) . $style . $templateLang;
        }
        return $html;
    }

    public function saveServices($justThis = 0, $inThat = 0) {
        global $wpdb, $table_prefix;
        if (get_option('madeep_data_type') == 'ecom' || get_option('madeep_data_type') == 'canale') {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $list = $this->getEcomServices();
            $table = $table_prefix . 'madeep_services';

            $this->logAddRow(__FUNCTION__, 'Found ' . count($list) . ' services', __LINE__);
            $this->logAddRow(__FUNCTION__, 'One element mode {' . (($justThis > 0) ? 'ON' : 'OFF') . '}', __LINE__);
            if ($justThis > 0) {
                $this->logAddRow(__FUNCTION__, 'jT: ' . (int) $justThis . ', iT: ' . (int) $inThat, __LINE__);
            }
            foreach ($list as $key => $val) {

                if ($justThis > 0 && ($justThis != $val->id || (int) $inThat != (int) $val->Ecomid)) {
                    $this->logAddRow(__FUNCTION__, 'These are not the pages you\'re looking for... {' . $val->id . '} != {' . $justThis . '}', __LINE__);
                    continue;
                }

                $serialized = serialize($val);
                $queryCheck = 'SELECT id, id_service, id_ecom, id_page FROM ' . $table . ' WHERE id_service = ' . (int) $val->id . ' AND id_ecom = ' . (int) $val->Ecomid;
                $queryCheckR = $wpdb->get_results($queryCheck);

                $val->ValidFrom = explode('-', $val->ValidFrom);
                $val->ValidFrom = date('U', mktime(0, 0, 1, $val->ValidFrom[1], $val->ValidFrom[0], $val->ValidFrom[2]));
                $val->ValidTo = explode('-', $val->ValidTo);
                $val->ValidTo = date('U', mktime(0, 0, 1, $val->ValidTo[1], $val->ValidTo[0], $val->ValidTo[2]));

                if ((int) count($queryCheckR) === 0) {
                    $query = $wpdb->query('INSERT INTO ' . $table . ' SET 
                                            id_hotel = "",
                                            id_service = ' . (int) $val->id . ',
                                            id_ecom = ' . (int) $val->Ecomid . ',
                                            id_channel = "' . $this->user . '",
                                            validityFrom = "' . $val->ValidFrom . '",
                                            validityTo = "' . $val->ValidTo . '",
                                            priceFrom = "' . $val->PriceForm . '",
                                            imgUrl = "' . $val->ImgUrl . '",
                                            code = "' . $val->Code . '",
                                            dataS = "' . base64_encode($serialized) . '",
                                            dataH = "' . md5($serialized) . '",
                                            lastUpdate = ' . time());

                    $tableEcom = $table_prefix . 'madeep_ecoms';
                    $queryecom = 'SELECT name,id_page FROM ' . $tableEcom . ' WHERE id_ecom = ' . (int) $val->Ecomid;
                    $queryecomR = $wpdb->get_results($queryecom);
                    $val->ecomName = $queryecomR[0]->name;
                    $val->ecomUrl = get_page_link($queryecomR[0]->id_page);

                    $val->status = 'new';
                    $val->id_page = 0;
                } else if ((int) count($queryCheckR) === 1) {
                    $query = $wpdb->query('UPDATE ' . $table . ' SET 
                                            id_hotel = "",
                                            id_service = ' . (int) $val->id . ',
                                            id_ecom = ' . (int) $val->Ecomid . ',
                                            id_channel = "' . $this->user . '",
                                            validityFrom = "' . $val->ValidFrom . '",
                                            validityTo = "' . $val->ValidTo . '",
                                            priceFrom = "' . $val->PriceForm . '",
                                            imgUrl = "' . $val->ImgUrl . '",
                                            code = "' . $val->Code . '",
                                            dataS = "' . base64_encode($serialized) . '",
                                            dataH = "' . md5($serialized) . '",
                                            lastUpdate = ' . time() . ' WHERE id_ecom = ' . (int) $val->Ecomid . ' AND id_service = ' . (int) $val->id);

                    $tableEcom = $table_prefix . 'madeep_ecoms';
                    $queryecom = 'SELECT name,id_page FROM ' . $tableEcom . ' WHERE id_ecom = ' . (int) $val->Ecomid;
                    $queryecomR = $wpdb->get_results($queryecom);
                    $val->ecomName = $queryecomR[0]->name;
                    $val->ecomUrl = get_page_link($queryecomR[0]->id_page);

                    $queryCheck = 'SELECT id_page FROM ' . $table . ' WHERE id_ecom = ' . (int) $val->Ecomid . ' AND id_service = ' . (int) $val->id;
                    $queryCheckR = $wpdb->get_results($queryCheck);

                    $val->status = 'mod';
                    $val->id_page = (int) $queryCheckR[0]->id_page;
                } else {
                    $pages = array();
                    foreach ($queryCheckR as $qKEy => $qVal) {
                        wp_delete_post($qVal->id_page, true);
                    }
                    $queryCheck = 'DELETE FROM ' . $table . ' WHERE id_service = ' . (int) $val->id . ' AND id_ecom = ' . (int) $val->Ecomid;
                    $queryCheckR = $wpdb->get_results($queryCheck);

                    $query = $wpdb->query('INSERT INTO ' . $table . ' SET 
                                            id_hotel = "",
                                            id_service = ' . (int) $val->id . ',
                                            id_ecom = ' . (int) $val->Ecomid . ',
                                            id_channel = "' . $this->user . '",
                                            validityFrom = "' . $val->ValidFrom . '",
                                            validityTo = "' . $val->ValidTo . '",
                                            priceFrom = "' . $val->PriceForm . '",
                                            imgUrl = "' . $val->ImgUrl . '",
                                            code = "' . $val->Code . '",
                                            dataS = "' . base64_encode($serialized) . '",
                                            dataH = "' . md5($serialized) . '",
                                            lastUpdate = ' . time());

                    $tableEcom = $table_prefix . 'madeep_ecoms';
                    $queryecom = 'SELECT name,id_page FROM ' . $tableEcom . ' WHERE id_ecom = ' . (int) $val->Ecomid;
                    $queryecomR = $wpdb->get_results($queryecom);
                    $val->ecomName = $queryecomR[0]->name;
                    $val->ecomUrl = get_page_link($queryecomR[0]->id_page);

                    $val->status = 'new';
                    $val->id_page = 0;
                }
                if ($this->writePages) {
                    $val->id_page = $this->genPage($val, 'services');
                }

                $sID = $data->Ecomid;
                if ($this->c >= $this->cMax && $this->debug && $justThis == 0) {
                    $this->logAddRow(__FUNCTION__, 'Iteration {' . $this->c . '} done, continue...', __LINE__);
                    $this->logAddRow(__FUNCTION__, 'Max debug iterations {' . ($this->cMax + 1) . '} reached, stopping... Have a nice debug!', __LINE__);
                    break;
                } else {
                    $this->logAddRow(__FUNCTION__, 'Iteration {' . $this->c . '} done, continue...', __LINE__);
                    $this->c = $this->c + 1;
                }
            }
            if ($justThis == 0) {
                $this->postToDraft('services');
            }
            update_option('madeep_time_last_update_services', time());
        }
    }

    public function replaceLangs($text, $lang) {

        $from = array();
        $to = array();
        $lang = $this->getLang($lang, 1);

        preg_match_all('/#([a-zA-Z0-9_-]{1,})#/i', $text, $arr);
        if (is_array($arr)) {
            foreach ($arr[1] as $key => $val) {
                $from[] = '#' . $val . '#';

                if (isset($this->lang[$lang])) {
                    if (isset($this->lang[$lang]->{$val})) {
                        $to[] = $this->lang[$lang]->{$val};
                    } else {
                        $to[] = ucfirst($val);
                    }
                } else {
                    $to[] = ucfirst($val);
                }
            }
        }

        $text = str_replace($from, $to, $text);

        return $text;
    }

    private function stripArrayTags($arr) {
        if (is_array($arr)) {
            foreach ($arr as $key => $val) {
                $arr[$key] = strip_tags($val);
            }
        } else {
            $arr = strip_tags($arr);
        }
        return $arr;
    }

    /* Tags */

    private function genMap($location = array('lat' => 0, 'lon' => ''), $options = array()) {

        $html = '[MadeepMap location="' . $location['lat'] . ',' . $location['lon'] . '"';
        foreach ($options as $key => $val) {
            $html .= ' ' . $key . '="' . base64_encode($val) . '" ';
        }
        $html .= ']';

        return $html;
    }

    private function genGallery($imgs = array(), $options = array(), $lang = null) {
        $imgsArr = array();
        $lang = ($lang == null) ? $this->defaultLang : $lang;
        if (!is_array($imgs) && strlen(trim($imgs)) > 0) {
            $imgsArr[] = $imgs;
        } else {
            $imgsArr = $imgs;
        }
        $imgIDs = array();
        foreach ($imgsArr as $key => $val) {
            if ($this->downloadGalleryImages) {
                $imgIDs[] = $this->uploadImage($val, $options['id_page'], $options);
            } else {
                $this->logAddRow(__FUNCTION__, 'Upload disabled, return URL', __LINE__);
                $imgIDs[] = $val;
            }
        }

        if ($this->downloadGalleryImages) {
            $this->logAddRow(__FUNCTION__, 'Uploaded ' . count($imgIDs) . ' images', __LINE__);
        } else {
            $this->logAddRow(__FUNCTION__, 'Returned ' . count($imgIDs) . ' URL', __LINE__);
        }

        return implode(',', $imgIDs);
    }

    private function removeOldMedia($id) {

        $galleryIDs = get_post_meta($id, 'madeep_galleryIDs');
        $galleryIDs = explode(',', $galleryIDs[0]);
        $this->logAddRow(__FUNCTION__, 'Found ' . count($galleryIDs) . ' old media files', __LINE__);
        foreach ($galleryIDs as $key => $val) {
            wp_delete_attachment($val, true);
        }
        return $galleryIDs;
    }

    private function removeOldFeaturedImage($id) {

        $galleryIDs = get_post_meta($id, 'madeep_featuredImageID');
        $this->logAddRow(__FUNCTION__, 'Found old featured media file {' . (int) $galleryIDs[0] . '}', __LINE__);
        $r = wp_delete_attachment((int) $galleryIDs[0], true);
    }

    private function genServiceList($arr, $lang, $perLang = false) {
        $html = '';

        if (is_array($arr['id'])) {
            $id = implode(',', $arr['id']);
        } else {
            $id = $arr['id'];
        }

        if ($perLang) {
            if ($this->multiLangPlugin === 'wp-multilang' && $this->multiLangPlugin != null) {
                foreach ($this->activeLanguage as $key => $val) {
                    $html .= '[:' . $this->getLang($val) . '][MadeepEcomServicesList id="' . $id . '" lang="' . $this->getLang($val) . '"]';
                }
                $html .= '[:]';
            }
        } else {


            $html = '[MadeepEcomServicesList id="' . $id . '" lang="' . $this->getLang($lang) . '"]';
        }

        return $html;
    }

    private function genOffersList($arr, $lang, $perLang = false) {
        $html = '';

        if (is_array($arr['id'])) {
            $id = implode(',', $arr['id']);
        } else {
            $id = $arr['id'];
        }

        if ($perLang) {
            if ($this->multiLangPlugin === 'wp-multilang' && $this->multiLangPlugin != null) {
                foreach ($this->activeLanguage as $key => $val) {
                    $html .= '[:' . $this->getLang($val) . '][MadeepOffersList id="' . $id . '" lang="' . $this->getLang($val) . '"]';
                }
                $html .= '[:]';
            }
        } else {


            $html = '[MadeepOffersList id="' . $id . '" lang="' . $this->getLang($lang) . '"]';
        }

        return $html;
    }

    private function genStructureTypeHTML($n) {
        $html = '';
        if ((int) $n > 6) {
            $html .= '<span class="structureType">' . $this->structureType[$n] . '</span>';
        } else {
            $html .= '<span class="stars">';
            for ($i = 0; $i < $n; $i++) {
                $html .= '<span class="madeepStars"></span>';
            }
            $html .= '</span>';
        }

        return $html;
    }

    public function arr2langarr($arr) {
        $langArr = array();
        //print_r($arr);
        if (is_array($arr)) {
            foreach ($arr as $key => $val) {
                foreach ($val->Detail as $key2 => $val2) {
                    if (isset($val2->name)) {
                        if (!isset($val2->id)) {
                            if (strpos($val2->name, 'SHORTDESC') !== false) {
                                $val2->id = 777;
                                $val2->name = 'shortdesc';
                            } else if (strpos($val2->name, 'LONGDESC') !== false) {
                                $val2->id = 888;
                                $val2->name = 'longdesc';
                            } else if (strpos($val2->name, 'NAME') !== false) {
                                $val2->id = 666;
                                $val2->name = 'name';
                            }
                        }

                        $langArr[$val2->id][$val->language] = array('title' => $val2->name, 'value' => str_replace(array('<![CDATA[', ']]>'), '', $val2->value));

                        if (isset($val2->crtdet) && is_array($val2->crtdet)) {
                            foreach ($val2->crtdet as $key3 => $val3) {
                                $langArr['_tags_'][$val->language][$val3->code] = $val3->name;
                            }
                        }
                        if (isset($val2->subDetail)) {
                            $langArr[$val2->id][$val->language]['subDetail'] = $val2->subDetail;
                        }
                    }
                }
                $langArr['burl'][$val->language] = array('title' => 'burl', 'value' => str_replace(array('<![CDATA[', ']]>'), '', $val->burl));
                $langArr['cate'][$val->language] = $val->cate;
                $langArr['catscan'][$val->language] = $val->catscan;

                if (strpos($langArr['cate'][$val->language], '#@') !== false) {
                    $langArr['cate'][$val->language] = explode('#@', $langArr['cate'][$val->language]);
                } else if (strlen(trim($langArr['cate'][$val->language])) > 0) {
                    $langArr['cate'][$val->language] = array($langArr['cate'][$val->language]);
                }

                if (strpos($langArr['catscan'][$val->language], '#@') !== false) {
                    $langArr['catscan'][$val->language] = explode('#@', $langArr['catscan'][$val->language]);
                } else if (strlen(trim($langArr['catscan'][$val->language])) > 0) {
                    $langArr['catscan'][$val->language] = array($langArr['catscan'][$val->language]);
                }
            }
        }
        return $langArr;
    }

    public function arr2langarrOffers($arr) {
        $langArr = array();
        //print_r($arr);
        if (is_array($arr->Contents->Content)) {
            foreach ($arr->Contents->Content as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if (stripos($key2, 'SHORTDESC') !== false) {
                        $langArr[777][$val->lang] = array('title' => $key2, 'value' => str_replace(array('<![CDATA[', ']]>'), '', $val2));
                        $langArr[strtolower($key2)][$val->lang] = array('title' => $key2, 'value' => str_replace(array('<![CDATA[', ']]>'), '', $val2));
                    } else if (stripos($key2, 'LONGDESC') !== false) {
                        $langArr[888][$val->lang] = array('title' => $key2, 'value' => str_replace(array('<![CDATA[', ']]>'), '', $val2));
                        $langArr[strtolower($key2)][$val->lang] = array('title' => $key2, 'value' => str_replace(array('<![CDATA[', ']]>'), '', $val2));
                    } else if (stripos($key2, 'NAME') !== false) {
                        $langArr[666][$val->lang] = array('title' => $key2, 'value' => str_replace(array('<![CDATA[', ']]>'), '', $val2));
                        $langArr[strtolower($key2)][$val->lang] = array('title' => $key2, 'value' => str_replace(array('<![CDATA[', ']]>'), '', $val2));
                    } else {
                        $langArr[strtolower($key2)][$val->lang] = array('title' => $key2, 'value' => str_replace(array('<![CDATA[', ']]>'), '', $val2));
                    }
                }
            }
        }
        return $langArr;
    }

    public function getDataActiveLanguage($arr = array()) {
        $langArr = array();
        $langArr = explode(',', get_option('madeep_active_languages'));

        return $langArr;
    }

    public function checkMultilanguagePlugins() {

        if (get_option('madeep_active_multilanguages') != 1) {
            $this->multiLangPlugin = null;
            return $this->multiLangPlugin;
        }

        if (in_array('wp-multilang/wp-multilang.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $this->multiLangPlugin = 'wp-multilang';
        } else if (function_exists('icl_object_id')) {
            $this->multiLangPlugin = 'wpml';
        } else {
            $this->multiLangPlugin = null;
        }
        return $this->multiLangPlugin;
    }

    public function strToLang($arr, $index = '', $lang = '') {

        return $this->strToLang2($arr, $index, $lang);
        if ($this->multiLangPlugin == null) {
            $str = $arr[$this->getLang($this->defaultLang, 1)][$index];
        } else if ($this->multiLangPlugin == 'wp-multilang') {
            if (is_array($arr)) {
                $str = '';
                //foreach ($arr as $key => $val) {
                foreach ($this->activeLanguage as $key => $val) {
                    $str .= '[:' . $this->getLang($val) . ']';
                    if (isset($arr[$this->getLang($val, 1)][$index])) {
                        $str .= $arr[$this->getLang($val, 1)][$index];
                    }
                }
                $str .= '[:]';
            } else if (is_object($arr)) {
                $str = '';
                foreach ($this->activeLanguage as $key => $val) {
                    $str .= '[:' . $this->getLang($val) . ']';
                    if (isset($arr->{getLang($val, 1)}->{$index})) {
                        $str .= $arr->{getLang($val, 1)}->{$index};
                    }
                }
                $str .= '[:]';
            } else {
                $str = '';
                foreach ($this->activeLanguage as $key => $val) {
                    $str .= '[:' . $this->getLang($val) . ']' . $arr;
                }
                $str .= '[:]';
            }
        }
        return $str;
    }

    public function strToLang2($arr, $index = '', $lang = '') {

        if ($this->multiLangPlugin === 'wp-multilang' && $this->multiLangPlugin != null) {
            if (is_array($arr)) {
                $str = '';
                foreach ($this->activeLanguage as $key => $val) {
                    $str .= '[:' . $this->getLang($val) . ']';
                    if (isset($arr[$index][$this->getLang($val, 1)])) {
                        $str .= $arr[$index][$this->getLang($val, 1)]['value'];
                    }
                }
                $str .= '[:]';
            } else if (is_object($arr)) {
                $str = '';
                foreach ($this->activeLanguage as $key => $val) {
                    $str .= '[:' . $this->getLang($val) . ']';
                    if (isset($arr->{$index}->{getLang($val, 1)})) {
                        $str .= $arr->{$index}->{getLang($val, 1)}->value;
                    }
                }
                $str .= '[:]';
            } else {
                $str = '';
                foreach ($this->activeLanguage as $key => $val) {
                    $str .= '[:' . $this->getLang($val) . ']' . $arr;
                }
                $str .= '[:]';
            }
        } else {

            $lang = ($lang != '') ? $lang : $this->defaultLang;

            if (is_array($arr)) {
                $str = $arr[$index][$this->getLang($lang, 1)]['value'];
            } else if (is_object($arr)) {
                $str = $arr->{$index}->{getLang($lang, 1)}->value;
            } else {
                $str = $arr;
            }
        }
        return $str;
    }

    public function replacePlaceholder($arr, $str, $lang) {
        $structuretype = '';
        if (isset($arr['stars'])) {
            $structuretype = $this->genStructureTypeHTML($arr['stars']);
        }

        $lang = $this->getLang($lang, 1);

        if (!isset($arr['image'])) {
            $arr['image'] = '';
        }
        if (!isset($arr['hotelUrl'])) {
            $arr['hotelUrl'] = '';
        }
        if (!isset($arr['serviceList'])) {
            $arr['serviceList'] = '';
        }
        if (isset($arr['structureType'])) {
            $arr['structureType'] = $this->getStructureType($arr['structureType'], $lang);
        }

        if (isset($arr['structureType'])) {
            if ($arr['type'] == 'hotels') {
                if ($arr['stars'] > 0 && $arr['stars'] < 7) {
                    $arr['structureCompleteType'] = str_replace(array('%type', '%starsN', '%stars'), array($arr['structureType'], $arr['stars'], $this->lang[$this->getLang($lang, 1)]->stars), $this->lang[$this->getLang($lang, 1)]->structureFormat);
                } else {
                    $arr['structureCompleteType'] = $arr['structureType'];
                }
            } else {
                $arr['structureCompleteType'] = $arr['structureType'];
            }
        } else {
            $arr['structureCompleteType'] = '';
        }

        $replaceArr = array(
            '%name%' => (isset($arr['name'])) ? wp_kses_post($arr['name']) : '',
            '%nameDetails%' => (isset($arr['texts'][666][$lang])) ? wp_kses_post($arr['texts'][666][$lang]['value']) : '',
            '%shortdesc%' => (isset($arr['texts'][777][$lang])) ? wp_kses_post($arr['texts'][777][$lang]['value']) : '',
            '%longdesc%' => (isset($arr['texts'][888][$lang])) ? wp_kses_post($arr['texts'][888][$lang]['value']) : '',
            '%email%' => (isset($arr['email'])) ? esc_html($arr['email']) : '',
            '%phone%' => (isset($arr['phone'])) ? esc_html($arr['phone']) : '',
            '%fax%' => (isset($arr['fax'])) ? esc_html($arr['fax']) : '',
            '%price%' => (isset($arr['price'])) ? esc_html($arr['price']) : '',
            '%priceFrom%' => (isset($arr['priceFrom'])) ? esc_html($arr['priceFrom']) : '',
            '%stars%' => (isset($arr['stars'])) ? esc_html($arr['stars']) : '',
            '%type%' => (isset($arr['type'])) ? esc_html($arr['type']) : '',
            '%structureType%' => (isset($arr['structureType'])) ? esc_html($arr['structureType']) : '',
            '%structureStars%' => (isset($arr['stars']) && $arr['stars'] > 0 && $arr['stars'] < 7) ? esc_html($arr['stars']) . ' ' . $this->lang[$this->getLang($lang, 1)]->stars : '',
            '%structureCompleteType%' => $arr['structureCompleteType'],
            '%image%' => (isset($arr['image'])) ? esc_url($arr['image']) : '',
            '%hotelUrl%' => (isset($arr['hotelUrl'])) ? esc_url($arr['hotelUrl']) : '',
            '%hotelName%' => (isset($arr['hotelName'])) ? esc_url($arr['hotelName']) : '',
            '%ecomName%' => (isset($arr['ecomName'])) ? esc_url($arr['ecomName']) : '',
            '%serviceUrl%' => (isset($arr['serviceUrl'])) ? esc_url($arr['serviceUrl']) : '',
            '%ecomUrl%' => (isset($arr['ecomUrl'])) ? esc_url($arr['ecomUrl']) : '',
            '%offerUrl%' => (isset($arr['offerUrl'])) ? esc_url($arr['offerUrl']) : '',
            '%address%' => (isset($arr['address'])) ? esc_html($arr['address']) : '',
            '%postalcode%' => (isset($arr['postalcode'])) ? esc_html($arr['postalcode']) : '',
            '%city%' => (isset($arr['city'])) ? esc_html($arr['city']) : '',
            '%province%' => (isset($arr['province'])) ? esc_html($arr['province']) : '',
            '%region%' => (isset($arr['region'])) ? esc_html($arr['region']) : '',
            '%nation%' => (isset($arr['nation'])) ? esc_html($arr['nation']) : '',
            '%fulladdress%' => $this->genAddress($arr),
            '%meet_address%' => (isset($arr['meet_address'])) ? esc_html($arr['meet_address']) : '',
            '%meet_postalcode%' => (isset($arr['meet_postalcode'])) ? esc_html($arr['meet_postalcode']) : '',
            '%meet_city%' => (isset($arr['meet_city'])) ? esc_html($arr['meet_city']) : '',
            '%meet_province%' => (isset($arr['meet_province'])) ? esc_html($arr['meet_province']) : '',
            '%meet_region%' => (isset($arr['meet_region'])) ? esc_html($arr['meet_region']) : '',
            '%meet_nation%' => (isset($arr['meet_nation'])) ? esc_html($arr['meet_nation']) : '',
            '%meet_fulladdress%' => $this->genAddress($arr, 1),
            '%serviceList%' => $this->genServiceList($arr, $lang),
            '%offersList%' => $this->genOffersList($arr, $lang),
            //'%info%' => $this->genInfoTabs($arr, $lang),
            '%ecomID%' => (isset($arr['id'])) ? (int) $arr['id'] : '',
            '%hotelID%' => (isset($arr['id'])) ? (int) $arr['id'] : '',
            '%serviceID%' => (isset($arr['id'])) ? (int) $arr['id'] : '',
            '%offerID%' => (isset($arr['id'])) ? (int) $arr['id'] : '',
            '%buyUrl%' => (isset($arr['texts']['burl'][$lang])) ? esc_url($arr['texts']['burl'][$lang]['value']) : '',
            '%buyUrlNoHttp%' => (isset($arr['texts']['burl'][$lang])) ? str_replace(array('http://', 'https://', 'http%3A%2F%2F', 'https%3A%2F%2F'), '', $arr['texts']['burl'][$lang]['value']) : '',
            '%lang%' => $lang,
            '%ecomName%' => (isset($arr['ecomName'])) ? esc_html($arr['ecomName']) : '',
            '%ecomPageLink%' => (isset($arr['ecomPageLink'])) ? esc_url($arr['ecomPageLink']) : '',
            '%hotelName%' => (isset($arr['hotelName'])) ? esc_html($arr['hotelName']) : '',
            '%hotelPageLink%' => (isset($arr['hotelUrl'])) ? esc_url($arr['hotelUrl']) : '',
            '%latitude%' => (isset($arr['latitude'])) ? $arr['latitude'] : '',
            '%longitude%' => (isset($arr['longitude'])) ? $arr['longitude'] : '',
            '%gallery%' => (isset($arr['galleryIDs'])) ? $arr['galleryIDs'] : '',
            '%tags%' => (isset($arr['texts']['_tags_'][$lang])) ? implode(', ', $arr['texts']['_tags_'][$lang]) : '',
        );

        $excludeExtra = array('666', '777', '888', '_tags_', 'burl', 'cate', 'catscan');
        foreach ($arr['texts'] as $key => $val) {
            if (!in_array($key, $excludeExtra)) {
                $replaceArr['%detail_' . $key . '_title%'] = (strlen(trim($arr['texts'][$key][$lang]['title'])) > 0) ? wp_kses_post($arr['texts'][$key][$lang]['title']) : '';
                $replaceArr['%detail_' . $key . '%'] = (strlen(trim($arr['texts'][$key][$lang]['value'])) > 0) ? wp_kses_post($arr['texts'][$key][$lang]['value']) : '';
            }
        }

        foreach ($arr['texts']['_tags_'][$lang] as $key => $val) {
            $replaceArr['%filter_' . $key . '%'] = $val;
        }

        $replaceArrBase64 = array();
        $replaceArrHTMLCodes = array();
        foreach ($replaceArr as $key => $val) {
            $replaceArrBase64[base64_encode($key)] = base64_encode($val);
            $replaceArrBase64[base64_encode(str_replace('%', '%25', $key))] = base64_encode($val);
            $replaceArrHTMLCodes[str_replace('%', '%25', $key)] = $val;
        }

        $replaceArr = array_merge($replaceArr, $replaceArrBase64, $replaceArrHTMLCodes);

        $str = str_replace('&#37;', '%', $str);
        $str = str_replace(array_keys($replaceArr), $replaceArr, $str);
        $str = str_replace('%all%', $this->allArrList($replaceArr), $str);
        $str = str_replace(base64_encode('%all%'), base64_encode($this->allArrList($replaceArr)), $str);
        $str = str_replace('%25all%25', $this->allArrList($replaceArr), $str);
        return $str;
    }

    private function allArrList($arr) {
        $r = '';
        foreach ($arr as $key => $val) {
            $r .= $key . ': <b>' . $val . '</b>
';
        }
        return $r;
    }

    private function genAddress($arr, $t = 0) {
        $str = array();
        $elements = array(
            'address',
            'postalcode',
            'city',
            'province',
            'region',
            'nation',
        );
        if ($t == 1) {
            $elements = array(
                'meet_address',
                'meet_postalcode',
                'meet_city',
                'meet_province',
                'meet_region',
                'meet_nation',
            );
        }


        foreach ($elements as $key => $val) {
            if (isset($arr[$val])) {
                $str[] = $arr[$val];
            }
        }

        return implode(', ', $str);
    }

    private function genPageData($arr) {
        $html = '[MadeepStructureData id="' . $arr['id'] . '" ';
        if (isset($arr['price'])) {
            $html .= ' price="' . $arr['price'] . '" ';
        }
        if (isset($arr['stars'])) {
            $html .= ' stars="' . $arr['stars'] . '" ';
        }
        $html .= ']';
        return $html;
    }

    private function genInfoTabs($arr, $lang) {

        $html = '';
            //$this->logAddRow(__FUNCTION__, print_r($arr['texts'],true), __LINE__);
        foreach ($arr['texts'] as $key => $val) {
            if($key == 'catscan' || $key == 'cate'){
                continue;
            }
            if ($key != 666 && $key != 777 && $key != 888 && $val[$lang]['title'] != 'burl') {
                if (strlen(trim($val[$lang]['title'])) > 0 && (strlen(trim($val[$lang]['value'])) > 0 || count($val[$lang]['subDetail']) > 0)) {
                    $html .= '<div class="infoBlock block_' . $key . '">';
                    $html .= '<h5>' . $val[$lang]['title'] . '</h5>';
                    $html .= '<p>' . $val[$lang]['value'] . '</p>';
                    if (isset($val[$lang]['subDetail'])) {
                        if (is_array($val[$lang]['subDetail'])) {
                            if (count($val[$lang]['subDetail']) > 0) {
                                foreach ($val[$lang]['subDetail'] as $key3 => $val3) {
                                    if (isset($val3->name) && isset($val3->subvalue)) {
                                        if (strlen(trim($val3->subvalue)) > 0 && strlen(trim($val3->name)) > 0) {
                                            $html .= '<p><b>' . $val3->name . '</b></p>';
                                            $html .= '<p>' . $val3->subvalue . '</p>';
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if (isset($val[$lang]['crtdet'])) {
                        if (is_array($val[$lang]['crtdet'])) {
                            if (count($val[$lang]['crtdet']) > 0) {
                                $html .= '<ul>';
                                foreach ($val[$lang]['crtdet'] as $key3 => $val3) {
                                    if (isset($val3->name)) {
                                        $html .= '<li>' . $val3->name . '</li>';
                                    }
                                }
                                $html .= '</ul>';
                            }
                        }
                    }
                    $html .= '</div>';
                }
            }
        }

        return $html;
    }

    public function getLang($l, $t = 0) {
        foreach ($this->languageList as $key => $val) {
            if (in_array($l, $val)) {
                return $val[$t];
            }
        }
    }

    public function getStructureType($t, $lang = null) {
        $lang = ($lang == null) ? $this->defaultLang : $lang;
        $r = $t;
        if (isset($this->lang[$this->getLang($lang, 1)]->{$t})) {
            $r = $this->lang[$this->getLang($lang, 1)]->{$t};
        } else {
            $r = $this->lang['eng']->{$t};
        }
        return $r;
    }

    private function uploadImage($image_url, $postID = 0, $data = array(), $lang = null) {
        $lang = ($lang == null) ? $this->defaultLang : $lang;
        if (strlen(trim($image_url)) > 0) {
            $this->logAddRow(__FUNCTION__, 'URL found, continue...', __LINE__);
            $image_url = str_replace(array('<![CDATA[', ']]>'), '', $image_url);

            $file_array = ['name' => wp_basename($image_url), 'tmp_name' => download_url($image_url)];

            $title = (isset($data['pageTitle'])) ? $data['pageTitle'] : '';
            $content = (isset($data['pageExcerpt'])) ? $data['pageExcerpt'] : '';

            $id = media_handle_sideload($file_array, $postID, $title . ((strlen(trim($title)) > 0 && strlen(trim($content)) > 0) ? ' - ' : '') . $content);
            if (!is_wp_error($id)) {
                $this->logAddRow(__FUNCTION__, 'Uploaded image ' . $id, __LINE__);
            } else {
                $this->logAddRow(__FUNCTION__, 'Error uploading image:', __LINE__);
                $this->logAddRow(__FUNCTION__, json_encode($id), __LINE__);
                $id = '';
            }
            return $id;
        }
    }

    private function getAttachUrl($attachID) {
        return wp_get_attachment_url($attachID);
    }

    public function getTemplate($t, $lang = '') {

        if ($lang == '') {
            $lang = $this->defaultLang;
        }

        switch ($t) {
            case 'hotels':
                $name = get_option('madeep_template_hotels');
                break;
            case 'ecoms':
                $name = get_option('madeep_template_ecoms');
                break;
            case 'services':
                $name = get_option('madeep_template_services');
                break;
            case 'offers':
                $name = get_option('madeep_template_offers');
                break;
        }

        $name = $this->manageTranslationTemplateContent($name, $lang);
        if ((int) $name > 0) {
            $template = get_post($name);
            $return = array('style' => '', 'html' => $template->post_content);
        } else {
            $return = null;
        }

        return $return;
    }

    public function getTemplatePostType($t, $lang = '') {

        if ($lang == '') {
            $lang = $this->defaultLang;
        }

        switch ($t) {
            case 'hotels':
                $name = get_option('madeep_template_hotels');
                break;
            case 'ecoms':
                $name = get_option('madeep_template_ecoms');
                break;
            case 'services':
                $name = get_option('madeep_template_services');
                break;
            case 'offers':
                $name = get_option('madeep_template_offers');
                break;
        }

        $name = $this->manageTranslationTemplateContent($name, $lang);
        if ((int) $name > 0) {
            $template = get_post($name);
            $return = $template->post_type;
        } else {
            $return = 'post';
        }
        $this->logAddRow(__FUNCTION__, 'Template {' . $name . '} is ' . $return, __LINE__);

        return $return;
    }

    private function manageTranslationTemplateContent($t, $lang = '') {
        if ($lang == '') {
            $lang = $this->defaultLang;
        }

        if ($this->multiLangPlugin === 'wpml' && $this->multiLangPlugin != null) {
            if ($lang == $this->defaultLang) {
                $this->logAddRow(__FUNCTION__, 'Base template and in-lang template are equal...', __LINE__);
                return $t;
            }
            //$byLangPosts = apply_filters('wpml_post_duplicates', $t);
            $byLangPosts = $this->getTranslatedTemplates($t);
            $this->logAddRow(__FUNCTION__, 'Found ' . count($byLangPosts) . ' templates in lang for {' . $t . '}...', __LINE__);
            $this->logAddRow(__FUNCTION__, json_encode($byLangPosts), __LINE__);
            if (isset($byLangPosts[$this->getLang($lang, 0)])) {
                $this->logAddRow(__FUNCTION__, 'Template in {' . $this->getLang($lang, 0) . '} selected {' . $byLangPosts[$this->getLang($lang, 0)] . '}', __LINE__);
                return $byLangPosts[$this->getLang($lang, 0)];
            } else {
                $this->logAddRow(__FUNCTION__, 'Template not found in {' . $this->getLang($lang, 0) . '} for {' . $t . '}...', __LINE__);
                return 0;
            }
        } else {
            $this->logAddRow(__FUNCTION__, 'No translation for template content or multilanguage is not active, continue...', __LINE__);
            return $t;
        }
    }

    private function getUnifiedIndex($index) {
        foreach ($this->detailsDefinition as $key => $val) {
            if (in_array($index, $val)) {
                return $key;
            }
        }
        return $index;
    }

    private function fixUTF8($str) {
        $cur_encoding = mb_detect_encoding($str);
        if ($cur_encoding == "UTF-8" && mb_check_encoding($str, "UTF-8")) {
            return $str;
        } else {
            return utf8_encode($str);
        }
    }

    private function loadLangs() {
        $dir = opendir(Madeep_Dir . 'sources/lang');
        while (false !== ($file = readdir($dir))) {
            if (strpos($file, '.json') !== false) {
                $file = str_replace('.json', '', $file);
                if (strlen(trim($file)) == 3) {
                    if (file_exists(Madeep_Dir . 'sources/lang/' . $file . '.json')) {
                        $this->lang[$file] = json_decode(file_get_contents(Madeep_Dir . 'sources/lang/' . $file . '.json'));
                    }
                }
            }
        }
    }

    public function cron() {
        $this->logAddRow(__FUNCTION__, 'CRON started...', __LINE__);

        if (get_option('madeep_data_type') == 'canale') {
            $this->logAddRow(__FUNCTION__, 'CRON {hotels} started...', __LINE__);
            $this->saveHotels();
            $this->logAddRow(__FUNCTION__, 'CRON {ecoms} started...', __LINE__);
            $this->saveEcom();
        }
        if (get_option('madeep_data_type') == 'canale' || get_option('madeep_data_type') == 'ecom') {
            $this->logAddRow(__FUNCTION__, 'CRON {services} started...', __LINE__);
            $this->saveServices();
        }
        if (get_option('madeep_data_type') == 'canale' || get_option('madeep_data_type') == 'hotel') {
            $this->logAddRow(__FUNCTION__, 'CRON {offers} started...', __LINE__);
            $this->saveOffers();
        }
        $this->logAddRow(__FUNCTION__, 'CRON ended...', __LINE__);
    }

    private function postToDraft($t) {
        global $wpdb, $table_prefix;

        $this->logAddRow(__FUNCTION__, 'Hiding not updated/received {' . $t . '} pages...', __LINE__);

        $list = $this->pageIdNotToClean[$t];
        $exlude = array();
        $sID = null;
        foreach ($list as $sID1 => $val1) {
            if ($t == 'offers' || $t == 'services') {
                foreach ($val1 as $sID2 => $val2) {
                    $exlude[] = $sID1 . '-' . $val2['product_id'];
                    $sID = $val2['sID'];
                }
            } else {
                $exlude[] = $val1['page_id'];
            }
        }

        $this->logAddRow(__FUNCTION__, 'Found {' . count($exlude) . '} pages to exclude from search:', __LINE__);

        if (count($exlude) > 0) {
            if ($t == 'offers') {
                $table = $table_prefix . 'madeep_offers';
                $filter = ' AND id_hotel = ' . $sID;
                $query = 'SELECT id_page FROM ' . $table . ' WHERE CONCAT(id_hotel,"-",productid) NOT IN ("' . implode('","', $exlude) . '")' . $filter;
            } else if ($t == 'services') {
                $table = $table_prefix . 'madeep_services';
                $filter = ' ';
                $query = 'SELECT id_page FROM ' . $table . ' WHERE CONCAT(id_ecom,"-",id_service) NOT IN ("' . implode('","', $exlude) . '")' . $filter;
            } else if ($t == 'ecoms' || $t == 'hotels') {
                $table = $this->getTable($t);
                $filter = ' ';
                $query = 'SELECT id_page FROM ' . $table . ' WHERE id_page NOT IN ("' . implode('","', $exlude) . '")' . $filter;
            }
            $queryRs = $wpdb->get_results($query);
            $this->logAddRow(__FUNCTION__, 'Found {' . count($queryRs) . '} pages to exclude...', __LINE__);

            foreach ($queryRs as $key => $val) {
                wp_update_post(array(
                    'ID' => $val->id_page,
                    'post_status' => 'draft'
                ));
                $this->logAddRow(__FUNCTION__, 'Page {' . $val->id_page . '} set to draft', __LINE__);

                if ($this->multiLangPlugin === 'wpml' && $this->multiLangPlugin != null) {
                    $byLangPosts = $this->getTranslatedPages($val->id_page);

                    $this->logAddRow(__FUNCTION__, 'Found {' . count($byLangPosts) . '}  pages translated of {' . $val->id_page . '} to hide:', __LINE__);
                    $this->logAddRow(__FUNCTION__, json_encode($byLangPosts), __LINE__);
                    foreach ($byLangPosts as $lKey => $tID) {
                        wp_update_post(array(
                            'ID' => $tID,
                            'post_status' => 'draft'
                        ));
                    }
                }
            }
        }
        $this->pageIdNotToClean = array();
    }

    private function addPostMeta($postId, $arr, $lang = '', $detailed = false) {

        $exclude = array('gallery', 'texts');

        foreach ($arr as $key => $val) {
            if (!in_array($key, $exclude)) {
                if ($detailed) {
                    update_post_meta($postId, (($val['hidden']) ? '_' : '') . 'madeep_' . $val['name'], $val['value'], (bool) $val['unique']);
                } else {
                    update_post_meta($postId, 'madeep_' . $key, $val, false);
                }
            }
        }

        update_post_meta($postId, 'madeep_tags', serialize($arr['texts']['_tags_'][$lang]), false);
    }

    private function manageTranslationPage($originalId, $arr, $t = 'hotel') {
        if ($this->multiLangPlugin === 'wpml' && $this->multiLangPlugin != null) {
            $this->logAddRow(__FUNCTION__, 'WPML found', __LINE__);
            $this->checkWPMLPage($originalId, $arr, $t);
        } else {
            
        }
    }

    public function getTranslatedPages($id) {
        $trid = apply_filters('wpml_element_trid', NULL, $id, 'post_page');
        $trans = apply_filters('wpml_get_element_translations', NULL, $trid, 'post_page');

        $this->logAddRow(__FUNCTION__, 'Found {' . count($trans) . '} translations for {' . $id . '}...', __LINE__);
        $r = array();
        foreach ($trans as $key => $val) {
            $r[$key] = $val->element_id;
        }
        return $r;
    }

    public function getTranslatedTemplates($id) {
        $arr1 = $this->getTranslatedPages($id);
        if (count($arr1) == 0) {
            return $this->getClonedPages($id);
        } else {
            return $arr1;
        }
    }

    private function getClonedPages($id) {
        $r = apply_filters('wpml_post_duplicates', $id);
        $this->logAddRow(__FUNCTION__, 'Found {' . count($r) . '} duplicates for {' . $id . '}...', __LINE__);
        return $r;
    }

    private function clonePages($id) {
        $this->logAddRow(__FUNCTION__, 'Creating duplicates for {' . $id . '}...', __LINE__);
        do_action('wpml_make_post_duplicates', $id);
        $r = apply_filters('wpml_post_duplicates', $id);

        return $r;
    }

    public function getTranslatedCategories($id) {
        $trid = apply_filters('wpml_element_trid', NULL, $id, 'tax_category');
        $trans = apply_filters('wpml_get_element_translations', NULL, $trid, 'tax_category');

        $r = array();
        foreach ($trans as $key => $val) {

            $r[$key] = array($val->element_id, $val->name);
        }
        return $r;
    }

    private function checkWPMLPage($originalId, $arr, $t = 'hotel') {
        $basePageLang = $this->defaultLang;
        $activePageLang = $this->activeLanguage;

        $this->checkWPMLOriginalPage($originalId);

        $byLangPosts = $this->getTranslatedPages($originalId);

        $templates = $this->getTranslatedTemplates(get_option('madeep_template_' . $t));

        $translatedPosts = array();
        $toTranslateLanguages = array();

        foreach ($activePageLang as $key => $lang) {
            if (isset($templates[$this->getLang($lang, 0)])) {
                $this->logAddRow(__FUNCTION__, 'In-lang {' . $lang . '} template found, continue...', __LINE__);
                if ($this->getLang($lang, 0) != $this->getLang($basePageLang, 0)) {

                    $this->logAddRow(__FUNCTION__, 'In-lang title found, continue...', __LINE__);
                    $this->logAddRow(__FUNCTION__, 'Found copy for {' . $originalId . '}:', __LINE__);
                    $this->logAddRow(__FUNCTION__, json_encode($byLangPosts), __LINE__);
                    if (isset($byLangPosts[$this->getLang($lang, 0)])) {
                        $this->logAddRow(__FUNCTION__, 'Copy found (' . $byLangPosts[$this->getLang($lang, 0)] . '), continue...', __LINE__);
                        $translatedPosts[$this->getLang($lang, 1)] = $byLangPosts[$this->getLang($lang, 0)];
                    } else {
                        $this->logAddRow(__FUNCTION__, 'Copy not found for {' . $originalId . '} in {' . $this->getLang($lang, 0) . '}, creating...', __LINE__);
                        $translatedPosts[$this->getLang($lang, 1)] = $this->createTempWPMLPage($originalId, $lang, $t);
                    }
                }
            } else {
                $this->logAddRow(__FUNCTION__, 'No in-lang {' . $lang . '} template found, continue...', __LINE__);
            }
        }

        $this->logAddRow(__FUNCTION__, count($translatedPosts) . ' translations to update...', __LINE__);
        foreach ($translatedPosts as $lang => $postID) {
            $this->updateWPMLPages($postID, $originalId, $arr, $lang, $t);
        }
    }

    private function checkWPMLOriginalPage($pageID) {

        $this->logAddRow(__FUNCTION__, 'Setting WPML data to original post', __LINE__);

        $get_language_args = array('element_id' => $pageID, 'element_type' => 'post');
        $original_post_language_info = apply_filters('wpml_element_language_details', null, $get_language_args);

        if ($original_post_language_info->language_code != $this->getLang($this->defaultLang, 0) || !is_null($original_post_language_info->source_language_code)) {
            $this->logAddRow(__FUNCTION__, 'Original post have wrong language or source language...', __LINE__);
            do_action('wpml_set_element_language_details', array(
                'element_id' => $pageID,
                'language_code' => $this->getLang($this->defaultLang, 0),
                'source_language_code' => null
            ));
        }
    }

    private function setWPMLTranslatedPage($pageID, $sourceID, $lang) {

        $this->manageTranslationHooks();
        $this->logAddRow(__FUNCTION__, 'Setting WPML data to translated post', __LINE__);

        $get_language_args = array('element_id' => $sourceID, 'element_type' => 'post');
        $original_post_language_info = apply_filters('wpml_element_language_details', null, $get_language_args);

        do_action('wpml_set_element_language_details', array(
            'element_id' => $pageID,
            'language_code' => $this->getLang($lang, 0),
            'trid' => $original_post_language_info->trid,
            'source_language_code' => $this->getLang($this->defaultLang, 0)
        ));
    }

    private function createTempWPMLPage($originalId, $lang, $t) {

        $this->manageTranslationHooks();
        $postOriginalData = get_post($originalId);

        $postData = array(
            'ID' => 0,
            'post_title' => $postOriginalData->post_title . ' - ' . $lang,
            'post_name' => $postOriginalData->post_name . '-' . $lang,
            'post_excerpt' => '',
            'post_status' => 'draft',
            'post_type' => 'post',
            'post_content' => ''
        );
        $newID = wp_insert_post($postData, true);

        $wpml_element_type = apply_filters('wpml_element_type', 'post');
        $get_language_args = array('element_id' => $originalId, 'element_type' => 'post');
        $original_post_language_info = apply_filters('wpml_element_language_details', null, $get_language_args);

        $set_language_args = array(
            'element_id' => $newID,
            'element_type' => $wpml_element_type,
            'trid' => $original_post_language_info->trid,
            'language_code' => $this->getLang($lang, 0),
            'source_language_code' => $original_post_language_info->language_code
        );

        do_action('wpml_set_element_language_details', $set_language_args);

        return $newID;
    }

    private function updateWPMLPages($postID, $originalPostID, $arr, $lang, $t = 'hotels') {

        $this->manageTranslationHooks();
        $this->logAddRow(__FUNCTION__, 'Preparing ' . $postID . '(' . $lang . ')', __LINE__);
        $arr['pageName'] = remove_accents($this->strToLang($arr['texts'], '666', $lang));
        if (strlen(trim($arr['pageName'])) == 0) {
            $arr['pageName'] = remove_accents($arr['name']);
        }

        $excerpt = $this->strToLang($arr['texts'], '777', $lang);
        if ($excerpt == null) {
            $excerpt = '';
        }

        $arr['pageTitle'] = (strlen(trim($this->strToLang($arr['texts'], '666', $lang))) > 0) ? $this->strToLang($arr['texts'], '666', $lang) : $arr['name'];
        $arr['pageExcerpt'] = $excerpt;

        $content = $this->genContent($arr, $t, $lang);
        $postType = $this->getTemplatePostType($t, $lang);

        $category = $this->getTranslatedCategories(get_option('madeep_category_' . $t));
        $category = $category[$this->getLang($lang)][0];

        $this->logAddRow(__FUNCTION__, 'Content length {' . strlen($content) . '} for page {' . $postID . '} in {' . $lang . '}', __LINE__);
        $this->logAddRow(__FUNCTION__, 'Category {' . get_option('madeep_category_' . $t) . '} in {' . $lang . '}: {' . $category . '} for page {' . $postID . '} in {' . $lang . '}', __LINE__);
        //$this->logAddRow(__FUNCTION__, 'Category list: ' . print_r($category,true), __LINE__);

        if (strlen(trim($arr['pageTitle'])) == 0) {
            $arr['pageTitle'] = $t . ' - ' . $arr['id_element'] . ' - ' . $lang;
        }
        if (strlen(trim($arr['pageName'])) == 0) {
            $arr['pageName'] = $t . ' ' . $arr['id_element'] . ' ' . $lang;
        }

        $postData = array(
            'ID' => $postID,
            'post_title' => $arr['pageTitle'],
            'post_name' => str_replace(' ', '-', $arr['pageName']),
            'post_excerpt' => $excerpt,
            'post_status' => 'publish',
            'post_type' => $postType,
            'post_content' => $content,
            'post_category' => $category
        );

        $return = wp_update_post($postData, true);

        if (!$this->checkPageMadatoryTexts($arr, $t, $lang)) {
            $this->logAddRow(__FUNCTION__, 'Page {' . $originalPostID . '} in {' . $lang . '} {' . $postID . '} haven\'t mandatory data, revert to draft...', __LINE__);
            $postData = array(
                'ID' => $postID,
                'post_status' => 'draft',
            );
            wp_update_post($postData);
        }

        wp_set_post_categories($postID, $category, false);
        $this->logAddRow(__FUNCTION__, 'Page {' . $originalPostID . '} in {' . $lang . '} {' . $postID . '} set category as {' . $category . '}', __LINE__);

        $this->setWPMLTranslatedPage($postID, $originalPostID, $lang);

        if (is_wp_error($return)) {
            $this->logAddRow(__FUNCTION__, 'Update FAIL ' . $postID . '(' . $lang . ') {' . $return->get_error_message() . '}', __LINE__);
        }

        $this->logAddRow(__FUNCTION__, 'Updated ' . $postID . '(' . $lang . ') copy of ' . $originalPostID, __LINE__);
        $postImage = get_the_post_thumbnail_url($originalPostID, 'full');

        $this->removeOldFeaturedImage($postID);
        if (has_post_thumbnail($postID)) {
            $attachment_id = get_post_thumbnail_id($postID);
            wp_delete_attachment($attachment_id, true);
        }

        if ($postImage != false) {
            $this->logAddRow(__FUNCTION__, 'Original thumb found, copying...', __LINE__);
            $attachId = $this->uploadImage($postImage, $postID, $arr, $lang);
            set_post_thumbnail($postID, $attachId);
            $arr['featuredImageID'] = $attachId;
        }
        if ($t != 'hotels') {
            wp_set_object_terms($postID, array(), 'post_tag', FALSE);
        }


        if (get_option('madeep_data_type') == 'canale') {
            if ((int) get_option('madeep_allow_structure_tag') == 1) {
                if (strlen(trim($arr['hotelName'])) > 0) {
                    wp_add_post_tags($postID, $arr['hotelName']);
                }

                if (strlen(trim($arr['ecomName'])) > 0) {
                    wp_add_post_tags($postID, $arr['ecomName']);
                }
            }
        }

        if ((int) get_option('madeep_allow_filters_tag') == 1) {
            wp_add_post_tags($postID, $hotelData['texts']['_tags_'][$lang]);
        }

        $this->addLanguageTag($postID, $t, $lang);

        if (is_array($arr['texts']['catscan'][$this->getLang($lang, 1)])) {
            $this->logAddRow(__FUNCTION__, 'Found {' . count($arr['texts']['catscan'][$this->getLang($lang, 1)]) . '} tags for ' . $postID . '(' . $lang . ') copy of ' . $originalPostID, __LINE__);

            if (count($arr['texts']['catscan'][$this->getLang($lang, 1)]) > 0) {
                $this->logAddRow(__FUNCTION__, json_encode($arr['texts']['catscan'][$this->getLang($lang, 1)]), __LINE__);
                wp_add_post_tags($postID, $arr['texts']['catscan'][$this->getLang($lang, 1)]);
            }
        }

        if (is_array($arr['texts']['cate'][$this->getLang($lang, 1)])) {
            $this->logAddRow(__FUNCTION__, 'Found {' . count($arr['texts']['cate'][$this->getLang($lang, 1)]) . '} tags for ' . $postID . '(' . $lang . ') copy of ' . $originalPostID, __LINE__);

            if (count($arr['texts']['cate'][$this->getLang($lang, 1)]) > 0) {
                $this->logAddRow(__FUNCTION__, json_encode($arr['texts']['cate'][$this->getLang($lang, 1)]), __LINE__);
                wp_add_post_tags($postID, $arr['texts']['cate'][$this->getLang($lang, 1)]);
            }
        }

        $arr['type'] = $t;
        $this->addPostMeta($postID, $arr, $lang);
    }

    private function addLanguageTag($id, $t, $lang = null) {
        if (get_option('madeep_active_multilanguages') == 1) {
            if (get_option('madeep_allow_lang_tag') == 1) {
                $lang = ($lang == null) ? $this->defaultLang : $lang;
                wp_add_post_tags($id, ucfirst($this->getLang($lang, 2)));
            }
        }
    }

    public function test() {
        
    }

    private function checkPageMadatoryTexts($arr, $type, $lang = null) {

        $lang = ($lang == null) ? $this->defaultLang : $lang;
        $r = true;
        if ($type == 'ecoms' || $type == 'hotels') {
            if (strlen(trim($arr['name'])) == 0 || strlen(trim($arr['texts'][777][$lang]['value'])) == 0) {
                $r = false;
            }
        } else {
            if (strlen(trim($arr['texts'][666][$lang]['value'])) == 0 || strlen(trim($arr['texts'][777][$lang]['value'])) == 0) {
                $r = false;
            }
        }
        return $r;
    }

    private function getTable($t) {
        global $table_prefix;
        switch ($t) {
            case 'hotels':
                return $table_prefix . 'madeep_hotels';
                break;
            case 'ecoms':
                return $table_prefix . 'madeep_ecoms';
                break;
            case 'services':
                return $table_prefix . 'madeep_services';
                break;
            case 'offers':
                return $table_prefix . 'madeep_offers';
                break;
        }
        return null;
    }

    private function genPage($data, $type) {
        global $wpdb;
        set_time_limit(0);
        $this->checkShutDown();
        if ($this->writePages) {
            if ($this->writePagesList['madeep_write_' . $type . '_page']) {

                $table = $this->getTable($type);

                $this->logAddRow(__FUNCTION__, 'Creating page for {' . $type . '} {' . $data->id . '}', __LINE__);

                $hotelData = array();
                if ($type == 'services') {
                    $hotelData['texts'] = $this->arr2langarr($data->ServDetails->Details);
                    $queryEcom = $wpdb->get_results('SELECT * FROM ' . $this->getTable('ecoms') . ' WHERE id_ecom = ' . $data->Ecomid . ' LIMIT 1');
                    $queryEcom[0]->dataS = unserialize(base64_decode($queryEcom[0]->dataS));
                    $hotelData['ecomName'] = $queryEcom[0]->dataS->Name;
                    $hotelData['ecomId'] = $queryEcom[0]->dataS->id;
                    $hotelData['id_container'] = $data->Ecomid;
                    $hotelData['ecomPageLink'] = esc_url(get_page_link($queryEcom[0]->id_page));
                    $hotelData['id'] = $data->id;
                    $hotelData['id_element'] = $data->id;
                    $hotelData['id_page'] = $data->id_page;
                    $hotelData['name'] = str_replace(array('<![CDATA[', ']]>'), '', $data->Code);
                    $hotelData['image'] = str_replace(array('<![CDATA[', ']]>'), '', $data->ImgUrl);
                    $hotelData['ecomID'] = $data->Ecomid;
                    $hotelData['gallery'] = $data->Gallery;
                    $hotelData['price'] = $data->PriceFrom;
                    $hotelData['pageName'] = str_replace(array('<![CDATA[', ']]>'), '', remove_accents($hotelData['texts'][666][$this->getLang($this->defaultLang, 1)]['value']));
                    $hotelData['pageTitle'] = str_replace(array('<![CDATA[', ']]>'), '', $this->strToLang($hotelData['texts'], '666'));
                } else if ($type == 'offers') {
                    $hotelData['texts'] = $this->arr2langarrOffers($data);
                    $hotelData['id_element'] = $data->Productid;
                    $hotelData['id_container'] = $data->Hotelid;
                    $hotelData['id'] = $data->Hotelid;
                    $hotelData['id_page'] = $data->id_page;
                    $hotelData['priceFrom'] = $data->PriceFrom;
                    $hotelData['image'] = $data->ImgUrl;
                    $hotelData['hotelName'] = str_replace(array('<![CDATA[', ']]>'), '', $data->hotelName);
                    $hotelData['hotelUrl'] = $data->hotelUrl;
                    $hotelData['pageName'] = str_replace(array('<![CDATA[', ']]>'), '', remove_accents($hotelData['texts'][666][$this->getLang($this->defaultLang, 1)]['value']));
                    $hotelData['pageTitle'] = str_replace(array('<![CDATA[', ']]>'), '', $this->strToLang($hotelData['texts'], '666'));
                } else if ($type == 'ecoms') {
                    $hotelData['texts'] = $this->arr2langarr($data->EcomDetails->Details);
                    $hotelData['id_element'] = $data->id;
                    $hotelData['id'] = $data->id;
                    $hotelData['id_page'] = $data->id_page;
                    $hotelData['name'] = str_replace(array('<![CDATA[', ']]>'), '', $data->Name);
                    $hotelData['image'] = $data->ImgUrl;
                    $hotelData['gallery'] = $data->Gallery;
                    $hotelData['longitude'] = $data->Lng;
                    $hotelData['latitude'] = $data->Lat;
                    $hotelData['address'] = $data->Address;
                    $hotelData['city'] = $data->City;
                    $hotelData['province'] = $data->Province;
                    $hotelData['region'] = $data->Region;
                    $hotelData['nation'] = $data->Nation;
                    $hotelData['pageName'] = remove_accents($hotelData['name']);
                    $hotelData['pageTitle'] = $hotelData['name'];
                    $hotelData['email'] = $data->Email;
                    $hotelData['phone'] = $data->Phone;
                } else if ($type == 'hotels') {
                    $hotelData['texts'] = $this->arr2langarr($data->HotelDetails->Details);
                    $hotelData['id_element'] = $data->id;
                    $hotelData['id'] = $data->id;
                    $hotelData['id_page'] = $data->id_page;
                    $hotelData['name'] = str_replace(array('<![CDATA[', ']]>'), '', $data->Name);
                    $hotelData['image'] = $data->Image;
                    $hotelData['gallery'] = $data->Gallery;
                    $hotelData['longitude'] = $data->Longitude;
                    $hotelData['latitude'] = $data->Latitude;
                    $hotelData['email'] = $data->Email;
                    $hotelData['phone'] = $data->Phone;
                    $hotelData['fax'] = $data->Fax;
                    $hotelData['price'] = $data->PriceFrom;
                    $hotelData['structureType'] = 'hotel';
                    if ((int) $data->Stars > 0 && (int) $data->Stars < 7) {
                        $hotelData['stars'] = $data->Stars;
                    } else {
                        $hotelData['stars'] = '';
                        $hotelData['structureType'] = $this->structureType[$data->Stars];
                    }
                    $hotelData['address'] = $data->Address;
                    $hotelData['postalcode'] = $data->PostalCode;
                    $hotelData['city'] = $data->City;
                    $hotelData['province'] = $data->Province;
                    $hotelData['region'] = $data->Region;
                    $hotelData['nation'] = $data->Nation;
                    $hotelData['pageName'] = remove_accents($hotelData['name']);
                    $hotelData['pageTitle'] = $hotelData['name'];
                }

                $hotelData['type'] = $type;

                if (isset($data->Places->Place)) {
                    $this->logAddRow(__FUNCTION__, 'Place found, continue... ', __LINE__);
                    if (isset($data->Places->Place->lng)) {
                        $this->logAddRow(__FUNCTION__, 'Place is not an array, fixing... ', __LINE__);
                        $data->Places->Place = array($data->Places->Place);
                    }


                    foreach ($data->Places->Place as $pKey => $pVal) {
                        if ($pVal->t_addr == 'addr') {
                            $hotelData['longitude'] = $pVal->lng;
                            $hotelData['latitude'] = $pVal->lat;
                            $hotelData['address'] = $pVal->address;
                            $hotelData['postalcode'] = $pVal->zipcode;
                            $hotelData['city'] = $pVal->city;
                            $hotelData['province'] = $pVal->province;
                            $hotelData['region'] = $pVal->region;
                            $hotelData['nation'] = $pVal->nation;
                        }

                        if ($type == 'services') {
                            if ($pVal->t_addr == 'meet') {
                                $hotelData['meet_longitude'] = $pVal->lng;
                                $hotelData['meet_latitude'] = $pVal->lat;
                                $hotelData['meet_address'] = $pVal->address;
                                $hotelData['meet_postalcode'] = $pVal->zipcode;
                                $hotelData['meet_city'] = $pVal->city;
                                $hotelData['meet_province'] = $pVal->province;
                                $hotelData['meet_region'] = $pVal->region;
                                $hotelData['meet_nation'] = $pVal->nation;
                            }
                        }
                    }
                }

                $data->id_page = $this->checkPagePostExists((int) $data->id_page);

                if (strlen(trim($hotelData['pageTitle'])) == 0) {
                    $hotelData['pageTitle'] = $type . ' - ' . $hotelData['id_element'] . ' - ' . $this->defaultLang;
                }
                
                if (strlen(trim($hotelData['pageName'])) == 0) {
                    $hotelData['pageName'] = $type . ' ' . $hotelData['id_element'] . ' ' . $this->defaultLang;
                }

                $postType = $this->getTemplatePostType($type);

                if ($data->id_page == 0) {
                    $this->logAddRow(__FUNCTION__, 'Page not found ' . $data->id_page . ', creating new one...', __LINE__);
                    $postData = array(
                        'ID' => $data->id_page,
                        'post_title' => $hotelData['pageTitle'],
                        'post_name' => str_replace(' ', '-', $hotelData['pageName']),
                        'post_excerpt' => '',
                        'post_status' => 'draft',
                        'post_type' => $postType,
                        //'post_category' => array(esc_attr(get_option('madeep_category_' . $type))),
                        'post_content' => '',
                        'comment_status' => 'closed',
                    );
                    $id = wp_insert_post($postData);
                    //wp_set_post_categories($data->id_page, esc_attr(get_option('madeep_category_' . $type)), false);
                    $category = $this->getTranslatedCategories(get_option('madeep_category_' . $t));
                    $category = $category[$this->getLang($this->getLang($this->defaultLang, 1))][1];
                    wp_set_object_terms($data->id_page, $category, 'category', false); //EDIT 10/05/2023
                    $data->id_page = $id;

                    if (!is_wp_error($id)) {
                        $data->id_page = $id;
                    } else {
                        $this->logAddRow(__FUNCTION__, 'Error creating page:', __LINE__);
                        $this->logAddRow(__FUNCTION__, $id->get_error_message(), __LINE__);
                        return;
                    }
                } else {
                    $id = $data->id_page;
                    $this->logAddRow(__FUNCTION__, 'Page found (' . $id . '), resetting media', __LINE__);

                    $this->removeOldMedia($data->id_page);
                    $this->removeOldFeaturedImage($data->id_page);
                }

                $excerpt = $this->strToLang($hotelData['texts'], '777');
                if ($excerpt == null) {
                    $excerpt = '';
                }

                $hotelData['pageExcerpt'] = $hotelData['name'];

                $hotelData['id_page'] = $data->id_page;

                if (isset($hotelData['gallery']->GImage)) {
                    $this->logAddRow(__FUNCTION__, 'Gallery found, uploading...', __LINE__);
                    if (is_array($hotelData['gallery']->GImage) || strlen($hotelData['gallery']->GImage) > 0) {
                        $gallery = $this->genGallery($hotelData['gallery']->GImage, $hotelData);
                    }
                }

                $hotelData['galleryIDs'] = $gallery;

                $content = $this->genContent($hotelData, $type);
                $postData = array(
                    'ID' => $data->id_page,
                    'post_title' => $hotelData['pageTitle'],
                    'post_name' => str_replace(' ', '-', $hotelData['pageName']),
                    'post_excerpt' => $excerpt,
                    'post_status' => 'publish',
                    'post_type' => $postType,
                    //'post_category' => array(esc_attr(get_option('madeep_category_' . $type))),
                    'post_content' => $content,
                    'comment_status' => 'closed',
                );

                $this->logAddRow(__FUNCTION__, 'Updating page informations', __LINE__);
                wp_update_post($postData);
                wp_set_post_categories($data->id_page, esc_attr(get_option('madeep_category_' . $type)), false);

                if (!$this->checkPageMadatoryTexts($hotelData, $type)) {
                    $this->logAddRow(__FUNCTION__, 'Page {' . $data->id_page . '} haven\'t mandatory data, revert to draft...', __LINE__);
                    $postData = array(
                        'ID' => $id,
                        'post_status' => 'draft',
                    );
                    wp_update_post($postData);
                }
                $this->removeOldFeaturedImage($data->id_page);

                if (has_post_thumbnail($data->id_page)) {
                    $attachment_id = get_post_thumbnail_id($data->id_page);
                    wp_delete_attachment($attachment_id, true);
                }

                $hotelData['featuredImageID'] = null;
                if (isset($data->ImgUrl)) {
                    $this->logAddRow(__FUNCTION__, 'Image found, adding thumb', __LINE__);
                    $attachId = $this->uploadImage($data->ImgUrl, $data->id_page, $hotelData);
                    set_post_thumbnail($data->id_page, $attachId);
                    $hotelData['featuredImageID'] = $attachId;
                } else if (isset($data->Image)) {
                    $this->logAddRow(__FUNCTION__, 'Image found, adding thumb', __LINE__);
                    $attachId = $this->uploadImage($data->Image, $data->id_page, $hotelData);
                    set_post_thumbnail($data->id_page, $attachId);
                    $hotelData['featuredImageID'] = $attachId;
                }

                if ($type != 'hotels') {
                    wp_set_object_terms($data->id_page, array(), 'post_tag', FALSE);
                }

                if (get_option('madeep_data_type') == 'canale') {
                    if ((int) get_option('madeep_allow_structure_tag') == 1) {
                        if (strlen(trim($hotelData['ecomName'])) > 0) {
                            wp_add_post_tags($data->id_page, $hotelData['ecomName']);
                        }
                        if (strlen(trim($hotelData['hotelName'])) > 0) {
                            wp_add_post_tags($data->id_page, $hotelData['hotelName']);
                        }
                    }
                }

                if ((int) get_option('madeep_allow_filters_tag') == 1) {
                    wp_add_post_tags($data->id_page, $hotelData['texts']['_tags_'][$this->defaultLang]);
                }

                $this->addLanguageTag($data->id_page, $type);

                if (is_array($hotelData['texts']['catscan'][$this->defaultLang])) {
                    $this->logAddRow(__FUNCTION__, 'Found {' . count($hotelData['texts']['catscan'][$this->defaultLang]) . '} tags for ' . $id . '(' . $this->defaultLang . ')', __LINE__);

                    if (count($hotelData['texts']['catscan'][$this->defaultLang]) > 0) {
                        $this->logAddRow(__FUNCTION__, json_encode($hotelData['texts']['catscan'][$this->defaultLang]), __LINE__);
                        wp_add_post_tags($data->id_page, $hotelData['texts']['catscan'][$this->defaultLang]);
                    }
                }

                if (is_array($hotelData['texts']['cate'][$this->defaultLang])) {
                    $this->logAddRow(__FUNCTION__, 'Found {' . count($hotelData['texts']['cate'][$this->defaultLang]) . '} tags for ' . $id . '(' . $this->defaultLang . ')', __LINE__);

                    if (count($hotelData['texts']['cate'][$this->defaultLang]) > 0) {
                        $this->logAddRow(__FUNCTION__, json_encode($hotelData['texts']['cate'][$this->defaultLang]), __LINE__);
                        wp_add_post_tags($data->id_page, $hotelData['texts']['cate'][$this->defaultLang]);
                    }
                }

                $this->addPostMeta($data->id_page, $hotelData, $this->defaultLang);

                if ((int) $id > 0) {
                    if ($type == 'services') {
                        $this->pageIdNotToClean['services'][$data->Ecomid][] = array('page_id' => $data->id_page, 'product_id' => $data->id, 'sID' => (int) $data->Ecomid);
                        $query = $wpdb->query('UPDATE ' . $table . ' SET id_page = ' . (int) $data->id_page . ', imgUrl = "' . $this->getAttachUrl($attachId) . '" WHERE id_service = ' . (int) $data->id . ' AND id_ecom= ' . $data->Ecomid . '  AND id_channel = ' . $this->user);
                    } else if ($type == 'offers') {
                        $this->pageIdNotToClean['offers'][$data->Hotelid][] = array('page_id' => $data->id_page, 'product_id' => $data->Productid, 'sID' => (int) $data->Hotelid);
                        $query = $wpdb->query('UPDATE ' . $table . ' SET id_page = ' . (int) $data->id_page . ', imgUrl = "' . $this->getAttachUrl($attachId) . '" WHERE id_hotel = ' . (int) $data->Hotelid . ' AND productid = ' . (int) $data->Productid . ' AND id_channel = ' . $this->user);
                    } else if ($type == 'ecoms') {
                        $this->pageIdNotToClean['ecoms'][] = array('page_id' => $data->id_page);
                        $query = $wpdb->query('UPDATE ' . $table . ' SET id_page = ' . (int) $data->id_page . ', imgUrl = "' . $this->getAttachUrl($attachId) . '" WHERE id_ecom = ' . (int) $data->id . ' AND id_channel = ' . $this->user);
                    } else if ($type == 'hotels') {
                        $this->pageIdNotToClean['hotels'][] = array('page_id' => $data->id_page);
                        $query = $wpdb->query('UPDATE ' . $table . ' SET id_page = ' . (int) $data->id_page . ', imgUrl = "' . $this->getAttachUrl($attachId) . '" WHERE id_hotel = ' . (int) $data->id . ' AND id_channel = ' . $this->user);
                    }
                }

                $this->manageTranslationPage($data->id_page, $hotelData, $type);

                update_option('madeep_time_last_update_' . $type . '_page', time());

                return $id;
            }
        }
    }

}
