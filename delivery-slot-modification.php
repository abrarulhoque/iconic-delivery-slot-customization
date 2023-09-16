// Modify allowed delivery days based on the selected shipping method and product category
function iconic_change_allowed_days($allowed_days) {
    $chosen_methods = WC()->session->get('chosen_shipping_methods');
    $chosen_shipping = $chosen_methods[0];

    if (empty($chosen_shipping)) {
        return $allowed_days;
    }

    $override_shipping_all_days = 'flat_rate:3'; // The shipping option to allow all days
    $override_shipping_saturday = 'flat_rate:2'; // The shipping option to allow only Saturday

    // Use the provided function to check for the 'frozen' category
    $is_frozen_category = iconic_enable_slots_for_specific_category(array('frozen'));

    $days = array(
        0 => false, // Sunday
        1 => false, // Monday
        2 => false, // Tuesday
        3 => false, // Wednesday
        4 => false, // Thursday
        5 => false, // Friday
        6 => false, // Saturday
    );

    if ($chosen_shipping === $override_shipping_all_days || ($chosen_shipping === $override_shipping_saturday && !$is_frozen_category)) {
        // User selected the shipping option 'flat_rate:3', so allow all days,
        // or selected 'flat_rate:2' but not for frozen products, so allow all days.
        foreach ($days as $day => $value) {
            $days[$day] = true;
        }
    } elseif ($chosen_shipping === $override_shipping_saturday && $is_frozen_category) {
        // User selected the shipping option 'flat_rate:2' for frozen products, so allow only Saturday.
        $days[6] = true; // Saturday
    }

    // Store billing information in session storage
    WC()->session->set('billing_information', $_POST);

    // Store the new postcode, first name, and last name in session storage
    if (isset($_POST['billing_postcode'])) {
        WC()->session->set('billing_postcode', sanitize_text_field($_POST['billing_postcode']));
    }

    if (isset($_POST['billing_first_name'])) {
        WC()->session->set('billing_first_name', sanitize_text_field($_POST['billing_first_name']));
    }

    if (isset($_POST['billing_last_name'])) {
        WC()->session->set('billing_last_name', sanitize_text_field($_POST['billing_last_name']));
    }

    return $days;
}

// Conditionally modify the same/next day cutoff time based on shipping methods
function iconic_conditionally_modify_cutoff_time() {
    global $iconic_wds;

    if ( empty( $iconic_wds ) || empty( WC()->session ) ) {
        return;
    }

    $shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

    if ( ! isset( $shipping_methods[0] ) && isset( $_POST['shipping_method'][0] ) ) {
        return;
    }

    $shipping_method = isset( $_POST['shipping_method'][0] ) ? $_POST['shipping_method'][0] : $shipping_methods[0];

    // Define the cutoff times based on shipping methods.
    $cutoff_time_flat_rate_2 = '14:00'; // Cutoff time for flat_rate:2
    $cutoff_time_flat_rate_3 = '18:00'; // Cutoff time for flat_rate:3

    if ( $shipping_method === 'flat_rate:2' || $shipping_method === 'flat_rate:3' ) {
        // For flat_rate:2 and flat_rate:3, set the same/next day cutoff times accordingly.
        $cutoff_time = $shipping_method === 'flat_rate:2' ? $cutoff_time_flat_rate_2 : $cutoff_time_flat_rate_3;
        $iconic_wds->settings['datesettings_datesettings_sameday_cutoff'] = $cutoff_time;
        $iconic_wds->settings['datesettings_datesettings_nextday_cutoff'] = $cutoff_time;
    } else {
        // For other shipping methods, you can set a default cutoff time if needed.
        // For example:
        // $iconic_wds->settings['datesettings_datesettings_sameday_cutoff'] = '00:00';
        // $iconic_wds->settings['datesettings_datesettings_nextday_cutoff'] = '00:00';
    }
}

// Add both actions
add_filter('iconic_wds_allowed_days', 'iconic_change_allowed_days');
add_action( 'wp_loaded', 'iconic_conditionally_modify_cutoff_time', 11 );

// Refresh the checkout page when the user changes the postcode and fills billing info
add_action('wp_footer', 'refresh_on_postcode_change');
function refresh_on_postcode_change() {
    ?>
    <script type="text/javascript">
        jQuery(function ($) {
            // Listen for input in the postcode field
            $('input#billing_postcode').on('input', function () {
                // Store billing information in session storage
                var billingInfo = <?php echo json_encode($_POST); ?>;
                sessionStorage.setItem('billing_information', JSON.stringify(billingInfo));

                // Store the new postcode, first name, and last name in session storage
                var newPostcode = $(this).val();
                sessionStorage.setItem('billing_postcode', newPostcode);

                var firstName = $('input#billing_first_name').val();
                sessionStorage.setItem('billing_first_name', firstName);

                var lastName = $('input#billing_last_name').val();
                sessionStorage.setItem('billing_last_name', lastName);

                // Reload the page with a 2-second delay
                setTimeout(function () {
                    location.reload();
                }, 5000); // Delay for 5 seconds (adjust as needed)
            });

            // Check if billing information is stored in session storage
            var storedBillingInfo = sessionStorage.getItem('billing_information');
            if (storedBillingInfo) {
                // Parse the stored billing information
                var billingInfoObj = JSON.parse(storedBillingInfo);

                // Fill in the billing fields
                $.each(billingInfoObj, function (key, value) {
                    $('input[name="' + key + '"]').val(value);
                });

                // Clear the stored billing information
                sessionStorage.removeItem('billing_information');
            }

            // Check if a new postcode, first name, and last name are stored in session storage
            var storedPostcode = sessionStorage.getItem('billing_postcode');
            if (storedPostcode) {
                // Fill in the postcode field with the new postcode
                $('input#billing_postcode').val(storedPostcode);
            }

            var storedFirstName = sessionStorage.getItem('billing_first_name');
            if (storedFirstName) {
                // Fill in the first name field with the stored first name
                $('input#billing_first_name').val(storedFirstName);
            }

            var storedLastName = sessionStorage.getItem('billing_last_name');
            if (storedLastName) {
                // Fill in the last name field with the stored last name
                $('input#billing_last_name').val(storedLastName);
            }
        });
    </script>
    <?php
}
