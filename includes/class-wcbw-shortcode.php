<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WCBW_Shortcode {

    public function __construct() {
        add_shortcode( 'booking_wizard_pro', [ $this, 'render_main' ] );
        add_shortcode( 'booking_wizard_single', [ $this, 'render_single' ] );
    }

    public function render_main() {
        $this->enqueue_assets(0); 
        ob_start();
        ?>
        <div id="wcbw-app" class="wcbw-wrapper">
            <ul class="wcbw-steps">
                <li class="active" data-step="1">1. Especialidad</li>
                <li data-step="2">2. Agenda</li>
                <li data-step="3">3. Datos</li>
            </ul>

            <div class="wcbw-content">
                <div id="step-1" class="step-pane active">
                    <div class="step-header">
                        <h3>¿Qué especialista necesitas?</h3>
                    </div>
                    <div id="specialties-grid" class="grid-loader"><div class="spinner">Cargando...</div></div>
                    
                    <div id="services-selection" class="fade-in-section" style="display:none; margin-top: 20px;">
                        <h3>Selecciona el Servicio</h3>
                        <select id="service-dropdown" class="form-control"><option value="">Cargando...</option></select>
                        <div style="text-align: right; margin-top: 15px;">
                            <button id="btn-go-calendar" class="btn-action" disabled>Ver Disponibilidad &rarr;</button>
                        </div>
                    </div>
                </div>

                <div id="step-2" class="step-pane">
                    <div class="booking-header">
                        <button class="btn-back" data-to="1">← Volver</button>
                        <h3>Selecciona fecha y hora</h3>
                    </div>
                    <div class="date-controls">
                        <input type="date" id="wcbw-date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div id="slots-container"></div>
                </div>

                <div id="step-3" class="step-pane">
                     <div class="booking-header">
                        <button class="btn-back" data-to="2">← Volver</button>
                        <h3>Completa tus datos</h3>
                    </div>
                    <?php $this->render_customer_form(); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_single( $atts ) {
        $atts = shortcode_atts( ['id' => ''], $atts );
        $product_id = !empty($atts['id']) ? intval($atts['id']) : get_the_ID();

        $product = wc_get_product($product_id);
        if ( !$product || !is_a($product, 'WC_Product_Booking') ) {
            return '<div class="alert-box">Este servicio no tiene reservas habilitadas.</div>';
        }

        $this->enqueue_assets($product_id); 
        ob_start();
        ?>
        <div id="wcbw-app" class="wcbw-wrapper single-mode">
            <ul class="wcbw-steps">
                <li class="active" data-step="2">1. Agenda</li>
                <li data-step="3">2. Datos</li>
            </ul>

            <div class="wcbw-content">
                <div id="step-2" class="step-pane active">
                    <div class="booking-header">
                        <h3>Selecciona fecha y hora para: <?php echo esc_html($product->get_name()); ?></h3>
                    </div>
                    <div class="date-controls">
                        <input type="date" id="wcbw-date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div id="slots-container"></div>
                </div>

                <div id="step-3" class="step-pane">
                     <div class="booking-header">
                        <button class="btn-back" data-to="2">← Volver</button>
                        <h3>Completa tus datos</h3>
                    </div>
                    <?php $this->render_customer_form(); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function enqueue_assets($single_id) {
        $ver = time(); 
        wp_enqueue_script( 'wc-add-to-cart' ); 
        wp_enqueue_style( 'wcbw-style', WCBW_URL . 'assets/css/style.css', [], $ver );
        wp_enqueue_script( 'wcbw-script', WCBW_URL . 'assets/js/app.js', ['jquery'], $ver, true );
        
        wp_localize_script( 'wcbw-script', 'wcbw_vars', [
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'wc_ajax_url' => WC_AJAX::get_endpoint( '%%endpoint%%' ),
            'checkout_url'=> wc_get_checkout_url(),
            'nonce'       => wp_create_nonce( 'wcbw_nonce' ),
            'single_id'   => $single_id
        ]);
    }

    private function render_customer_form() {
        ?>
        <form id="wcbw-customer-form">
            <div class="form-row">
                <label>RUT</label>
                <input type="text" id="rut" class="form-control" placeholder="11.111.111-1">
            </div>
            <div class="form-row"><label>Nombre Completo</label><input type="text" id="fullname" class="form-control"></div>
            <div class="form-row"><label>Email</label><input type="email" id="email" class="form-control"></div>
            <div class="form-row"><label>Teléfono</label><input type="tel" id="phone" class="form-control"></div>
            
            <div class="form-row">
                <label>Plataforma de atención</label>
                <select id="contact_method" class="form-control">
                    <option value="">Seleccione una opción...</option>
                    <option value="WhatsApp">WhatsApp</option>
                    <option value="Google Meet">Google Meet</option>
                </select>
            </div>

            <div class="wcbw-summary">
                <h4>Resumen de Reserva</h4>
                <div id="summary-text"></div>
            </div>

            <button type="button" id="btn-process" class="btn-action">Ir a Pagar</button>
        </form>
        <?php
    }
}