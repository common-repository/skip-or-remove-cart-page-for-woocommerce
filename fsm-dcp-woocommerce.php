<?php

/*
    Plugin Name: Skip or Remove cart page for WooCommerce
    Plugin URI: https://fullstackmonks.com/
    Description: This plugin remove or skip cart page from shoping follow and redirect user directly to chackout page.
    Author: Sikander Maan
    Author URI: https://about.me/sikandermann
    Version: 0.1
	License: GPLv2
	Text Domain: fullstackmonks
    WC requires at least: 3.0
    WC tested up to: 6.1
 */


/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) { exit; }


/* Below code run on plugin activation */
register_activation_hook( __FILE__, function() {
	if ( ! current_user_can( 'activate_plugins' ) ) { return; }
} );


/* Below code run on plugin activation deactivates */
register_deactivation_hook( __FILE__, function() {
	if ( ! current_user_can( 'activate_plugins' ) ) { return; }
} );


/* Add language support to internationalize plugin */
add_action( 'init', function() {
	load_plugin_textdomain( 'skip-or-remove-cart-page-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/' );
} );


/* Below code is for creating new tab in woocommerce settings */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
	return array_merge( array(
		'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=wcdcp' ) . '">' . __( 'Settings', 'skip-or-remove-cart-page-for-woocommerce' ) . '</a>'
	), $links );
} );

/* Below code is for adding tab text in woocommerce settings */
function woo_settings_tab_fields() {
    $fields = array(
        'section_title-enable' => array(
            'name'     => __( 'Remove cart page', 'skip-or-remove-cart-page-for-woocommerce' ),
            'type'     => 'title',
            'desc'     => __( 'Enabling this option will delete the cart page and for each purchase customers will be redirected directly to the checkout.', 'skip-or-remove-cart-page-for-woocommerce' )
        ),
        'sorcart_enable' => array(
            'name' => __( 'Enable', 'skip-or-remove-cart-page-for-woocommerce' ),
            'type' => 'checkbox',
            'desc' => __( 'Remove cart page and redirect directly to checkout', 'skip-or-remove-cart-page-for-woocommerce' ),
            'id'   => 'sorcart_enable'
        ),
        'multipurchase_enable' => array(
            'name' => __( 'Enable', 'skip-or-remove-cart-page-for-woocommerce' ),
            'type' => 'checkbox',
            'desc' => __( 'User can buy more then one product', 'skip-or-remove-cart-page-for-woocommerce' ),
            'id'   => 'multipurchase_enable'
        ),
        'addtocart_button_text' => array(
            'name' => __( 'Button Text', 'skip-or-remove-cart-page-for-woocommerce' ),
            'type' => 'text',
            'desc' => __( 'Please insert Add to cart button text', 'skip-or-remove-cart-page-for-woocommerce' ),
            'id'   => 'addtocart_button_text'
        ),
        'section_end-enable' => array(
             'type' => 'sectionend'
        )
    );
    return apply_filters( 'woo_settings_tab_fields', $fields );
}


/* Add settings tab to WooCommerce options */
add_filter( 'woocommerce_settings_tabs_array', function( $tabs ) {
    $tabs['wcdcp'] = __( 'Skip or Remove cart page', 'skip-or-remove-cart-page-for-woocommerce' );
    return $tabs;
}, 50 );


/* Add settings to the new tab */
add_action( 'woocommerce_settings_tabs_wcdcp', function() {
    woocommerce_admin_fields( woo_settings_tab_fields() );
} );


/* Save settings */
add_action( 'woocommerce_update_options_wcdcp', function() {
    woocommerce_update_options( woo_settings_tab_fields() );
} );


/*** Run below code if plugin is enable  ***/
if ( get_option( 'sorcart_enable' ) == 'yes' ) {

    /* Remove cart button from mini-cart */
    remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10 );


    /* Add checks and notices */
    add_action( 'admin_notices', function() {
        if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            ?><div class="notice notice-error"><p><?php _e( 'Warning! To use Disable cart page for WooCommerce it need WooCommerce is installed and active.', 'skip-or-remove-cart-page-for-woocommerce' ); ?></p></div><?php
        }
    } );


    /* Force WooCommerce to redirect after product added to cart */
    add_filter( 'pre_option_woocommerce_cart_redirect_after_add', function( $pre_option ) {
        return 'yes';
    } );


    add_filter( 'woocommerce_product_settings', function( $fields ) {
        foreach ( $fields as $key => $field ) {
            if ( $field['id'] === 'woocommerce_cart_redirect_after_add' ) {
                $fields[$key]['custom_attributes'] = array(
                    'disabled' => true
                );
            }
        }
        return $fields;
    }, 10, 1 );


    /* Empty cart when product is added to cart, so we can't have multiple products in cart */
    add_action( 'woocommerce_add_cart_item_data', function( $cart_item_data ) {
        if ( get_option( 'multipurchase_enable' ) != 'yes' ) {
            wc_empty_cart();
        }
        return $cart_item_data;
    } );


    /* When add a product to cart, redirect to checkout */
    add_action( 'woocommerce_init', function() {
        if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) {
            add_filter( 'add_to_cart_redirect', function() {
                return wc_get_checkout_url();
            } );
        } else {
            add_filter( 'woocommerce_add_to_cart_redirect', function() {
                return wc_get_checkout_url();
            } );
        }
    } );


    /* Remove added to cart message */
    add_filter( 'wc_add_to_cart_message_html', '__return_null' );


    /* If some try to open cart page redirect to checkout permanently */
    add_action( 'template_redirect', function() {
        if ( ! is_cart() ) { return; }
        if ( WC()->cart->get_cart_contents_count() == 0 ) {
            wp_redirect( apply_filters( 'sorcart_redirect', wc_get_page_permalink( 'shop' ) ) );
            exit;
        }

        // Redirect to checkout page
        wp_redirect( wc_get_checkout_url(), '301' );
        exit;
    } );


    /* Change add to cart button text ( in loop ) */
    add_filter( 'add_to_cart_text', function() {
        $buttonText = get_option( 'addtocart_button_text' );
        if($buttonText == ''){
            return __( 'Buy now', 'skip-or-remove-cart-page-for-woocommerce' );
        }else{
            return __( $buttonText, 'skip-or-remove-cart-page-for-woocommerce' );
        }
    } );


    /* Change add to cart button text ( in product page ) */
    add_filter( 'woocommerce_product_single_add_to_cart_text', function() {
        $buttonText = get_option( 'addtocart_button_text' );
        if($buttonText == ''){
            return __( 'Buy now', 'skip-or-remove-cart-page-for-woocommerce' );
        }else{
            return __( $buttonText, 'skip-or-remove-cart-page-for-woocommerce' );
        }
    } );


    /* Clear cart if there are errors */
    add_action( 'woocommerce_cart_has_errors', function() {
        if ( get_option( 'multipurchase_enable' ) != 'yes' ) {
            wc_empty_cart();
        }
    } );

}