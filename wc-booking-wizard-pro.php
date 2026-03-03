<?php
/**
 * Plugin Name: WC Booking Wizard Pro (Chile Edition)
 * Description: Asistente de reservas paso a paso integrado con WC Bookings, Transbank y validación de RUT.
 * Version: 2.1.0
 * Author: Senior Dev Team
 * Text Domain: wcbw-pro
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Definir constantes de rutas
define( 'WCBW_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCBW_URL', plugin_dir_url( __FILE__ ) );

// Cargar clases
require_once WCBW_PATH . 'includes/class-wcbw-ajax.php';
require_once WCBW_PATH . 'includes/class-wcbw-shortcode.php';

// Inicializar el plugin
function wcbw_init() {
    new WCBW_Ajax();
    new WCBW_Shortcode();
}
add_action( 'plugins_loaded', 'wcbw_init' );