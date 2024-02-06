<?php



function isDirectoryPages()
{
    global $wp_query;
    if (
        isset($wp_query->query_vars['directory']) || isset($wp_query->query_vars['directory_page'])
        || isset($wp_query->query_vars['committee_slug']) || isset($wp_query->query_vars['committee_state'])
        || isset($wp_query->query_vars['advocacy_page']) || isset($wp_query->query_vars['advocacy_state'])
        || isset($wp_query->query_vars['advocacy_state']) || isset($wp_query->query_vars['advocacy_slug'])
    ) {
        return true;
    }
    return false;
}

function sa_directory_enqueue_scripts()
{
    global $wp_query;
    if (isDirectoryPages()) {
        wp_enqueue_style(
            'sa-directory-style',
            plugins_url('assets/css/style.css', __FILE__),
            array(),
            '1.0.0'
        );
        wp_enqueue_script(
            'sa-directory-script',
            plugins_url('assets/js/sa-directory-script.js', __FILE__),
            array('jquery'), // Dependencies
            '1.0.0', // Version number
            true // Load in the footer
        );

        // Localize the correct script with new data
        $script_data_array = array(
            'site_url' => get_bloginfo('url'),
            'directory_default_state' => returnDefaultDirectoryUrl('sc'),
            'directory_url' => home_url('directory'),
        );
        wp_localize_script('sa-directory-script', 'site_data', $script_data_array); // Use 'sa-directory-script'

    }
}
add_action('wp_enqueue_scripts', 'sa_directory_enqueue_scripts');


function search_legislators($legislators, $search_query)
{
    $search_query = strtolower($search_query);  // Convert search query to lower case
    $matching_legislators = [];  // Array to store matching legislators

    foreach ($legislators as $legislator) {
        $first_name = strtolower($legislator->first_name);
        $last_name = strtolower($legislator->last_name);
        $full_name = $first_name . ' ' . $last_name;

        if (strpos($first_name, $search_query) !== false || strpos($last_name, $search_query) !== false || strpos($full_name, $search_query) !== false) {
            $matching_legislators[] = $legislator;
        }
    }

    return $matching_legislators;
}


function checkProMember($state = null)
{
    $response = [
        'status' => false,
        'message' => 'Unlock this information with State Affairs Pro'
    ];

    $stateNames = [
        'in' => 'Indiana',
        'ks' => 'Kansas'
    ];





    if (is_user_logged_in()) {
        $user = wp_get_current_user();

        // Check if the user is an administrator
        if (in_array('administrator', (array) $user->roles)) {
            $response['status'] = true;
            $response['message'] = '';
            return $response;
        }

        // Check if WooCommerce memberships plugin function exists
        if (function_exists('wc_memberships_get_user_memberships') && !empty(wc_memberships_get_user_memberships(get_current_user_id()))) {
            $user_id = get_current_user_id();
            $args = array('status' => array('active'));
            $plans = wc_memberships_get_user_memberships($user_id, $args);
            $plans_ids = array_map(function ($plan) {
                return $plan->plan_id;
            }, $plans);



            $membershipPlansWithStates = get_field('field_64c788a7d2e6a', 'option');
            if (is_array($membershipPlansWithStates) && !empty($membershipPlansWithStates)) {
                $stateMemberShips = array_column($membershipPlansWithStates, 'membership_plans', 'state');



                if ($state && isset($stateMemberShips[$state])) {

                    $common_products = array_intersect($stateMemberShips[$state], $plans_ids);
                    if (!empty($common_products)) {
                        $response['status'] = true;
                    } else if (isset($stateNames[$state])) {
                        $response['message'] = 'You must have State Affairs Pro ' . $stateNames[$state] . ' account to view this content.';
                    }
                }
            }
        }
    }



    return $response;
}



function getDirectoryFullUrl($added_slug = null)
{
    global $wp;
    $current_url = home_url(add_query_arg(array($_GET), $wp->request));
    if ($added_slug) {
        $current_url = $current_url . '/' . $added_slug;
    }
    return $current_url;
}




