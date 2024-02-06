<?php

// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Remove rewrite rules and then flush them
remove_rewrite_rules();
flush_rewrite_rules();

// Function to remove rewrite rules
function remove_rewrite_rules() {
    global $wp_rewrite;
    
    foreach ($wp_rewrite->rules as $rule => $rewrite) {
        if (false !== strpos($rule, 'directory')) {
            unset($wp_rewrite->rules[$rule]);
        }
    }
}
