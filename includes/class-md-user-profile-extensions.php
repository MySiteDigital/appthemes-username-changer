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
        add_action('init', [$this, 'init'], 10, 3);
        add_action('user_profile_update_errors', [$this, 'validate_username_update'], 10, 3);
    }

    public function init(){
      if ( current_user_can('manage_options') ) {
          add_action( 'show_user_profile', [ $this, 'update_username_field' ], 9999 );
          add_action( 'edit_user_profile', [ $this, 'update_username_field' ], 9999 );
      }
    }

    public function update_username_field($userdata)
    {
        ?>
        <h2>Update User Name</h2>
        <table class="form-table">
            <tbody>
                <tr class="user-comment-shortcuts-wrap">
                    <th scope="row"><label for="user_login">User Name</label></th>
                    <td>
                        <input type="text" name="user_login" id="user_login" class="text regular-text" value="<?php echo esc_attr($userdata->user_login);  ?>" />
                    </td>
                </tr>
            </tbody>
        </table>
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
                    $errors->add('user_login', '<strong>ERROR</strong>: You can\'t change your username to ' . $new_user_login . ', another user is using that username.');
                    return;
                }



                if (is_plugin_active('appthemes-xero-invoices/appthemes-xero-invoices.php')) {
                    $xero_contact_exists = AXI_User_Validation::check_if_xero_contact_exists($new_user_login);
                    //check if there is Xero Contact with the desired name
                    if ($xero_contact_exists) {
                        $message = '<strong>ERROR</strong>: You can\'t change your username to ' . $new_user_login . ', another user is using that username.';
                        if(current_user_can( 'manage_options' )){
                            $message = '<strong>ERROR</strong>: You can\'t change this user\'s username to <strong>' . $new_user_login . '</strong>, there is an existing Xero Contact using that name.';
                        }
                        $errors->add('user_login', $message);
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
