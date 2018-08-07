<?php
/**
 * class-affiliates-mailchimp.php
 *
 * Copyright (c) 2018 www.itthinx.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author itthinx
 * @package affiliates-mailchimp
 * @since 3.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Affiliates MailChimp
 */
class Affiliates_MailChimp {

	/**
	 * Error notices
	 *
	 * @var array
	 */
	private static $notices = array();

	/**
	 * Init Class
	 */
	public static function init() {
		load_plugin_textdomain( 'affiliates-mailchimp', false, 'affiliates-mailchimp/languages' );
		add_action( 'init', array( __CLASS__, 'wp_init' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
		//register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );
	}

	/**
	 * Plugin dependencies
	 */
	public static function wp_init() {
		if ( !defined( 'AFFILIATES_PLUGIN_DOMAIN' ) ) {
			self::$notices[] = "<div class='error'>" . __( '<strong>Affiliates Mailchimp</strong> plugin requires <a href="http://www.itthinx.com/plugins/affiliates-pro" target="_blank">Affiliates Pro</a> or <a href="http://www.itthinx.com/plugins/affiliates-enterprise" target="_blank">Affiliates Enterprise</a>.', 'affiliates-mailchimp' ) . '</div>';
		} else {
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 40 );
		}
	}

	/**
	 * Prints admin notices
	 */
	public static function admin_notices() {
		if ( !empty( self::$notices ) ) {
			foreach ( self::$notices as $notice ) {
				echo wp_kses(
					$notice,
					array(
						'strong' => array(),
						'div' => array( 'class' ),
						'a' => array(
							'href'   => array(),
							'target' => array( '_blank' )
						),
						'div' => array(
							'class' => array()
						),
					)
				);
			}
		}
	}

	/**
	 * Adds the admin section.
	 */
	public static function admin_menu() {
		$admin_page = add_submenu_page(
			'affiliates-admin',
			__( 'MailChimp' , 'affiliates-mailchimp' ),
			__( 'MailChimp' , 'affiliates-mailchimp' ),
			AFFILIATES_ADMINISTER_OPTIONS,
			'affiliates-mailchimp',
			array( __CLASS__, 'affiliates_mailchimp_settings' )
		);
	}

	/**
	 * Show Groups MailChimp setting page.
	 */
	public static function affiliates_mailchimp_settings() {
		$options = array();
		if ( !current_user_can( AFFILIATES_ADMINISTER_OPTIONS ) ) {
			wp_die( esc_html__( 'Access denied.', 'affiliates-mailchimp' ) );
		}
		$options = get_option( 'affiliates-mailchimp' );
		?>
		<div class="wrap">

		<?php
		if ( isset( $_POST['submit'] ) ) {
			if ( wp_verify_nonce( $_POST['aff-mailchimp-nonce'], 'aff-mc-set-admin-options' ) ) {
				$options['api_key']            = $_POST['api_key'];
				$options['list_name']          = $_POST['list_name'];
				$options['interests_category'] = $_POST['interests_category'];
				$options['interest']           = $_POST['interest'];
				$options['need_confirm']       = $_POST['need_confirm'];
				$options['delete_settings']    = isset( $_POST['delete_settings'] ) ? 1 : 0;
			}
			update_option( 'affiliates-mailchimp', $options );
		} elseif ( isset( $_POST['generate'] ) ) {
			Affiliates_Mc::synchronize();
		}

		$api_key            = isset( $options['api_key'] ) ? $options['api_key'] : null;
		$list_name          = isset( $options['list_name'] ) ? $options['list_name'] : '';
		$interests_category = isset( $options['interests_category'] ) ? $options['interests_category'] : '';
		$interest           = isset( $options['interest'] ) ? $options['interest'] : '';
		$need_confirm       = isset( $options['need_confirm'] ) ? $options['need_confirm'] : 0;
		$delete_settings    = isset( $options['delete_settings'] ) ? $options['delete_settings'] : null;

		$description = '';
		if ( !$api_key ) {
			$description = esc_html__( 'Affiliates Mailchimp needs a valid API key to connect with MailChimp servers.', 'affiliates-mailchimp' );
		}
		?>

	<h2>
		<?php echo esc_html__( 'Affiliates MailChimp', 'affiliates-mailchimp' ); ?>
	</h2>
	<form method="post" name="options" action="">
		<table class="form-table">
			<tr valign="top">
			<th scope="row"><?php echo esc_html__( 'API Key:', 'affiliates-mailchimp' ); ?></th>
			<td><input type="text" name="api_key" value="<?php echo esc_attr( $api_key ); ?>" />
				<p class="description"><?php echo esc_html( $description ); ?></p>
			</td>
			</tr>
	
					 <tr valign="top">
			<th scope="row"><?php echo esc_html__( 'List name:', 'affiliates-mailchimp' ); ?></th>
			<td><input type="text" name="list_name" value="<?php echo esc_attr( $list_name ); ?>" /></td>
			</tr>
	
				<tr valign="top">
			<th scope="row"><?php echo esc_html__( 'Interest Category:', 'affiliates-mailchimp' ); ?></th>
			<td><input type="text" name="interests_category" value="<?php echo esc_attr( $interests_category ); ?>" /></td>
			</tr>
	
					<tr valign="top">
			<th scope="row"><?php echo esc_html__( 'Interest:', 'affiliates-mailchimp' ); ?></th>
			<td><input type="text" name="interest" value="<?php echo esc_attr( $interest ); ?>" /></td>
			</tr>

			  <tr valign="top">
			<th scope="row"><?php echo esc_html__( 'Confirm Subscription:', 'affiliates-mailchimp' ); ?></th>
			<td>
				<select name="need_confirm">
				<?php
				if ( $need_confirm == '1' ) {
				?>
					  <option value="1" SELECTED><?php echo esc_html__( 'YES', 'affiliates-mailchimp' ); ?></option>
					<?php
				} else {
					?>
					<option value="1"><?php echo esc_html__( 'YES', 'affiliates-mailchimp' ); ?></option>
					<?php
				}
				if ( $need_confirm == '0' ) {
				?>
					<option value="0" SELECTED><?php echo esc_html__( 'NO','affiliates-mailchimp' ); ?></option>
				<?php
				} else {
					?>
					<option value="0"><?php echo esc_html__( 'NO','affiliates-mailchimp' ); ?></option>
					<?php
				}
				?>
				  </select>
						<p class="description"><?php echo esc_html__( 'Control whether a double opt-in confirmation message is sent. Abusing this may cause your mailchimp account to be suspended.' , 'affiliates-mailchimp' ); ?></p>
			  </tr>
			  <tr>
			  <th scope="row"><?php echo esc_html__( 'Delete Settings:', 'affiliates-mailchimp' ); ?></th>
			  <td>
			  <input type="checkbox" name="delete_settings" <?php echo ( esc_attr( $delete_settings ) == 1 ? ' checked="checked" ' : '' ); ?> />
			  <p class="description"><?php echo esc_html__( 'CAUTION: If this option is enabled while the plugin is deactivated, the above settings will be DELETED. If you want to keep these settings and are going to deactivate it, make sure to keep a note or backup or do not enable this option.' , 'affiliates-mailchimp' ); ?></p>
			  </td>
			  </tr>
		</table>
		<p>
		<?php
			echo wp_nonce_field( 'aff-mc-set-admin-options', 'aff-mailchimp-nonce', true, false );
			echo '<input class="button button-primary" type="submit" name="submit" value="' . esc_attr__( 'Save', 'affiliates-mailchimp' ) . '"/>';
		?>
		</p>
	</form>

	</div>

	<div class="wrap">
	<h3><?php echo esc_html__( 'Synchronize', 'affiliates-mailchimp' ); ?></h3>

	<form method="POST" action="">
	<table class="form-table">
		<tr>
			<th scope="row">
				<?php submit_button( __( 'Syncronize', 'affiliates-mailchimp' ), 'secondary', 'generate' ); ?>
			</th>
			<td>
				<p class="description"><?php echo esc_html__( 'Use this to add already existing affiliates in your mailchimp list.', 'affiliates-mailchimp' ); ?></p>
			</td>
		</tr>
	</table>
	</form>
	</div>
	<?php
	}

	/**
	 * Option to delete plugin settings upon uninstall
	 */
	/*public static function deactivate() {
		$options = get_option( 'affiliates-mailchimp' );write_log('deacti');
		if ( isset( $options['delete_settings'] ) && $options['delete_settings'] == 1 ) {
			delete_option( 'affiliates-mailchimp' );
		}
	}*/
}
Affiliates_MailChimp::init();
