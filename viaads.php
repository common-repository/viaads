<?php
/**
 * Plugin Name: ViaAds
 * Description: Plugin der muliggør forbindelsen til ViaAds / Plug-in enabling the connection to ViaAds.
 * Version: 2.1.0
 * Author: ViaAds
 * Author URI: https://www.viaads.dk/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 **/

/**
 * ViaAds is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * ViaAds is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ViaAds. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

namespace ViaAds;


defined('ABSPATH') || exit;

if (is_admin()) {
    include(plugin_dir_path(__FILE__) . 'apikey.php');
}
include_once(plugin_dir_path(__FILE__) . 'http.php');
include(plugin_dir_path(__FILE__) . 'externalJS.php');
include(plugin_dir_path(__FILE__) . 'endpoints.php');

//Hooks
include(plugin_dir_path(__FILE__) . 'hooks/addCart.php');
include(plugin_dir_path(__FILE__) . 'hooks/removeCart.php');
include(plugin_dir_path(__FILE__) . 'hooks/pageLook.php');
include(plugin_dir_path(__FILE__) . 'hooks/orderHooks.php');
include(plugin_dir_path(__FILE__) . 'hooks/productHooks.php');


register_uninstall_hook(__FILE__, 'ViaAds\\viaads_uninstall');
function viaads_uninstall() {
    //Delete option
    delete_option('viaads_cookie_consent');
    delete_option('viaads_api_key');

    //Get user
    $user_id = username_exists("ViaAds");
    global $wpdb;
    //Delete keys
    $wpdb->delete(
        "{$wpdb->prefix}woocommerce_api_keys",
        array('user_id' => $user_id),
        array('%d')
    );
    //Delete user
    wp_delete_user($user_id);

    //Delete role
    remove_role('ViaAds');
}