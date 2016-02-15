<?php
class Easy_Series_Posts_Db {
	const STATUS_ACTIVE = 'active';
	const STATUS_INACTIVE = 'inactive';
	
	private $db;
	private $table_name;

	function __construct( $db, $table_name ) {
		$this->db = $db;
		$this->table_name = $table_name;
	}

	public function has_table() {
		return ( $this->db->get_var( "SHOW TABLES LIKE '" . $this->table_name . "'" ) == $this->table_name );
	}

	public function create_table() {
		$sql = "CREATE TABLE " . $this->table_name . "(" .
			"id INT(11) NOT NULL AUTO_INCREMENT," .
			"post_id INT(11) NOT NULL," .
			"series_id INT(11) NOT NULL," .
			"number INT(11) NOT NULL," .
			"created_at DATETIME," .
			"updated_at DATETIME, " .
			"PRIMARY KEY (id)
			);";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		dbDelta( $sql );
	}

	public function drop_table() {
		$sql = "DROP TABLE ". $this->table_name;
		$this->db->query( $sql );   	
	}

	public function find_by_post_id( $post_id ) {
		$post_series = $this->db->get_row( "SELECT * FROM " . $this->table_name . " WHERE post_id=" . $post_id . ";" );

		return $post_series;
	}

	public function find_by_series_id( $series_id ) {
		$post_series_list = $this->db->get_results( "SELECT * FROM " . $this->table_name . " WHERE series_id=" . $series_id . " ORDER BY number;" );

		return $post_series_list;
	}

	public function find_by_series_id_and_number( $series_id, $number ) {
		$post_series = $this->db->get_row(
			$this->db->prepare( "SELECT * FROM " . $this->table_name . " WHERE series_id=%d AND number=%d;", $series_id, $number )
		);

		return $post_series;
	}

	public function create( $data ) {
		$date = date( 'Y-m-d H:i:s' );
		$data['created_at'] = $date;
		$data['updated_at'] = $date;

		$this->db->insert( $this->table_name, $data );

		return $this->db->insert_id;
	}

	public function update( $id, $data ) {
		$data['updated_at'] = date('Y-m-d H:i:s');

		$where = array(
			'id'  => $id
		);

		$this->db->update( $this->table_name, $data, $where );		
	}

	public function delete( $id ) {
		$this->db->query( "DELETE FROM " . $this->table_name . " WHERE id=" . $id . ";" );
	}

	public function delete_by_series_id( $series_id ) {
		$this->db->query( "DELETE FROM " . $this->table_name . " WHERE series_id=" . $series_id . ";" );
	}

	public function delete_by_post_id( $post_id ) {
		$this->db->query( "DELETE FROM " . $this->table_name . " WHERE post_id=" . $post_id . ";" );
	}	
}

?>