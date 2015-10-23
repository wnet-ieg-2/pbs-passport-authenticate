<?php
show_admin_bar(false);
remove_all_actions('wp_footer',1);
remove_all_actions('wp_header',1);
header('content-type: application/json; charset=utf-8');
{
    var_dump( $_REQUEST, true );
}
exit();
?>
