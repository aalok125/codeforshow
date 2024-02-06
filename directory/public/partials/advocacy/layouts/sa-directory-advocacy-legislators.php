<?php
if (class_exists('SaDirectoryAPI')) {
    global $directory_seo_title;
    global $directory_seo_desc;
    $fullurl = null;
    $hideEmail = true;

    if (function_exists('getDirectoryFullUrl')) {
        $fullurl = getDirectoryFullUrl();
    }

    $republicanCount = 0;
    $democraticCount = 0;
    $state = get_query_var('advocacy_state');
    $category = get_query_var('advocacy_type');
    $current_state = $state;
    if ($state || $category) {
        $state = preg_replace("/[^A-Za-z0-9 ]/", '', $state);
        $category = preg_replace("/[^A-Za-z0-9 ]/", '', $category);
    }

    $activeAside = $category;

    $directory_seo_title = returnAdvocacySeoDetails($state, $category)['title'] ?? '';
    $directory_seo_desc = returnAdvocacySeoDetails($state, $category)['description'] ?? '';

    $checkProMember = checkProMember($state);



    // $data = $api->get_directories_data("api/legislators?state=" . $state . "&directory=" . $category);
    $data = $prodApi->get_directories_data("api/legislators?state=" . $state . "&directory=" . $category);

    // $secretary = $api->get_directories_data("api/directories-information/" . $state . "/" . $category);
    $secretary = $prodApi->get_directories_data("api/directories-information/" . $state . "/" . $category);


    $allmemberEmails = $emailApi->get_emails("legislators/" . $state . "/all_members?branch=" . $category);
    $allDemocratsEmails = $emailApi->get_emails("legislators/" . $state . "/democratic?branch=" . $category);
    $allRepublicansEmails = $emailApi->get_emails("legislators/" . $state . "/republican?branch=" . $category);
   



    if (is_wp_error($data)) {
        echo 'Error fetching directories: ' . $data->get_error_message();
    } elseif (empty($data)) {
        // For some reason, this code fires when we try to access /committees or /committees/in|ks...
        get_header();
        get_template_part('template-parts/content', 'none');
        get_footer();
        exit();
    } else {




        if ($data[0]->legislators) {
            foreach ($data[0]->legislators as $legislator) {

                if($legislator->active_until_date) continue;
                
                if (isset($legislator->party_type->name) && $legislator->party_type->name == 'Republican') {
                    $republicanCount++;
                } elseif (isset($legislator->party_type->name) && $legislator->party_type->name == 'Democratic') {
                    $democraticCount++;
                }
            }
        }

        $partyCounts = [
            'Republicans' => $republicanCount,
            'Democrats' => $democraticCount,
        ];
        arsort($partyCounts);


        get_header();
?>

        <section class="directory--section pageSection" id="directoryList">
            <div class="o-section__wrapper">
                <div class="directory--main">
                    <?php
                    include(plugin_dir_path(dirname(__FILE__)) . '/common/aside.php');  ?>

                    <div class="directory--box">
                        <div class="home--block">
                            <div class="title">
                                <h1><img src="<?php echo plugins_url('../assets/advocacy-tools.png', dirname(__FILE__)) ?>" alt="Advocacy Tools"> Advocacy Tools</h1>
                            </div>
                            <div class="stateSearchWrapper">
                                <?php
                                $siteStates = array(
                                    'in' => 'Indiana',
                                    'ks' => 'Kansas',
                                );
                                if ($siteStates && count($siteStates) > 0) :
                                ?>
                                    <div class="states">
                                        <h5>Select a state</h5>
                                        <select name="states" id="states" class="large gfield_select select2-hidden-accessible custom-select" onchange="handleNavStateChange(event)">
                                            <?php foreach ($siteStates as $key => $sitestate) : ?>
                                                <option <?php selected($key, $current_state ?? '') ?> value="<?php echo site_url('pro/directories/advocacy/' . $key . '/' . $category); ?>">
                                                    <?php echo $sitestate; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                        <?php if ($checkProMember['status'] == true) : ?>
                            <div class="dir-detail--box">
                                <ul class="dir-detail--range">
                                    <li><button class="trigger"><svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M11.4651 16L19.4651 8L20.8984 9.43333L14.3318 16L20.8984 22.5667L19.4651 24L11.4651 16Z" fill="#7F919F" />
                                            </svg>
                                        </button></li>
                                    <li>2023-2024</li>
                                    <li><button class="trigger"><svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M20.5349 16L12.5349 24L11.1016 22.5667L17.6682 16L11.1016 9.43333L12.5349 8L20.5349 16Z" fill="#7F919F" />
                                            </svg>
                                        </button></li>
                                </ul>
                                <ul class="dir-detail--list">
                                <li>
                                        <h4><?php echo $secretary->data->address ?? '' ?></h4>
                                    </li>
                                    <li style="display: none;">Secretary: <a class="disabled" href="<?php echo $secretary->data->directory_url ?? ''; ?>" title="<?php echo $secretary->data->secretary_name ?? '' ?>"><?php echo $secretary->data->secretary_name ?? '' ?></a></li>
                                    <?php if ($secretary->data->phone) : ?>
                                        <li>Phone Number: <a href="tel:<?php echo $secretary->data->phone; ?>" title="Get in touch"><?php echo $secretary->data->phone; ?></a></li>
                                    <?php endif; ?>
                                    <?php if ($secretary->data->directory && $secretary->data->directory_url) : ?>
                                        <li><a target="_blank" href="<?php echo $secretary->data->directory_url; ?>" title="Senate Website"><?php echo ucfirst($secretary->data->directory) ?> Website</a></li>
                                <?php endif; ?>
                                </ul>
                                <ul class="dir-detail--block">
                                    <?php foreach ($partyCounts as $party => $count) : ?>
                                        <li>
                                            <p class="number"><?php echo $count; ?></p>
                                            <p><?php echo $party; ?></p>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="dir-detail--emailMembers">
                                    <ul class="emails">
                                        <?php if ($allmemberEmails) : ?>
                                            <li><a href="mailto:<?php echo $allmemberEmails; ?>" title="email all members" class="ga-pro-feature-email-all-members">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M2.66536 2.66666H13.332C14.0654 2.66666 14.6654 3.26666 14.6654 3.99999V12C14.6654 12.7333 14.0654 13.3333 13.332 13.3333H2.66536C1.93203 13.3333 1.33203 12.7333 1.33203 12V3.99999C1.33203 3.26666 1.93203 2.66666 2.66536 2.66666Z" stroke="#00242C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="M14.6654 4L7.9987 8.66667L1.33203 4" stroke="#00242C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    email all members
                                                </a></li>
                                        <?php endif; ?>

                                        <?php if ($allDemocratsEmails) : ?>
                                            <li><a href="mailto:<?php echo $allDemocratsEmails; ?>" title="email democrats" class="ga-pro-feature-email-all-democrats">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M2.66536 2.66666H13.332C14.0654 2.66666 14.6654 3.26666 14.6654 3.99999V12C14.6654 12.7333 14.0654 13.3333 13.332 13.3333H2.66536C1.93203 13.3333 1.33203 12.7333 1.33203 12V3.99999C1.33203 3.26666 1.93203 2.66666 2.66536 2.66666Z" stroke="#00242C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="M14.6654 4L7.9987 8.66667L1.33203 4" stroke="#00242C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    email all democrats
                                                </a></li>
                                        <?php endif; ?>
                                        <?php if ($allRepublicansEmails) : ?>
                                            <li><a href="mailto:<?php echo $allRepublicansEmails; ?>" title="email republicans" class="ga-pro-feature-email-all-republicans">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M2.66536 2.66666H13.332C14.0654 2.66666 14.6654 3.26666 14.6654 3.99999V12C14.6654 12.7333 14.0654 13.3333 13.332 13.3333H2.66536C1.93203 13.3333 1.33203 12.7333 1.33203 12V3.99999C1.33203 3.26666 1.93203 2.66666 2.66536 2.66666Z" stroke="#00242C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="M14.6654 4L7.9987 8.66667L1.33203 4" stroke="#00242C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    email all republicans
                                                </a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                            <div class="dir-detail--action">
                                <?php
                                $buttonLabelText = $category == 'senate' ? 'View All Senators' : 'View All Representatives';
                                ?>
                                <a href="<?php echo site_url("pro/directories/" . $state . '/' . $category); ?>" title="<?php echo $buttonLabelText; ?>"><?php echo $buttonLabelText; ?></a>
                            </div>
                        <?php else :
                            $paywallMessage = getPaywallMessageAdvocacy($state);
                        ?>

                            <div class="staffNotPro">
                                <h5><?php echo $paywallMessage['title'] ?? '' ?></h5>
                                <p><?php echo $paywallMessage['message'] ?? '' ?></p>
                                <a href="<?php echo site_url('pro/request-a-demo/'); ?>" class="subscribe" title="Request A Demo">Request A Demo</a>
                                <?php if (!is_user_logged_in()) : ?>
                                    <p>Already a member?</p>
                                    <a href="<?php echo home_url('/pro/login/'); ?>" class="signIn theme2" title="Sign in">Sign in</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </section>

<?php
        get_footer();
    }
} else {
    die('The SaDirectoryAPI class is not defined.');
}
