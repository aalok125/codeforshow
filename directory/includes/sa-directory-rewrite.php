<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SaDirectoryRewrite')) {
    class SaDirectoryRewrite
    {
        public function __construct()
        {
            add_action('init', array($this, 'add_rewrite_rules'), 20);
            add_filter('query_vars', array($this, 'add_query_vars'));
            add_action('template_include', array($this, 'include_templates'));
            add_filter('request', array($this, 'modify_request_for_advocacy'));
           
        }

        public function add_rewrite_rules()
        {

            // Advocacy rewrite rules
            add_rewrite_rule('^pro/directories/advocacy/?$', 'index.php?advocacy_page=true', 'top');
            add_rewrite_rule('^pro/directories/advocacy/([^/]*)/([^/]*)/?$', 'index.php?advocacy_state=$matches[1]&advocacy_type=$matches[2]', 'top');

            // Committees rewrite rules
            add_rewrite_rule('^pro/directories/committee/([^/]*)/([^/]*)/([^/]*)/([^/]*)/?', 'index.php?committee_state=$matches[1]&category=$matches[2]&year=$matches[3]&committee_slug=$matches[4]', 'top');
            add_rewrite_rule('^pro/directories/committees/([^/]*)/([^/]*)/([^/]*)/?', 'index.php?committee_state=$matches[1]&category=$matches[2]&year=$matches[3]', 'top');

            // Previous rewrite rules
            add_rewrite_rule('^pro/directories/([^/]*)/([^/]*)/([^/]*)/?', 'index.php?directory=$matches[1]&state=$matches[2]&representative=$matches[3]', 'top');
            add_rewrite_rule('^pro/directories/([^/]*)/([^/]*)/?', 'index.php?directory=$matches[1]&state=$matches[2]', 'top');
            add_rewrite_rule('^pro/directories/([^/]*)/?', 'index.php?directory=$matches[1]', 'top');
            add_rewrite_rule('^pro/directories/?', 'index.php?directory_page=true', 'top');
        }

        public function add_query_vars($query_vars)
        {
            // Previous query vars
            $query_vars[] = 'directory';
            $query_vars[] = 'state';
            $query_vars[] = 'representative';
            $query_vars[] = 'directory_page';

            // Committeess query vars
            $query_vars[] = 'committee_state';
            $query_vars[] = 'category';
            $query_vars[] = 'year';
            $query_vars[] = 'committee_slug';

            // Advocacy query vars
            $query_vars[] = 'advocacy_page';
            $query_vars[] = 'advocacy_state';
            $query_vars[] = 'advocacy_type';

            return $query_vars;
        }

        public function include_templates($template)
        {
            if (get_query_var('advocacy_type')) {
                return plugin_dir_path(dirname(__FILE__)) . 'public/partials/advocacy/sa-directory-advocacy-list.php';
            } elseif (get_query_var('advocacy_state')) {
                return plugin_dir_path(dirname(__FILE__)) . 'public/partials/advocacy/sa-directory-advocacy-list.php';
            } elseif (get_query_var('advocacy_page')) {
                return plugin_dir_path(dirname(__FILE__)) . 'public/partials/advocacy/sa-directory-advocacy-page.php';
            } elseif (get_query_var('committee_slug')) {
                return plugin_dir_path(dirname(__FILE__)) . 'public/partials/committees/sa-directory-committee-single.php';
            } elseif (get_query_var('committee_state')) {
                return plugin_dir_path(dirname(__FILE__)) . 'public/partials/committees/sa-directory-committees.php';
            } elseif (get_query_var('representative')) {
                return plugin_dir_path(dirname(__FILE__)) . 'public/partials/sa-directory-rep-single.php';
            } elseif (get_query_var('state')) {
                return plugin_dir_path(dirname(__FILE__)) . 'public/partials/sa-directory-rep-list.php';
            } elseif (get_query_var('directory')) {
                return plugin_dir_path(dirname(__FILE__)) . 'public/partials/sa-directory-rep-list.php';
            } elseif (get_query_var('directory_page')) {
                return plugin_dir_path(dirname(__FILE__)) . 'public/partials/sa-directory-display.php';
            }
            return $template;
        }
        
        
        public function modify_request_for_advocacy($query_vars) {
            if (isset($query_vars['category_name']) && $query_vars['category_name'] === 'directories' && isset($query_vars['article_type']) && $query_vars['article_type'] === 'advocacy') {
                $query_vars = array('advocacy_page' => true);
            }
            return $query_vars;
        }
        
    }
}
