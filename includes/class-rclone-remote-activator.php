<?php

/**
 * Fired during plugin activation
 *
 * @link       https://austinginder.com
 * @since      1.0.0
 *
 * @package    Rclone_Remote
 * @subpackage Rclone_Remote/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Rclone_Remote
 * @subpackage Rclone_Remote/includes
 * @author     Austin Ginder <austinginder@gmail.com>
 */
class Rclone_Remote_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		if ( ! get_option("rclone_remote_token") ) {
			$token = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(32))), 0, 32); // 32 chars, without /=+
			update_option("rclone_remote_token", $token);
		}

	}

}
