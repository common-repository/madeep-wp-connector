<table class="form-table">
    <tr valign="top">
        <th  scope="row"><?php echo __('Aggiornamento pagina singola: ', 'madeep'); ?></th>
        <td><input type="checkbox" value="1" name="madeep_allow_single_sync" <?php echo (get_option('madeep_allow_single_sync') == 1) ? 'checked="checked"' : ''; ?> /><?php echo __('Abilita', 'madeep'); ?>
            <p><small><?php echo __('Aggiunge la possibilità di aggiornare le singole pagine dalla lista degli articoli.', 'madeep'); ?></small></p>
        </td>
    </tr>
    <tr valign="top">
        <th  scope="row"><?php echo __('Aggiungi TAG di lingua: ', 'madeep'); ?></th>
        <td><input type="checkbox" value="1" name="madeep_allow_lang_tag" <?php echo (get_option('madeep_allow_lang_tag') == 1) ? 'checked="checked"' : ''; ?> /><?php echo __('Abilita', 'madeep'); ?>
            <p><small><?php echo __('Aggiunge i TAG di lingua per gli articoli cosí da poterli categorizzare al meglio nei siti multilingua. (Ha effetto solo se il flag multilingua é attivo)', 'madeep'); ?></small></p>
        </td>
    </tr>
    <tr valign="top">
        <th  scope="row"><?php echo __('Aggiungi filtri come TAG: ', 'madeep'); ?></th>
        <td><input type="checkbox" value="1" name="madeep_allow_filters_tag" <?php echo (get_option('madeep_allow_filters_tag') == 1) ? 'checked="checked"' : ''; ?> /><?php echo __('Abilita', 'madeep'); ?>
            <p><small><?php echo __('In caso siano presenti filtri , li aggiunge ai tag della pagina.', 'madeep'); ?></small></p>
        </td>
    </tr>
    <?php
    if(get_option('madeep_data_type') == 'canale'){
    ?>
    <tr valign="top">
        <th  scope="row"><?php echo __('Aggiungi TAG con nome struttura: ', 'madeep'); ?></th>
        <td><input type="checkbox" value="1" name="madeep_allow_structure_tag" <?php echo (get_option('madeep_allow_structure_tag') == 1) ? 'checked="checked"' : ''; ?> /><?php echo __('Abilita', 'madeep'); ?>
            <p><small><?php echo __('Aggiunge il TAG con il nome della struttura a cui é associato il servizio o offerta.', 'madeep'); ?></small></p>
        </td>
    </tr>
    <?php
    }
    ?>
</table>
<?php
submit_button();
?>