<?php
require_once( Madeep_Dir . 'sources/Madeep-Admin.php' );
$mad = new MadeepAdmin();
//$mad->getCategories();

$url = "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

if (isset($_GET['action'])) {
    if (get_option('madeep_data_type') == 'canale') {
        if ($_GET['action'] == 'hotels') {
            $mad->saveHotels(true);
        }
        /* if ($_GET['action'] == 'hotels-list') {
          $mad->saveHotelsList();
          } */
        if ($_GET['action'] == 'ecom') {
            $mad->saveEcom(true);
        }
        /* if ($_GET['action'] == 'ecom-list') {
          $mad->saveEcomList();
          } */
    }
    if (get_option('madeep_data_type') == 'canale' || get_option('madeep_data_type') == 'ecom') {
        if ($_GET['action'] == 'services') {
            $mad->saveServices(true);
        }
        /* if ($_GET['action'] == 'services-list') {
          $mad->saveServicesList();
          } */
    }
    if (get_option('madeep_data_type') == 'canale' || get_option('madeep_data_type') == 'hotel') {
        if ($_GET['action'] == 'offers') {
            $mad->saveOffers(true);
        }
        /* if ($_GET['action'] == 'offers-list') {
          $mad->saveOffersList();
          } */
    }


    if ($_GET['action'] == 'setCron') {
        Madeep::cronSet(absint($_GET['t']));
    }
    if ($_GET['action'] == 'unsetCron') {
        Madeep::cronUnset();
    }
    if ($_GET['action'] == 'resetLog') {
        $mad->clearLog();
    }

    if ($_GET['action'] == 'test') {
        $mad->test();
    }
    //echo '<script> document.location.href = "' . preg_replace('/&action\=[a-z\-]{1,}/', '', $url) . '";</script>';
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <p><?php Madeep::cronCheck(); ?></p>
    <table class="form-table" role="presentation">
        <tbody>
            <?php
            if ((bool) get_option('madeep_write_hotel_page')) {
                ?>
                <tr>
                    <th scope="row">Last sync: <?php echo (get_option('madeep_time_last_update_hotels') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_hotels')) : "Not synced yet"; ?></th>
                    <td>Last page write: <?php echo (get_option('madeep_time_last_update_hotels_page') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_hotels_page')) : "Not synced yet"; ?></td>
                </tr>
                <?php
            }
            if (get_option('madeep_data_type') == 'canale') {
                ?>

                <tr>
                    <th scope="row"><label for="blogname"><?php echo __('Sincronizza hotels', 'madeep'); ?></label></th>
                    <td><a onclick="startSync('hotels', this)" class="sync sync-hotels"><span class="dashicons dashicons-image-rotate"></span></a> Last sync: <?php echo (get_option('madeep_time_last_update_hotels') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_hotels')) : "Not synced yet"; ?></td>
                </tr>
                <!--tr>
                    <th scope="row"><label for="blogname"><?php echo __('Sincronizza lista hotels', 'madeep'); ?></label></th>
                    <td><a href="<?php echo $url; ?>&action=hotels-list"><span class="dashicons dashicons-image-rotate"></span></a> Last sync: <?php echo (get_option('madeep_time_last_update_hotels_list') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_hotels_list')) : "Not synced yet"; ?></td>
                </tr-->
                <tr>
                    <th scope="row"><label for="blogname"><?php echo __('Sincronizza ecom', 'madeep'); ?></label></th>
                    <td><a onclick="startSync('ecom', this)" class="sync sync-ecoms"><span class="dashicons dashicons-image-rotate"></span></a> Last sync: <?php echo (get_option('madeep_time_last_update_ecom') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_ecom')) : "Not synced yet"; ?></td>
                </tr>
                <!--tr>
                    <th scope="row"><label for="blogname"><?php echo __('Sincronizza lista ecom', 'madeep'); ?></label></th>
                    <td><a href="<?php echo $url; ?>&action=ecom-list"><span class="dashicons dashicons-image-rotate"></span></a> Last sync: <?php echo (get_option('madeep_time_last_update_ecom_list') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_ecom_list')) : "Not synced yet"; ?></td>
                </tr-->
                <?php
            }
            if (get_option('madeep_data_type') == 'canale' || get_option('madeep_data_type') == 'ecom') {
                ?>
                <tr>
                    <th scope="row"><label for="blogname"><?php echo __('Sincronizza servizi', 'madeep'); ?></label></th>
                    <td><a onclick="startSync('services', this)" class="sync sync-services"><span class="dashicons dashicons-image-rotate"></span></a> Last sync: <?php echo (get_option('madeep_time_last_update_services') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_services')) : "Not synced yet"; ?></td>
                </tr>
                <!--tr>
                    <th scope="row"><label for="blogname"><?php echo __('Sincronizza lista servizi', 'madeep'); ?></label></th>
                    <td><a href="<?php echo $url; ?>&action=services-list"><span class="dashicons dashicons-image-rotate"></span></a> Last sync: <?php echo (get_option('madeep_time_last_update_services_list') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_services_list')) : "Not synced yet"; ?></td>
                </tr-->
                <?php
            }
            if (get_option('madeep_data_type') == 'canale' || get_option('madeep_data_type') == 'hotel') {
                ?>
                <tr>
                    <th scope="row"><label for="blogname"><?php echo __('Sincronizza offerte', 'madeep'); ?></label></th>
                    <td><a onclick="startSync('offers', this)" class="sync sync-offers"><span class="dashicons dashicons-image-rotate"></span></a> Last sync: <?php echo (get_option('madeep_time_last_update_offers') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_offers')) : "Not synced yet"; ?></td>
                </tr>
                <!--tr>
                    <th scope="row"><label for="blogname"><?php echo __('Sincronizza lista offerte', 'madeep'); ?></label></th>
                    <td><a href="<?php echo $url; ?>&action=offers-list"><span class="dashicons dashicons-image-rotate"></span></a> Last sync: <?php echo (get_option('madeep_time_last_update_offers_list') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_offers_list')) : "Not synced yet"; ?></td>
                </tr-->
                <?php
            }
            ?>
        </tbody>
    </table>
    <table class="form-table">
        <?php
        if (get_option('madeep_debug_mode') == 1) {
            ?>
            <tr valign="top">
                <td scope="row" colspan="2">Log:</td>
            </tr>
            <tr valign="top">
                <td scope="row" colspan="2">
                    <textarea readonly="readonly" id="logContent" style="width:100%; height: 300px;"><?php echo $mad->readLog(); ?></textarea>
                </td>
            </tr>
            <tr valign="top">
                <td scope="row" colspan="2">
                    <a onclick="copyLog()" style="cursor:pointer;"><span class="dashicons dashicons-clipboard"></span></a> | <a onclick="resetLog()" style="cursor:pointer;"><span class="dashicons dashicons-trash"></span></a> (<span id="logSize"></span>) <span id="lastIteration"></span>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>
</div>
<style>
    .sync{cursor:pointer;}
    .rotate {
        -webkit-animation:spin 4s linear infinite;
        -moz-animation:spin 4s linear infinite;
        animation:spin 4s linear infinite;
    }
    @-moz-keyframes spin { 100% { -moz-transform: rotate(-360deg); } }
    @-webkit-keyframes spin { 100% { -webkit-transform: rotate(-360deg); } }
    @keyframes spin { 100% { -webkit-transform: rotate(-360deg); transform:rotate(-360deg); } }
    .disabled{
        pointer-events: none;
    }
</style>
<script>
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    function startSync(t, el) {
        jQuery(el).addClass('disabled');
        jQuery(el).find('span').addClass('rotate');
        jQuery.ajax({
            type: 'POST',
            dataType: 'json',
            url: ajaxurl,
            data: {action: "madeep_sync_" + t},
            success: function (data) {
                jQuery(".sync-" + data.type).removeClass("disabled").find('span').removeClass('rotate');
            },
            error: function (data) {}
        });
    }
<?php
if (get_option('madeep_debug_mode') == 1) {
    ?>
        function readLog() {
            jQuery.ajax({
                type: 'POST',
                dataType: 'json',
                url: ajaxurl,
                data: {action: "madeep_log"},
                success: function (data) {

                    if (jQuery('#logContent')[0].scrollTop == jQuery('#logContent')[0].scrollTopMax) {
                        jQuery('#logContent').text(data.log).scrollTop(jQuery('#logContent')[0].scrollHeight);
                    } else {
                        jQuery('#logContent').text(data.log);
                    }
                    jQuery('#logSize').text(("" + data.size) + 'KB');
                    if (data.lastIteration * 1 > 0) {
                        jQuery('#lastIteration').text("<?php echo __('Ultima iterazione'); ?>: " + data.lastIteration);
                    } else {
                        jQuery('#lastIteration').text("");
                    }

                    setTimeout(function () {
                        readLog();
                    }, 3000);

                },
                error: function (data) {
                    //console.log(data);
                    setTimeout(function () {
                        readLog();
                    }, 3000);
                }
            });

        }
        function copyLog() {
            var text = jQuery("#logContent")[0];
            text.select()
            text.setSelectionRange(0, -1);
            document.execCommand("copy")
        }
        function resetLog() {
            jQuery.ajax({
                type: 'POST',
                datatype: 'json',
                url: ajaxurl,
                data: {action: "madeep_resetLog"},
                success: function (data) {
                },
                error: function (data) {
                }
            });
        }
        jQuery(document).ready(function () {
            readLog();
        });
        function test() {
            jQuery.ajax({
                type: 'GET',
                datatype: 'json',
                url: ajaxurl,
                data: {action: "madeep_test_in"},
                success: function (data) {
                    console.log(data);
                },
                error: function (data) {
                    console.log(data);
                }
            });
        }
    <?php
}
?>
</script>
