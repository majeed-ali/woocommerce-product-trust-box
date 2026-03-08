<?php
namespace WCPTB;

if ( ! defined( 'ABSPATH' ) ) exit;

final class Plugin {

    private static $instance = null;

    public static function instance() : self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() : void {
        require_once WCPTB_PLUGIN_DIR . 'inc/class-wcptb-settings.php';
        require_once WCPTB_PLUGIN_DIR . 'inc/class-wcptb-render.php';
        require_once WCPTB_PLUGIN_DIR . 'admin/class-wcptb-admin.php';
    }

    private function init_hooks() : void {
        add_action( 'init', [ $this, 'load_textdomain' ] );

        // Admin
        if ( is_admin() ) {
            Admin::instance();
        }

        // Frontend
        Render::instance();

        // Assets
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function load_textdomain() : void {
        load_plugin_textdomain( 'product-trust-box-for-woocommerce', false, dirname( plugin_basename( WCPTB_PLUGIN_FILE ) ) . '/languages' );
    }

    public function enqueue_frontend_assets() : void {
        if ( ! function_exists( 'is_product' ) || ! is_product() ) return;

        wp_enqueue_style(
            'wcptb-frontend',
            WCPTB_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            WCPTB_VERSION
        );
    }

    public function enqueue_admin_assets( $hook ) : void {
        // Only load on our settings page.
        if ( strpos( (string) $hook, 'wcptb' ) === false ) return;

        wp_enqueue_style(
            'wcptb-admin',
            WCPTB_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WCPTB_VERSION
        );

        // Media uploader for custom icons
        wp_enqueue_media();
        wp_enqueue_script(
            'wcptb-admin',
            WCPTB_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            WCPTB_VERSION,
            true
        );
        wp_localize_script( 'wcptb-admin', 'wcptbAdmin', [
            'mediaTitle' => __( 'Select or Upload an Icon', 'product-trust-box-for-woocommerce' ),
            'mediaButton'=> __( 'Use this icon', 'product-trust-box-for-woocommerce' ),
        ] );
    }
}
