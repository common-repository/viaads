<?php

namespace ViaAds;

use WP_REST_Response;
use WP_REST_Request;

defined('ABSPATH') || exit;

add_action('rest_api_init', function () {
    register_rest_route('viaads/v1', '/validate/plugin', array(
        'methods' => 'GET',
        'callback' => function (WP_REST_Request $request) {
            return new WP_REST_Response('Successfully Installed', 200);
        },
        'permission_callback' => '__return_true',
    ));

    register_rest_route('viaads/v1', '/validate/apikey', array(
        'methods' => 'GET',
        'callback' => function (WP_REST_Request $request) {
            $apiKeyParam = $request->get_param('apiKeyViabillMarketing');
            $apiKey = trim(get_option("viaads_api_key"));
            if ($apiKeyParam != $apiKey) {
                if($apiKey == "") {
                    return new WP_REST_Response('Empty API Key', 401);
                }
                return new WP_REST_Response('Invalid API Key', 401);
            }
            return new WP_REST_Response('Valid API Key', 200);
        },
        'permission_callback' => '__return_true',
    ));

    register_rest_route('viaads/v1', '/setup', array(
        'methods' => 'GET',
        'callback' => function (WP_REST_Request $request) {
            try {
                $apiKeyParam = $request->get_param('apiKeyViabillMarketing');
                $apiKey = trim(get_option("viaads_api_key"));
                if ($apiKeyParam != $apiKey) {
                    return new WP_REST_Response('Invalid API Key', 401);
                }

                global $wpdb;

                //Username
                $user_login = "ViaAds";
                //GroupName
                $groupName = "ViaAds";

                //Get the administrator role
                $admin_role = get_role('administrator');

                //New role
                $role = get_role($groupName);

                if ($role == null){
                    //Create role
                    add_role($groupName, $groupName, $admin_role->capabilities);
                } else {
                    //Update role
                    foreach ($admin_role->capabilities as $cap => $grant) {
                        $role->add_cap($cap, $grant);
                    }
                }

                //Get UserId
                $user_id = username_exists($user_login);

                //Check if user already exists
                if ($user_id == "") {
                    // Create user
                    $user_data = array(
                        'user_login' => $user_login,
                        'user_email' => "Api@viaads.dk",
                        'user_pass' => bin2hex(random_bytes(24)),
                        'role' => $groupName,
                        'first_name' => 'ViaAds',
                        'last_name' => 'API User'
                    );

                    $user_id = wp_insert_user($user_data);
                }

                //Delete api key
                $deleteApiKey = $request->get_param('deleteApiKey');
                if (isset($deleteApiKey)){
                    $wpdb->delete(
                        "{$wpdb->prefix}woocommerce_api_keys",
                        array('user_id' => $user_id),
                        array('%d')
                    );
                }

                //Check if user already have an api key
                $user_keys = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_api_keys WHERE user_id = %d",
                        $user_id
                    )
                );

                //Check if any keys exists
                if ($user_keys == 0){
                    // Generate API Key
                    $consumer_key = 'ck_' . wc_rand_hash();
                    $consumer_secret = 'cs_' . wc_rand_hash();

                    $data = array(
                        'user_id'         => $user_id,
                        'consumer_key'    => wc_api_hash($consumer_key),
                        'consumer_secret' => $consumer_secret,
                        'truncated_key'   => substr($consumer_key, -7),
                        'description'     => 'ViaAds api key for marketing',
                        'permissions'     => 'read',
                        'last_access'     => null,
                    );

                    $wpdb->insert(
                        "{$wpdb->prefix}woocommerce_api_keys",
                        $data,
                        array(
                            '%d',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                        )
                    );


                    $api_key_object = new \stdClass();
                    $api_key_object->ApiKey = $apiKey;
                    $api_key_object->ConsumerKey = $consumer_key;
                    $api_key_object->ConsumerSecret = $consumer_secret;

                    ViaAds_PostToUrl("https://integration.viaads.dk/woocommerce/ApiKey", $api_key_object, true);

                    print_r(json_encode($api_key_object));
                    echo "\r\n\r\n\r\n\r\n";
                    print_r(get_userdata( $user_id ));
                    echo "\r\n\r\n\r\n\r\n";
                    print_r($admin_role);
                    echo "\r\n\r\n\r\n\r\n";
                    print_r(get_role($groupName));
                    echo "\r\n\r\n\r\n\r\n";

                    return new WP_REST_Response('Created user', 200);
                }
                return new WP_REST_Response('Successfully', 200);
            } catch (Exception $e) {
                $error_object = new \stdClass();
                $error_object->Error = $e->getMessage();

                $currentPageUrl = sanitize_url(home_url($_SERVER['REQUEST_URI']));
                $error_object->Url = wp_http_validate_url($currentPageUrl);

                ViaAds_PostToUrlEvent("https://integration.viaads.dk/error", $error_object);
                return new WP_REST_Response('Bad request', 400);
            }
        },
        'permission_callback' => '__return_true',
    ));
});