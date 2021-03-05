<?php

namespace MySiteDigital\User;

use AXI_User_Validation;

if (!defined('ABSPATH')) {
    exit;
}

class ProfileExtensions
{


    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('output_user_name_field', [$this, 'frontend_username_field'], 10);
        add_action('user_profile_update_errors', [$this, 'validate_username_update'], 10, 3);
    }

    public function frontend_username_field($userdata)
    {
        ?>
            <p>
                <label for="first_name">User Name</label>
                <input type="text" name="user_login" id="user_login" class="text regular-text" value="<?php echo esc_attr($userdata->user_login);  ?>" />
            </p>
        <?php
    }

    public function validate_username_update($errors, $update, $user)
    {
        //first check this is an existing user
        if (property_exists($user, 'ID')){
            $user_id = $user->ID;
            $old_user_login = $user->user_login;
            $new_user_login = $old_user_login;
            if (isset($_POST['user_login'])) {
                $new_user_login = trim( sanitize_text_field($_POST['user_login']) );
            }



            //we need to validate annother user doesn't exist with the same name
            if ($new_user_login !== $old_user_login) {
                $another_user_has_user_login = username_exists($new_user_login);
                if ($another_user_has_user_login) {
                    $errors->add('user_login', '<strong>ERROR</strong>: You can\'t change you username to ' . $new_user_login . ', another user is using that username.');
                    return;
                }



                if (is_plugin_active('appthemes-xero-invoices/appthemes-xero-invoices.php')) {
                    $xero_contact_exists = AXI_User_Validation::check_if_xero_contact_exists($new_user_login);
                    //check if there is Xero Contact with the desired name
                    if ($xero_contact_exists) {
                        $errors->add('user_login', '<strong>ERROR</strong>: You can\'t change you username to ' . $new_user_login . ', another user is using that username.');
                        return;
                    }
                }

                // //now update the username
                global $wpdb;
                $update1 = $wpdb->prepare("UPDATE $wpdb->users SET user_login = %s WHERE user_login = %s", $new_user_login, $old_user_login);

                if ($wpdb->query($update1) !== false) {
                    // Update user_nicename.
                    $update2 = $wpdb->prepare("UPDATE $wpdb->users SET user_nicename = %s WHERE user_login = %s AND user_nicename = %s", $new_user_login, $new_user_login, $old_user_login); // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables
                    $wpdb->query(
                        $update2
                    );

                    // Update display_name.
                    $update3 = $wpdb->prepare("UPDATE $wpdb->users SET display_name = %s WHERE user_login = %s AND display_name = %s", $new_user_login, $new_user_login, $old_user_login); // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables
                    $wpdb->query(
                        $update3
                    );

                    // If the user is a Super Admin, update their permissions.
                    if (is_multisite() && is_super_admin($user_id)) {
                        grant_super_admin($user_id);
                    }

                    wp_set_auth_cookie( $user_id );
                }

                //we may also need to update the contact name in Xero
                if (is_plugin_active('appthemes-xero-invoices/appthemes-xero-invoices.php')) {
                    $xero_contact_update = AXI_User_Validation::update_contact_name($user_id, $new_user_login);
                }

            }
        } 
    }
}

new ProfileExtensions();
