<?php
/**
 * Emails Catch All parts.
 *
 * @package eca
 */

$use_reset = ( ! empty( self::$settings->just_record ) || ! empty( self::$settings->history ) );
?>

<div class="as-row flat border v-middle" style="--item-flex: 1 1 auto">
	<?php if ( ECA_NETWORK_SCREEN ) : ?>
		<label>
			<input type="radio"
				name="_secas_settings[just_record]"
				id="_secas_settings_just_record_0"
				value="0"
				disabled="disabled"
				<?php checked( true, true ); ?>/>
			<?php esc_html_e( 'More options', 'secas' ); ?>
		</label>
	<?php else : ?>
		<div class="as-row">
			<label>
				<input type="radio" name="_secas_settings[just_record]" id="_secas_settings_just_record_1"
					value="1" <?php checked( self::$settings->just_record, true ); ?>/>
				<?php esc_html_e( 'Log only', 'secas' ); ?>
			</label>
			<label>
				<input type="radio" name="_secas_settings[just_record]" id="_secas_settings_just_record_0"
					value="0" <?php checked( self::$settings->just_record, false ); ?>/>
				<?php esc_html_e( 'More options', 'secas' ); ?>
			</label>
		</div>
	<?php endif; ?>
	<label>
		<input type="checkbox" name="_secas_settings[compact_view]" id="_secas_settings_compact_view"
			<?php checked( self::$settings->compact_view, true ); ?>/>
		<?php esc_html_e( 'Compact view', 'secas' ); ?>
	</label>
	<label>
		<input type="checkbox" name="_secas_settings[deactivate_cleanup]" id="_secas_settings_deactivate_cleanup"
			<?php checked( self::$settings->deactivate_cleanup, true ); ?>/>
		<?php esc_html_e( 'Cleanup settings and records on deactivate', 'secas' ); ?>
	</label>
	<?php if ( ! empty( self::$settings->email ) ) : ?>
		<label>
			<input type="checkbox" name="_secas_settings[test]" id="_secas_settings_test" value="">
			<?php esc_html_e( 'Test the settings', 'secas' ); ?>
		</label>
	<?php endif; ?>
	<?php if ( $use_reset ) : ?>
		<label class="as-box" style="white-space: nowrap;">
			<?php esc_html_e( 'Log cleanup', 'secas' ); ?>
			<select name="_secas_settings[auto_cleanup]"
				id="_secas_settings_auto_cleanup">
				<?php self::cleanup_records_options(); ?>
			</select>
		</label>
	<?php endif; ?>
	<div class="clear">
		<?php submit_button( __( 'Save', 'secas' ), 'primary wide', 'save-settings-0', false ); ?>
	</div>
</div>
