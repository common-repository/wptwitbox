<?php
/* Option löschen */
delete_option('wptwitbox');

/* DB bereinigen */
$GLOBALS['wpdb']->query("DELETE FROM `" .$GLOBALS['wpdb']->postmeta. "` WHERE meta_key = '_wptwitbox_bitly_url'");
$GLOBALS['wpdb']->query("OPTIMIZE TABLE `" .$GLOBALS['wpdb']->options. "`");