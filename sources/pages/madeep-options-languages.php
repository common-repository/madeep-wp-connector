<?php
require_once( Madeep_Dir . 'sources/Madeep-Admin.php' );
$mad = new MadeepAdmin();

$langs = $mad->languageList;
?>
<table class="form-table">

    <?php
    //delete_option('madeep_data_type');
    if (!in_array('wp-multilang/wp-multilang.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        ?>
        <tr valign="top">
            <th scope="row"><?php echo __('Lingua di default: ', 'madeep'); ?></th>
            <td>
                <select name="madeep_default_language" id="lang">
                    <option value=""><?php echo __('-Seleziona-', 'madeep'); ?></option>
                    <?php
                    foreach ($langs as $key => $val) {
                        echo '<option value="' . $key . '" ' . (($key == get_option('madeep_default_language')) ? 'selected="selected"' : '') . '>' . ucfirst($val[3]) . ' ('.ucfirst($val[2]).')</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php
    }
    ?>
        
    <tr valign="top">
        <td scope="row"><?php echo __('Multilingua: ', 'madeep'); ?></td>
        <td><input type="checkbox" value="1" name="madeep_active_multilanguages" <?php echo (get_option('madeep_active_multilanguages') == 1) ? 'checked="checked"' : ''; ?> /><?php echo __('Abilita', 'madeep'); ?><br/>
            <small>Necessita l'installazione di <a href="https://wpml.org/" target="_blank" rel="noreferrer">WPML</a> o <a href="https://wordpress.org/plugins/wp-multilang/" target="_blank" rel="noreferrer">WP Multilang</a></small>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php echo __('Lingue attive: ', 'madeep'); ?></th>
        <td>
            <input type="hidden" id="langsArr" name="madeep_active_languages" value="<?php echo get_option('madeep_active_languages'); ?>" />
            <select name="madeep_active_languages_w" id="langs" multiple="multiple">
                <option value=""><?php echo __('-Seleziona-', 'madeep'); ?></option>
                <?php
                $activeLangs = explode(',', get_option('madeep_active_languages'));


                foreach ($langs as $key => $val) {
                    echo '<option value="' . $key . '" ' . ((in_array($key, $activeLangs)) ? 'selected="selected"' : '') . '>' . ucfirst($val[3]) . ' ('.ucfirst($val[2]).')</option>';
                }
                ?>
            </select>
            <script>
                var langs = [];
                jQuery('#langs').change(function () {
                    console.log('changed');
                    langs = [];
                    jQuery(this).find(':selected').each(function () {
                        if (langs.indexOf(jQuery(this).attr('value')) < 0) {
                            langs.push(jQuery(this).attr('value'));
                        }
                    });
                    jQuery('#langsArr').val(langs.join(','));
                });
            </script>
        </td>
    </tr>
</table>
<?php

submit_button();
?>