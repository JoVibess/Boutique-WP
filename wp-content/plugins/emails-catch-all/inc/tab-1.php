<?php
/**
 * Emails Catch All parts.
 *
 * @package eca
 */

?>
<div class="as-grid flat border cols-4">
	<div>
		<h2><?php esc_html_e( 'Recipients', 'secas' ); ?></h2>
		<label for="_secas_settings_email" class="screen-reader-text"><?php esc_html_e( 'Recipient email address', 'secas' ); ?></label>
		<input type="text" name="_secas_settings[email]" id="_secas_settings_email" value="<?php echo esc_attr( self::$settings->email ); ?>" autocomplete="new-recipient" required="required">
		<p>
			<?php esc_html_e( 'The email address (separate with , if you have more) that can receive a copy or replace the recipients of all the emails sent from the site', 'secas' ); ?>
		</p>
	</div>

	<div class="span-3">
		<h2><?php esc_html_e( 'Recipient Type', 'secas' ); ?></h2>

		<label class="recipient-type">
			<input type="radio"
				name="_secas_settings[recipient]"
				id="_secas_settings_none"
				value=""
				<?php checked( '', self::$settings->recipient, true ); ?>/>
			<?php echo self::$settings->icons_list['']; // phpcs:ignore ?>
			<b><?php esc_html_e( 'default', 'secas' ); ?></b>
		</label>
		<p class="hints">
			<?php esc_html_e( 'The default recipients of the messages, without any custom changes', 'secas' ); ?>
		</p>

		<label class="recipient-type">
			<input type="radio"
				name="_secas_settings[recipient]"
				id="_secas_settings_receive"
				value="receive"
				<?php checked( 'receive', self::$settings->recipient, true ); ?>/>
			<?php echo self::$settings->icons_list['receive']; // phpcs:ignore ?>
			<b><?php esc_html_e( 'receive a copy', 'secas' ); ?></b>
		</label>
		<p class="hints">
			<?php esc_html_e( 'The emails will be sent to the intended recipients as normal + a copy of all emails will be sent also to the catch all email address specified', 'secas' ); ?>
		</p>

		<label class="recipient-type">
			<input type="radio"
				name="_secas_settings[recipient]"
				id="_secas_settings_replace"
				value="replace"
				<?php checked( 'replace', self::$settings->recipient, true ); ?>/>
			<?php echo self::$settings->icons_list['replace']; // phpcs:ignore ?>
			<b><?php esc_html_e( 'replace all recipients', 'secas' ); ?></b>
		</label>
		<p class="hints">
			<?php esc_html_e( 'The emails will be sent only to the catch all email address you specified, the intended recipient will not receive the emails', 'secas' ); ?>
		</p>

		<label class="recipient-type">
			<input type="radio"
				name="_secas_settings[recipient]"
				id="_secas_settings_disable"
				value="disable"
				<?php checked( 'disable', self::$settings->recipient, true ); ?>/>
			<?php echo self::$settings->icons_list['disable']; // phpcs:ignore ?>
			<b><?php esc_html_e( 'disable all emails', 'secas' ); ?></b>
		</label>
		<p class="hints">
			<?php esc_html_e( 'The emails will not be sent', 'secas' ); ?>
		</p>

		<?php if ( ECA_NETWORK_SCREEN ) : ?>
			<label class="recipient-type">
				<input type="radio"
					name="_secas_settings[recipient]"
					id="_secas_settings_disable"
					value="record"
					<?php checked( 'record', self::$settings->recipient, true ); ?>/>
				<?php echo self::$settings->icons_list['record']; // phpcs:ignore ?>
				<b><?php esc_html_e( 'Log only', 'secas' ); ?></b>
			</label>
			<p class="hints">
				<?php esc_html_e( 'The emails will only be recorded', 'secas' ); ?>
			</p>
		<?php endif; ?>
	</div>

	<div>
		<h2><?php esc_html_e( 'Content Type', 'secas' ); ?></h2>
		<div class="as-box">
			<label>
				<input type="radio"
					name="_secas_settings[content_type]"
					id="_secas_settings_content_type_auto"
					value="auto"
					<?php checked( 'auto', self::$settings->content_type, true ); ?>>
				<?php esc_html_e( 'Auto', 'secas' ); ?>
			</label>
			<label>
				<input type="radio"
					name="_secas_settings[content_type]"
					id="_secas_settings_content_type_plain"
					value="plain"
					<?php checked( 'plain', self::$settings->content_type, true ); ?>>
				<?php esc_html_e( 'Plain Text', 'secas' ); ?>
			</label>
			<label>
				<input type="radio"
					name="_secas_settings[content_type]"
					id="_secas_settings_content_type_html"
					value="html"
					<?php checked( 'html', self::$settings->content_type, true ); ?>>
				<?php esc_html_e( 'HTML', 'secas' ); ?>
			</label>
		</div>
		<p><?php esc_html_e( 'The content type for email sent through', 'secas' ); ?></p>
	</div>

	<div>
		<h2><?php esc_html_e( 'Record Emails', 'secas' ); ?></h2>
		<div class="as-box">
			<label>
				<input type="radio" name="_secas_settings[history]" id="_secas_settings_history_0"
					value="0" <?php checked( 0, (int) self::$settings->history, true ); ?>>
				<?php esc_html_e( 'No', 'secas' ); ?>
			</label>
			<label >
				<input type="radio" name="_secas_settings[history]" id="_secas_settings_history_1"
					value="1" <?php checked( 1, (int) self::$settings->history, true ); ?>>
				<?php esc_html_e( 'Yes', 'secas' ); ?>
			</label>
		</div>
		<p>
			<?php esc_html_e( 'Record the emails for later review', 'secas' ); ?>
		</p>
	</div>

	<div>
		<h2><?php esc_html_e( 'Cleanup Interval', 'secas' ); ?></h2>
		<label for="_secas_settings_auto_cleanup" class="screen-reader-text"><?php esc_html_e( 'Cleanup Interval', 'secas' ); ?></label>
		<select name="_secas_settings[auto_cleanup]" id="_secas_settings_auto_cleanup">
			<?php self::cleanup_records_options(); ?>
		</select>
		<p>
			<?php esc_html_e( 'Cleanup the emails from the history log after a number of days (recommended)', 'secas' ); ?>
		</p>
	</div>

	<?php if ( ! empty( self::$settings->email ) ) : ?>
		<div>
			<h2><?php esc_html_e( 'Test', 'secas' ); ?></h2>
			<p>
				<label for="_secas_settings_email_test" class="screen-reader-text"><?php esc_html_e( 'Test email address', 'secas' ); ?></label>
				<input type="text" name="_secas_settings[email_test]" id="_secas_settings_email_test" value="<?php echo esc_attr( self::$settings->email_test ); ?>" autocomplete="new-email-test">
			</p>
			<label class="label-row">
				<input type="checkbox" name="_secas_settings[test]" id="_secas_settings_test" value="">
				<?php esc_html_e( 'Test the settings', 'secas' ); ?>
			</label>
			<p><?php esc_html_e( 'Set up the recipient email (separate with , if you have more). Leave empty to use the admin email', 'secas' ); ?></p>
		</div>
	<?php endif; ?>

	<?php
	if ( file_exists( __DIR__ . '/dummy.php' ) ) {
		?>
		<div>
			<h2><?php esc_html_e( 'Dummy Records', 'secas' ); ?></h2>
			<label class="label-row">
				<input type="checkbox" name="_secas_settings[dummy]" id="_secas_settings_dummy" value="">
				<?php esc_html_e( 'Add random dummy data', 'secas' ); ?>
			</label>
			<p><?php esc_html_e( 'This is only for testing purpose, no emails are sent out', 'secas' ); ?></p>
		</div>
		<?php
	}
	?>
</div>

<?php submit_button( __( 'Update Settings', 'secas' ), 'primary', 'save-settings-1' ); ?>
