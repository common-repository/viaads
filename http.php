<?php

namespace ViaAds;

defined( 'ABSPATH' ) || exit;

function ViaAds_PostToUrl( $url, $data, $json = true ) {
    if ( $json == true ) {
        $data = json_encode( $data );
    }
    $args = array(
        'body' => $data,
        'timeout' => '240',
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'cookies' => array(),
    );
    return wp_remote_post( $url, $args );
}

function ViaAds_PostToUrlEvent( $url, $data, $json = true ) {
    if ( $json == true ) {
        $data = json_encode( $data );
    }
    $args = array(
        'body' => $data,
        'timeout' => '0.10',
        'httpversion' => '1.0',
        'blocking' => false,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'cookies' => array(),
    );

    $response = wp_remote_post( $url, $args );
    return;
}

?>