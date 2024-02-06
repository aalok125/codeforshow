<?php

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SaDirectoryAPI')) {

	class SaDirectoryAPI
	{

		private $base_url;

		public function __construct($base_url)
		{
			$this->base_url = $base_url;
		}

		public function get_directories()
		{
			$url = $this->base_url . '/directories';
			return $this->fetch_data($url);
		}

		public function get_directories_data($directory_slug)
		{
			$url = $this->base_url . $directory_slug;
			return $this->fetch_data($url);
		}


		public function get_profile_picture($path)
		{

			$url = site_url($path);
			return $url;
		}

		private function fetch_data($url)
		{
			$response = wp_remote_get($url);

			if (is_wp_error($response)) {
				return $response;
			}

			$body = wp_remote_retrieve_body($response);
			return json_decode($body);
		}
	}
}
