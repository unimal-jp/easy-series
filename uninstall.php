<?php
require_once dirname( __FILE__ ) . '/includes/easy-series-db.php';

global $wpdb;
global $easy_series_table_name;
$easy_series_table_name = $wpdb->prefix . 'easy_series_series';

$plugin_prefix = 'easy-series-';

//drop easy-series table
$series_db = new Easy_Series_Db( $wpdb, $easy_series_table_name );
if ($series_db->has_table()) {
	$series_db->drop_table();
}

//delete post meta data of easy-series
$table_name = $wpdb->prefix . 'postmeta';
$wpdb->get_results( "DELETE FROM " . $table_name . " WHERE meta_key='" . $plugin_prefix . "ids';" );
$wpdb->get_results( "DELETE FROM " . $table_name . " WHERE meta_key='" . $plugin_prefix . "positions';" );
?>