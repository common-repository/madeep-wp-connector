<?php
require_once( Madeep_Dir . 'sources/Madeep-Admin.php' );
$mad = new MadeepAdmin();
//$mad->getCategories();
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'hotels') {
        $mad->saveHotels(true);
    }
    if ($_GET['action'] == 'hotels-list') {
        $mad->saveHotelsList();
    }
    if ($_GET['action'] == 'ecom') {
        $mad->saveEcom(true);
    }
    if ($_GET['action'] == 'ecom-list') {
        $mad->saveEcomList();
    }
    if ($_GET['action'] == 'services') {
        $mad->saveServices(true);
    }
    if ($_GET['action'] == 'services-list') {
        $mad->saveServicesList();
    }
    if ($_GET['action'] == 'offers') {
        $mad->saveOffers(true);
    }
    if ($_GET['action'] == 'offers-list') {
        $mad->saveOffersList();
    }
    
    if ($_GET['action'] == 'setCron') {
        Madeep::cronSet();
    }
    if ($_GET['action'] == 'unsetCron') {
        Madeep::cronUnset();
    }
    header('Location: ?page=madeep');
}

$url = "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>
<p><?php Madeep::cronCheck(); ?></p>
<table class="form-table" role="presentation">
    <tbody>
        <tr>
            <th scope="row"><label for="blogname"><?php echo __('Sincronizza hotels', 'madeep'); ?></label></th>
            <td><a href="<?php echo $url; ?>&action=hotels"><span class="dashicons dashicons-image-rotate"></span></a> Last sync: <?php echo (get_option('madeep_time_last_update_hotels') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_hotels')) : "Not synced yet"; ?></td>
        </tr>
        <tr>
            <th scope="row"><label for="blogname"><?php echo __('Sincronizza lista hotels', 'madeep'); ?></label></th>
            <td><a href="<?php echo $url; ?>&action=hotels-list"><span class="dashicons dashicons-image-rotate"></span></a> Last sync: <?php echo (get_option('madeep_time_last_update_hotels_list') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_hotels_list')) : "Not synced yet"; ?></td>
        </tr>
        <tr>
            <th scope="row"><label for="blogname"><?php echo __('Sincronizza ecom', 'madeep'); ?></label></th>
            <td><a href="<?php echo $url; ?>&action=ecom"><span class="dashicons dashicons-image-rotate"></span></a> Last sync: <?php echo (get_option('madeep_time_last_update_ecom') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_ecom')) : "Not synced yet"; ?></td>
        </tr>
        <tr>
            <th scope="row"><label for="blogname"><?php echo __('Sincronizza lista ecom', 'madeep'); ?></label></th>
            <td><a href="<?php echo $url; ?>&action=ecom-list"><span class="dashicons dashicons-image-rotate"></span></a> Last sync: <?php echo (get_option('madeep_time_last_update_ecom_list') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_ecom_list')) : "Not synced yet"; ?></td>
        </tr>
        <tr>
            <th scope="row"><label for="blogname"><?php echo __('Sincronizza servizi', 'madeep'); ?></label></th>
            <td><a href="<?php echo $url; ?>&action=services"><span class="dashicons dashicons-image-rotate"></span></a> Last sync: <?php echo (get_option('madeep_time_last_update_services') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_services')) : "Not synced yet"; ?></td>
        </tr>
        <tr>
            <th scope="row"><label for="blogname"><?php echo __('Sincronizza lista servizi', 'madeep'); ?></label></th>
            <td><a href="<?php echo $url; ?>&action=services-list"><span class="dashicons dashicons-image-rotate"></span></a> Last sync: <?php echo (get_option('madeep_time_last_update_services_list') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_services_list')) : "Not synced yet"; ?></td>
        </tr>
        <tr>
            <th scope="row"><label for="blogname"><?php echo __('Sincronizza offerte', 'madeep'); ?></label></th>
            <td><a href="<?php echo $url; ?>&action=offers"><span class="dashicons dashicons-image-rotate"></span></a> Last sync: <?php echo (get_option('madeep_time_last_update_offers') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_offers')) : "Not synced yet"; ?></td>
        </tr>
        <tr>
            <th scope="row"><label for="blogname"><?php echo __('Sincronizza lista offerte', 'madeep'); ?></label></th>
            <td><a href="<?php echo $url; ?>&action=offers-list"><span class="dashicons dashicons-image-rotate"></span></a> Last sync: <?php echo (get_option('madeep_time_last_update_offers_list') > 0) ? date('d/m/Y H:i:s', get_option('madeep_time_last_update_offers_list')) : "Not synced yet"; ?></td>
        </tr>
    </tbody>
</table>
<?php
$mad->test();
?>
