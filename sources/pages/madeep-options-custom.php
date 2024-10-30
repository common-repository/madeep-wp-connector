<table class="form-table">
    <tr valign="top">
        <th  scope="row"><?php echo __('Custom CSS: ', 'madeep'); ?></th>
        <td>
            <textarea name="madeep_post_template_css" style="width: 100%; min-height: 300px;"><?php echo get_option('madeep_post_template_css'); ?></textarea>
        </td>
    </tr>
    <tr valign="top">
        <th  scope="row"><?php echo __('Custom JS: ', 'madeep'); ?></th>
        <td>
            <textarea name="madeep_post_template_js" style="width: 100%; min-height: 300px;"><?php echo get_option('madeep_post_template_js'); ?></textarea>
        </td>
    </tr>
    <tr valign="top">
        <th  scope="row"><?php echo __('Google API key: ', 'madeep'); ?></th>
        <td><input type="text" name="madeep_google_api_key" value="<?php echo esc_attr(get_option('madeep_google_api_key')); ?>" /></td>
    </tr>
</table>
<?php
submit_button();
?>