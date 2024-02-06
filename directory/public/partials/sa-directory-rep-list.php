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
    $state = get_query_var('directory');
    $category = get_query_var('state');
    $current_state = $state;
    if ($state || $category) {
        $state = preg_replace("/[^A-Za-z0-9 ]/", '', $state);
        $category = preg_replace("/[^A-Za-z0-9 ]/", '', $category);
    }

    $activeAside = $category;

    $directory_seo_title = returnDirectorySeoDetails($state, $category)['title'] ?? '';
    $directory_seo_desc = returnDirectorySeoDetails($state, $category)['description'] ?? '';

    $checkProMember = checkProMember($state);

    // $data = $api->get_directories_data("api/legislators?state=" . $state . "&directory=" . $category);
    $data = $prodApi->get_directories_data("api/legislators?state=" . $state . "&directory=" . $category);

    // $secretary = $api->get_directories_data("api/directories-information/" . $state . "/" . $category);
    $secretary = $prodApi->get_directories_data("api/directories-information/" . $state . "/" . $category);

    $allmemberEmails = $emailApi->get_emails("legislators/" . $state . "/all_members?branch=" . $category);
    $allDemocratsEmails = $emailApi->get_emails("legislators/" . $state . "/democratic?branch=" . $category);
    $allRepublicansEmails = $emailApi->get_emails("legislators/" . $state . "/republican?branch=" . $category);




    $search_query = isset($_GET['search']) ? $_GET['search'] : '';
    $order_query = isset($_GET['sortby']) ? $_GET['sortby'] : '';
    $party_query = isset($_GET['party']) ? $_GET['party'] : '';


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
                    include(plugin_dir_path(dirname(__FILE__)) . '/partials/common/aside.php');  ?>
                    <div class="directory--box">
                        <div class="home--block">
                            <?php
                            if ($data[0] && $data[0]->directory) : ?>
                                <div class="title">
                                    <h1><?php echo $data[0]->directory->name ?> Directory</h1>
                                </div>
                            <?php endif; ?>
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
                                            <?php foreach ($siteStates as $key => $state) : ?>
                                                <option <?php selected($key, $current_state ?? '') ?> value="<?php echo site_url('directory/' . $key . '/' . $category); ?>">
                                                    <?php echo $state; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                <form class="searchBox" action="" method="GET">
                                    <button class="icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M19.9 20.975L13.325 14.4C12.825 14.8333 12.2417 15.1708 11.575 15.4125C10.9083 15.6541 10.2 15.775 9.45 15.775C7.65 15.775 6.125 15.15 4.875 13.9C3.625 12.65 3 11.1416 3 9.37498C3 7.60831 3.625 6.09998 4.875 4.84998C6.125 3.59998 7.64167 2.97498 9.425 2.97498C11.1917 2.97498 12.6958 3.59998 13.9375 4.84998C15.1792 6.09998 15.8 7.60831 15.8 9.37498C15.8 10.0916 15.6833 10.7833 15.45 11.45C15.2167 12.1166 14.8667 12.7416 14.4 13.325L21 19.875L19.9 20.975ZM9.425 14.275C10.775 14.275 11.925 13.7958 12.875 12.8375C13.825 11.8791 14.3 10.725 14.3 9.37498C14.3 8.02498 13.825 6.87081 12.875 5.91248C11.925 4.95414 10.775 4.47498 9.425 4.47498C8.05833 4.47498 6.89583 4.95414 5.9375 5.91248C4.97917 6.87081 4.5 8.02498 4.5 9.37498C4.5 10.725 4.97917 11.8791 5.9375 12.8375C6.89583 13.7958 8.05833 14.275 9.425 14.275Z" fill="#0D0D0D" />
                                        </svg></button>
                                    <input type="text" name="search" value="<?php echo $search_query; ?>" id="search" placeholder="Search Representative">
                                </form>
                            </div>

                        </div>
                        <!-- <div class="directory--filter"> -->
                        <!-- Search From Removed From Here -->
                        <!-- <div class="filterButton">
                                <button onclick="handleDirFilter(event)">
                                    <p class="icon"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M14.6654 2H1.33203L6.66536 8.30667V12.6667L9.33203 14V8.30667L14.6654 2Z" stroke="#161616" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </p> <span>Filters</span>
                                </button>
                                <ul>
                                    <li><a href="</?php echo add_query_arg('party', 'Democratic', $fullurl); ?>" title="Democrats">Democrats</a></li>
                                    <li><a href="</?php echo add_query_arg('party', 'Republican', $fullurl); ?>" title="Republicans">Republicans</a></li>
                                </ul>
                            </div> -->
                        <!-- </div> -->
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
                            <?php if ($secretary->data) : ?>
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
                            <?php endif; ?>
                            <ul class="dir-detail--block">
                                <?php foreach ($partyCounts as $party => $count) : ?>
                                    <li>
                                        <p class="number"><?php echo $count; ?></p>
                                        <p><?php echo $party; ?></p>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ($checkProMember['status'] == true) : ?>
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
                            <?php endif; ?>
                        </div>

                        <div class="dir-representative--box">
                            <div class="rep--group">
                                <?php if ($category == "house") : ?>
                                    <h5>Representatives</h5>
                                <?php else : ?>

                                    <h5>Senators</h5>
                                <?php endif; ?>
                                <div class="sortBy">
                                    <button onclick="handleDirFilter(event)"><span>Sort By</span>
                                        <p class="icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3 18V16H9V18H3ZM3 13V11H15V13H3ZM3 8V6H21V8H3Z" fill="#7F919F" />
                                            </svg>
                                        </p>
                                    </button>
                                    <ul>
                                        <li><a href="<?php echo add_query_arg('sortby', 'asc', $fullurl); ?>">A-Z</a></li>
                                        <li><a href="<?php echo add_query_arg('sortby', 'desc', $fullurl); ?>">Z-A</a></li>
                                        <li><a href="<?php echo add_query_arg('sortby', 'district', $fullurl); ?>">district number</a></li>
                                    </ul>
                                </div>
                                <div class="rep--listGroup loaderWrapper">
                                    <div id="repLoader">
                                        <div class="lds-dual-ring"></div>
                                    </div>
                                    <ul class="rep--lists">
                                        <?php
                                        $legislators = $data[0]->legislators;

                                        if ($search_query) {
                                            $legislators = search_legislators($legislators, $search_query);
                                        }
                                        if ($order_query) {
                                            if ($order_query == "asc") {
                                                usort($legislators, function ($a, $b) {
                                                    return strcmp($a->first_name, $b->first_name);
                                                });
                                            } elseif ($order_query == "desc") {
                                                usort($legislators, function ($a, $b) {
                                                    return strcmp($b->first_name, $a->first_name);
                                                });
                                            } elseif ($order_query == "district") {
                                                usort($legislators, function ($a, $b) {
                                                    return $a->district - $b->district;
                                                });
                                            }
                                        }

                                        if ($party_query == 'Democratic' || $party_query == 'Republican') {
                                            $legislators = array_filter($legislators, function ($legislator) use ($party_query) {
                                                return $legislator->party_type->name == $party_query;
                                            });
                                        }




                                        // $legislators = $data->legislators;

                                        foreach ($legislators as $legislator) : ?>

                                            <?php

                                            // Skip displaying member if is inactive but only if searched for.
                                            if(!$search_query && $legislator->active_until_date) continue;

                                            $firstName = $legislator->first_name ?? '';
                                            $lastName = $legislator->last_name ?? '';
                                            $party = $legislator->party_type->name ?? '';
                                            $district = $legislator->district ?? '';

                                            $email = "SUPPORT@STATEAFFAIRS.COM";
                                            $phone = "SALES@STATEAFFAIRS.COM";
                                            if ($checkProMember['status'] == true) {
                                                $email = $legislator->email ?? '';
                                                $phone = $legislator->work_phone[0] ?? '';
                                            }



                                            $profilePicture = $legislator->profile_picture_path ?? null;
                                            $twitter_url = $legislator->social_media_link->twitter ?? '';
                                            $office_number = $legislator->office_number ?? '';

                                            if ($profilePicture) {
                                                // $profilePicture = $api->get_profile_picture($profilePicture);
                                                $profilePicture = $prodApi->get_profile_picture($profilePicture);
                                            } else {
                                                $profilePicture = plugins_url('assets/images/profile.png', dirname(__FILE__));
                                            }
                                            $wordpress_member_tag = $legislator->wordpress_member_tag ?? '';

                                            $slug = '';
                                            if ($wordpress_member_tag) {
                                                $slug = str_replace('_', '-', $wordpress_member_tag);
                                            }
                                            $urlLink = site_url('pro/directories/' . strtolower($current_state) . '/' . $category . '/' . $slug);
                                            ?>

                                            <li>
                                                <?php
                                                $fileExists = false;
                                                if (file_exists(ABSPATH . $legislator->profile_picture_path)) {
                                                    $fileExists = true;
                                                }
                                                if ($profilePicture && $fileExists) : ?>
                                                    <figure>
                                                        <a href="<?php echo $urlLink; ?>"><img src='<?php echo $profilePicture ?>' alt='<?php echo $firstName ?>'></a>
                                                    </figure>
                                                <?php else : ?>
                                                    <figure>
                                                        <a href="<?php echo $urlLink; ?>"><img src='<?php echo home_url(); ?>/wp-content/uploads/legislators_profile_pictures/legislator_placeholder.png' alt=''></a>
                                                    </figure>
                                                <?php endif; ?>
                                                <div class='detail'>
                                                    <h5><a href="<?php echo $urlLink; ?>"><?php echo $firstName ?> <?php echo $lastName ?></a></h5>
                                                    <h6><?php echo $party ?></h6>
                                                    <p> District <?php echo $district ?></p>
                                                </div>
                                                <!-- If Not Pro add class notPro -->
                                                <div class="infoContactBlock <?php if ($checkProMember['status'] == false) {
                                                                                    echo "notPro";
                                                                                } ?>">
                                                    <ul class='info contact'>
                                                        <li>Email: <a href='mailto:<?php echo $email ?>' title='Mail'><?php echo $email ?></a></li>
                                                        <li>Phone Number: <a href='tel:<?php echo $phone ?>' title='Call'><?php echo $phone; ?></a></li>
                                                    </ul>
                                                    <?php if ($checkProMember['status'] == false) : ?>
                                                        <p class="infoContactMessage">
                                                            <span><?php echo $checkProMember['message'] ?? ''; ?></span>
                                                            <?php if (!is_user_logged_in()) : ?>
                                                                <a href="<?php echo home_url('/pro/login/'); ?>" title="Sign in">
                                                                    Sign in
                                                                </a>
                                                            <?php endif; ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($office_number) : ?>
                                                    <ul class='info'>
                                                        <li>Room <?php echo $office_number; ?></li>
                                                    </ul>
                                                <?php endif; ?>
                                                <?php if ($twitter_url) : ?>
                                                    <ul class='social'>
                                                        <li><a href='<?php echo $twitter_url; ?>' title='Twitter' target="_blank">
                                                                <svg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'>
                                                                    <circle cx='15' cy='15' r='15' fill='#7F9195' />
                                                                    <path d='M21.9109 11.2515C21.3998 11.4787 20.8498 11.6319 20.2726 11.7005C20.8619 11.3477 21.3137 10.7889 21.5268 10.1223C20.9756 10.4494 20.3655 10.6868 19.7155 10.8149C19.1954 10.2606 18.454 9.91406 17.6336 9.91406C16.0585 9.91406 14.7814 11.1911 14.7814 12.7665C14.7814 12.9897 14.8065 13.2073 14.8554 13.4164C12.4847 13.2973 10.3827 12.162 8.97583 10.4362C8.7303 10.8572 8.58975 11.3472 8.58975 11.8704C8.58975 12.8599 9.09324 13.7331 9.85862 14.2445C9.39126 14.2295 8.95128 14.1011 8.56661 13.8875C8.56633 13.8993 8.56633 13.9115 8.56633 13.9236C8.56633 15.3053 9.54959 16.4579 10.8546 16.7204C10.6153 16.7853 10.3632 16.8203 10.103 16.8203C9.91902 16.8203 9.74037 16.8025 9.56624 16.7689C9.92946 17.9021 10.9827 18.727 12.2307 18.7499C11.2545 19.515 10.0249 19.971 8.68825 19.971C8.45824 19.971 8.23105 19.9575 8.00781 19.931C9.27047 20.7407 10.7696 21.2125 12.3803 21.2125C17.6271 21.2125 20.4958 16.8663 20.4958 13.097C20.4958 12.9733 20.4933 12.85 20.4879 12.7275C21.045 12.3262 21.5288 11.8236 21.9109 11.2515Z' fill='white' />
                                                                </svg>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                <?php endif; ?>
                                            </li>

                                        <?php
                                        endforeach;

                                        ?>

                                    </ul>
                                    <?php if (count($legislators) > 3) : ?>
                                        <button class="showMore" aria-label="see-more" onclick="handleListShow(event)">See More</button>
                                    <?php elseif (count($legislators) == 0 || empty($legislators)) : ?>
                                        <button class="showMore" aria-label="see-more">No Data Found!!</button>
                                    <?php endif; ?>
                                </div>



                            </div>

                            <!-- Staff -->
                            <!-- <div class="rep--group">
                                <h5 class="trigger" onclick="handleAboutSidebarClick(event)">Staff</h5>
                                <div class="items">
                                    <ul class="staffList">
                                        <li>
                                            <strong>Tiffany Castro</strong>
                                            <a href="#" title="">email@example.com</a>
                                        </li>
                                        <li>
                                            <strong>Grace Bell</strong>
                                            <a href="#" title="">email@example.com</a>
                                        </li>
                                        <li>
                                            <strong>Benjamin Hamilton</strong>
                                            <a href="#" title="">email@example.com</a>
                                        </li>
                                    </ul>
                                    <div class="staffNotPro">
                                        <h5>Content exclusive for Pro users</h5>
                                        <p>Subscribe to Pro Account to have access to all Directories</p>
                                        <a href="#" class="subscribe" title="subscribe to state affairs pro">subscribe to state affairs pro</a>
                                        <p>Already a member?</p>
                                        <a href="#" class="signIn" title="Sign in">Sign in</a>
                                    </div>
                                </div>
                            </div> -->

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
