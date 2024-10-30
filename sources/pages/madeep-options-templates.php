<?php
require_once( Madeep_Dir . 'sources/Madeep-Admin.php' );
$mad = new MadeepAdmin();

$madLangPlugin = $mad->multiLangPlugin;
$baseLang = $mad->getLang($mad->defaultLang);

function genPageList($selected, $baseLang = null, $langPlugin = null) {

    $return = '';
    $page_list = get_posts(array('numberposts' => -1, 'post_status' => array('publish', 'pending', 'draft', 'future', 'private', 'inherit', 'trash'), 'post_type' => 'any'));

    if ($langPlugin == 'wpml') {

        foreach ($page_list as $key => $val) {
            if ($val->post_type != 'attachment') {
                $locale = apply_filters('wpml_post_language_details', NULL, $val->ID);

                if ($baseLang == $locale['language_code'] && $locale['different_language'] == false) {
                    $return .= '<option value="' . $val->ID . '" ' . (($selected == $val->ID) ? 'selected' : '') . '>' . $val->post_title . ' (' . $val->ID . ')</option>';
                }
            }
        }
    } else {
        foreach ($page_list as $key => $val) {
            if ($val->post_type != 'attachment') {
                $return .= '<option value="' . $val->ID . '" ' . (($selected == $val->ID) ? 'selected' : '') . '>' . $val->post_title . ' (' . $val->ID . ')</option>';
            }
        }
    }
    return $return;
}

Madeep::languageCheck();
?>
<table class="form-table">
    <tr valign="top">
        <th colspan="2" scope="row"><h2><?php echo __('Templates', 'madeep'); ?></h2></th>
    </tr>
    <?php
    if (get_option('madeep_data_type') == 'canale') {
        ?>
        <tr valign="top">
            <td scope="row"><?php echo __('Pagina hotel: ', 'madeep'); ?></td>
            <td>
                <select name="madeep_template_hotels" class="templateSelect">
                    <option value=""><?php echo __('-Seleziona-', 'madeep'); ?></option>
                    <?php
                    $madeep_template = get_option('madeep_template_hotels');
                    echo genPageList($madeep_template, $baseLang, $madLangPlugin);
                    ?>
                </select><br/><small></small>
            </td>
        </tr>
        <tr valign="top">
            <td scope="row"><?php echo __('Pagina ecommerce: ', 'madeep'); ?></td>
            <td>
                <select name="madeep_template_ecoms" class="templateSelect">
                    <option value=""><?php echo __('-Seleziona-', 'madeep'); ?></option>
                    <?php
                    $madeep_template = get_option('madeep_template_ecoms');
                    echo genPageList($madeep_template, $baseLang, $madLangPlugin);
                    ?>
                </select><br/><small></small>
            </td>
        </tr>
        <?php
    }

    if (get_option('madeep_data_type') == 'canale' || get_option('madeep_data_type') == 'ecom') {
        ?>
        <tr valign="top">
            <td scope="row"><?php echo __('Pagina servizi: ', 'madeep'); ?></td>
            <td>
                <select name="madeep_template_services" class="templateSelect">
                    <option value=""><?php echo __('-Seleziona-', 'madeep'); ?></option>
                    <?php
                    $madeep_template = get_option('madeep_template_services');
                    echo genPageList($madeep_template, $baseLang, $madLangPlugin);
                    ?>
                </select><br/><small></small>
            </td>
        </tr>
        <?php
    }
    if (get_option('madeep_data_type') == 'canale' || get_option('madeep_data_type') == 'hotel') {
        ?>

        <tr valign="top">
            <td scope="row"><?php echo __('Pagina offerte: ', 'madeep'); ?></td>
            <td>
                <select name="madeep_template_offers" class="templateSelect">
                    <option value=""><?php echo __('-Seleziona-', 'madeep'); ?></option>
                    <?php
                    $madeep_template = get_option('madeep_template_offers');
                    echo genPageList($madeep_template, $baseLang, $madLangPlugin);
                    ?>
                </select><br/><small></small>
            </td>
        </tr>
        <?php
    }
    ?>
    <tr valign="top">
        <th colspan="2" scope="row"><h2><?php echo __('Categorie', 'madeep'); ?></h2></th>
    </tr>
    <?php
    if (get_option('madeep_data_type') == 'canale') {
        ?>
        <tr valign="top">
            <td scope="row"><?php echo __('Pagine di hotel: ', 'madeep'); ?></td>
            <td>
                <select name="madeep_category_hotels" class="categorySelect">
                    <?php
                    $madeep_category_hotel = get_option('madeep_category_hotels');
                    foreach ($categories as $category) {
                        echo '<option ' . (($madeep_category_hotel == (int) $category->term_id) ? 'selected' : '') . ' value="' . (int) $category->term_id . '">' . esc_html($category->name) . '</option>';
                    }
                    ?>
                </select><br/><small></small>

            </td>
        </tr>
        <tr valign="top">
            <td scope="row"><?php echo __('Pagine di ecommerce: ', 'madeep'); ?></td>
            <td>
                <select name="madeep_category_ecoms" class="categorySelect">
                    <?php
                    $madeep_category_ecom = get_option('madeep_category_ecoms');
                    foreach ($categories as $category) {
                        echo '<option ' . (($madeep_category_ecom == (int) $category->term_id) ? 'selected' : '') . ' value="' . (int) $category->term_id . '">' . esc_html($category->name) . '</option>';
                    }
                    ?>
                </select><br/><small></small>

            </td>
        </tr>
        <?php
    }
    if (get_option('madeep_data_type') == 'canale' || get_option('madeep_data_type') == 'ecom') {
        ?>
        <tr valign="top">
            <td scope="row"><?php echo __('Pagine di servizi: ', 'madeep'); ?></td>
            <td>
                <select name="madeep_category_services" class="categorySelect">
                    <?php
                    $madeep_category_ecom = get_option('madeep_category_services');
                    foreach ($categories as $category) {
                        echo '<option ' . (($madeep_category_ecom == (int) $category->term_id) ? 'selected' : '') . ' value="' . (int) $category->term_id . '">' . esc_html($category->name) . '</option>';
                    }
                    ?>
                </select><br/><small></small>

            </td>
        </tr>
        <?php
    }
    if (get_option('madeep_data_type') == 'canale' || get_option('madeep_data_type') == 'hotel') {
        ?>

        <tr valign="top">
            <td scope="row"><?php echo __('Pagine delle offerte: ', 'madeep'); ?></td>
            <td>
                <select name="madeep_category_offers" class="categorySelect">
                    <?php
                    $madeep_category = get_option('madeep_category_offers');
                    foreach ($categories as $category) {
                        echo '<option ' . (($madeep_category == (int) $category->term_id) ? 'selected' : '') . ' value="' . (int) $category->term_id . '">' . esc_html($category->name) . '</option>';
                    }
                    ?>
                </select><br/><small></small>

            </td>
        </tr>
        <?php
    }
    ?>
