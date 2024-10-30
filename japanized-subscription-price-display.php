<?php
/**
 * Plugin Name: Japanized Subscription Price Display
 * Plugin URI: https://wordpress.org/plugins/japanized-subscription-price-display
 * Description: Modifies default WooCommerce Subscriptions prices to look more natural in Japanese.
 * Author: James Konno
 * Author URI: 
 * Text Domain: japanized-subscription-price-display
 * Domain Path: /languages
 * Version: 1.0.0
 */

/*
Copyright 2020 James Konno

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
*/

defined( 'ABSPATH' ) || exit;

/**
 * Reformats variable subscription prices.
 * 
 * Expects the WooCommerce string 'From:' in the formatted price and if found, changes it
 * to 'yori' then moves that part to the end, but before the tax, of the price.
 * @since 1.0.0
 */
function jpd4wcs_wc_variable_price_fix( $price, $product ) {
    if ( strpos( $price, _x( 'From:', 'min_price', 'woocommerce' ) ) !== false ) {
        /*
         <span class="from">From: </span>
         <span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">¥</span>2,000</span>
         <small class="woocommerce-price-suffix">税別</small>
        */
        list( $from, $yen, $fig, $tax ) = explode( '</span>', $price );
        $yori = esc_html__( 'yori', 'japanized-subscription-price-display' );
        return $yen . '</span>' . $fig . '<small>' . $yori . '</small></span> ' . $tax;
    }
    return $price;
}
add_filter( 'woocommerce_variable_price_html', 'jpd4wcs_wc_variable_price_fix', 50, 2 );

/**
 * Reformats subscription price details.
 * 
 * Only applies to 'per period' versions of the detail strings (e.g. '/ month')
 * Removes the 'per period' postfix, then prefix the price with just the 'period'.
 * @since 1.0.0
 */
function jpd4wcs_wc_var_subs_details_fix( $subscription_string, $product, $include ) {
    /*
    <del><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">¥</span>3,000</bdi></span></del>
     <ins><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">¥</span>2,000</bdi></span></ins>
      <small class="woocommerce-price-suffix">税別</small>
       <span class="subscription-details"> / month with a 3-day free trial and a
        <span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">¥</span>300</bdi></span> sign-up fee
       </span>
    */

    $billing_interval = (int) WC_Subscriptions_Product::get_interval( $product );
    $billing_period = WC_Subscriptions_Product::get_period( $product );
    $subscription_period = wcs_get_subscription_period_strings( $billing_interval, $billing_period );

    // modifying only when the string contains " / period"
    if ( strpos( $subscription_string, ' / ' . $subscription_period ) !== false ) {
        $jpd4wcs_period = '<span class="subscription-details jpd4wcs-period">' . $subscription_period . '</span> ';

        // removing only " / period", leaving other details and the span tag
        $new_str = str_replace( ' / ' . $subscription_period, '', $subscription_string );

        // adding prefix and return if it's not yet there
        if ( strpos( $new_str, $jpd4wcs_period ) !== false ) {
            return $new_str;
        }
        return  $jpd4wcs_period . $new_str;
    }
    return $subscription_string;
}
add_filter( 'woocommerce_subscriptions_product_price_string', 'jpd4wcs_wc_var_subs_details_fix', 50, 3 );


/**
 * Checks if WooCommerce Subsubscriptions is enabled, shows an admin warning if not.
 */
function jpd4wcs_check() {
	if ( ! class_exists( 'WC_Subscriptions' ) ) {
        add_action( 'admin_notices', 'jpd4wcs_notice' );
        return;
    }
    if ( strcasecmp( 'ja', get_locale() ) !== 0 ) {
        add_action( 'admin_notices', 'jpd4wcs_lang_notice' );
    }
}
add_action( 'plugins_loaded', 'jpd4wcs_check' );

function jpd4wcs_notice() {
	echo '<div class="notice notice-warning"><p><strong>' . sprintf( esc_html__( 'Japanized Subscription Price Display does nothing when WooCommerce Subscriptions is not active.', 'japanized-subscription-price-display' ) ) . '</strong></p></div>';
}
function jpd4wcs_lang_notice() {
	echo '<div class="notice notice-warning"><p><strong>' . sprintf( esc_html__( 'With Japanized Subscription Price Display activated, WooCommerce prices might appear a little odd when the site language is not Japanese.', 'japanized-subscription-price-display' ) ) . '</strong></p></div>';
}
  
/**
 * Loads translations.
 */
function jpd4wcs_load_translation() {
  load_plugin_textdomain( 'japanized-subscription-price-display', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}
add_action( 'init', 'jpd4wcs_load_translation' );
