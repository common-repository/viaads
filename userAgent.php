<?php 

namespace ViaAds;

defined( 'ABSPATH' ) || exit;

function ViaAds_getBrowser() {
    $u_agent = sanitize_text_field($_SERVER[ 'HTTP_USER_AGENT' ]);
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version = "";

    // Platform
    if ( preg_match( '/linux/i', $u_agent ) ) {
        $platform = 'linux';
    } elseif ( preg_match( '/macintosh|mac os x/i', $u_agent ) ) {
        $platform = 'mac';
    }
    elseif ( preg_match( '/windows|win32/i', $u_agent ) ) {
        $platform = 'windows';
    }

    // Useragent name
    if ( preg_match( '/MSIE/i', $u_agent ) && !preg_match( '/Opera/i', $u_agent ) ) {
        $bname = 'Internet Explorer';
        $ub = "MSIE";
    } elseif ( preg_match( '/Firefox/i', $u_agent ) ) {
        $bname = 'Mozilla Firefox';
        $ub = "Firefox";
    }
    elseif ( preg_match( '/Chrome/i', $u_agent ) ) {
        $bname = 'Chrome';
        $ub = "Chrome";
    }
    elseif ( preg_match( '/Safari/i', $u_agent ) ) {
        $bname = 'Apple Safari';
        $ub = "Safari";
    }
    elseif ( preg_match( '/Opera/i', $u_agent ) ) {
        $bname = 'Opera';
        $ub = "Opera";
    }
    elseif ( preg_match( '/Netscape/i', $u_agent ) ) {
        $bname = 'Netscape';
        $ub = "Netscape";
    }

    // Version number
    $known = array( 'Version', $ub, 'other' );
    $pattern = '#(?<browser>' . join( '|', $known ) .
    ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if ( !preg_match_all( $pattern, $u_agent, $matches ) ) {
        // we have no matching number just continue
    }

    $i = count( $matches[ 'browser' ] );
    if ( $i != 1 ) {
        //see if version is before or after the name
        if ( strripos( $u_agent, "Version" ) < strripos( $u_agent, $ub ) ) {
            $version = $matches[ 'version' ][ 0 ];
        } else {
            $version = $matches[ 'version' ][ 1 ];
        }
    } else {
        $version = $matches[ 'version' ][ 0 ];
    }

    // check if we have a number
    if ( $version == null || $version == "" ) {
        $version = "?";
    }

    return array(
        'userAgent' => $u_agent,
        'name' => $bname,
        'version' => $version,
        'platform' => $platform,
        'pattern' => $pattern
    );
}