<?php
/*
Plugin Name: WooCommerce Big Selects Fixer
Plugin URI: http://bonsaibudget.com/product/woocommerce-big-selects/
Description: Enable Big Selects if it is not already enabled for better WooCommercing
Author: Katherine Semel
Author URI: http://bonsaibudget.com/
Version: 1.0

Copyright Bonsaibudget.com, LLC.  All Rights Reserved.
*/
class WooBigSelectsFixer {

    function __construct() {
        // Get Woo version
        add_action( 'init', array( $this, 'setup_by_woo_version' ) );
    }

    /*
        Determine which version of WooCommerce we are using and set up the functions appropriately
    */
    function setup_by_woo_version() {
        // Get Woo version
        global $woocommerce;
        if ( intval( $woocommerce->version ) >= 2 ) {

            // BIG SELECTS warning
            add_action( 'admin_notices', array( $this, 'big_selects_not_enabled' ) );

            // Attempt to set SQL_BIG_SELECTS when we need it
            //add_action( 'woocommerce_before_checkout_form', array( $this, 'enable_big_selects' ) );
            //add_action( 'wp_ajax_woocommerce_update_order_review', array( $this, 'enable_big_selects' ) );
            //add_action( 'woocommerce_checkout_update_order_review', array( $this, 'enable_big_selects' ) );
            add_action( 'woocommerce_before_calculate_totals', array( $this, 'enable_big_selects' ) );

            // Attempt to revert our change to the value of SQL_BIG_SELECTS
            //add_action( 'woocommerce_after_checkout_form', array( $this, 'disable_big_selects' ) );
        }
    }

    // Check for Big Selects
    function big_selects_not_enabled( ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        global $wpdb;
        $sql_big_selects = $wpdb->get_results( 'SHOW VARIABLES LIKE "sql_big_selects";' );

        if ( $sql_big_selects[0]->Value !== 'ON' ) {
            // Attempt to enable big selects as a test of the server rights
            $sql_big_selects = $wpdb->query( 'SET SQL_BIG_SELECTS=1;' );

            if ( $sql_big_selects === false ) {
                // BIG SELECTS isn't on, and we can't enable it.  Let the user know.
                $notice = '<div class="updated">';
                $notice .= '<h3>MySQL "big selects" is not enabled.  This may cause errors on the checkout pages when looking up tax rates.</h3>';
                $notice .= '<p>We attempted to enable big selects to fix this, but your server did not allow the change.  Please contact your hosting provider to enable SQL_BIG SELECTS.</p>';
                $notice .= '<p><a href="">Hide this message</a></p>';
                $notice .= '</div>';

                echo $notice;
            } else {
                // Overriding BIG SELECTS setting worked, no need to bother the user with a message
                // We enabled BIG SELECTS, so let's clean up after ourselves.
                $sql_big_selects = $wpdb->query( 'SET SQL_BIG_SELECTS=0;' );
            }
        }
    }

    // Enable Big Selects
    function enable_big_selects( $checkout ) {
        global $wpdb, $enable_big_selects;
        $sql_big_selects = $wpdb->get_results( 'SHOW VARIABLES LIKE "sql_big_selects";' );

        if ( $sql_big_selects[0]->Value !== 'ON' ) {
            //error_log( 'Enabled BIG Selects: "' . $sql_big_selects[0]->Value . '"' );
            $enable_big_selects = true;
            $sql_big_selects = $wpdb->query( 'SET SQL_BIG_SELECTS=1;' );

        } else {
            // No need to run, BIG SELECTS is already on
            $enable_big_selects = false;
        }
    }

    // Disable Big Selects (but only if we previously enabled them)
    function disable_big_selects( $checkout ) {
        global $wpdb, $enable_big_selects;

        //error_log( 'Disabled BIG Selects' );
        // We enabled BIG SELECTS, so let's clean up after ourselves.
        if ( $enable_big_selects ) {
            $sql_big_selects = $wpdb->query( 'SET SQL_BIG_SELECTS=0;' );
        }
    }

}
$WooBigSelectsFixer = new WooBigSelectsFixer();
