<?php
$url = "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'regenKey') {
        Madeep::genKey();
        Madeep::cronUnset();
    }
}
?>
<table class="form-table">
    <?php /* <tr valign="top">
      <td scope="row"><?php echo __('Chiave per il sync con Madeep: ', 'madeep'); ?></td>
      <td><input type="text" value="<?php echo get_option('madeep_sync_key'); ?>" disabled="disabled" style="widtd:260px" /></td>
      </tr> */ ?>
    <tr valign="top">
        <th scope="row"><?php echo __('Tipo richiesta: ', 'madeep'); ?></th>
        <td>
            <select name="madeep_data_type">
                <option value="hotel" <?php echo (get_option('madeep_data_type') == 'hotel') ? 'selected' : ''; ?>><?php echo __('Struttura', 'madeep'); ?></option>
                <option value="ecom" <?php echo (get_option('madeep_data_type') == 'ecom') ? 'selected' : ''; ?>><?php echo __('E-commerce', 'madeep'); ?></option>
                <?php /* <option value="area" <?php echo (get_option('madeep_data_type')=='area')?'checked':''; ?>><?php __('Area','madeep'); ?></option> */ ?>
                <option value="canale" <?php echo (get_option('madeep_data_type') == 'canale') ? 'selected' : ''; ?>><?php echo __('DMS Canale', 'madeep'); ?></option>
            </select>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php echo __('Username: ', 'madeep'); ?></th>
        <td><input type="text" name="madeep_username" value="<?php echo esc_attr(get_option('madeep_username')); ?>" /></td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php echo __('Password: ', 'madeep'); ?></th>
        <td><input type="password" name="madeep_password" value="<?php echo esc_attr(get_option('madeep_password')); ?>" /></td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php echo __('Chiave asincrona: ', 'madeep'); ?></th>
        <td><span style="border: 1px solid #7e8993;padding: 5px;margin-bottom: 3px;display: inline-block;border-radius: 4px;color: #000;background: #fff;"><?php echo esc_attr(get_option('madeep_sync_key')); ?></span> <a href="<?php echo $url; ?>&action=regenKey" style="text-decoration:none;" class="regenKey"><span class="dashicons dashicons-image-rotate"></span></a><br/>
            <small><?php echo __('Con i giusti requisiti questa chiave apre le porte della sincronizzazione.', 'madeep'); ?></small><br/>
            <small><?php echo __('La rigenerazione disattiverá il cron caso sia attivo.', 'madeep'); ?></small>
        </td>
    </tr>
</table>
<?php
submit_button();
?>