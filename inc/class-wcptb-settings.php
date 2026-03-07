<?php
namespace WCPTB;

if ( ! defined( 'ABSPATH' ) ) exit;

class Settings {

    const OPTION_KEY = 'wcptb_settings';

    public static function get_defaults() : array {
        return [
            'enabled' => 1,

            'position' => 'woocommerce_single_product_summary:12',

            'delivery_enabled' => 1,
            'delivery_label' => 'Order now to get delivery between',
            'delivery_min_days' => 3,
            'delivery_max_days' => 5,
            'date_format' => 'd/m/Y',

            'shipping_enabled' => 1,
            'shipping_label' => 'Free delivery',
            'shipping_mode' => 'no_min', // no_min|min_required
            'shipping_min_amount' => '100',

            'discount_enabled' => 1,
            'discount_label' => 'Benefit from the current instant discount',

            'secure_enabled' => 1,
            'secure_label' => 'Secure payment',

            'payment_icons' => [
                'visa' => 1,
                'mastercard' => 1,
                'amex' => 0,
                'klarna' => 0,
                'discover' => 0,
                'unionpay' => 0,
                'paypal' => 0,
                'applepay' => 0,
                'googlepay' => 0,
            ],

            // Optional custom icons uploaded via media library.
            // Stored as attachment IDs keyed by slug.
            'custom_icon_ids' => [],

            // Styling
            'border_color' => '#1e2a78',
			'text_color' => '#1e2a78',
        ];
    }

    public static function get() : array {
        $saved = get_option( self::OPTION_KEY, [] );
        if ( ! is_array( $saved ) ) $saved = [];

        return wp_parse_args( $saved, self::get_defaults() );
    }

    public static function update( array $new ) : void {
        update_option( self::OPTION_KEY, $new, false );
    }
}
