<?php 


$category = get_query_var('advocacy_type');

if ($category == "committees") {
    include(plugin_dir_path(dirname(__FILE__)) . 'advocacy/layouts/sa-directory-advocacy-committees.php');
} else {
    include(plugin_dir_path(dirname(__FILE__)) . 'advocacy/layouts/sa-directory-advocacy-legislators.php');
}


