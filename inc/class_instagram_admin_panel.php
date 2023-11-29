<?php
namespace InstagramApp;

if( ! defined( 'ABSPATH' ) ) exit;

class Instagram_Admin_Panel {

    public function __construct() {
        add_action('init', array($this, 'check_url_param'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Instagram',
            'Instagram',
            'manage_options',
            'instagram_api',
            array($this, 'render_admin_page'),
            'dashicons-instagram',
            10
        );
    }

    public function render_admin_page() {
        $token = isset($_GET['my_token']) ? $_GET['my_token'] : '';

        include_once(plugin_dir_path(__FILE__) . '../views/admin-page.php');
    }

    /*
    This part is necessary since Instagram oAuth redirect URI strips my basic URL from parameters. In the end, user was redirected to some blank page. 
    */

    public function check_url_param() {
        if(isset($_GET['code'])) {
            $myClass = new \InstagramApp\Instagram_integration();
            $myClass->setNewToken($_GET['code']);
        }
    }
}

new \InstagramApp\Instagram_Admin_Panel();

?>
