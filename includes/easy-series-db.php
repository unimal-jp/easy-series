<?php
class Easy_Series_Db {
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
			"title VARCHAR(255) NOT NULL," .
			"number_of_times INT(11)," .
			"status VARCHAR(16)," .
			"title_format TEXT," .
			"list_title_format TEXT," .
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

	public function get_series_list() {
		$series_list = $this->db->get_results( "SELECT * FROM " . $this->table_name . " ORDER BY id desc;" );

		return $series_list;
	}

	public function get_active_series_list() {
		$series_list = $this->db->get_results( "SELECT * FROM " . $this->table_name . " where status='" . self::STATUS_ACTIVE . "' ORDER BY id desc;" );

		return $series_list;
	}

	public function get_series( $id ) {
		$series = $this->db->get_row( "SELECT * FROM " . $this->table_name . " WHERE id=" . $id . ";" );

		return $series;
	}

	public function is_active( $id ) {
		$series = $this->get_series( $id );

		if (($series = null) || ($series->status != self::STATUS_ACTIVE)) {
			return false;
		}

		return true;
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
}

?>