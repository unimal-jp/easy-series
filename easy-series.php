<?php
/**
 * Plugin Name: Easy Series
 * Version: 1.0.0
 * Description: This plugin enables you to manage post series easily.
 * Author: Hide Nakayama, unimal Co,.Ltd.
 * Author URI: http://unimal.jp
 * Plugin URI: PLUGIN SITE HERE
 * Text Domain: easy-series
 * Domain Path: /languages
 * @package Easy-series
 */

require_once dirname( __FILE__ ) . '/includes/easy-series-db.php';
require_once dirname( __FILE__ ) . '/includes/easy-series-posts-db.php';

register_activation_hook( __FILE__,  array( 'easy_series', 'activate' ) );

global $wpdb;
global $series_table_name;
global $posts_series_table_name;
$series_table_name = $wpdb->prefix . 'easy_series_series';
$posts_series_table_name = $wpdb->prefix . 'easy_series_posts';


class Easy_Series {
	const PLUGIN_PREFIX = 'easy-series-';

	private static $series_db = null;
	private static $posts_series_db = null;

	public function __construct() {
		global $wpdb;
		global $series_table_name;
		global $posts_series_table_name;

		if ( is_null( self::$series_db ) ) {
			self::$series_db = new Easy_Series_Db( $wpdb, $series_table_name );
		}
		if ( is_null( self::$posts_series_db ) ) {
			self::$posts_series_db = new Easy_Series_Posts_Db( $wpdb, $posts_series_table_name );
		}

		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'post_data' ) );
		add_action( 'admin_init', array( $this, 'manage_columns' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'delete_post', array( $this, 'delete_post' ) );

		add_action( 'init', array( $this, 'enqueue_scripts_and_styles' ) );

		add_filter( 'the_title', array( $this, 'the_title' ) );
		add_filter( 'the_content', array( $this, 'the_content' ) );
		add_filter( 'the_excerpt', array( $this, 'the_excerpt' ) );

		add_action( 'wp_ajax_easy_series_check_series_and_number', array( $this, 'ajax_check_series_and_number' ) );
	}

	public static function activate() {
		global $wpdb;
		global $series_table_name;
		global $posts_series_table_name;

		$series_db = new Easy_Series_Db( $wpdb, $series_table_name );
		if ( !$series_db->has_table() ) {
			$series_db->create_table();
		}
		$posts_series_db = new Easy_Series_Posts_Db( $wpdb, $posts_series_table_name );
		if ( !$posts_series_db->has_table() ) {
			$posts_series_db->create_table();
		}
	}

	public function add_menu() {
		add_menu_page(
			'連載',
			'連載',
			'edit_others_posts',
			basename( __FILE__ ),
			array( $this, 'settings' )
		);
	}

	public function settings() {
		if ( !current_user_can( 'edit_others_posts' ) ) {
			wp_die( 'アクセスできません。' );
		}

		//settings top
		if ( !isset( $_GET[self::PLUGIN_PREFIX . 'page'] ) ) {
			$this->settings_top_page();
			return;
		}

		//settings sub
		switch ( $_GET[self::PLUGIN_PREFIX . 'page'] ) {
			case 'form':
				$this->settings_form_page();
				break;
		}
	}

	public function post_data() {
		if ( !current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		//set series
		if ( isset( $_POST[self::PLUGIN_PREFIX . 'add'] ) ) {
			$this->add_series();
			return;
		}

		//edit series
		if ( isset( $_POST[self::PLUGIN_PREFIX . 'edit'] ) ) {
			$this->update_series( $_POST[self::PLUGIN_PREFIX . 'id'] );
			return;
		}

		//delete series
		if ( isset( $_POST[self::PLUGIN_PREFIX . 'delete'] ) ) {
			$this->delete_series( $_POST[self::PLUGIN_PREFIX . 'id'] );
			return;
		}		
	}

	public function manage_columns() {
		add_filter('manage_posts_columns', array( $this, 'manage_columns_header' ) );

		add_action( 'manage_posts_custom_column', array( $this, 'manage_custom_column' ), 10, 2 );
	}

	public function manage_columns_header($defaults) {
		$defaults[self::PLUGIN_PREFIX . 'name'] = '連載';
		return $defaults;
	}

	public function manage_custom_column( $column_name, $id ) {
		if ($column_name != self::PLUGIN_PREFIX . 'name') {
			return;
		}

		$post_series = self::$posts_series_db->find_by_post_id( $id );

		if ($post_series == null) {
			return;
		}

		$series = self::$series_db->get_series( $post_series->series_id );

		if (($series == null) || ($series->status != Easy_Series_Db::STATUS_ACTIVE)) {
			return;
		}

		echo sprintf('%s 第%d回', $series->title, $post_series->number);
	}

	public function add_meta_boxes() {
		add_meta_box( 'easy-series', '連載', array( $this, 'meta_box' ), 'post', 'advanced' );
	}

	public function meta_box() {
		$post_series = self::$posts_series_db->find_by_post_id( get_the_ID() );

		$series = null;
		if ($post_series != null) {
			$series = self::$series_db->get_series( $post_series->series_id );
		}

		if ($series == null) {
			$series_id = '';
			$series_name = '';
			$series_number = '';

			$series_status = '';

			$delete_btn_display = 'style="display: numberne;"';
		} else {
			$series_id = $series->id;
			$series_name = $series->title;
			$series_number = $post_series->number;

			if ($series->status == Easy_Series_Db::STATUS_ACTIVE) {
				$series_status = '';
			} else {
				$series_status = '(' . $this->get_status_name( $series->status ) . ')';
			}

			$delete_btn_display = '';
		}

?>
		<div>
			<div>設定は投稿を保存するまで反映されません。</div>
			<br>

			<div>
				<div class="<?php echo( esc_attr( self::PLUGIN_PREFIX ) ); ?>info-not-set"><strong>連載 : </strong>設定なし</div>
				<div class="<?php echo( esc_attr( self::PLUGIN_PREFIX ) ); ?>info"><strong>連載 : </strong>
					<span class="<?php echo( esc_attr( self::PLUGIN_PREFIX ) ); ?>name"><?php echo( esc_html( $series_name ) ); ?></span>
					<span> 第 </span>
					<span class="<?php echo( esc_attr( self::PLUGIN_PREFIX ) ); ?>display-number"><?php echo( esc_html( $series_number ) ); ?></span> 回
					<span> <?php echo( esc_html( $series_status ) ); ?></span>
				</div>

				<input type="hidden" class="<?php echo( esc_attr( self::PLUGIN_PREFIX ) ); ?>id" name="<?php echo( esc_attr( self::PLUGIN_PREFIX ) ); ?>id" value="<?php echo( esc_attr( $series_id ) ); ?>">
				<input type="hidden" class="<?php echo( esc_attr( self::PLUGIN_PREFIX ) ); ?>number" name="<?php echo( esc_attr( self::PLUGIN_PREFIX ) ); ?>number" value="<?php echo( esc_attr( $series_number ) ); ?>">
			</div>

			<div class="submit">
				<input type="submit" <?php echo( $delete_btn_display ); ?> class="button <?php echo self::PLUGIN_PREFIX; ?>delete-btn" value="解除する">
			</div>

			<br>
			<table>
				<tbody>
					<tr>
						<td class="left">
							<select class="<?php echo self::PLUGIN_PREFIX; ?>series-select" name="<?php echo self::PLUGIN_PREFIX; ?>series-select">
								<option value="#NONE#">— 選択 —</option>
<?php
		$series_list = self::$series_db->get_active_series_list();
		foreach ($series_list as $series) {
			$option_html = sprintf('<option value="%d">%s</option>', $series->id, $series->title);
			echo $option_html;
		}
?>
							</select>
						</td>
						<td width="100"><div class="<?php echo( esc_attr( self::PLUGIN_PREFIX ) ); ?>display-number-of-times"></div><td>
						<td class="right">
							第 
							<select class="<?php echo self::PLUGIN_PREFIX; ?>number-select" name="<?php echo self::PLUGIN_PREFIX; ?>number-select"></select>
							<input type="number" class="<?php echo self::PLUGIN_PREFIX; ?>number-input" name="<?php echo self::PLUGIN_PREFIX; ?>number-input">
							 回
						</td>
					</tr>
					<tr>
						<td>
							<div class="submit">
								<input type="submit" class="button <?php echo self::PLUGIN_PREFIX; ?>set-btn" value="設定">
							</div>
						</td>
						<td></td>
						<td></td>
					</tr>									
				</tbody>
			</table>

		</div>
<?php
	}

	private function get_status_name( $value ) {
		$status_names_hash = $this->get_status_names_hash();
		return $status_names_hash[$value];
	}

	private function get_status_names_hash() {
		return array(
			Easy_Series_Db::STATUS_ACTIVE 	=> '有効',
			Easy_Series_Db::STATUS_INACTIVE	=> '無効',
		);
	}

	public function ajax_check_series_and_number() {
		if ( !current_user_can( 'edit_posts' ) ) {
			$this->ajax_exit(403, __('You do not have sufficient permissions to import the content of this site.'));
			return;
		}

		$series_id = $_POST[self::PLUGIN_PREFIX . 'id'];
		$number = $_POST[self::PLUGIN_PREFIX . 'number'];

		$post_series = self::$posts_series_db->find_by_series_id_and_number( $series_id, $number );

		if ($post_series != null) {
			$this->ajax_exit(200, 'USED');
			return;
		}

		$this->ajax_exit(200);
	}

	//ajax return script
	public function ajax_exit($status, $message="") {
		$exit_js_code = sprintf("easy_series_ajax_result=%d; easy_series_ajax_error_message='%s'", $status, $message);
	
		die($exit_js_code); //Functions echoing for AJAX must die
	}	

	public function save_post() {	
		if( empty($_POST) ) { //trash, untrash
			return;
		}

		$post_id = get_the_ID();

		$post_series = self::$posts_series_db->find_by_post_id( $post_id );

		$series_id = $_POST[self::PLUGIN_PREFIX . 'id'];
		$number = $_POST[self::PLUGIN_PREFIX . 'number'];

		//unset
		if (($series_id == null) || ($series_id == '')) {
			//delete
			if ($post_series != null) {
				self::$posts_series_db->delete( $post_series->id );
			}

			return;
		}

		$data = array(
			'post_id'	=> $post_id,
			'series_id'	=> $series_id,
			'number'	=> $number
		);

		//If there is a row which already has the same series_id and number, delete the row;
		$another_post_series = self::$posts_series_db->find_by_series_id_and_number( $series_id, $number );
		if (($another_post_series != null) && ($another_post_series->post_id != $post_id)) {
			self::$posts_series_db->delete( $another_post_series->id );
		}

		//new
		if ($post_series == null) {
			self::$posts_series_db->create( $data );
			return;
		}

		//no change
		if (($post_series->series_id == $series_id) && ($post_series->number == $number)) {
			return;
		}

		//update
		self::$posts_series_db->update( $post_series->id, $data );
	}

	public function delete_post( $postid ) {
		self::$posts_series_db->delete_by_post_id( $postid );
	}

	public function enqueue_scripts_and_styles() {
		$is_post_new = ($_SERVER['SCRIPT_NAME'] == '/wp-admin/post-new.php');
		$is_post_edit = (isset( $_GET['post'] ) && isset( $_GET['action'] ) && ($_GET['action'] == 'edit'));

		if ($is_post_new || $is_post_edit) {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'sack' );
			wp_enqueue_script( 'easy-series-edit', plugins_url( 'easy-series-edit.js' , __FILE__ ), array( 'jquery' ) );

			$params = array(
				'plugin_prefix'	=> self::PLUGIN_PREFIX,
				'series_list'	=> self::$series_db->get_active_series_list(),
				'ajaxurl'		=> admin_url('admin-ajax.php')
			);
			wp_localize_script( 'easy-series-edit', 'easy_series', $params );

			//css
			wp_enqueue_style( 'easy-series-edit', plugins_url( 'easy-series-edit.css' , __FILE__ ) );

			return;
		}

		//css
		wp_enqueue_style( 'easy-series', plugins_url( 'easy-series.css' , __FILE__ ) );
	}

	public function the_title( $title ) {
		$posts_series = self::$posts_series_db->find_by_post_id( get_the_ID() );
		if ($posts_series == null) {
			return $title;
		}

		$series = self::$series_db->get_series( $posts_series->series_id );
		if (($series == null) || ($series->status != Easy_Series_Db::STATUS_ACTIVE)) {
			return $title;
		}

		$format = $series->title_format;
		if (($format == null) || ($format == '')) {
			$format = $this->get_default_title_format();
		}

		$customized_title = str_replace('%series_title%',	$series->title,			$format);
		$customized_title = str_replace('%number%',			$posts_series->number,	$customized_title);
		$customized_title = str_replace('%post_title%',		$title,					$customized_title);
		
		return $customized_title;
	}

	//add toc of series
	public function the_content( $content ) {
		$posts_series = self::$posts_series_db->find_by_post_id( get_the_ID() );
		if ($posts_series == null) {
			return $content;
		}

		$series = self::$series_db->get_series( $posts_series->series_id );
		if (($series == null) || ($series->status != Easy_Series_Db::STATUS_ACTIVE)) {
			return $content;
		}

		$content = $this->get_toc_html( $series, get_the_ID() ) . $content;

		return $content;
	}

	private function add_series() {
		$data = array(
			'title'					=> $_POST[self::PLUGIN_PREFIX . 'title'],
			'number_of_times'		=> (empty( $_POST[self::PLUGIN_PREFIX . 'number_of_times'] ))? 0 : $_POST[self::PLUGIN_PREFIX . 'number_of_times'],
			'status'				=> $_POST[self::PLUGIN_PREFIX . 'status'],
			'title_format'			=> $_POST[self::PLUGIN_PREFIX . 'title-format'],
			'list_title_format'		=> $_POST[self::PLUGIN_PREFIX . 'list-title-format'],
		);

		$id = self::$series_db->create( $data );

		wp_redirect( $this->get_settings_top_url() );
	}

	private function update_series( $id ) {
		$data = array(
			'title'				=> $_POST[self::PLUGIN_PREFIX . 'title'],
			'number_of_times'	=> empty( $_POST[self::PLUGIN_PREFIX . 'number_of_times'] )? 0 : $_POST[self::PLUGIN_PREFIX . 'number_of_times'],
			'status'			=> $_POST[self::PLUGIN_PREFIX . 'status'],
			'title_format'			=> $_POST[self::PLUGIN_PREFIX . 'title-format'],
			'list_title_format'		=> $_POST[self::PLUGIN_PREFIX . 'list-title-format'],
		);

		self::$series_db->update( $id, $data );

		wp_redirect( $this->get_settings_top_url() );
	}

	private function delete_series( $id ) {
		$series = self::$series_db->get_series( $id );

		if ($series == null) {
			return;
		}

		self::$series_db->delete( $id );
		self::$posts_series_db->delete_by_series_id( $id );

		wp_redirect( $this->get_settings_top_url() );
	}

	private function settings_top_page() {
		$series_list = self::$series_db->get_series_list();

		$series_list_sorted = array();

		foreach ( $series_list as $series ) {
			$posts_series = self::$posts_series_db->find_by_series_id( $series->id );

			$latest_post_date = '';
			$numbers = array();
			
			foreach ($posts_series as $post_series) {
				$post = get_post( $post_series->post_id );
				if ($post == null) {
					continue;
				}

				if (($post->post_date != null) && 
					(($post->post_status == 'publish') || ($post->post_status == 'future'))) {
					
					if (strcmp( $post->post_date, $latest_post_date ) > 0) {
						$latest_post_date = $post->post_date;
					}
				}

				$numbers[] = $post_series->number;
			}

			$series->latest_post_date = $latest_post_date;
			$series->numbers = $numbers;
		}

		//sort by latest_post_dat
		usort($series_list, "Easy_Series::cmp_by_latest_post_date");

		//sort by status
		$active_series_list = array();
		$inactive_series_list = array();

		foreach ( $series_list as $series ) {
			if ($series->status == Easy_Series_Db::STATUS_ACTIVE) {
				$active_series_list[] = $series;
			} else {
				$inactive_series_list[] = $series;
			}
		}

		$series_list = array_merge( $active_series_list, $inactive_series_list );

?>
		<div>
			<h1>連載</h1>
			<h2>連載一覧</h2>

			<button type="button" class="button" onclick="location.href='<?php echo $this->get_settings_url('form'); ?>'">追加</button>

			<table class="wp-list-table widefat fixed striped">
				<thead> 
					<th>タイトル</th>
					<th>回数</th>
					<th>作成済み回</th>
					<th>最新の公開日時</th>
					<th>状態</th>
					<th>操作</th>

				</thead>
				<tbody>
					<?php foreach ( $series_list as $series ) { 

						$number_of_times = ($series->number_of_times == 0)? '未定' : $series->number_of_times;

						if ($series->latest_post_date == '') {
							$latest_post_date = '';
						} else {
							$latest_post_date = get_date_from_gmt( 
								get_gmt_from_date( $series->latest_post_date ), 
								get_option( 'date_format' ) . ' ' . get_option( 'time_format' )
							);
						}

?>
						<tr>
							<td><?php echo( esc_html( $series->title ) ); ?></td>
							<td><?php echo( esc_html( $number_of_times ) ); ?></td>
							<td><?php echo( esc_html( join(', ', $series->numbers ) ) ); ?></td>
							<td><?php echo( esc_html( $latest_post_date ) ); ?></td>
							<td><?php echo( esc_html( $this->get_status_name( $series->status ) ) ); ?></td>
							<td>
								<form method="get" style="float:left; margin-right:5px; margin-bottom:5px;">
									<input type="submit" class="button" value="編集">
									<input type="hidden" name="page" value="<?php echo( esc_attr( basename( __FILE__ ) ) ); ?>">
									<input type="hidden" name="<?php echo( self::PLUGIN_PREFIX ); ?>page" value="form">
									<input type="hidden" name="<?php echo( self::PLUGIN_PREFIX ); ?>id" value="<?php echo( esc_attr( $series->id ) ); ?>">
								</form>
								<form method="post">
									<span>
									<input type="submit" name="<?php echo( self::PLUGIN_PREFIX ); ?>delete" class="button" value="削除" onclick="return confirm('削除しますか？')">
									</span>
									<input type="hidden" name="<?php echo( self::PLUGIN_PREFIX ); ?>id" value="<?php echo( esc_attr( $series->id ) ); ?>">
								</form>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
<?php		
	}

	private  function settings_form_page() {
		$is_edit = isset( $_GET[self::PLUGIN_PREFIX . 'id'] );
		if ($is_edit) {
			$id = $_GET[self::PLUGIN_PREFIX . 'id'];
			$series = self::$series_db->get_series( $id );

			$title = $series->title;
			$number_of_times = ($series->number_of_times == 0)? '' : $series->number_of_times;
			$status = $series->status;

			$title_format = $series->title_format;
			$list_title_format = $series->list_title_format;			


			$submit_name = self::PLUGIN_PREFIX . 'edit';
			$sub_title = '連載の編集';
		} else { //new
			$id = '';

			$title = '';
			$number_of_times = '';
			$status = Easy_Series_Db::STATUS_ACTIVE;

			$title_format = $this->get_default_title_format();
			$list_title_format = $this->get_default_list_title_format();

			$submit_name = self::PLUGIN_PREFIX . 'add';
			$sub_title = '連載の追加';
		}
?>
		<div>
			<h1>連載</h1>
			<h2><?php echo( esc_html( $sub_title ) ) ?></h2>

			<form method="post">
				<input type="hidden" name="<?php echo self::PLUGIN_PREFIX; ?>id" value="<?php echo( esc_attr( $id ) ) ?>" /> 
				<table class="form-table">
					<tr>
						<th>タイトル</th>
						<td>(必須)</td>
						<td>
							<input type="text" class="regular-text" required="required" name="<?php echo self::PLUGIN_PREFIX; ?>title" value="<?php echo( esc_attr( $title ) ) ?>" />
						</td>
					</tr>

					<tr>
						<th>回数</th>
						<td></td>
						<td>
							<input type="number" class="regular-text" name="<?php echo self::PLUGIN_PREFIX; ?>number_of_times" value="<?php echo( esc_attr( $number_of_times ) ) ?>" />
						</td>
					</tr>

					<tr>
						<th>状態</th>
						<td></td>
						<td>
<?php if ($status == Easy_Series_Db::STATUS_ACTIVE) { ?>
							<input type="radio" name="<?php echo self::PLUGIN_PREFIX; ?>status" value="<?php echo esc_attr( Easy_Series_Db::STATUS_ACTIVE ) ?>" checked="checked" />
							<?php echo esc_html( $this->get_status_name( Easy_Series_Db::STATUS_ACTIVE ) ) ?>
							
							<input type="radio" name="<?php echo self::PLUGIN_PREFIX; ?>status" value="<?php echo esc_attr( Easy_Series_Db::STATUS_INACTIVE ) ?>" />
							<?php echo esc_html( $this->get_status_name( Easy_Series_Db::STATUS_INACTIVE ) ) ?>
<?php } else { ?>
							<input type="radio" name="<?php echo self::PLUGIN_PREFIX; ?>status" value="<?php echo esc_attr( Easy_Series_Db::STATUS_ACTIVE ) ?>" />
							<?php echo esc_html( $this->get_status_name( Easy_Series_Db::STATUS_ACTIVE ) ) ?>
							
							<input type="radio" name="<?php echo self::PLUGIN_PREFIX; ?>status" value="<?php echo esc_attr( Easy_Series_Db::STATUS_INACTIVE ) ?>" checked="checked" />
							<?php echo esc_html( $this->get_status_name( Easy_Series_Db::STATUS_INACTIVE ) ) ?>
<?php } ?>
						</td>
					</tr>				

					<tr>
						<th>記事タイトルのフォーマット</th>
						<td></td>
						<td>
							<textarea style="width:75%" name="<?php echo self::PLUGIN_PREFIX; ?>title-format"  /><?php echo esc_html( $title_format ) ?></textarea>
							<br>デフォルト : <?php echo $this->get_default_title_format() ?>
						</td>
					</tr>

					<tr>
						<th>連載記事一覧のタイトルのフォーマット</th>
						<td></td>
						<td>
							<textarea style="width:75%" name="<?php echo self::PLUGIN_PREFIX; ?>list-title-format" /><?php echo esc_html( $list_title_format ) ?></textarea>
							<br>デフォルト : <?php echo $this->get_default_list_title_format() ?>
						</td>
					</tr>


				</table>
				<p class="submit">
					<input name="<?php echo( esc_attr( $submit_name ) ) ?>" type="submit" class="button" value="保存" />
				</p>
				<a href="<?php echo( esc_url( $this->get_settings_top_url() ) ); ?>">キャンセル</a>			
			</form>

		</div>
<?php		
	}

	private function get_toc_html( $series, $post_id ) {
		if ($series == null) {
			return '';
		}

		if ($series->status != Easy_Series_Db::STATUS_ACTIVE) {
			return '';
		}

		$posts_series = self::$posts_series_db->find_by_series_id( $series->id );

?>
		<div class="<?php echo self::PLUGIN_PREFIX; ?>toc">
			<table class="<?php echo self::PLUGIN_PREFIX; ?>toc-table">
				<thead>
					<tr>
						<th>[連載] <?php echo( esc_html( $series->title ) ); ?></th>
						<th>公開日</th>
					</tr>
				</thead>
				<tbody>
<?php				
		foreach ($posts_series as $post_series) {
			$post = get_post( $post_series->post_id );
			if ($post == null) {
				continue;
			}

			$title = '（その' . $post_series->number . '）' . $post->post_title;

			//title
			$format = $series->list_title_format;
			if (($format == null) || ($format == '')) {
				$format = $this->get_default_list_title_format();
			}

			$title = str_replace('%series_title%',	$series->title,			$format);
			$title = str_replace('%number%',		$post_series->number,	$title);
			$title = str_replace('%post_title%',	$post->post_title,		$title);

			if ($post->post_status == 'publish') {
				$public_date = get_the_date( get_option('date_format'), $post_series->post_id );
				if ($post_series->post_id == $post_id) {
					$link = '';
				} else {
					$link = get_permalink( $post_series->post_id );
				}
			} elseif ($post->post_status == 'future') {
				$public_date = get_the_date( get_option('date_format'), $post_series->post_id ) . '予定';
				$link = '';				
			} else {
				$public_date = '未公開';
				$link = '';	
			}
?>
					<tr>
						<td>
<?php if ($link == '') { ?>
							<?php echo( esc_html( $title ) ); ?>
<?php } else {?>
							<a href="<?php echo( esc_url( $link ) ); ?>"><?php echo( esc_html( $title ) ); ?></a>
<?php } ?>						
						</td>
						<td><?php echo( esc_html( $public_date ) ); ?></td>
					<tr>
<?php } ?>
				</tbody>
			</table>
		</div>

<?php
	}

	private function get_settings_url( $page ) {
		return $this->get_settings_top_url() . '&' . self::PLUGIN_PREFIX . 'page=' . $page;
	}

	private function get_settings_top_url() {
		$exploded = explode( "?", $_SERVER["REQUEST_URI"] );

		$url = $exploded[0];

		parse_str( $_SERVER['QUERY_STRING'], $query_array );
		
		if ( isset( $query_array[self::PLUGIN_PREFIX . "page"] ) ) {
			unset( $query_array[self::PLUGIN_PREFIX . "page"] );
		}
		if ( isset( $query_array[self::PLUGIN_PREFIX . "id"] ) ) {
			unset( $query_array[self::PLUGIN_PREFIX . "id"] );
		}
		
		$url = $url . "?" . http_build_query( $query_array );
		
		return $url;		
	}

	private function get_default_title_format() {
		return '%post_title% 〜 %series_title%（その%number%）';
	}

	private function get_default_list_title_format() {
		return '（その%number%）%post_title%';
	}

	public static function cmp_by_latest_post_date($a, $b) {
		return strcmp($b->latest_post_date, $a->latest_post_date);
	}
}

new Easy_Series();