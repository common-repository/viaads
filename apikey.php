<?php

namespace ViaAds;

defined('ABSPATH') || exit;

add_action('admin_menu', 'ViaAds\\ViaAds_pluginSetupMenu');

function ViaAds_pluginSetupMenu()
{
    add_menu_page('ViaAds plugin page', 'ViaAds', 'manage_options', 'viaads-plugin', 'ViaAds\\ViaAds_pluginHtml');
}

function ViaAds_pluginHtml()
{
    $name = "viaads_api_key";
    $optionValue = get_option($name);
    $viaadsApiKey = sanitize_option($name, $optionValue);
    if ($viaadsApiKey == "") {
        add_option($name, "", "", "yes");
    }
    $cookieConsentOptionName = "viaads_cookie_consent";
    $cookieConsentOptionValue = get_option($cookieConsentOptionName);
    $viaadsCookieConsent = sanitize_option($cookieConsentOptionName, $cookieConsentOptionValue);
    if ($viaadsCookieConsent == "") {
        add_option($cookieConsentOptionName, "", "", "yes");
    }

    $html = '
            <div class="wrap">
                <h1>ViaAds Settings</h1>
                <hr class="wp-header-end">
                <form method="post">
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="api_key">API Key</label>
                                </th>
                                <td>
                                    <input type="text" required="" class="regular-text ltr" id="api_key" name="viaadsApiKey" value="' . esc_html($viaadsApiKey) . '">
                                    <p class="description" id="api-key-description">
                                        Enter the API Key provided by ViaAds. If it\'s misplaced or not found, please contact <a href="mailto:msp@viabill.com">msp@viabill.com</a> to obtain it.
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    Behavior Tracking					
                                </th>
                                <td>
                                    <label for="viaads_cookie_consent">
                                        <input type="checkbox" name="viaadsCookieConsent" id="viaads_cookie_consent"' . (isset($viaadsCookieConsent) && $viaadsCookieConsent == "1" ? ' checked' : '') . '>
                                        Disable Automatic Enhanced Behavior Tracking (with cookies)						
                                    </label>
                                    <p class="description" id="automatic-enhanced-behavior-tracking-description">
                                        By ticking this checkbox, you will disable the automatic collection of detailed behavioral data via cookies. <br>
                                        Although retargeting and user profiling remain possible, they may be less accurate due to reduced data collection. <br>
                                        For optimal retargeting and user profiling results, you would need to manually integrate additional functionality into the Cookie Consent Accept process. <a href="https://www.viaads.dk/woocommerce-guide#managing-cookie-policy" target="_blank">(Learn more)</a> <br>
                                        If left unticked (default), the system will automatically handle data collection for more precise retargeting and profiling.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="viaadsApiKeyUpdate" id="submit" class="button" value="Update settings">			
                    </p>
                </form>
            </div>
            ';

    echo $html;
}

add_action('init', 'ViaAds\\ViaAds_pluginHandler');

function ViaAds_pluginHandler()
{
    if (isset($_POST['viaadsApiKeyUpdate'])) {
        if (isset($_POST['viaadsApiKey'])) {
            $name = "viaads_api_key";
            $apiKey = sanitize_key($_POST['viaadsApiKey']);
            if (ViaAds_validateApiKey($apiKey)) {
                update_option($name, $apiKey, "yes");
            }
        }
        $cookieConsentOptionName = "viaads_cookie_consent";
        if (isset($_POST['viaadsCookieConsent'])) {
            update_option($cookieConsentOptionName, 1, "yes");
        } else {
            update_option($cookieConsentOptionName, 0, "yes");
        }
    }
}

function ViaAds_validateApiKey(string $apiKey): bool
{
    if (empty($apiKey)) {
        add_action('admin_notices', 'ViaAds\\viaads_apikey_error_notice');
        return false;
    }

    if (36 < strlen($apiKey)) {
        add_action('admin_notices', 'ViaAds\\viaads_apikey_error_notice');
        return false;
    }

    if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $apiKey)) {
        add_action('admin_notices', 'ViaAds\\viaads_apikey_error_notice');
        return false;
    }

    add_action('admin_notices', 'ViaAds\\viaads_apikey_success_notice');
    return true;
}


function viaads_apikey_error_notice()
{
    ?>
    <div class="error notice">
        <p><?php echo _e('The API Key provided is not of a valid format'); ?></p>
    </div>
    <?php
}

function viaads_apikey_success_notice()
{
    ?>
    <div class="updated notice">
        <p><?php echo _e('The settings is updated'); ?></p>
    </div>
    <?php
}