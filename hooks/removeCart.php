<?php

namespace ViaAds;

use stdClass;
use DateTime;
use Exception;

defined('ABSPATH') || exit;

add_action('woocommerce_remove_cart_item', 'ViaAds\\ViaAds_removeFromCart', 100000, 2);

function ViaAds_removeFromCart($cart_item_key, $cart)
{
    try {
        //Product
        $variationId = $cart->cart_contents[$cart_item_key]['variation_id'];
        $productId = $cart->cart_contents[$cart_item_key]['product_id'];
        if ($variationId != "" && is_numeric($variationId) && $variationId != 0) {
            $product = wc_get_product($variationId);
        } else {
            $product = wc_get_product($productId);
            if (is_bool($product)) {
                $_pf = new WC_Product_Factory();
                $product = $_pf->get_product($productId);
            }
        }

        //Checking for cookie consent
        if (!isset($_COOKIE['via_ads'])){
            return;
        }
        $cookieValues = json_decode(base64_decode($_COOKIE['via_ads']));
        if (!$cookieValues->Consent) {
            return;
        }

        $data = new stdClass();

        //ClientInfo
        $clientInfo = new stdClass();
        $ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        $clientInfo->ip = $ip;

        //User Agent
        $userAgentFile = realpath(dirname(plugin_dir_path(__FILE__)) . '/userAgent.php');
        if ($userAgentFile) {
            require_once($userAgentFile);
            $ua = ViaAds_getBrowser();
            $userAgent = new stdClass();
            $userAgent->device_name = $ua['platform'];
            $userAgent->name = $ua['name'];
            $userAgent->original = $_SERVER['HTTP_USER_AGENT'];
            $userAgent->version = $ua['version'];
            $data->user_agent = $userAgent;
        }

        //ProductPageUrl
        $productPageUrl = new stdClass();
        $productPageUrl->full = get_permalink($productId);

        //Email
        global $current_user;
        get_currentuserinfo();
        $email = strtolower(( string )$current_user->user_email);
        $customer = new stdClass();
        if (strlen($email) > 4) {
            $customer->Email = $email;
        } else {
            $customer->Email = strtolower($cookieValues->Email);
        }

        $customer->Session_id = $cookieValues->Session ?? "";
        $customer->ViaAds = $cookieValues->ViaAds ?? "";

        //Thrid party cookies
        if (isset($_COOKIE['via_ads2'])) {
            $cookieValues2 = json_decode(base64_decode($_COOKIE['via_ads2']));
            $customer->ViaAds2 = $cookieValues2->ViaAds ?? "";
            $customer->Email2 = $cookieValues2->Email ?? "";
        }

        $data->customer = $customer;

        //CustomerShopEvent
        $shopEvent = new stdClass();
        $shopEvent->Event_type = "RemoveCart";
        if (!is_bool($product)) {
            $shopEvent->Product_sku = $product->get_sku();
            $shopEvent->Price = $product->get_price() == "" ? null : $product->get_price();
            $shopEvent->Product_id = $cart->cart_contents[$cart_item_key]['product_id'];
            $shopEvent->Product_variant_id = $cart->cart_contents[$cart_item_key]['variation_id'];
        }

        //Plugin
        $path = dirname(dirname(__FILE__)) . '/viaads.php';
        $pluginData = get_plugin_data($path);
        $plugin = new stdClass();
        $plugin->Name = "WooCommerce";
        $plugin->Version = $pluginData['Version'];
        $data->Plugin = $plugin;

        $data->ApiKey = strval(get_option("viaads_api_key"));
        $data->client = $clientInfo;
        $data->url = $productPageUrl;
        $data->Shop_event = $shopEvent;
        $date = new DateTime();
        $data->Event_date = $date->format('Y-m-d\TH:i:s');

        ViaAds_PostToUrlEvent("https://integration.viaads.dk/woocommerce/event", $data);
    } catch (Exception $e) {
        $error_object = new stdClass();
        $error_object->Error = $e->getMessage();

        $currentPageUrl = sanitize_url(home_url($_SERVER['REQUEST_URI']));
        $error_object->Url = wp_http_validate_url($currentPageUrl);

        ViaAds_PostToUrlEvent("https://integration.viaads.dk/error", $error_object);
    }
}