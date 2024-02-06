<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SaDirectoryEmailAPI')) {

    class SaDirectoryEmailAPI
    {

        private $base_url;

        public function __construct($base_url)
        {
            $this->base_url = $base_url;
        }



        public function get_emails($email_slug)
        {
            $url = $this->base_url . $email_slug;
            
            $data = $this->fetch_email_data($url);

            // Check if there is an error with fetching the data
            if (is_wp_error($data)) {
                error_log('Error fetching data: ' . $data->get_error_message());
                return $data;
            }

            if (!isset($data->data) || !is_array($data->data)) {
                error_log('Unexpected data structure: ' . print_r($data, true));
                return new WP_Error('unexpected_data_structure', 'Unexpected data structure');
            }

            return $this->returnEmailString($data);
        }


        public function returnEmailString($emailListArray)
        {
            $emailsList = null;
            $emails = array_map(function ($object) {
                // Check if there's an 'email' attribute
                if (!isset($object->email)) {
                    error_log('Unexpected object structure: ' . print_r($object, true));
                    return '';
                }
                return $object->email;
            }, $emailListArray->data);

            // Filter out empty values
            $emails = array_filter($emails);
            if (count($emails) > 0) {
                $emailsList = implode(',', $emails);
            }

            return $emailsList;
        }

        private function fetch_email_data($url)
        {
            $response = wp_remote_get($url);

            if (is_wp_error($response)) {
                error_log('API request error: ' . $response->get_error_message());
                return $response;
            }

            $body = wp_remote_retrieve_body($response);
            $decoded_body = json_decode($body);

            // Check if decoding was successful
            if (is_null($decoded_body)) {
                error_log('Failed to decode JSON response: ' . print_r($body, true));
                return new WP_Error('json_decode_error', 'Failed to decode JSON response');
            }

            return $decoded_body;
        }
    }
}
