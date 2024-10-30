<?php
require_once( Madeep_Dir . 'sources/Madeep-Admin.php' );
$mad = new MadeepAdmin();
$url = "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'resetLog') {
        $mad->clearLog();
    }
    if ($_GET['action'] == 'forceStop') {
        $mad->shutItDown();
    }
    if ($_GET['action'] == 'forceStart') {
        $mad->turnItUp();
    }
}

$code = 'mad_' . uniqid();
?>
<table class="form-table">
    <tr valign="top">
        <td scope="row"><?php echo __('Forza stop esecuzione: ', 'madeep'); ?></td>
        <td>
            <a href="<?php echo $url; ?>&action=forceStop">Attiva</a> 
            <a href="<?php echo $url; ?>&action=forceStart">Disattiva</a> 
            <p><small><?php echo __('Attiva/disattiva blocco forzato, questo controllo avviene a ogni ciclo di singola creazione', 'madeep'); ?></small></p>
        </td>
    </tr>
    <tr valign="top">
        <td scope="row"><?php echo __('Debug mode: ', 'madeep'); ?></td>
        <td><input type="checkbox" value="1" name="madeep_debug_mode" <?php echo (get_option('madeep_debug_mode') == 1) ? 'checked="checked"' : ''; ?> /><?php echo __('Abilita', 'madeep'); ?>
            <p><small><?php echo __('Abilita la scrittura di un log e un blocco dopo X iterazioni', 'madeep'); ?></small></p>
        </td>
    </tr>
    <tr valign="top">
        <td scope="row"><?php echo __('Numero di iterazioni: ', 'madeep'); ?></td>
        <td><input type="number" value="<?php echo (int) sanitize_text_field(get_option('madeep_debug_quantity')); ?>" name="madeep_debug_quantity" <?php echo ((int) get_option('madeep_debug_mode') == 0) ? 'disabled="disabled"' : ''; ?> />
            <p><small><?php echo __('Numero di iterazioni dopo il quale si ferma l\'esecuzione', 'madeep'); ?></small></p>
        </td>
    </tr>
    <tr class="rP" valign="top">
        <td scope="row"><?php echo __('Reset Pagine: ', 'madeep'); ?></td>
        <td>
            <span data-s="0"><button onclick="madP(1, 'rP');" type="button"><?php echo __('Resetta', 'madeep'); ?></button></span>
            <span data-s="1" style="display:none;"><?php echo __('Sicuro di voler resettare? ', 'madeep'); ?><button onclick="madP(2, 'rP');" type="button"><?php echo __('Sicuro!', 'madeep'); ?></button></span>
            <span data-s="2" style="display:none;"><?php echo __('Ma davvero davvero? ', 'madeep'); ?><button onclick="<?php echo $code; ?>('rP');" type="button"><?php echo __('Sicurissimo! So quello che faccio! ;-)', 'madeep'); ?></button></span>
            <span data-s="3" style="display:none;"><?php echo __('Cancellando...', 'madeep'); ?></span>
            <span data-s="4" style="display:none;"><?php echo __('Ok, fatto, ma non dare la colpa a me!', 'madeep'); ?></span>
            <p><small><?php echo __('Cancella tutte le pagine generate dal plugin che sono presenti nel DB.', 'madeep'); ?></small></p>
        </td>
    </tr>
    <tr class="rDB" valign="top">
        <td scope="row"><?php echo __('Reset DB: ', 'madeep'); ?></td>
        <td>
            <span data-s="0"><button onclick="madP(1, 'rDB');" type="button"><?php echo __('Resetta', 'madeep'); ?></button></span>
            <span data-s="1" style="display:none;"><?php echo __('Sicuro di voler resettare? ', 'madeep'); ?><button onclick="madP(2, 'rDB');" type="button"><?php echo __('Sicuro!', 'madeep'); ?></button></span>
            <span data-s="2" style="display:none;"><?php echo __('Ma davvero davvero? ', 'madeep'); ?><button onclick="<?php echo $code; ?>('rDB');" type="button"><?php echo __('Sicurissimo! So quello che faccio! ;-)', 'madeep'); ?></button></span>
            <span data-s="3" style="display:none;"><?php echo __('Cancellando...', 'madeep'); ?></span>
            <span data-s="4" style="display:none;"><?php echo __('Ok, fatto, ma non dare la colpa a me!', 'madeep'); ?></span>
            <p><small><?php echo __('Tronca tutte le tabelle inerenti al plugin.', 'madeep'); ?></small></p>
        </td>
    </tr>

</table>
<?php
submit_button();
?>
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
<script>
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    function madP(s, t) {
        jQuery('.' + t).find("span[data-s]").hide();
        jQuery('.' + t).find("span[data-s='" + s + "']").fadeIn();
    }
    function <?php echo $code; ?>(t) {

        if (typeof t != 'undefined') {
            madP(3, t);
            var data = {};
            if (t == 'rDB') {
                data = {action: "madeep_resetDB"}
            } else if (t == 'rP') {
                data = {action: "madeep_resetPages"}
            }
            jQuery.ajax({
                type: 'POST',
                dataType: 'json',
                url: ajaxurl,
                data: data,
                success: function (data) {
                    if (data.status == "success") {
                        jQuery('.' + t).find("span[data-s]").hide();
                        jQuery('.' + t).find("span[data-s='4']").fadeIn();
                    }
                },
                error: function (data) {
                    console.log(data);
                }
            });
        }
    }
</script>
<?php
if (get_option('madeep_debug_mode') == 1) {
    ?>
    <script>
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
                    console.log(data);

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
    </script>
    <?php
}
?>