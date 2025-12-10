<?php
/**
 * Emails Catch All parts.
 *
 * @package eca
 */

?>

<div class="as-grid flat border cols-4">
	<div>
		<h2><?php esc_html_e( 'SMTP Auth', 'secas' ); ?></h2>
		<div class="as-box">
			<label>
				<input type="radio" name="_secas_settings[smtp_auth]" id="_secas_settings_smtp_auth_1" value="1" <?php checked( true, self::$settings->smtp_auth, true ); ?>>
				<?php esc_html_e( 'Yes', 'secas' ); ?>
			</label>
			<label>
				<input type="radio" name="_secas_settings[smtp_auth]" id="_secas_settings_smtp_auth_0" value="0" <?php checked( false, self::$settings->smtp_auth, true ); ?>>
				<?php esc_html_e( 'No', 'secas' ); ?>
			</label>
		</div>
		<p><?php esc_html_e( 'Enable SMTP authentication', 'secas' ); ?></p>
	</div>

	<div>
		<h2><?php esc_html_e( 'Host', 'secas' ); ?></h2>
		<label for="_secas_settings_smtp_host" class="screen-reader-text"><?php esc_html_e( 'Host', 'secas' ); ?></label>
		<input type="text" name="_secas_settings[smtp_host]" id="_secas_settings_smtp_host" value="<?php echo esc_attr( self::$settings->smtp_host ); ?>">
		<p><?php esc_html_e( 'Specify the SMTP servers (separate with ; if you have more, for example: smtp1.dom.com;smtp2.dom.com)', 'secas' ); ?></p>
	</div>

	<div>
		<h2><?php esc_html_e( 'Port', 'secas' ); ?></h2>
		<label for="_secas_settings_smtp_port" class="screen-reader-text"><?php esc_html_e( 'Port', 'secas' ); ?></label>
		<input type="text" name="_secas_settings[smtp_port]" id="_secas_settings_smtp_port" value="<?php echo esc_attr( self::$settings->smtp_port ); ?>">
		<p><?php esc_html_e( 'The TCP port to connect to', 'secas' ); ?></p>
	</div>

	<div>
		<h2><?php esc_html_e( 'Username', 'secas' ); ?></h2>
		<label for="_secas_settings_smtp_uname" class="screen-reader-text"><?php esc_html_e( 'Username', 'secas' ); ?></label>
		<input type="text" name="_secas_settings[smtp_uname]" id="_secas_settings_smtp_uname" value="<?php echo esc_attr( self::$settings->smtp_uname ); ?>" autocomplete="new-password">
		<p><?php esc_html_e( 'SMTP username (for example user@yourdomain.com)', 'secas' ); ?></p>
	</div>

	<div>
		<h2><?php esc_html_e( 'Password', 'secas' ); ?></h2>
		<label for="_secas_settings_smtp_upass" class="screen-reader-text"><?php esc_html_e( 'Password', 'secas' ); ?></label>
		<input type="password" name="_secas_settings[smtp_upass]" id="_secas_settings_smtp_upass" value="<?php echo esc_attr( self::$settings->smtp_upass ); ?>" autocomplete="new-password">
		<p><?php esc_html_e( 'SMTP password', 'secas' ); ?></p>
	</div>

	<div>
		<h2><?php esc_html_e( 'SMTP Secure', 'secas' ); ?></h2>
		<div class="as-box">
			<label>
				<input type="radio" name="_secas_settings[smtp_secure]" id="_secas_settings_smtp_secure_tls" value="tls" <?php checked( 'tls', self::$settings->smtp_secure, true ); ?>>
				<?php esc_html_e( 'TLS', 'secas' ); ?>
			</label>
			<label>
				<input type="radio" name="_secas_settings[smtp_secure]" id="_secas_settings_smtp_secure_ssl" value="ssl" <?php checked( 'ssl', self::$settings->smtp_secure, true ); ?>>
				<?php esc_html_e( 'SSL', 'secas' ); ?>
			</label>
			<label>
				<input type="radio" name="_secas_settings[smtp_secure]" id="_secas_settings_smtp_secure_" value="" <?php checked( '', self::$settings->smtp_secure, true ); ?>>
				<?php esc_html_e( 'N/A', 'secas' ); ?>
			</label>
		</div>
		<p><?php esc_html_e( 'Enable TLS or SSL encryption (use N/A if you are not sure what option to select)', 'secas' ); ?></p>
	</div>

	<div>
		<h2><?php esc_html_e( 'From Email', 'secas' ); ?></h2>
		<label for="_secas_settings_smtp_from" class="screen-reader-text"><?php esc_html_e( 'From Email', 'secas' ); ?></label>
		<input type="email" name="_secas_settings[smtp_from]" id="_secas_settings_smtp_from" value="<?php echo esc_attr( self::$settings->smtp_from ); ?>">
		<p><?php esc_html_e( 'The email address to be used as from for the emails', 'secas' ); ?></p>
	</div>
	<div>
		<h2><?php esc_html_e( 'From Name', 'secas' ); ?></h2>
		<label for="_secas_settings_smtp_from_name" class="screen-reader-text"><?php esc_html_e( 'From Name', 'secas' ); ?></label>
		<input type="text" name="_secas_settings[smtp_from_name]" id="_secas_settings_smtp_from_name" value="<?php echo esc_attr( self::$settings->smtp_from_name ); ?>">
		<p><?php esc_html_e( 'The name to be used as from for the emails', 'secas' ); ?></p>
	</div>
</div>

<?php submit_button( __( 'Update Settings', 'secas' ), 'primary', 'save-settings-2' ); ?>
