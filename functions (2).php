<?php
/**
 * Woostify
 *
 * @package woostify
 */

// Define constants.
define( 'WOOSTIFY_VERSION', '2.2.3' );
define( 'WOOSTIFY_PRO_MIN_VERSION', '1.7.8' );
define( 'WOOSTIFY_THEME_DIR', get_template_directory() . '/' );
define( 'WOOSTIFY_THEME_URI', get_template_directory_uri() . '/' );

// Woostify svgs icon.
require_once WOOSTIFY_THEME_DIR . 'inc/class-woostify-icon.php';

// Woostify functions, hooks.
require_once WOOSTIFY_THEME_DIR . 'inc/woostify-functions.php';
require_once WOOSTIFY_THEME_DIR . 'inc/woostify-template-hooks.php';
require_once WOOSTIFY_THEME_DIR . 'inc/woostify-template-builder.php';
require_once WOOSTIFY_THEME_DIR . 'inc/woostify-template-functions.php';

// Woostify generate css.
require_once WOOSTIFY_THEME_DIR . 'inc/customizer/class-woostify-webfont-loader.php';
require_once WOOSTIFY_THEME_DIR . 'inc/customizer/class-woostify-fonts-helpers.php';
require_once WOOSTIFY_THEME_DIR . 'inc/customizer/class-woostify-get-css.php';

// Woostify customizer.
require_once WOOSTIFY_THEME_DIR . 'inc/class-woostify.php';
require_once WOOSTIFY_THEME_DIR . 'inc/customizer/class-woostify-customizer.php';

// Woostify woocommerce.
if ( woostify_is_woocommerce_activated() ) {
	require_once WOOSTIFY_THEME_DIR . 'inc/woocommerce/class-woostify-woocommerce.php';
	require_once WOOSTIFY_THEME_DIR . 'inc/woocommerce/class-woostify-adjacent-products.php';
	require_once WOOSTIFY_THEME_DIR . 'inc/woocommerce/woostify-woocommerce-template-functions.php';
	require_once WOOSTIFY_THEME_DIR . 'inc/woocommerce/woostify-woocommerce-archive-product-functions.php';
	require_once WOOSTIFY_THEME_DIR . 'inc/woocommerce/woostify-woocommerce-single-product-functions.php';
}

// Woostify admin.
if ( is_admin() ) {
	require_once WOOSTIFY_THEME_DIR . 'inc/admin/class-woostify-admin.php';
	require_once WOOSTIFY_THEME_DIR . 'inc/admin/class-woostify-meta-boxes.php';
}

// Compatibility.
require_once WOOSTIFY_THEME_DIR . 'inc/compatibility/class-woostify-divi-builder.php';

/**
 * Note: Do not add any custom code here. Please use a custom plugin so that your customizations aren't lost during updates.
 */

//para agregar el campo dni al registro
add_action( 'woocommerce_register_form', 'agregar_campo_dni' );
function agregar_campo_dni() {
    if ( get_field( 'dni', 'user_' . get_current_user_id() ) ) {
        return;
    }
    ?>
    <p class="form-row form-row-wide">
        <label for="dni"><?php _e( 'DNI', 'text-domain' ); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="dni" id="dni" value="<?php echo ( isset( $_POST['dni'] ) ) ? esc_attr( $_POST['dni'] ) : ''; ?>" />
    </p>
    <?php
}


add_action( 'woocommerce_register', 'validar_campo_dni', 10, 3 );
function validar_campo_dni( $username, $email, $validation_errors ) {
    if ( empty( $_POST['dni'] ) ) {
        $validation_errors->add( 'dni_error', __( 'Por favor, ingresa tu DNI.', 'text-domain' ) );
    }
    return $validation_errors;
}

//valida que no haya registrado ya ese dni
add_action('woocommerce_register_post', 'validar_dni_registro', 10, 3);
function validar_dni_registro($username, $email, $validation_errors){
    $dni = sanitize_text_field($_POST['dni']); // Obtener el valor del campo DNI del formulario de registro
    
    // Verificar si ya existe un usuario con el mismo DNI
    $existing_user = get_users(array('meta_key' => 'dni', 'meta_value' => $dni));
    
    if (!empty($existing_user)) {
        $validation_errors->add('dni_error', __('Ya existe un usuario registrado con este DNI. Por favor, ingresa un DNI diferente.', 'text-domain'));
    }
    
    return $validation_errors;
}

add_action( 'woocommerce_created_customer', 'guardar_campo_dni' );
function guardar_campo_dni( $customer_id ) {
    if ( ! empty( $_POST['dni'] ) ) {
        update_field( 'dni', sanitize_text_field( $_POST['dni'] ), 'user_' . $customer_id );
    }
}
//fin de codigo agregar campo dni

//reemplazar id por DNI
// Cambiar el ID de usuario al DNI ingresado en el campo DNI
add_action('woocommerce_created_customer', 'cambiar_id_usuario_a_dni');
function cambiar_id_usuario_a_dni($customer_id){
    $dni = sanitize_text_field($_POST['dni']); // Obtener el valor del campo DNI del formulario de registro
    
    global $wpdb;
    $wpdb->update($wpdb->users, array('ID' => $dni), array('ID' => $customer_id), array('%s'), array('%d')); // Actualizar el ID de usuario en la tabla wp_users
    $wpdb->update($wpdb->usermeta, array('user_id' => $dni), array('user_id' => $customer_id), array('%s'), array('%d')); // Actualizar las referencias al ID de usuario en la tabla wp_usermeta
}


//para solo mostrar los productos a los que estan logueados
add_filter( 'woocommerce_variable_sale_price_html', 'update_price_html', 10, 2 );
add_filter( 'woocommerce_variable_price_html', 'update_price_html', 10, 2 );
add_filter( 'woocommerce_get_price_html','update_price_html', 999, 2 );

function update_price_html( $html, $product ) {

    if(!is_user_logged_in()) {  // Si el usuario no está logueado
        add_filter( 'woocommerce_is_purchasable', '__return_false');
        $html = "Necesitas estar registrado para ver los precios";
        return $html;
    } else {
        return $html;
    }
}