</table>
<?php
submit_button();
if ((int) get_option('madeep_active_multilanguages') == 1) {
    ?>
    <script>
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        function getTemplateTranlations(el) {
            var that = el;
            var v = jQuery(el).val() * 1;
            if (v > 0) {
                jQuery.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: ajaxurl,
                    data: {action: "madeep_getTemplatesTranlations", v: v},
                    success: function (data) {
                        var t = [];
                        for (var i in data) {
                            t.push(data[i].lang + ': ' + data[i].title + ' (' + data[i].id + ')');
                        }
                        jQuery(that).closest('td').find('small').text(t.join(' | '));
                    },
                    error: function (data) {}
                });
            } else {
                jQuery(that).closest('td').find('small').text('');
            }
        }
        function getCategoriesTranlations(el) {
            var that = el;
            var v = jQuery(el).val() * 1;
            if (v > 0) {
                jQuery.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: ajaxurl,
                    data: {action: "madeep_getCategoriesTranlations", v: v},
                    success: function (data) {
                        var t = [];
                        for (var i in data) {
                            t.push(data[i].lang + ': ' + data[i].title + ' (' + data[i].id + ')');
                        }
                        jQuery(that).closest('td').find('small').text(t.join(' | '));
                    },
                    error: function (data) {}
                });
            } else {
                jQuery(that).closest('td').find('small').text('');
            }
        }
        jQuery(document).ready(function () {
            jQuery('.templateSelect').each(function () {
                getTemplateTranlations(this);
            }).change(function () {
                getTemplateTranlations(this);
            });
            jQuery('.categorySelect').each(function () {
                getCategoriesTranlations(this);
            }).change(function () {
                getCategoriesTranlations(this);
            });
        });
    </script>
    <?php
}
?>