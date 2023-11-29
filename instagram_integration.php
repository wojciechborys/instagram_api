<?php 

    /*
    Plugin Name: Instagram API integration
    Description: Simple integration to fetch photos
    Version: 1.0
    Author: Wojciech Borys
    */

    namespace InstagramApp;

    if( ! defined( 'ABSPATH' ) ) exit;

    require_once('config.php');
    require_once('inc/class_instagram_admin_panel.php');
    if ( ! function_exists( 'download_url' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    class Instagram_integration {

        private $instagram_id = INSTAGRAM_ID;
        private $instagram_secret = INSTAGRAM_SECRET;
        private $instagram_redirect_uri = INSTAGRAM_REDIRECT_URI;
        private $instagram_oauth_url = 'https://api.instagram.com/oauth/authorize';
        private $instagram_graph_url = 'https://graph.instagram.com';
        private $short_lived_token = '';
        public $long_lived_token = '';
        private $access_token_call_url = '';
        public $instagram_oauth_url_call = '';
        public $instagram_photos_ids = [];
        public function __construct() {
            $this->getUserToken();
        }

        private function getUserToken() {
            $params = array(
                'redirect_uri'  => $this->instagram_redirect_uri,
                'scope'         => 'user_profile,user_media',
                'response_type' => 'code',
                'state'         => 'page=instagram_api',
                'client_id'     => $this->instagram_id,
            );
            
            $this->instagram_oauth_url_call = $this->instagram_oauth_url .'?'. http_build_query($params);
        }

        private function postLongLivedToken() {
            $params = array(
                'body' => array(
                    'client_id'     => $this->instagram_id,
                    'client_secret' => $this->instagram_secret,
                    'grant_type'    => 'authorization_code',
                    'redirect_uri'  => 'https://api.wojciechborys.pl/wp-admin/admin.php/',
                    'code'          => $this->short_lived_token,
                )
            );

            $url = 'https://api.instagram.com/oauth/access_token';
            
            $response = wp_remote_post($url, $params);
            
            if (is_wp_error($response)) {
                echo 'Wystąpił błąd: ' . $response->get_error_message();
            } else {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                $this->long_lived_token = $data['access_token'];
                $this->getUserInstagramFeed();
            }
        }

        private function getUserInstagramFeed() {
            $base_url = 'https://graph.instagram.com/';
            $api_version = 'v18.0';
            $endpoint = 'me/media';
            $fields = 'media_url';
            $access_token = $this->long_lived_token;

            $request_url = sprintf(
                '%s%s/%s?fields=%s&access_token=%s',
                $base_url,
                $api_version,
                $endpoint,
                $fields,
                $access_token
            );

            $response = wp_remote_get($request_url);

            if (is_wp_error($response)) {
                echo 'Błąd zapytania: ' . esc_html($response->get_error_message());
            } else {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                $this->handleMediaFromAPI($data);
            }
        }
        
        public function setNewToken($token) {
            $this->short_lived_token = $token;
            $this->postLongLivedToken();
        }

        public function redirectToPanel() {
            wp_redirect(admin_url('admin.php?page=instagram_api'));
            exit();
        }

        public function displayImages() {
            $photos = $this->instagram_photos_ids;
            foreach($photos as $photo):
                echo '<img src="' . $this->getImageByTitle($photo) . '" />';
            endforeach;
        }

        public function handleMediaFromAPI($data) {
            $instagram_data = $data['data'];
            $stored_instagram_data = array();
            
            foreach ($instagram_data as $photo) {
                $image_url = $photo['media_url'];
                $image_ID = $photo['id'];
                $stored_instagram_data[] = $image_ID;
                $this->saveMediaFromURL($image_url, $image_ID);
            }
            update_option('instagram_photos_ids', $stored_instagram_data);
            $this->redirectToPanel();
        }

        private function saveMediaFromURL($url, $ID) {
    
            if (empty($url)) {
                return false;
            }
        
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        
            $media_name = basename($ID);
        
            $media_id = media_sideload_image($url, 0, $media_name);
        
            if (is_wp_error($media_id)) {
                return false;
            }
        
            return $media_id;
        }

        public function getImageByTitle($title) {
            $attachment = get_page_by_title($title, OBJECT, 'attachment');
        
            if ($attachment) {
                $attachment_id = $attachment->ID;
                $image_url = wp_get_attachment_url($attachment_id);

                return $image_url;
            }
        
            return false;
        }
    }
?>