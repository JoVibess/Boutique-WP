<?php
/**
 * Emails Catch All parts.
 *
 * @package eca
 */

?>
<div class="menu-wrap">
	<div class="menu-items" tabindex="0">
		<a href="<?php echo esc_url( $url ); ?>"
			class="button<?php echo esc_attr( '' === $tab ? ' button-primary' : '' ); ?>">
			<span class="dashicons dashicons-editor-ul"></span>
			<?php esc_html_e( 'Emails Log', 'secas' ); ?>
		</a>
		<?php if ( empty( self::$settings->just_record ) ) : ?>
			<a href="<?php echo esc_url( $url . '&tab=settings' ); ?>"
				class="button<?php echo esc_attr( 'settings' === $tab ? ' button-primary' : '' ); ?>">
				<?php echo self::$settings->icon; // phpcs:ignore ?>
				<?php esc_html_e( 'General Settings', 'secas' ); ?>
			</a>
			<?php if ( ! empty( self::$settings->email ) ) : ?>
				<a href="<?php echo esc_url( $url . '&tab=smtp' ); ?>"
					class="button<?php echo esc_attr( 'smtp' === $tab ? ' button-primary' : '' ); ?>">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php esc_html_e( 'SMTP Settings', 'secas' ); ?>
				</a>
			<?php else : ?>
				<a href="#" class="button disabled">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php esc_html_e( 'SMTP Settings', 'secas' ); ?>
				</a>
			<?php endif; ?>
		<?php else : ?>
			<a href="#" class="button disabled">
				<?php echo self::$settings->icon; // phpcs:ignore ?>
				<?php esc_html_e( 'General Settings', 'secas' ); ?>
			</a>
			<a href="#" class="button disabled">
				<span class="dashicons dashicons-admin-generic"></span>
				<?php esc_html_e( 'SMTP Settings', 'secas' ); ?>
			</a>
		<?php endif; ?>
		<?php if ( ECA_NETWORK_SCREEN ) : ?>
			<a href="<?php echo esc_url( $url . '&tab=network' ); ?>"
				class="button<?php echo esc_attr( 'network' === $tab ? ' button-primary' : '' ); ?>">
				<span class="dashicons dashicons-admin-multisite"></span>
				<?php esc_html_e( 'Sites Settings', 'secas' ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
