<?php
namespace WCPTB;

if ( ! defined( 'ABSPATH' ) ) exit;

class Admin {

    private static $instance = null;

    public static function instance() : self {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function menu() : void {
        add_submenu_page(
            'woocommerce',
            __( 'Product Trust Box', 'wc-product-trust-box' ),
            __( 'Product Trust Box', 'wc-product-trust-box' ),
            'manage_woocommerce',
            'wcptb',
            [ $this, 'page' ]
        );
    }

    public function register_settings() : void {
        register_setting( 'wcptb', Settings::OPTION_KEY, [ $this, 'sanitize' ] );
    }

    public function sanitize( $input ) {
        $defaults = Settings::get_defaults();
        $out = wp_parse_args( is_array($input) ? $input : [], $defaults );

        $out['enabled'] = ! empty( $out['enabled'] ) ? 1 : 0;

        $allowed_positions = $this->get_positions();
        if ( ! isset( $allowed_positions[ $out['position'] ] ) ) {
            $out['position'] = $defaults['position'];
        }

        $out['delivery_enabled'] = ! empty( $out['delivery_enabled'] ) ? 1 : 0;
        $out['delivery_label'] = sanitize_text_field( $out['delivery_label'] );
        $out['delivery_min_days'] = max( 0, intval( $out['delivery_min_days'] ) );
        $out['delivery_max_days'] = max( $out['delivery_min_days'], intval( $out['delivery_max_days'] ) );
        $out['date_format'] = sanitize_text_field( $out['date_format'] );

        $out['shipping_enabled'] = ! empty( $out['shipping_enabled'] ) ? 1 : 0;
        $out['shipping_label'] = sanitize_text_field( $out['shipping_label'] );
        $out['shipping_mode'] = in_array( $out['shipping_mode'], ['no_min','min_required'], true ) ? $out['shipping_mode'] : 'no_min';
        $out['shipping_min_amount'] = sanitize_text_field( $out['shipping_min_amount'] );

        $out['discount_enabled'] = ! empty( $out['discount_enabled'] ) ? 1 : 0;
        $out['discount_label'] = sanitize_text_field( $out['discount_label'] );

        $out['secure_enabled'] = ! empty( $out['secure_enabled'] ) ? 1 : 0;
        $out['secure_label'] = sanitize_text_field( $out['secure_label'] );

        // Payment icon toggles
        if ( ! is_array( $out['payment_icons'] ) ) $out['payment_icons'] = [];
        $catalog = Render::instance()->get_payment_icon_catalog();
        foreach ( $catalog as $slug => $label ) {
            $out['payment_icons'][ $slug ] = ! empty( $out['payment_icons'][ $slug ] ) ? 1 : 0;
        }

        // Custom icon attachment IDs
        if ( ! is_array( $out['custom_icon_ids'] ) ) $out['custom_icon_ids'] = [];
        foreach ( $out['custom_icon_ids'] as $k => $v ) {
            $out['custom_icon_ids'][ sanitize_key($k) ] = intval( $v );
        }

        // Style
        $out['border_color'] = sanitize_hex_color( $out['border_color'] ) ?: $defaults['border_color'];
        $out['text_color']   = sanitize_hex_color( $out['text_color'] ) ?: $defaults['text_color'];

        return $out;
    }

    public function page() : void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) return;

        $s = Settings::get();
        $positions = $this->get_positions();
        $catalog = Render::instance()->get_payment_icon_catalog();

