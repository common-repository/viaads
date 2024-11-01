<?php

namespace ViaAds;

use stdClass;
use DateTime;
use Exception;
use WC_Tax;

defined('ABSPATH') || exit;

add_action('woocommerce_new_order', 'ViaAds\\ViaAds_orderCreate', 100000, 6);
add_action('woocommerce_checkout_order_processed', 'ViaAds\\ViaAds_orderCreate', 100000, 6);

function ViaAds_orderCreate($order_id, $order = null)
{
    try {
        //Get order
        $order = wc_get_order($order_id);

        if (count($order->get_items()) == 0) {
            return;
        }
        if(isset($_COOKIE['via_ads'])) {
            $cookieValues = json_decode(base64_decode($_COOKIE['via_ads']));
            if ($cookieValues->Consent) {
                $cookieValues->Email = strtolower($order->get_billing_email());
                setcookie("via_ads", base64_encode(json_encode($cookieValues)), time() + (34560000), "/");
            }
        }

        $order_object = new stdClass();
        // Order Number
        $order_object->Order_number = "{$order->get_id()}";
        // Order Status
        $order_object->Status = $order->get_status();
        // Total Price
        $order_object->Total_price = $order->get_total() - $order->get_total_tax() - $order->get_shipping_total();
        // Tax
        $order_object->Total_price_tax = $order->get_cart_tax();
        // Total Price Tax Included
        $order_object->Total_price_tax_included = $order->get_total() - $order->get_shipping_total();
        // Currency
        $order_object->Currency = $order->get_currency();

        // Vat Percentage
        $vat_percentage = floatval('0.25');
        foreach ($order->get_items('tax') as $item_id => $item) {
            $tax_rate_id = $item->get_rate_id(); // Tax rate ID
            $tax_percent = WC_Tax::get_rate_percent($tax_rate_id); // Tax percentage
            $vat_percentage = floatval("0." . str_replace('%', '', $tax_percent)); // Tax rate
        }

        $order_object->vat_percentage = $vat_percentage;

        // Last Modified
        $lastModifiedGmt = new DateTime($order->get_date_modified());
        $order_object->Last_modified = $lastModifiedGmt->format('c');

        // Billing Address
        $order_billing_address = new stdClass();
        $order_billing_address->first_name = $order->get_billing_first_name();
        $order_billing_address->last_name = $order->get_billing_last_name();
        $order_billing_address->address1 = $order->get_billing_address_1();
        $order_billing_address->city = $order->get_billing_city();
        $order_billing_address->state = $order->get_billing_state();
        $order_billing_address->zip_code = $order->get_billing_postcode();
        $order_billing_address->country = $order->get_billing_country();
        $order_billing_address->phone_number = $order->get_billing_phone();
        $order_billing_address->email = strtolower($order->get_billing_email());
        $order_object->Billing_address = $order_billing_address;


        // Shipping Address
        $order_shipping_address = new stdClass();
        $order_shipping_address->first_name = $order->get_shipping_first_name();
        $order_shipping_address->last_name = $order->get_shipping_last_name();
        $order_shipping_address->address1 = $order->get_shipping_address_1();
        $order_shipping_address->city = $order->get_shipping_city();
        $order_shipping_address->state = $order->get_shipping_state();
        $order_shipping_address->zip_code = $order->get_shipping_postcode();
        $order_shipping_address->country = $order->get_shipping_country();
        $order_shipping_address->phone_number = $order->get_shipping_phone();
        $order_shipping_address->email = strtolower($order->get_billing_email());
        $order_object->Shipping_address = $order_shipping_address;

        $refundItems = [];
        foreach ($order->get_refunds() as $orderRefunds) {
            foreach ($orderRefunds->get_items() as $orderItem) {
                $order_refund_item_object = new stdClass();

                $order_refund_item_object->Product_id = $orderItem->get_product_id();
                $order_refund_item_object->Product_variant_id = $orderItem->get_variation_id();
                $order_refund_item_object->Quantity = $orderItem->get_quantity();
                $order_refund_item_object->Order_id = $orderItem->get_id();

                array_push($refundItems, $order_refund_item_object);
            }
        }

        $orderItems = [];
        foreach ($order->get_items() as $item) {
            $order_item_object = new stdClass();
            // Product Id
            $order_item_object->Product_id = "{$item->get_product_id()}";
            $order_item_object->Product_variant_id = "{$item->get_variation_id()}";
            $order_item_object->WebshopProductId = "{$item->get_product_id()}";
            // Name
            $order_item_object->Name = $item->get_name();
            // SKU
            $order_item_object->Sku = $item->get_product()->get_sku();
            // Price
            //$order_item_object->Price = number_format( $item->get_subtotal() / $item->get_quantity(), 2, ".", "" );
            $order_item_object->Price = $item->get_subtotal() / $item->get_quantity();
            // Quantity
            $order_item_object->Quantity = round($item->get_quantity());
            // Total
            //$order_item_object->Total_price = number_format( $item->get_total(), 2, ".", "" );
            $order_item_object->Total_price = $item->get_total();
            //$order_item_object->Total_price_tax = number_format( $item->get_total_tax(), 2, ".", "" );
            $order_item_object->Total_price_tax = $item->get_total_tax();
            //$order_item_object->Total_price_tax_included = number_format( $item->get_total() + $item->get_total_tax(), 2, ".", "" );
            $order_item_object->Total_price_tax_included = $item->get_total() + $item->get_total_tax();


            if (count($refundItems) > 0) {
                foreach ($refundItems as $refund) {
                    if ($order_item_object->Product_id == $refund->Product_id &&
                        $order_item_object->Product_variant_id == $refund->Product_variant_id) {
                        $order_item_object->Quantity += $refund->Quantity;
                    }
                }
            }

            if ($order_item_object->Quantity > 0) {
                array_push($orderItems, $order_item_object);
            }
        }

        // Order Items
        $order_object->order_items = $orderItems;

        // Final infos
        $orderFinal = new stdClass();
        $orderFinal->Shop_order = $order_object;

        // Order Date
        $createdAt = new DateTime($order->get_date_created());
        $orderFinal->Order_date = $createdAt->format('c');
        // Customer
        $customer = new stdClass();
        $customer->Email = strtolower($order->get_billing_email());
        $orderFinal->customer = $customer;

        //Plugin
        $path = dirname(dirname(__FILE__)) . '/viaads.php';
        $pluginData = get_plugin_data($path);
        $plugin = new stdClass();
        $plugin->Name = "WooCommerce";
        $plugin->Version = $pluginData['Version'];
        $orderFinal->Plugin = $plugin;
        $orderFinal->ApiKey = strval(get_option("viaads_api_key"));

        ViaAds_PostToUrl("https://integration.viaads.dk/woocommerce/WebShopOrderHook", $orderFinal, true);
    } catch (Exception $e) {
        $error_object = new stdClass();
        $error_object->Error = $e->getMessage();

        $currentPageUrl = sanitize_url(home_url($_SERVER['REQUEST_URI']));
        $error_object->Url = wp_http_validate_url($currentPageUrl);

        ViaAds_PostToUrlEvent("https://integration.viaads.dk/error", $error_object);
    }
}