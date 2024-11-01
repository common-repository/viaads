<?php

namespace ViaAds;

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', 'ViaAds\\viaads_add_script' );
function viaads_add_script() {
    $cookie_provider = get_option( 'viaads_cookie_consent' );

    if (isset($cookie_provider) && $cookie_provider == "1") {
        echo "<script> try {
        (function(){
            window.viaadsOptions = window.viaadsOptions || [];
            window.viaadsOptions.push({'tracking.cookiesEnabled': false});
        var o=document.createElement('script');
        o.type='text/javascript';
        o.async=true;
        o.src='https://files.viaads.dk/plugins/min/wooCommerce2.min.js';
        var s=document.getElementsByTagName('script')[0];
        s.parentNode.insertBefore(o,s);
    })();
    } catch (err) {
        console.log(err);
    }</script>";
    } else {
        echo "<script> try {
        (function(){
        var o=document.createElement('script');
        o.type='text/javascript';
        o.async=true;
        o.src='https://files.viaads.dk/plugins/min/wooCommerce2.min.js';
        var s=document.getElementsByTagName('script')[0];
        s.parentNode.insertBefore(o,s);
    })();
    } catch (err) {
        console.log(err);
    }</script>";
    }
}