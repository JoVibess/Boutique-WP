<?php
/**
 * Emails Catch All parts.
 *
 * @package eca
 */

?>

<table class="wp-list-table striped widefat fixed pages">
	<thead>
		<tr>
			<td width="80"><b><?php esc_html_e( 'Blog', 'secas' ); ?></b></td>
			<td><b><?php esc_html_e( 'Name', 'secas' ); ?></b></td>
			<td><b><?php esc_html_e( 'URL', 'secas' ); ?></b></td>
			<td width="120" class="a-center"><b><?php esc_html_e( 'Enabled', 'secas' ); ?></b></td>
			<td width="120" class="a-center"><b><?php esc_html_e( 'Inherit', 'secas' ); ?></b></td>
			<td><b><?php esc_html_e( 'Summary', 'secas' ); ?></b></td>
			<td><b><?php esc_html_e( 'Site Settings', 'secas' ); ?></b></td>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( self::$settings->sites_options as $bid => $site ) : ?>
			<?php $site_info = get_blog_details( [ 'blog_id' => $bid ] ); ?>
			<tr>
				<td data-colname="<?php esc_attr_e( 'Blog', 'secas' ); ?>">
					<div class="bg-blog bg-blog<?php echo (int) $bid; ?>"><?php echo (int) $bid; ?></div>
				</td>
				<td data-colname="<?php esc_attr_e( 'Name', 'secas' ); ?>">
					<b><?php echo esc_html( $site_info->blogname ); ?></b>
				</td>
				<td data-colname="<?php esc_attr_e( 'URL', 'secas' ); ?>">
					<?php echo esc_html( $site_info->siteurl ); ?>
				</td>
				<td data-colname="<?php esc_attr_e( 'Enabled', 'secas' ); ?>" class="a-center">
					<label for="_secas_settings_sites_options_<?php echo (int) $bid; ?>_enabled" class="screen-reader-text"><?php esc_html_e( 'Enabled', 'secas' ); ?> <?php echo esc_html( $site_info->blogname ); ?></label>
					<input type="checkbox"
						name="_secas_settings[sites_options][<?php echo (int) $bid; ?>][enabled]"
						id="_secas_settings_sites_options_<?php echo (int) $bid; ?>_enabled"
						data-control="#_secas_settings_sites_options_<?php echo (int) $bid; ?>_inherit"
						class="change-control"
						<?php checked( true, ! empty( $site['enabled'] ) ); ?>
						>
				</td>
				<td data-colname="<?php esc_attr_e( 'Inherit', 'secas' ); ?>" class="a-center">
					<label for="_secas_settings_sites_options_<?php echo (int) $bid; ?>_inherit" class="screen-reader-text"><?php esc_html_e( 'Inherit', 'secas' ); ?> <?php echo esc_html( $site_info->blogname ); ?></label>
					<input type="checkbox"
						name="_secas_settings[sites_options][<?php echo (int) $bid; ?>][inherit]"
						id="_secas_settings_sites_options_<?php echo (int) $bid; ?>_inherit"
						<?php if ( empty( $site['enabled'] ) ) : ?>
						disabled="disabled"
						<?php endif; ?>
						<?php checked( true, ! empty( $site['inherit'] ) ); ?>
						>
				</td>
				<td data-colname="<?php esc_attr_e( 'Summary', 'secas' ); ?>">
					<div class="as-row">
						<?php
						$site_opt = self::get_the_blog_options( $bid );
						if ( ! empty( $site['enabled'] ) && ! empty( $site_opt->email ) ) {
							echo $site_opt->icon . ' <div>' . esc_html( $site_opt->email ) . '</div>'; // phpcs:ignore
						}
						?>
					</div>
				</td>
				<td data-colname="<?php esc_attr_e( 'Settings', 'secas' ); ?>">
					<?php if ( ! empty( $site['enabled'] ) && empty( $site['inherit'] ) ) : ?>
						<a href="<?php echo esc_url( $site_info->siteurl . '/wp-admin/' . self::$plugin_url ); ?>" class="button"><?php esc_html_e( 'Go to settings', 'secas' ); ?></a>
					<?php endif; ?>
					<?php if ( ! empty( $site['inherit'] ) ) : ?>
						<span class="small-text"><?php esc_html_e( 'inherits the network settings (no settings panel for the site)', 'secas' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach ?>
	</tbody>
</table>

<?php submit_button( __( 'Update Settings', 'secas' ), 'primary', 'save-settings-3' ); ?>