        ?>
        <div class="wrap wcptb-wrap">
            <h1><?php esc_html_e( 'WooCommerce Product Trust Box', 'wc-product-trust-box' ); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'wcptb' ); ?>

                <div class="wcptb-card">
                    <h2><?php esc_html_e( 'General', 'wc-product-trust-box' ); ?></h2>

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable', 'wc-product-trust-box' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[enabled]" value="1" <?php checked( ! empty($s['enabled']) ); ?> />
                                    <?php esc_html_e( 'Show trust box on single product pages', 'wc-product-trust-box' ); ?>
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e( 'Position', 'wc-product-trust-box' ); ?></th>
                            <td>
                                <select name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[position]">
                                    <?php foreach ( $positions as $value => $label ) : ?>
                                        <option value="<?php echo esc_attr($value); ?>" <?php selected( $s['position'], $value ); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e( 'Controls where the box appears on the single product page.', 'wc-product-trust-box' ); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e( 'Border color', 'wc-product-trust-box' ); ?></th>
                            <td>
                                <input type="text" class="regular-text" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[border_color]" value="<?php echo esc_attr($s['border_color']); ?>" />
                                <p class="description"><?php esc_html_e( 'Hex color like #1e2a78', 'wc-product-trust-box' ); ?></p>
                            </td>
                        </tr>
						<tr>
							<th scope="row">
								<label for="wcptb_text_color"><?php _e('Text Color', 'wcptb'); ?></label>
							</th>
							<td>
								<input type="text"
									   id="wcptb_text_color"
									   name="wcptb_settings[text_color]"
									   value="<?php echo esc_attr($s['text_color'] ?? '#1e2a78'); ?>"
									   class="wcptb-color-field"
									   data-default-color="#1e2a78" />
								<p class="description"><?php _e('Select the text color for the trust box.', 'wcptb'); ?></p>
							</td>
						</tr>
                    </table>
                </div>

                <div class="wcptb-grid">
                    <div class="wcptb-card">
                        <h2><?php esc_html_e( 'Delivery date range', 'wc-product-trust-box' ); ?></h2>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Enable', 'wc-product-trust-box' ); ?></th>
                                <td><input type="checkbox" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[delivery_enabled]" value="1" <?php checked( ! empty($s['delivery_enabled']) ); ?> /></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Label', 'wc-product-trust-box' ); ?></th>
                                <td><input type="text" class="regular-text" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[delivery_label]" value="<?php echo esc_attr($s['delivery_label']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'From (days)', 'wc-product-trust-box' ); ?></th>
                                <td><input type="number" min="0" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[delivery_min_days]" value="<?php echo esc_attr($s['delivery_min_days']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'To (days)', 'wc-product-trust-box' ); ?></th>
                                <td><input type="number" min="0" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[delivery_max_days]" value="<?php echo esc_attr($s['delivery_max_days']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Date format', 'wc-product-trust-box' ); ?></th>
                                <td>
                                    <input type="text" class="regular-text" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[date_format]" value="<?php echo esc_attr($s['date_format']); ?>" />
                                    <p class="description"><?php esc_html_e( 'Uses WordPress date format tokens (e.g. d/m/Y or m/d/Y).', 'wc-product-trust-box' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Custom icon', 'wc-product-trust-box' ); ?></th>
                                <td><?php $this->icon_uploader_field('box', $s); ?></td>
                            </tr>
                        </table>
                    </div>

                    <div class="wcptb-card">
                        <h2><?php esc_html_e( 'Free delivery line', 'wc-product-trust-box' ); ?></h2>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Enable', 'wc-product-trust-box' ); ?></th>
                                <td><input type="checkbox" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[shipping_enabled]" value="1" <?php checked( ! empty($s['shipping_enabled']) ); ?> /></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Label', 'wc-product-trust-box' ); ?></th>
                                <td><input type="text" class="regular-text" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[shipping_label]" value="<?php echo esc_attr($s['shipping_label']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Minimum order value', 'wc-product-trust-box' ); ?></th>
                                <td>
                                    <label style="display:block;margin-bottom:6px;">
                                        <input type="radio" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[shipping_mode]" value="no_min" <?php checked( $s['shipping_mode'], 'no_min' ); ?> />
                                        <?php esc_html_e( 'No minimum order value', 'wc-product-trust-box' ); ?>
                                    </label>
                                    <label style="display:block;">
                                        <input type="radio" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[shipping_mode]" value="min_required" <?php checked( $s['shipping_mode'], 'min_required' ); ?> />
                                        <?php esc_html_e( 'Require minimum order value', 'wc-product-trust-box' ); ?>
                                    </label>

                                    <div class="wcptb-inline">
                                        <span><?php esc_html_e( 'Minimum amount:', 'wc-product-trust-box' ); ?></span>
                                        <input type="text" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[shipping_min_amount]" value="<?php echo esc_attr($s['shipping_min_amount']); ?>" />
                                    </div>

                                    <p class="description"><?php esc_html_e( 'Amount is formatted with your WooCommerce currency.', 'wc-product-trust-box' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Custom icon', 'wc-product-trust-box' ); ?></th>
                                <td><?php $this->icon_uploader_field('truck', $s); ?></td>
                            </tr>
                        </table>
                    </div>

                    <div class="wcptb-card">
                        <h2><?php esc_html_e( 'Discount line', 'wc-product-trust-box' ); ?></h2>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Enable', 'wc-product-trust-box' ); ?></th>
                                <td><input type="checkbox" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[discount_enabled]" value="1" <?php checked( ! empty($s['discount_enabled']) ); ?> /></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Text', 'wc-product-trust-box' ); ?></th>
                                <td><input type="text" class="regular-text" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[discount_label]" value="<?php echo esc_attr($s['discount_label']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Custom icon', 'wc-product-trust-box' ); ?></th>
                                <td><?php $this->icon_uploader_field('tag', $s); ?></td>
                            </tr>
                        </table>
                    </div>

                    <div class="wcptb-card">
                        <h2><?php esc_html_e( 'Secure payment line', 'wc-product-trust-box' ); ?></h2>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Enable', 'wc-product-trust-box' ); ?></th>
                                <td><input type="checkbox" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[secure_enabled]" value="1" <?php checked( ! empty($s['secure_enabled']) ); ?> /></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Label', 'wc-product-trust-box' ); ?></th>
                                <td><input type="text" class="regular-text" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[secure_label]" value="<?php echo esc_attr($s['secure_label']); ?>" /></td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e( 'Payment icons', 'wc-product-trust-box' ); ?></th>
                                <td>
                                    <div class="wcptb-checkgrid">
                                        <?php foreach ( $catalog as $slug => $label ) : ?>
                                            <label>
                                                <input type="checkbox" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[payment_icons][<?php echo esc_attr($slug); ?>]" value="1" <?php checked( ! empty($s['payment_icons'][$slug]) ); ?> />
                                                <?php echo esc_html($label); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'If you upload a custom icon for a payment method, it will replace the text pill.', 'wc-product-trust-box' ); ?></p>

                                    <h4 style="margin-top:12px;"><?php esc_html_e( 'Upload custom payment icons (optional)', 'wc-product-trust-box' ); ?></h4>
                                    <?php foreach ( $catalog as $slug => $label ) : ?>
                                        <div class="wcptb-uploadrow">
                                            <strong style="min-width:110px;display:inline-block;"><?php echo esc_html($label); ?></strong>
                                            <?php $this->payment_icon_uploader_field( $slug, $s ); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e( 'Custom icon', 'wc-product-trust-box' ); ?></th>
                                <td><?php $this->icon_uploader_field('lock', $s); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php submit_button(); ?>
            </form>

            <div class="wcptb-card">
                <h2><?php esc_html_e( 'Preview note', 'wc-product-trust-box' ); ?></h2>
                <p><?php esc_html_e( 'Save changes, then view any single product page to see the trust box.', 'wc-product-trust-box' ); ?></p>
            </div>
        </div>
        <?php
    }

    private function get_positions() : array {
        return [
            'woocommerce_single_product_summary:12' => __( 'Above short description (below price)', 'wc-product-trust-box' ),
            'woocommerce_single_product_summary:21' => __( 'Below short description (before add to cart)', 'wc-product-trust-box' ),
            'woocommerce_single_product_summary:31' => __( 'Below add to cart', 'wc-product-trust-box' ),
            'woocommerce_after_add_to_cart_form:10' => __( 'After add to cart form', 'wc-product-trust-box' ),
        ];
    }

    private function icon_uploader_field( string $slug, array $s ) : void {
        $key = $slug;
        $id  = intval( $s['custom_icon_ids'][ $key ] ?? 0 );
        $url = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';

        ?>
        <div class="wcptb-uploader" data-target="<?php echo esc_attr( Settings::OPTION_KEY . '[custom_icon_ids][' . $key . ']' ); ?>">
            <input type="hidden" class="wcptb-attach-id" name="<?php echo esc_attr( Settings::OPTION_KEY . '[custom_icon_ids][' . $key . ']' ); ?>" value="<?php echo esc_attr( $id ); ?>" />
            <button type="button" class="button wcptb-upload"><?php esc_html_e( 'Select icon', 'wc-product-trust-box' ); ?></button>
            <button type="button" class="button wcptb-remove"><?php esc_html_e( 'Remove', 'wc-product-trust-box' ); ?></button>
            <span class="wcptb-preview">
                <?php if ( $url ) : ?>
                    <img src="<?php echo esc_url( $url ); ?>" alt="" />
                <?php else : ?>
                    <em><?php esc_html_e( 'No custom icon set', 'wc-product-trust-box' ); ?></em>
                <?php endif; ?>
            </span>
        </div>
        <?php
    }

    private function payment_icon_uploader_field( string $slug, array $s ) : void {
        $key = 'pay_' . $slug;
        $id  = intval( $s['custom_icon_ids'][ $key ] ?? 0 );
        $url = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';

        ?>
        <div class="wcptb-uploader wcptb-uploader--compact" data-target="<?php echo esc_attr( Settings::OPTION_KEY . '[custom_icon_ids][' . $key . ']' ); ?>">
            <input type="hidden" class="wcptb-attach-id" name="<?php echo esc_attr( Settings::OPTION_KEY . '[custom_icon_ids][' . $key . ']' ); ?>" value="<?php echo esc_attr( $id ); ?>" />
            <button type="button" class="button wcptb-upload"><?php esc_html_e( 'Select', 'wc-product-trust-box' ); ?></button>
            <button type="button" class="button wcptb-remove"><?php esc_html_e( 'Remove', 'wc-product-trust-box' ); ?></button>
            <span class="wcptb-preview">
                <?php if ( $url ) : ?>
                    <img src="<?php echo esc_url( $url ); ?>" alt="" />
                <?php else : ?>
                    <em><?php esc_html_e( '—', 'wc-product-trust-box' ); ?></em>
                <?php endif; ?>
            </span>
        </div>
        <?php
    }
}
