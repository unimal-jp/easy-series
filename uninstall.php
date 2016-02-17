<?php
require_once dirname( __FILE__ ) . '/includes/easy-series-db.php';

global $wpdb;
global $easy_series_table_name;
$easy_series_table_name = $wpdb->prefix . 'easy_series_series';
$posts_series_table_name = $wpdb->prefix . 'easy_series_posts';

//drop easy-series table
$series_db = new Easy_Series_Db( $wpdb, $easy_series_table_name );
if ($series_db->has_table()) {
	$series_db->drop_table();
}

//drop easy-series posts table
$posts_series_db = new Easy_Series_Posts_Db( $wpdb, $posts_series_table_name );
if ($posts_series_db->has_table()) {
	$posts_series_db->drop_table();
}

?>