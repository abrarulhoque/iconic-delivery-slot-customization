// Modify allowed delivery days based on the selected shipping method
function iconic_change_allowed_days($allowed_days) {
    $chosen_methods = WC()->session->get('chosen_shipping_methods');
    $chosen_shipping = $chosen_methods[0];

    if (empty($chosen_shipping)) {
        return $allowed_days;
    }

    $override_shipping_all_days = 'flat_rate:3'; // The shipping option to allow all days
    $override_shipping_saturday = 'flat_rate:2'; // The shipping option to allow only Saturday

    $days = array(
        0 => false, // Sunday
        1 => false, // Monday
        2 => false, // Tuesday
        3 => false, // Wednesday
        4 => false, // Thursday
        5 => false, // Friday
        6 => false, // Saturday
    );

    if ($chosen_shipping === $override_shipping_all_days) {
        // User selected the shipping option 'flat_rate:3', so allow all days.
        foreach ($days as $day => $value) {
            $days[$day] = true;
        }
    } elseif ($chosen_shipping === $override_shipping_saturday) {
        // User selected the shipping option 'flat_rate:2', so allow only Saturday.
        $days[6] = true; // Saturday
    }

    // Store billing information in session storage
    WC()->session->set('billing_information', $_POST);

    // Store the new postcode in session storage
    if (isset($_POST['billing_postcode'])) {
        WC()->session->set('billing_postcode', sanitize_text_field($_POST['billing_postcode']));
    }

    return $days;
}

add_filter('iconic_wds_allowed_days', 'iconic_change_allowed_days');

// Refresh the checkout page when the user changes the postcode and fill billing info
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

                // Store the new postcode in session storage
                var newPostcode = $(this).val();
                sessionStorage.setItem('billing_postcode', newPostcode);

                // Reload the page with a 2-second delay
                setTimeout(function () {
                    location.reload();
                }, 5000); // Delay for 2 seconds (adjust as needed)
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

            // Check if a new postcode is stored in session storage
            var storedPostcode = sessionStorage.getItem('billing_postcode');
            if (storedPostcode) {
                // Fill in the postcode field with the new postcode
                $('input#billing_postcode').val(storedPostcode);
            }
        });
    </script>
    <?php
}