function returnDefaultDirectoryUrl($type = null)
{

    // Check if the user is a pro member
    $proMemberStatus = checkProMember()['status'];

    if (!$proMemberStatus && $type == null) {
        // Return URL for non-pro members
        return home_url('pro/directories/');
    }


    // Get the default state based on user's filter preferences
    $defaultState = "indiana";

    if (function_exists('returnUserStateforFilter')) {
        $defaultState = returnUserStateforFilter();
    }


    // List of valid states for the directory
    $directoryStates = ['indiana', 'kansas'];

    // Ensure the default state is valid; if not, fallback to "indiana"
    if (!in_array($defaultState, $directoryStates)) {
        $defaultState = "indiana";
    }

    // Return the state name if type is "sc"
    if ($type != null && $type == "sn") {
        return $defaultState;
    }

    // Convert state slug to state code
    $stateCode = strtolower(sa_convert_state_slug_to_state_code($defaultState));

    // Return the state code if type is "sc"
    if ($type != null && $type == "sc") {
        return $stateCode;
    }



    // Return URL for pro members
    $url = home_url('pro/directories/' . $stateCode . '/senate/');
    return $url;
}
add_filter('wpseo_robots', 'yoast_seo_robots_replace', 10, 1);
function yoast_seo_robots_replace($robots)
{
    if (isDirectoryPages()) {
        return 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    }
    return $robots;
}

// Hook to modify the title for dynamic page
function directory_dynamic_page_wpseo_title($title)
{
    global $wp_query;
    if (isDirectoryPages()) {
        global $directory_seo_title;
        if (isset($directory_seo_title)) {
            return $directory_seo_title;
        }
    }
    return $title;
}
add_filter('wpseo_title', 'directory_dynamic_page_wpseo_title');


// Hook to modify the meta description for dynamic page
function directory_dynamic_page_wpseo_metadesc($metadesc)
{

    if (isDirectoryPages()) {
        global $directory_seo_desc;
        if (isset($directory_seo_desc)) {
            return $directory_seo_desc;
        }
    }
    return $metadesc;
}
add_filter('wpseo_metadesc', 'directory_dynamic_page_wpseo_metadesc');



function returnDirectorySeoDetails($state = null, $type = null)
{
    $args = [
        'title' => 'State Legislative Directory - State Affairs ',
        'description' => 'State general assembly guide for House, Senate and Judiciary members. Find profiles, contact information, committees and the latest news on your state legislators.'
    ];

    if ($state == 'in' && $type == 'senate') {
        $args = [
            'title' => 'Indiana Senate Members - State Affairs',
            'description' => 'Find Indiana Senate contact information, news, committee details and profiles for your senator.'
        ];
    } else if ($state == 'in' && $type == 'house') {
        $args = [
            'title' => 'Indiana House of Representatives Members - State Affairs ',
            'description' => 'Indiana House of Representative profiles, district information for democrat and republican members. Find the latest news and contact information for your state legislators.'
        ];
    } elseif ($state == 'ks' && $type == 'house') {
        $args = [
            'title' => 'Kansas House of Representatives Members - State Affairs',
            'description' => 'Kansas House of Representative profiles, district information for democrat and republican members. Find the latest news and contact information for your state legislators.'
        ];
    } elseif ($state == 'ks' && $type == 'senate') {
        $args = [
            'title' => 'Kansas Senate Members - State Affairs',
            'description' => 'Find Kansas Senate contact information, news, committee details and profiles for your senator.'
        ];
    }

    return $args;
}

function returnCommitteeSeoDetails($state = null, $category = null)
{
    $states = [
        'ks' => 'Kansas',
        'in' => 'Indiana'
    ];

    $args = [];

    if (isset($states[$state]) && $category) {
        switch ($category) {
            case 'all':
                $args = [
                    'title' => $states[$state] . ' State Legislature Committees',
                    'description' => 'Explore ' . $states[$state] . ' State Legislature standing, joint, and interim committee members, meetings, and the latest news.'
                ];
                break;

            case 'senate':
                $args = [
                    'title' => $states[$state] . ' Senate Committees',
                    'description' => 'Discover ' . $states[$state] . ' Senate standing committee information, including members, meetings, and the latest news.'
                ];
                break;

            case 'house':
                $args = [
                    'title' => $states[$state] . ' House Committees',
                    'description' => 'Access ' . $states[$state] . ' House standing committee information, including members, meetings, and the latest news.'
                ];
                break;

            case 'joint':
                $args = [
                    'title' => $states[$state] . ' Legislature Joint Committees',
                    'description' => 'View ' . $states[$state] . ' Legislature joint committee information, including members, meetings, and the latest news.'
                ];
                break;

            case 'interim':
                $args = [
                    'title' => $states[$state] . ' Legislature Interim Committees',
                    'description' => 'Browse ' . $states[$state] . ' Legislature interim committee information, including members, meetings, and the latest news.'
                ];
                break;

            default:
                $args = [
                    'title' => 'State Legislative Directory - State Affairs ',
                    'description' => 'State general assembly guide for House, Senate, and Judiciary members. Find profiles, contact information, committees, and the latest news on your state legislators.'
                ];
                break;
        }
    }

    return $args;
}



