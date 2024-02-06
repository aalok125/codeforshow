<?php
if (class_exists('SaDirectoryAPI')) {
    global $directory_seo_title;
    global $directory_seo_desc;
    $fullurl = null;
    $hideEmail = false;

    if (function_exists('getDirectoryFullUrl')) {
        $fullurl = getDirectoryFullUrl();
    }


    $state = get_query_var('advocacy_state');
    $category = get_query_var('advocacy_type');
    $current_state = $state;
    if ($state || $category || $year) {
        $state = preg_replace("/[^A-Za-z0-9 ]/", '', $state);
        $category = preg_replace("/[^A-Za-z0-9 ]/", '', $category);
    }

    $activeAside = $category;

    $directory_seo_title = returnAdvocacySeoDetails($state, $category)['title'] ?? '';
    $directory_seo_desc = returnAdvocacySeoDetails($state, $category)['description'] ?? '';

    $checkProMember = checkProMember($state);


    $data = $prodApi->get_directories_data("api/committees/" . $state . "/all/2023");

    if (is_wp_error($data)) {
        echo 'Error fetching directories: ' . $data->get_error_message();
    } elseif (empty($data)) {
        // Handle the situation here if data is empty
        echo 'No Data found.';
    } else {


        get_header();
?>


        <section class="directory--section pageSection" id="committeeMain">
            <div class="o-section__wrapper">
                <div class="directory--main">
                    <?php
                    include(plugin_dir_path(dirname(__FILE__)) . '/common/aside.php');  ?>
                    <div class="directory--box">
                        <div class="home--block">
                            <div class="title">
                                <h1><img src="<?php echo plugins_url('../assets/advocacy-tools.png', dirname(__FILE__)) ?>" alt="Advocacy Tools"> Advocacy Tools</h1>

                            </div>
                            <div class="stateSearchWrapper states">
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
                                            <?php foreach ($siteStates as $key => $sstate) :
                                                $stateUrl = site_url('pro/directories/advocacy/' . strtolower($key) . '/' . $category);
                                            ?>
                                                <option <?php selected($key, $current_state ?? '') ?> value="<?php echo $stateUrl; ?>">
                                                    <?php echo $sstate; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                        if ($checkProMember['status'] == true) : ?>
                            <div class="dir-committee--box">
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
                                <?php
                                $tyleLists = [
                                    'All' => '',
                                    'Senate' => 'senate',
                                    'House' => 'house',
                                    'Joint' => 'joint',
                                    'Interim' => 'interim',
                                ]
                                ?>
                                <div class="dir-committee--itemBox">
                                    <?php if ($tyleLists && count($tyleLists) > 0) : ?>
                                        <ul class="dir-committee--filter">
                                            <?php foreach ($tyleLists as $key => $type) :

                                            ?>
                                                <li><button class="<?php if ($type == $category || $type == '') {
                                                                        echo "active";
                                                                    } ?>" data-slug="<?php echo $type; ?>" title="<?php echo $key; ?>"><?php echo $key; ?></button></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <?php
                                    if (isset($data->success) && $data->success == true && isset($data->data) && count($data->data) > 0) :
                                    ?>
                                        <div class="dir-committee--items" id="dirCommitteeItems" data-empty="Currently, there are no committees listed for this legislative session.">
                                            <?php foreach ($data->data as $committee) :
                                                $slug = $committee->id ?? "";
                                                $type = $committee->type ?? null;
                                                $urlLink = site_url('/pro/directories/committee/' . strtolower($state) . '/' . strtolower($type) . '/2023/' . $slug);
                                                $parties = [];
												foreach ($committee->membersPartiesCount as $party => $count) {
													if ($count > 0) {
														$parties[$count] = "{$party} {$count}";
													}
												}
												krsort($parties);

                                                $is_interim = $committee->is_interim ?? 0;


                                                $allmemberEmails = null;
                                                $allDemocratsEmails = null;
                                                $allRepublicansEmails = null;
                                                if (isset($committee->advocacyEmails)) {

                                                    $allmemberEmails = returnAdvocacyEmailString($committee->advocacyEmails, 'all');

                                                    $allDemocratsEmails = returnAdvocacyEmailString($committee->advocacyEmails->Democratic);
                                                    $allRepublicansEmails = returnAdvocacyEmailString($committee->advocacyEmails->Republican);
                                                }


                                                $interim = $is_interim == 1 ? "interim" : null;
                                                $filterType = strtolower($type)


                                            ?>
                                                <div class="dir-committee--item " data-filter="<?php echo $filterType; ?>,<?php echo $interim; ?>">
                                                    <a href="<?php echo $urlLink; ?>">
                                                        <div class="title">
                                                            <h6><?php echo $committee->name ?? ""; ?></h6>
                                                            <?php if ($category === 'all') : //Only show label on "all" tab, as requested: https://app.asana.com/0/1204886861861967/1205347770423695/f 
                                                            ?>
                                                                <p class="committee_name"><span><?php echo $committee->type ?? ""; ?></span></p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php
                                                        if (count($parties) > 0) :
                                                            $parties_str = implode(' / ', $parties);
                                                        ?>
                                                            <p><?php echo htmlspecialchars($parties_str); ?></p>
                                                        <?php
                                                        endif;
                                                        ?>
                                                    </a>
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
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
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
            </div>
        </section>


<?php
        get_footer();
    }
} else {
    die('The SaDirectoryAPI class is not defined.');
}
