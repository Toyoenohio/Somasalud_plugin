<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WCBW_Ajax {

    const WC_CK = 'ck_19b8432006f83a09f94ca5f226b0676c166ddaa9'; 
    const WC_CS = 'cs_e72e729fc207ba3e2a065f01e0749db220978815'; 

    public function __construct() {
        $actions = [
            'get_specialties', 
            'get_services_by_cat', 
            'get_booking_slots', 
            'process_direct_order'
        ];

        foreach ( $actions as $action ) {
            add_action( 'wp_ajax_wcbw_' . $action, [ $this, $action ] );
            add_action( 'wp_ajax_nopriv_wcbw_' . $action, [ $this, $action ] );
        }
    }

    public function get_specialties() {
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);
        $data = [];
        if ( ! is_wp_error( $terms ) ) {
            foreach ($terms as $term) {
                $thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
                $data[] = [
                    'id'    => $term->term_id,
                    'name'  => $term->name,
                    'image' => $thumb_id ? wp_get_attachment_url( $thumb_id ) : wc_placeholder_img_src()
                ];
            }
        }
        wp_send_json_success($data);
    }

    public function get_services_by_cat() {
        $cat_id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
        $products = get_posts([
            'post_type'      => 'product',
            'tax_query'      => [[ 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $cat_id ]],
            'posts_per_page' => -1,
            'status'         => 'publish'
        ]);
        
        $data = [];
        foreach ($products as $p) {
            $prod = wc_get_product($p->ID);
            if ( $prod && is_a( $prod, 'WC_Product_Booking' ) ) {
                $data[] = [ 'id' => $p->ID, 'name' => $p->post_title ];
            }
        }
        wp_send_json_success($data);
    }

    public function get_booking_slots() {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $date_str   = sanitize_text_field($_POST['date']);

        if ( !$product_id || empty( $date_str ) ) wp_send_json_error( 'Faltan datos.' );

        $min_date = $date_str;
        $max_date = date('Y-m-d', strtotime($date_str . ' +1 day'));

        $api_url = add_query_arg([
            'product_ids' => $product_id,
            'min_date'    => $min_date,
            'max_date'    => $max_date,
            'nocache'     => time() 
        ], home_url('/wp-json/wc-bookings/v1/products/slots'));

        $auth = base64_encode( self::WC_CK . ':' . self::WC_CS );

        $response = wp_remote_get($api_url, [
            'headers' => [ 'Authorization' => 'Basic ' . $auth ],
            'timeout' => 45
        ]);

        if ( is_wp_error( $response ) ) wp_send_json_error( 'Error API: ' . $response->get_error_message() );

        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        if ( isset($json['code']) ) wp_send_json_error( 'Error API: ' . ($json['message'] ?? 'Desconocido') );

        $available_data = [];
        $slots_found    = [];

        if ( isset($json['records']) && is_array($json['records']) ) {
            $product      = wc_get_product($product_id);
            $resources    = $product->get_resources();
            $resource_map = [];
            
            foreach($resources as $r) {
                $resource_map[$r->get_id()] = $r->get_name();
            }

            $now_ts = current_time('timestamp');

            foreach ($json['records'] as $slot) {
                if ( !isset($slot['available']) || $slot['available'] <= 0 ) continue;
                if ( $slot['product_id'] != $product_id ) continue;
                if ( substr($slot['date'], 0, 10) !== $date_str ) continue;

                $slot_time = strtotime($slot['date']);
                if ( $slot_time <= $now_ts ) continue;

                $res_id   = isset($slot['resource']) ? $slot['resource'] : (isset($slot['resource_id']) ? $slot['resource_id'] : 0);
                $doc_name = isset($resource_map[$res_id]) ? $resource_map[$res_id] : $product->get_name();

                if (!isset($slots_found[$res_id])) {
                    $slots_found[$res_id] = [
                        'product_id'  => $product_id,
                        'resource_id' => $res_id,
                        'doctor_name' => $doc_name,
                        'price'       => $product->get_price_html(),
                        'slots'       => []
                    ];
                }

                $slots_found[$res_id]['slots'][] = [
                    'time'  => date('H:i', $slot_time),
                    'value' => date('Y-m-d H:i:s', $slot_time)
                ];
            }
        }

        foreach($slots_found as $group) {
            usort($group['slots'], function($a, $b) { return strcmp($a['time'], $b['time']); });
            $available_data[] = $group;
        }

        wp_send_json_success($available_data);
    }

    public function process_direct_order() {
        check_ajax_referer( 'wcbw_nonce', 'nonce' );

        try {
            $product_id  = intval($_POST['product_id']);
            $resource_id = intval($_POST['resource_id']);
            $date_str    = sanitize_text_field($_POST['date']); 
            $time_str    = sanitize_text_field($_POST['time']); 

            $rut            = sanitize_text_field($_POST['rut']);
            $first_name     = sanitize_text_field($_POST['name']);
            $email          = sanitize_email($_POST['email']);
            $phone          = sanitize_text_field($_POST['phone']);
            $contact_method = sanitize_text_field($_POST['contact_method']); // <-- Recibimos el dato

            $product = wc_get_product($product_id);
            if (!$product) throw new Exception('Producto no encontrado.');

            $clean_date_str = substr($date_str . ' ' . $time_str, 0, 16);
            $start_date     = strtotime($clean_date_str);
            
            $duration = $product->get_duration();
            $unit     = $product->get_duration_unit();
            $seconds  = $unit === 'hour' ? $duration * 3600 : ($unit === 'minute' ? $duration * 60 : $duration * 86400);
            $end_date = $start_date + $seconds;

            if ( $product->has_resources() && empty( $resource_id ) ) {
                $resources = $product->get_resources();
                if ( ! empty( $resources ) ) {
                    $first_resource = current( $resources );
                    $resource_id = $first_resource->get_id();
                }
            }

            // --- B. CREAR PEDIDO ---
            $order = wc_create_order();
            $item_id = $order->add_product($product, 1);
            
            $address = [
                'first_name' => $first_name,
                'email'      => $email,
                'phone'      => $phone,
                'company'    => $rut,
                'address_1'  => 'Reserva Online',
                'city'       => 'Santiago',
                'country'    => 'CL'
            ];
            
            $order->set_address($address, 'billing');
            $order->calculate_totals();
            
            $gateway_id = 'transbank_webpay_plus_rest'; 
            $order->set_payment_method($gateway_id);
            $order->set_status('pending', 'Reserva creada vía Wizard. Esperando pago.');
            
            // Añadimos una nota interna al pedido para que el médico lo vea rápido
            $order->add_order_note('El paciente ha solicitado que la atención sea vía: ' . $contact_method);
            $order->save();

            // --- C. CREAR RESERVA ---
            $booking = new WC_Booking();
            $booking->set_product_id($product_id);
            $booking->set_resource_id($resource_id); 
            $booking->set_start($start_date);
            $booking->set_end($end_date);
            $booking->set_all_day(false);
            $booking->set_order_id($order->get_id());
            $booking->set_order_item_id($item_id);
            $booking->set_status('unpaid'); 
            $booking->set_cost($order->get_total());
            $booking->set_customer_id($order->get_customer_id());
            
            if ( is_callable( [$product, 'has_persons'] ) && $product->has_persons() ) {
                $booking->set_person_counts( [ 0 => 1 ] );
            }
            
            $booking->save(); 

            // Vinculamos la reserva al pedido y agregamos la plataforma elegida visible para el cliente
            wc_add_order_item_meta($item_id, 'Booking ID', $booking->get_id());
            wc_add_order_item_meta($item_id, '_booking_id', $booking->get_id());
            wc_add_order_item_meta($item_id, 'Plataforma de atención', $contact_method); // <-- Guardado aquí

            if ( class_exists( 'WC_Bookings_Cache' ) ) {
                WC_Bookings_Cache::delete_booking_slots_transient( $product_id );
            }
            delete_transient( 'wc_bookings_availability' );
            if ( class_exists('WC_Cache_Helper') ) {
                WC_Cache_Helper::get_transient_version( 'bookings', true );
            }

            // --- E. URL DE PAGO ---
            $pay_url = '';
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            
            if ( isset( $available_gateways[ $gateway_id ] ) ) {
                $result = $available_gateways[ $gateway_id ]->process_payment( $order->get_id() );
                if ( isset($result['result']) && $result['result'] === 'success' && isset($result['redirect']) ) {
                    $pay_url = $result['redirect'];
                }
            }

            if ( empty($pay_url) ) {
                $pay_url = $order->get_checkout_payment_url();
            }

            wp_send_json_success(['redirect' => $pay_url]);

        } catch (Exception $e) {
            wp_send_json_error( 'Error: ' . $e->getMessage() );
        }
    }
}