<?php
/**
 * Emails Catch All parts.
 *
 * @package eca
 */

$use_network     = self::use_network();
$is_compact_view = self::$settings->compact_view;
if ( $use_network ) {
	$options = get_network_option( get_current_network_id(), self::$option_name, [] );
	if ( isset( $options['compact_view'] ) ) {
		$is_compact_view = $options['compact_view'];
	}
}

$is_ajax = filter_input( INPUT_POST, 'is_ajax', FILTER_DEFAULT );
$cp      = filter_input( INPUT_POST, 'cp', FILTER_SANITIZE_NUMBER_INT );
if ( empty( $cp ) ) {
	$cp = filter_input( INPUT_GET, 'cp', FILTER_SANITIZE_NUMBER_INT );
}

$cleanup = filter_input( INPUT_POST, 'cleanup', FILTER_DEFAULT );
if ( ! empty( $cleanup ) ) {
	self::emails_catch_all_cleanup_records( $cleanup );
}

// phpcs:disable
$search = filter_input( INPUT_POST, 'search', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
$search = wp_parse_args( $search, [
	'email' => '',
	'date'  => '',
	'text'  => '',
	'blog'  => '',
] );
$search = array_map( 'trim', $search );
// phpcs:enable

$cp = ( ! empty( $cp ) ) ? abs( (int) $cp ) : 1;
if ( $use_network ) {
	$t_query = 'SELECT count(id) as total FROM ' . self::$table . ' WHERE 1=%d ';
	$q_args  = [ 1 ];
} else {
	$t_query = 'SELECT count(id) as total FROM ' . self::$table . ' WHERE 1=%d and blog_id=%d ';
	$q_args  = [ 1, get_current_blog_id() ];
}
if ( ! empty( $search['date'] ) ) {
	$t_query .= ' AND ( `date` >= %d AND `date` <= %d )';
	$q_args[] = strtotime( $search['date'] );
	$q_args[] = strtotime( $search['date'] ) + DAY_IN_SECONDS - 1;
}
if ( ! empty( $search['email'] ) ) {
	$t_query .= ' AND ( `initial` LIKE %s OR `final` LIKE %s )';
	$q_args[] = '%' . $wpdb->esc_like( $search['email'] ) . '%';
}
if ( ! empty( $search['text'] ) ) {
	$t_query .= ' AND ( `all` LIKE %s )';
	$q_args[] = '%' . $wpdb->esc_like( $search['text'] ) . '%';
}
if ( ! empty( $search['blog'] ) ) {
	$t_query .= ' AND ( `blog_id` = %d )';
	$q_args[] = $search['blog'];
}

// phpcs:disable
$total = $wpdb->get_results( $wpdb->prepare( $t_query, $q_args ) ); // phpcs:ignore
$total = ! empty( $total[0]->total ) ? (int) $total[0]->total : 0;
$perp  = self::$records_per_page;
$pages = ceil( $total / $perp );
$pages = ( ! empty( $pages ) ) ? abs( (int) $pages ) : 1;
if ( $cp > $pages ) {
	$cp = $pages;
}
// phpcs:enable

$offset = ( $cp - 1 ) * $perp;
if ( $use_network ) {
	$query  = 'SELECT * FROM ' . self::$table . ' WHERE 1=1 ';
	$q_args = [];
} else {
	$query  = 'SELECT * FROM ' . self::$table . ' WHERE 1=1 and blog_id=%d ';
	$q_args = [ get_current_blog_id() ];
}
if ( ! empty( $search['date'] ) ) {
	$query   .= ' AND ( `date` >= %d AND `date` <= %d )';
	$q_args[] = strtotime( $search['date'] );
	$q_args[] = strtotime( $search['date'] ) + DAY_IN_SECONDS - 1;
}
if ( ! empty( $search['email'] ) ) {
	$query   .= ' AND ( `all` LIKE %s )';
	$q_args[] = '%' . $wpdb->esc_like( $search['email'] ) . '%';
}
if ( ! empty( $search['text'] ) ) {
	$query   .= ' AND ( `all` LIKE %s )';
	$q_args[] = '%' . $wpdb->esc_like( $search['text'] ) . '%';
}
if ( ! empty( $search['blog'] ) ) {
	$query   .= ' AND ( `blog_id` = %d )';
	$q_args[] = $search['blog'];
}
$query   .= ' ORDER BY date DESC LIMIT %d,%d';
$q_args[] = $offset;
$q_args[] = $perp;

$records = $wpdb->get_results( $wpdb->prepare( $query, $q_args ) ); // phpcs:ignore

$date_format = get_option( 'date_format' );
$time_format = get_option( 'time_format' );
$pagination  = self::history_pagination( $pages, $cp, $total );
if ( ! empty( $pagination ) ) {
	echo '<hr><div class="secas-list">' . $pagination . '</div>'; // phpcs:ignore
}

$def = [
	'date'    => 0,
	'initial' => [
		'to'          => '',
		'subject'     => '',
		'message'     => '',
		'headers'     => [],
		'attachments' => [],
		'cc'          => '',
		'bcc'         => '',
	],
	'final'   => [
		'to'          => '',
		'subject'     => '',
		'message'     => '',
		'headers'     => [],
		'attachments' => [],
		'cc'          => '',
		'bcc'         => '',
	],
	'type'    => '',
];

$uploads_dir  = wp_upload_dir();
$uploads_root = ( ! empty( $uploads_dir['basedir'] ) ) ? $uploads_dir['basedir'] : '';
?>

<table class="wp-list-table striped widefat fixed pages">
	<thead>
		<tr>
			<td width="160"><b><?php esc_html_e( 'Date', 'secas' ); ?></b></td>
			<?php if ( $use_network ) : ?>
				<td width="160"><b><?php esc_html_e( 'Blog', 'secas' ); ?></b></td>
			<?php endif; ?>
			<td width="16%"><b><?php esc_html_e( 'From', 'secas' ); ?></b></td>
			<td width="16%"><b><?php esc_html_e( 'Intended Recipient', 'secas' ); ?></b></td>
			<td width="16%"><b><?php esc_html_e( 'Final Recipient', 'secas' ); ?></b></td>
			<td><b><?php esc_html_e( 'Subject', 'secas' ); ?> / <?php esc_html_e( 'Message', 'secas' ); ?></b></td>
			<td width="80"></td>
		</tr>
	</thead>
	<thead id="secas-search-wrap">
		<tr>
			<td>
				<label for="secas_search_date" class="screen-reader-text"><?php esc_html_e( 'Date', 'secas' ); ?></label>
				<input type="date" name="secas_search[date]"
				id="secas_search_date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}"
				value="<?php echo esc_attr( $search['date'] ); ?>">
			</td>
			<?php if ( $use_network ) : ?>
				<td>
					<label for="secas_search_blog" class="screen-reader-text"><?php esc_html_e( 'Blog', 'secas' ); ?></label>
					<select name="secas_search[blog]" id="secas_search_blog">
						<option value=""><?php esc_html_e( 'Any', 'secas' ); ?></option>
						<?php
						$sites_list = get_sites( [ 'network_id' => get_current_network_id() ] );
						if ( ! empty( $sites_list ) ) {
							foreach ( $sites_list as $site ) {
								$site_info = get_blog_details( [ 'blog_id' => $site->blog_id ] )
								?>
								<option value="<?php echo (int) $site->blog_id; ?>"
									<?php selected( (int) $site->blog_id, (int) $search['blog'] ); ?>><?php echo esc_html( $site_info->blogname . ' (' . (int) $site->blog_id . ')' ); ?></option>
								<?php
							}
						}
						?>
					</select>
				</td>
			<?php endif; ?>
			<td colspan="2">
				<input type="text" name="secas_search[email]" id="secas_search_email"
				placeholder="<?php esc_attr_e( 'Intended or final recipient email address', 'secas' ); ?>"
				value="<?php echo esc_attr( $search['email'] ); ?>">
			</td>
			<td colspan="2">
				<input type="text" name="secas_search[text]" id="secas_search_text"
				placeholder="<?php esc_attr_e( 'Subject or content of the message', 'secas' ); ?>"
				value="<?php echo esc_attr( $search['text'] ); ?>">
			</td>
			<td>
				<div class="as-box v-middle">
					<a href="javascript:void(0);" class="button as-icon" id="secas_search" title="<?php esc_html_e( 'Search', 'secas' ); ?>"><span class="dashicons dashicons-search"></span></a>
					<a href="javascript:void(0);" class="button as-icon" id="secas_reset_search" title="<?php esc_html_e( 'Reset', 'secas' ); ?>"><span class="dashicons dashicons-no"></span></a>
				</div>
			</td>
		</tr>
	</thead>
	<tbody>
		<?php if ( empty( $records ) ) : ?>
			<?php if ( $use_network && ! empty( $search['blog'] ) ) : ?>
				<tr>
					<td colspan="6">
						<p><?php esc_html_e( 'No recorded history for the selected site.', 'secas' ); ?></p>
					</td>
				</tr>
			<?php else : ?>
				<tr>
					<td colspan="6">
						<p><?php esc_html_e( 'No recorded history.', 'secas' ); ?></p>
					</td>
				</tr>
			<?php endif; ?>
		<?php else : ?>
			<?php wp_nonce_field( '_emails_history_rows_save', '_emails_history_rows_nonce' ); ?>
			<?php wp_nonce_field( '_emails_history_rows_save', '_emails_history_rows_nonce' ); ?>
			<?php foreach ( $records as $i => $item ) : ?>
				<?php
				$row = maybe_unserialize( $item->all );
				$row = wp_parse_args( $row, $def );

				$initial_receiver = '<div class="info"><b class="label to">' . esc_html__( 'TO', 'secas' ) . '</b><div>' . str_replace( ',', ', ', $row['initial']['to'] ) . '</div></div>';
				if ( ! empty( $row['initial']['cc'] ) ) {
					$initial_receiver .= '<div class="info"><b class="label cc">' . esc_html__( 'CC', 'secas' ) . '</b><div>' . str_replace( 'cc: ', '', str_replace( ',', ', ', strtolower( $row['initial']['cc'] ) ) ) . '</div></div>';
				}
				if ( ! empty( $row['initial']['bcc'] ) ) {
					$initial_receiver .= '<div class="info"><b class="label bcc">' . esc_html__( 'BCC', 'secas' ) . '</b><div>' . str_replace( 'bcc: ', '', str_replace( ',', ', ', strtolower( $row['initial']['bcc'] ) ) ) . '</div></div>';
				}

				$row['final']['to'] = ( ! empty( $row['final']['to'] ) ) ? $row['final']['to'] : '';
				$row['final']['to'] = ( ! empty( $row['final']['bcc'] ) ) ? $row['final']['to'] . ', ' . $row['final']['bcc'] : $row['final']['to'];
				if ( is_array( $row['final']['to'] ) ) {
					$row['final']['to'] = implode( ',', $row['final']['to'] );
				}
				$row['final']['to'] = str_replace( ',', ', ', $row['final']['to'] );
				$row['final']['to'] = ( ! empty( $row['final']['to'] ) ) ? ltrim( $row['final']['to'], ',' ) : ' ';

				$counter = ( $cp - 1 ) * (int) self::$records_per_page + $i + 1;

				$message = ! empty( $row['final']['message'] ) ? self::cleaned_message( $row['final']['message'] ) : '';

				$message = ! empty( $message ) ? preg_replace( '/^(<br\s*\/?>)*|(<br\s*\/?>)*$/i', '', wp_kses_post( $message ) ) : '';


				$class_to = 'replace' === $row['type'] || 'disable' === $row['type'] ? 'is-strike' : '';
				?>
				<tr id="history_row_<?php echo (int) $item->id; ?>">
					<td data-colname="<?php esc_attr_e( 'Date', 'secas' ); ?>">
						<div class="as-row nowrap v-auto v-top small-gap">
							<a href="javascript:void(0);" class="button as-icon button-item secas-cleanup"
								data-cleanid="<?php echo (int) $item->id; ?>"
								data-cleanpag="<?php echo (int) $cp; ?>"
								title="<?php esc_attr_e( 'Delete', 'secas' ); ?>">
								<span class="dashicons dashicons-trash"></span>
							</a>
							<span class="as-date">
								<?php echo wp_kses_post( date_i18n( $date_format . '\<\b\r\>' . $time_format, $row['date'] ) ); ?>
							</span>
						</div>
					</td>
					<?php if ( $use_network ) : ?>
						<td data-colname="<?php esc_attr_e( 'Blog', 'secas' ); ?>">
							<div class="bg-blog bg-blog<?php echo (int) $item->blog_id; ?>"><?php echo (int) $item->blog_id; ?></div>
						</td>
					<?php endif; ?>
					<td data-colname="<?php esc_attr_e( 'From', 'secas' ); ?>">
						<?php if ( ! empty( $item->from ) ) : ?>
							<div class="info">
								<b class="label"><?php esc_html_e( 'From', 'secas' ); ?></b>
								<div class="with-break">
									<?php $f = maybe_unserialize( $item->from ); ?>
									<?php echo esc_html( $f['email'] ); ?>
									<div><?php echo esc_html( $f['name'] ); ?></div>
								</div>
							</div>
						<?php endif; ?>
					</td>
					<td data-colname="<?php esc_attr_e( 'Intended Recipient', 'secas' ); ?>" class="<?php echo esc_attr( $class_to ); ?>">
						<?php echo wp_kses_post( $initial_receiver ); ?>
					</td>
					<td data-colname="<?php esc_attr_e( 'Final Recipient', 'secas' ); ?>">
						<?php
						if ( ! empty( $row['final']['to'] ) ) {
							?>
							<div class="info">
								<b class="label"><?php esc_attr_e( 'Final', 'secas' ); ?></b>
								<div class="with-break">
									<div><?php echo esc_html( $row['final']['to'] ); ?></div>
								</div>
							</div>
							<?php
						}
						?>
						<div class="as-row nowrap small-gap v-middle">
							<?php
							$type = empty( $row['type'] ) ? 'record' : $row['type']; // phpcs:ignore
							echo self::$settings->icons_list[ $type ]; // phpcs:ignore
							?>
							<div>
								<?php esc_html_e( 'Type', 'secas' ); ?>: <b><?php echo esc_html( $type ); ?></b>
							</div>
						</div>
					</td>
					<td colspan="2" data-colname="<?php esc_attr_e( 'Subject', 'secas' ); ?> / <?php esc_attr_e( 'Message', 'secas' ); ?>">
						<?php
						$text = wp_kses_post( wpautop( $message ) );
						if ( true === $is_compact_view ) {
							$small = wp_trim_words( wp_strip_all_tags( $text ), 15 );
							?>
							<details>
								<summary>
									<h3><?php echo ( ! empty( $row['final']['subject'] ) ) ? esc_html( $row['final']['subject'] ) : ''; ?></h3>
									<div><?php echo wp_kses_post( $small ); ?></div>
								</summary>
							<?php
						} else {
							?>
							<h3><?php echo ( ! empty( $row['final']['subject'] ) ) ? esc_html( $row['final']['subject'] ) : ''; ?></h3>
							<?php
						}
						?>

						<?php
						if ( ! empty( $row['final']['attachments'] ) ) {
							$attachments = '<ul><li><span class="dashicons dashicons-paperclip"></span> ' . implode( '</li><li><span class="dashicons dashicons-paperclip"></span> ', $row['final']['attachments'] ) . '</li></ul>';
							$attachments = str_replace( $uploads_root, '', $attachments );
							echo wp_kses_post( $attachments );
						}
						?>
						<div class="secas-body">
							<?php echo $text; // phpcs:ignore ?>
						</div>
						<?php
						if ( true === $is_compact_view ) {
							?>
							</details>
							<?php
						}
						?>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>

<?php
if ( ! empty( $pagination ) ) {
	$pagination = str_replace( 'secas_refresh', 'secas_refresh2', $pagination );
	echo '<div class="secas-list">' . $pagination . '</div>'; // phpcs:ignore
}

if ( ! empty( $is_ajax ) ) {
	die();
}