function returnAdvocacyEmailString($emailListArray, $type = null)
{
    if ($type == "all") {
        $emailListArray = array_merge($emailListArray->Democratic, $emailListArray->Republican);
    }


    $emailsList = null;
    $emails = array_map(function ($object) {

        // Check if there's an 'email' attribute
        if (!isset($object->email)) {
            error_log('Unexpected object structure: ' . print_r($object, true));
            return '';
        }
        return $object->email;
    }, $emailListArray);


    // Filter out empty values
    $emails = array_filter($emails);
    if (count($emails) > 0) {
        $emailsList = implode(',', $emails);
    }

    return $emailsList;
}


function returnDirectoryPostLink($wordpress_member_tag =  null)
{
    $postLink = null;
    $taxonomy_name = 'legislators_tags';
    $term = get_term_by('name', $wordpress_member_tag, $taxonomy_name);
    if (isset($term->slug)) {
        $postLink = get_term_link($term->slug, $taxonomy_name);
    }

    return $postLink;
}


function returnMemberToDirectorySinglePage($state =  null, $type = null, $wordpress_member_tag =  null)
{
    $postLink = null;
    if ($state && $type && $wordpress_member_tag) {

        $slug = str_replace('_', '-', $wordpress_member_tag);

        $postLink = home_url('pro/directories/' . $state . '/' . $type . '/' . $slug);
    }

    return $postLink;
}


function redirect_directory_to_pro_directories($wp)
{
    // Check if the request starts with 'directory/'
    if (isset($wp->request) && strpos($wp->request, 'directory/') === 0) {
        // Replace 'directory/' with 'pro/directories/' in the request
        $new_request = str_replace('directory/', 'pro/directories/', $wp->request);
        // Build the new URL
        $new_url = home_url($new_request);
        // Redirect to the new URL with a 301 Moved Permanently status
        wp_redirect($new_url, 301);
        exit;
    }
}

add_action('parse_request', 'redirect_directory_to_pro_directories');



function does_image_exist($path)
{
    $upload_dir = wp_upload_dir(); // Get the WordPress upload directory info
    $image_path = $upload_dir['basedir'];

    $realpath =  str_replace('/wp-content/uploads', '', $image_path);
    $full_path = $realpath  . $path;
    return file_exists($full_path);
}



function getPaywallMessageAdvocacy(?string $state = null): array
{
    // Default message
    $args = [
        'title' => 'Premium access for Pro subscribers',
        'message' => 'Unlock exclusive contact information and advocacy tools with State Affairs Pro'
    ];

    // Ensure the userHandler function provides an array with the expected structure.
    $userSubscriptions = userHandler()['subscriptions'] ?? [];
    $proMemberStatus = $userSubscriptions[0]['subscription_type'] ?? false;

    if ($proMemberStatus === "pro" && $state) {
        $statename = sa_convert_state_code_to_state_slug($state);
        $statename = ucfirst($statename);
        $args = [
            'title' => "Premium access for $statename Pro subscribers",
            'message' => "Unlock exclusive contact information and advocacy tools with State Affairs $statename Pro"
        ];
    }

    return $args;
}



function returnAdvocacySeoDetails($state = null, $type = null)
{
    // Map of states and their full names
    $states = [
        'ks' => 'Kansas',
        'in' => 'Indiana'
    ];


    $args = [
        'title' => 'Legislative Advocacy Tools',
        'description' => 'Contact state senators and representatives by chamber, committee or caucus. See General Assembly member email, phone numbers and committee assignments.'
    ];
    // Check if the state exists in the map
    if ($state && isset($states[$state]) && $type) {

        switch ($type) {
            case 'senate':
                $args = [
                    'title' => $states[$state] . ' Senate Contacts',
                    'description' => 'Use State Affairs legislative advocacy tools to contact ' . $states[$state] . ' Senate members'
                ];
                break;

            case 'house':
                $args = [
                    'title' => $states[$state] . ' House Contacts',
                    'description' => 'Use State Affairs legislative advocacy tools to contact ' . $states[$state] . ' House members'
                ];
                break;

            case 'committees':
                $args = [
                    'title' => $states[$state] . ' Legislature Committees Contacts',
                    'description' => 'Use State Affairs advocacy tools to contact ' . $states[$state] . ' Senate and House committee members'
                ];
                break;

            default:
                $args = [
                    'title' => 'Legislative Advocacy Tools',
                    'description' => 'Contact state senators and representatives by chamber, committee or caucus. See General Assembly member email, phone numbers and committee assignments.'
                ];
                break;
        }
    }

    return $args;
}
