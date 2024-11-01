<?php

namespace ViaAds;

use stdClass;
use DateTime;
use Exception;
use WC_Tax;

defined('ABSPATH') || exit;

add_action('before_delete_post', 'ViaAds\\ViaAds_productDelete', 100000, 1);

function ViaAds_productDelete($post_id)
{
    try {
        if (get_post_type($post_id) != 'product') {
            return;
        }

        $product_object = new stdClass();
        $product_object->ProductId = "{$post_id}";
        $product_object->ApiKey = strval(get_option("viaads_api_key"));

        ViaAds_PostToUrl("https://integration.viaads.dk/woocommerce/webhooks/ProductDeleted", $product_object, true);
    } catch (Exception $e) {
        $error_object = new stdClass();
        $error_object->Error = $e->getMessage();

        $currentPageUrl = sanitize_url(home_url($_SERVER['REQUEST_URI']));
        $error_object->Url = wp_http_validate_url($currentPageUrl);

        ViaAds_PostToUrlEvent("https://integration.viaads.dk/error", $error_object);
    }
}