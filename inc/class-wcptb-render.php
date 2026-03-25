<?php
namespace WCPTB;

if ( ! defined( 'ABSPATH' ) ) exit;

class Render {

    private static $instance = null;

    public static function instance() : self {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp', [ $this, 'register_position_hook' ] );
    }

    public function register_position_hook() : void {
        if ( ! function_exists('is_product') || ! is_product() ) return;

        $settings = Settings::get();
        if ( empty( $settings['enabled'] ) ) return;

        // Format: hook:priority
        $pos = isset($settings['position']) ? (string)$settings['position'] : 'woocommerce_single_product_summary:12';
        $parts = explode(':', $pos);
        $hook = $parts[0] ?? 'woocommerce_single_product_summary';
        $priority = intval( $parts[1] ?? 12 );

        add_action( $hook, [ $this, 'output_box' ], $priority );
    }

    public function output_box() : void {
        if ( ! function_exists( 'is_product' ) || ! is_product() ) return;

        $s = Settings::get();
        if ( empty( $s['enabled'] ) ) return;

        $border = ! empty( $s['border_color'] ) ? esc_attr( $s['border_color'] ) : '#1e2a78';
		$text_color = ! empty( $s['text_color'] ) ? $s['text_color'] : '#1e2a78';

        $rows = [];

        // Delivery range
        if ( ! empty( $s['delivery_enabled'] ) ) {
            $min = max( 0, intval( $s['delivery_min_days'] ?? 0 ) );
            $max = max( $min, intval( $s['delivery_max_days'] ?? $min ) );

            $format = ! empty( $s['date_format'] ) ? (string) $s['date_format'] : 'd/m/Y';

            $from = date_i18n( $format, strtotime( '+' . $min . ' days' ) );
            $to   = date_i18n( $format, strtotime( '+' . $max . ' days' ) );

            $label = ! empty( $s['delivery_label'] ) ? $s['delivery_label'] : __( 'Order now to get delivery between', 'sirpi-trust-box-for-woocommerce' );

            $rows[] = [
                'icon' => 'box',
                'html' => '<strong>' . esc_html( $label ) . '</strong> ' . esc_html( $from . ' – ' . $to ),
            ];
        }

        // Shipping / free delivery
        if ( ! empty( $s['shipping_enabled'] ) ) {
            $label = ! empty( $s['shipping_label'] ) ? $s['shipping_label'] : __( 'Free delivery', 'sirpi-trust-box-for-woocommerce' );

            if ( ($s['shipping_mode'] ?? 'no_min') === 'min_required' ) {
                $amount_raw = (string) ( $s['shipping_min_amount'] ?? '0' );
                $amount = wc_price( floatval( preg_replace('/[^0-9\.\,]/', '', $amount_raw) ) );
                $text = sprintf(
                    /* translators: 1: min order amount */
                    __( '%1$s for orders over %2$s.', 'sirpi-trust-box-for-woocommerce' ),
                    esc_html( $label ),
                    wp_kses_post( $amount )
                );
            } else {
                $text = esc_html( $label ) . ' ' . esc_html__( 'with no minimum order value.', 'sirpi-trust-box-for-woocommerce' );
            }

            $rows[] = [
                'icon' => 'truck',
                'html' => $text,
            ];
        }

        // Discount
        if ( ! empty( $s['discount_enabled'] ) ) {
            $label = ! empty( $s['discount_label'] ) ? $s['discount_label'] : __( 'Benefit from the current instant discount', 'sirpi-trust-box-for-woocommerce' );
            $rows[] = [
                'icon' => 'tag',
                'html' => esc_html( $label ),
            ];
        }

        // Secure payment icons
        if ( ! empty( $s['secure_enabled'] ) ) {
            $label = ! empty( $s['secure_label'] ) ? $s['secure_label'] : __( 'Secure payment', 'sirpi-trust-box-for-woocommerce' );

            $icons_html = $this->render_payment_icons( $s );

            $rows[] = [
                'icon' => 'lock',
                'html' => '<strong>' . esc_html( $label ) . ':</strong> ' . $icons_html,
                'class' => 'wcptb-row--payments',
            ];
        }

        if ( empty( $rows ) ) return;


        echo '<div class="wcptb-box" style="--wcptb-border:' . esc_attr($border) . '; color:' . esc_attr($text_color) . ';">';
        foreach ( $rows as $i => $row ) {
            $class = 'wcptb-row' . ( $i === 0 ? ' wcptb-row--first' : '' );
            if ( ! empty( $row['class'] ) ) $class .= ' ' . esc_attr( $row['class'] );

            echo '<div class="' . esc_attr( $class ) . '">';
            echo $this->render_icon( $row['icon'], $s );
            echo '<div class="wcptb-text">' . wp_kses_post( $row['html'] ) . '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    private function render_icon( string $type, array $s ) : string {
        // Simple inline SVG icons. Can be overridden by custom uploaded icon IDs.
        $map = [
            'box'   => 'box',
            'truck' => 'truck',
            'tag'   => 'tag',
            'lock'  => 'lock',
        ];

        $slug = $map[ $type ] ?? 'box';

        // Custom icon?
        $custom_ids = $s['custom_icon_ids'] ?? [];
        if ( is_array( $custom_ids ) && ! empty( $custom_ids[ $slug ] ) ) {
            $id = intval( $custom_ids[ $slug ] );
            $url = wp_get_attachment_image_url( $id, 'thumbnail' );
            if ( $url ) {
                return '<div class="wcptb-icon"><img src="' . esc_url( $url ) . '" alt="" loading="lazy" /></div>';
            }
        }

        $svg = '';
        switch ( $slug ) {
            case 'truck':
                $svg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v10H3V7zm12 4h3l3 3v3h-6v-6zM6 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm12 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>';
                break;
            case 'tag':
                $svg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10l-8 8-10-10V2h6l12 12zM7.5 7A1.5 1.5 0 1 0 7.5 4a1.5 1.5 0 0 0 0 3z"/></svg>';
                break;
            case 'lock':
                $svg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2h1a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h1zm2 0h6V8a3 3 0 0 0-6 0v2z"/></svg>';
                break;
            case 'box':
            default:
                $svg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 8l-9 4-9-4 9-4 9 4zM3 10l9 4 9-4v10l-9 4-9-4V10z"/></svg>';
                break;
        }

        return '<div class="wcptb-icon">' . $svg . '</div>';
    }

    private function render_payment_icons( array $s ) : string {
        $enabled = $s['payment_icons'] ?? [];
        if ( ! is_array( $enabled ) ) $enabled = [];

        $out = '<span class="wcptb-payments">';

        foreach ( $this->get_payment_icon_catalog() as $slug => $label ) {
            if ( empty( $enabled[ $slug ] ) ) continue;

            // Custom uploaded icon for payment?
            $custom_ids = $s['custom_icon_ids'] ?? [];
            if ( is_array( $custom_ids ) && ! empty( $custom_ids[ 'pay_' . $slug ] ) ) {
                $id = intval( $custom_ids[ 'pay_' . $slug ] );
                $url = wp_get_attachment_image_url( $id, 'thumbnail' );
                if ( $url ) {
                    $out .= '<img class="wcptb-payimg" src="' . esc_url( $url ) . '" alt="' . esc_attr( $label ) . '" loading="lazy" />';
                    continue;
                }
            }

            // Fallback pill label
            $out .= '<span class="wcptb-paypill" aria-label="' . esc_attr( $label ) . '">' . esc_html( $label ) . '</span>';
        }

        $out .= '</span>';
        return $out;
    }

    public function get_payment_icon_catalog() : array {
        return [
            'visa'       => 'VISA',
            'mastercard' => 'Mastercard',
            'amex'       => 'AmEx',
            'klarna'     => 'Klarna',
            'discover'   => 'Discover',
            'unionpay'   => 'UnionPay',
            'paypal'     => 'PayPal',
            'applepay'   => 'Apple Pay',
            'googlepay'  => 'Google Pay',
        ];
    }
}
