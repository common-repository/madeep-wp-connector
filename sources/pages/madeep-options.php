<?php
if($_GET['madeepDebug'] == 1){
    error_reporting(E_ALL);
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', 1);
}
$categories = get_categories(array(
    'orderby' => 'name',
    'hide_empty' => false
        ));
Madeep::addJs('madeepJs', Madeep_Url . 'js/app.js');
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <?php echo Madeep::tabMenu(); ?>
    <form method="post" action="options.php">
        <div class="tab-content">
            <?php
            $varGroup = Madeep::varGroup();
            if ($varGroup) {
                settings_fields($varGroup);
                do_settings_sections($varGroup);
            }
            include('madeep-options-' . Madeep::tabContent() . '.php');
            ?>
        </div>
    </form>
</div>