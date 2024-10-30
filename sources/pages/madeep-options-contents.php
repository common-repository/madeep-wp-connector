<table class="form-table">

    <tr valign="top">
        <th colspan="2" scope="row"><h2><?php echo __('Scrittura pagine', 'madeep'); ?></h2></th>
    </tr>
    <tr valign="top">
        <th  scope="row"><?php echo __('Scrittura pagine: ', 'madeep'); ?></th>
        <td><input type="checkbox" class="pagesCheckGeneral" value="1" name="madeep_enable_write" <?php echo (get_option('madeep_enable_write') == 1) ? 'checked="checked"' : ''; ?> /><?php echo __('Abilita', 'madeep'); ?>
            <p><small><?php echo __('Caso non sia attivo, solo i dati nel DB verranno scritti.', 'madeep'); ?></small></p>
        </td>
    </tr>
    <?php
    if (get_option('madeep_data_type') == 'canale') {
        ?>
        <tr valign="top">
            <th  scope="row"><?php echo __('Pagina hotel: ', 'madeep'); ?></th>
            <td><input type="checkbox" class="pagesCheck" value="1" name="madeep_write_hotels_page" <?php echo (get_option('madeep_write_hotels_page') == 1) ? 'checked="checked"' : ''; ?> /><?php echo __('Abilita', 'madeep'); ?></td>
        </tr>
        <tr valign="top">
            <th  scope="row"><?php echo __('Pagina e-commerce: ', 'madeep'); ?></th>
            <td><input type="checkbox" class="pagesCheck" value="1" name="madeep_write_ecoms_page" <?php echo (get_option('madeep_write_ecoms_page') == 1) ? 'checked="checked"' : ''; ?> /><?php echo __('Abilita', 'madeep'); ?></td>
        </tr>
        <?php
    }
    if (get_option('madeep_data_type') == 'canale' || get_option('madeep_data_type') == 'ecom') {
        ?>
        <tr valign="top">
            <th  scope="row"><?php echo __('Pagina servizio: ', 'madeep'); ?></th>
            <td><input type="checkbox" class="pagesCheck" value="1" name="madeep_write_services_page" <?php echo (get_option('madeep_write_services_page') == 1) ? 'checked="checked"' : ''; ?> /><?php echo __('Abilita', 'madeep'); ?></td>
        </tr>
        <?php
    }
    if (get_option('madeep_data_type') == 'canale' || get_option('madeep_data_type') == 'hotel') {
        ?>
        <tr valign="top">
            <th  scope="row"><?php echo __('Pagina offerta: ', 'madeep'); ?></th>
            <td><input type="checkbox" class="pagesCheck" value="1" name="madeep_write_offers_page" <?php echo (get_option('madeep_write_offers_page') == 1) ? 'checked="checked"' : ''; ?> /><?php echo __('Abilita', 'madeep'); ?></td>
        </tr>
        <?php
    }
    ?>
    <tr valign="top">
        <th  scope="row"></th>
        <td></td>
    </tr>
    <tr valign="top">
        <th colspan="2" scope="row"><h2><?php echo __('Altre configurazioni', 'madeep'); ?></h2></th>
    </tr>
    <tr valign="top">
        <th  scope="row"><?php echo __('Gallery: ', 'madeep'); ?></th>
        <td><input type="checkbox" value="1" name="madeep_download_gallery" <?php echo (get_option('madeep_download_gallery') == 1) ? 'checked="checked"' : ''; ?> /> <?php echo __('Scarica immagini gallery localmente', 'madeep'); ?>
            <p><small><?php echo __('Scaricando i files localmente l\'elemento %gallery% restituirà gli ID dei media al posto delle URL.', 'madeep'); ?></small></p>
        </td>
    </tr>
</table>
<?php
submit_button();
?>
<script>
    jQuery(document).ready(function () {
        jQuery('.pagesCheckGeneral').change(function () {
            checkCheckbox();
        });
        checkCheckbox();
    });
    function checkCheckbox() {
        if (jQuery('.pagesCheckGeneral').is(':checked')) {
            jQuery('.pagesCheck').removeAttr('disabled');
        } else {
            jQuery('.pagesCheck').prop('disabled', true);
        }
    }

</script>