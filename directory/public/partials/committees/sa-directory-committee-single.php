<?php
if (class_exists('SaDirectoryAPI')) {
    global $directory_seo_title;
    global $directory_seo_desc;
    $fullurl = null;
    $hideEmail = false;

    if (function_exists('getDirectoryFullUrl')) {
        $fullurl = getDirectoryFullUrl();
    }


    $republicanCount = 0;
    $democraticCount = 0;
    $state = get_query_var('committee_state');
    $category = get_query_var('category');
    $year = get_query_var('year');
    $committee_slug = get_query_var('committee_slug');
    $current_state = $state;
    if ($state || $category || $year || $committee_slug) {
        $state = preg_replace("/[^A-Za-z0-9 ]/", '', $state);
        $category = preg_replace("/[^A-Za-z0-9 ]/", '', $category);
        $year = preg_replace("/[^A-Za-z0-9 ]/", '', $year);
        $committee_slug = preg_replace("/[^A-Za-z0-9 ]/", '', $committee_slug);
    }




    $checkProMember = checkProMember($state);

    $data = $prodApi->get_directories_data("api/committee/" . $committee_slug);


    if (is_wp_error($data)) {
        echo 'Error fetching directories: ' . $data->get_error_message();
    } elseif (empty($data)) {
        // Handle the situation here if data is empty
        echo 'No Data found.';
    } else {

        $committee = $data->data;


        $directory_seo_title = $committee->seo_title ?? '';
        $directory_seo_desc = $committee->seo_description ?? '';

        $committeeMeetings = $committee->committeeMeetings ?? '';

        get_header();


        if ($data->success == true) :


            $parties = [];
            foreach ($committee->membersPartiesCount as $party => $count) {
                if ($count > 0) {
                    $parties[$count] = "{$party} {$count}";
                }
            }

            krsort($parties);

            $allmemberEmails = $emailApi->get_emails("committees/" . $state . "/all_members?committee_id=" . $committee_slug);
            $allDemocratsEmails = $emailApi->get_emails("committees/" . $state . "/democratic?committee_id=" . $committee_slug);
            $allRepublicansEmails = $emailApi->get_emails("committees/" . $state . "/republican?committee_id=" . $committee_slug);
?>


            <section class="directory--section pageSection" id="committeeDetail">
                <div class="o-section__wrapper">
                    <div class="backButton">
                        <a href="<?php echo site_url('/pro/directories/committees/' . strtolower($state) . '/' . $category . '/' . $year); ?>" title="Back to Committees">
                            <p class="icon"><svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11.4651 16L19.4651 8L20.8984 9.43333L14.3318 16L20.8984 22.5667L19.4651 24L11.4651 16Z" fill="#0D0D0D"></path>
                                </svg></p>
                            <span>Back to Committees</span>
                        </a>
                    </div>
                    <div class="dir-committee--det">
                        <div class="titleBlock">
                            <h1><?php echo $committee->name ?? '' ?></h1>
                            <p><?php echo $committee->description ?? '' ?></p>
                        </div>
                        <div class="infoBlock">
                            <?php
                            if (count($parties) > 0) :
                                $parties_str = implode(' / ', $parties);
                            ?>
                                <h3><?php echo htmlspecialchars($parties_str); ?></h3>
                            <?php
                            endif;
                            ?>

                            <?php
                            $allmembers = [];
                            $unique_key = 0;
                            if ($committee->roles) :
                            ?>
                                <ul class="members">
                                    <?php
                                    foreach ($committee->roles as $role) :

                                    ?>
                                        <li><strong><?php echo $role->role_name ?? '' ?>:</strong>
                                            <ul>
                                                <?php foreach ($role->members as $key => $member) :
                                                    $name = $member->first_name . ' ' . $member->last_name;



                                                    $allmembers[$unique_key]['first_name'] = $member->first_name ?? '';
                                                    $allmembers[$unique_key]['last_name'] = $member->last_name ?? '';
                                                    $allmembers[$unique_key]['wordpress_member_tag'] = $member->wordpress_member_tag ?? '';
                                                    $allmembers[$unique_key]['party_type_name'] = $member->party_type_name ?? '';
                                                    $allmembers[$unique_key]['profile_picture_path'] = $member->profile_picture_path ?? '';
                                                    $allmembers[$unique_key]['role'] = $role->role_name  ?? '';

                                                ?>
                                                    <li><a href="<?php echo returnMemberToDirectorySinglePage($state ?? null, $category ?? null, $member->wordpress_member_tag ?? null); ?>" title="<?php echo $name; ?>"><?php echo $name; ?> </a></li>
                                                <?php
                                                    $unique_key++;
                                                endforeach; ?>
                                            </ul>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                            <?php endif; ?>

                            <?php if ($checkProMember['status'] == true) : ?>
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
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="com-singleBox">

                        <?php
                        if (($allmembers && count($allmembers) > 0) || ($committee->membersWithoutRoles && count($committee->membersWithoutRoles) > 0)) :
                        ?>

                            <div class="com-single">
                                <h3 class="pro--title">Members</h3>
                                <div class="com-single--members">
                                    <?php
                                    if ($allmembers && count($allmembers) > 0) :
                                        foreach ($allmembers as $member) :
                                            $name =  $member['first_name'] . ' ' .  $member['last_name'];
                                            $party_name =  $member['party_type_name'] ?? '';
                                            $image =  $member['profile_picture_path'] ?? '';
                                            $wordpress_tag = $member['wordpress_member_tag'] ?? '';
                                            $role = $member['role'] ?? '';
                                            if ($image) {
                                                // $profilePicture = $api->get_profile_picture($image);
                                                $profilePicture = $prodApi->get_profile_picture($image);
                                            } else {
                                                $profilePicture = plugins_url('assets/images/profile.png', dirname(__FILE__));
                                            }
                                    ?>
                                            <a href="<?php echo returnMemberToDirectorySinglePage($state ?? null, $category ?? null, $wordpress_tag ?? null); ?>" title="<?php echo $name; ?>">

                                                <?php
                                                $fileExists = false;
                                                if (file_exists(ABSPATH . $member->profile_picture_path)) {
                                                    $fileExists = true;
                                                }
                                                if ($profilePicture && $fileExists) : ?>
                                                    <figure>
                                                        <a href="<?php echo $urlLink; ?>"><img src='<?php echo $profilePicture ?>' alt='<?php echo $name ?>'></a>
                                                    </figure>
                                                <?php else : ?>
                                                    <figure>
                                                        <a href="<?php echo $urlLink; ?>"><img src='<?php echo home_url(); ?>/wp-content/uploads/legislators_profile_pictures/legislator_placeholder.png' alt=''></a>
                                                    </figure>
                                                <?php endif; ?>
                                                <h5><?php echo $name; ?></h5>
                                                <h6><?php echo $party_name; ?></h6>
                                                <p><?php echo $role; ?></p>
                                            </a>
                                        <?php endforeach;

                                    endif;

                                    // Get the members without roles
                                    $partyCounts = $committee->membersPartiesCount;

                                    // Create an associative array to map members to their party count
                                    $membersWithPartyCounts = [];

                                    if ($committee->membersWithoutRoles && count($committee->membersWithoutRoles) > 0) :


                                        foreach ($committee->membersWithoutRoles as $member) {
                                            $partyName = $member->party_type_name ?? '';



                                            // Check if the party name exists in $partyCounts, and default to 0 if not found

                                            $partyCount = $partyCount = $partyCounts->$partyName  ?? 0;

                                            $membersWithPartyCounts[] = [
                                                'name' => $member->first_name . ' ' . $member->last_name,
                                                'partyName' => $partyName,
                                                'partyCount' => $partyCount,
                                                'image' => $member->profile_picture_path ?? '',
                                                'wordpressTag' => $member->wordpress_member_tag ?? '',
                                                'directoryName' => strtolower($member->directory_name) ?? '',
                                            ];
                                        }

                                        usort($membersWithPartyCounts, function ($a, $b) {
                                            return $b['partyCount'] - $a['partyCount'];
                                        });

                                        foreach ($membersWithPartyCounts as $memberData) :

                                            $name =  $memberData['name'];
                                            $party_name =  $memberData['partyName'] ?? '';
                                            $image =  $memberData['image'] ?? '';
                                            $wordpress_tag = $memberData['wordpressTag'] ?? '';
                                            $directory_name = $memberData['directoryName'] ?? '';
                                            if ($image) {
                                                // $profilePicture = $api->get_profile_picture($image);
                                                $profilePicture = $prodApi->get_profile_picture($image);
                                            } else {
                                                $profilePicture = plugins_url('assets/images/profile.png', dirname(__FILE__));
                                            }
                                        ?>
                                            <a href="<?php echo returnMemberToDirectorySinglePage($state ?? null, $directory_name ?? $category, $wordpress_tag); ?>" title="<?php echo $name; ?>">
                                                <?php
                                                $fileExists = false;
                                                if (file_exists(ABSPATH . $image)) {
                                                    $fileExists = true;
                                                }

                                                if ($profilePicture && $fileExists) : ?>
                                                    <figure>
                                                        <img src='<?php echo $profilePicture ?>' alt='<?php echo $name ?>'>
                                                    </figure>
                                                <?php else : ?>
                                                    <figure>
                                                        <img src='<?php echo home_url(); ?>/wp-content/uploads/legislators_profile_pictures/legislator_placeholder.png' alt=''>
                                                    </figure>
                                                <?php endif; ?>
                                                <h5><?php echo $name; ?></h5>
                                                <h6><?php echo $party_name; ?></h6>
                                            </a>
                                    <?php endforeach;
                                    endif;
                                    ?>
                                </div>
                            </div>
                        <?php endif;
                        if ($committeeMeetings && is_array($committeeMeetings) && count($committeeMeetings) > 0) :
                        ?>


                            <div class="com-single">
                                <h3 class="pro--title">COMMITTEE MEETINGS</h3>
                                <div class="com-single--meetings">
                                    <table>
                                        <thead>
                                            <tr>
                                                <td>Date</td>
                                                <td>Time</td>
                                                <td>Location</td>
                                                <td>Meeting links</td>
                                                <td></td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($committeeMeetings as $meeting) {
                                                $date = $meeting->date ?? '';
                                                $time = $meeting->time ?? '';
                                                $location = $meeting->location ?? '';
                                                $meetingUrl = $meeting->virtual_meeting_url ?? '';
                                                $meeting_details = $meeting->meeting_details ?? '';
                                                $committee_name = $committee->name ?? '';

                                                $formattedDate = date('M j, Y', strtotime($date));
                                                $formattedTime = date('g:i A', strtotime($time));
                                                $eventStartDate = date("Ymd\THis", strtotime("$date $time"));
                                                $eventEndDate = date("Ymd\THis", strtotime("$date $time +2 hours"));

                                                $detailsWithUrl = $meeting_details . "\n\nJoin the virtual meeting: " . $meetingUrl;


                                                $googleCalendarUrl = "https://www.google.com/calendar/render?action=TEMPLATE";
                                                $googleCalendarUrl .= "&text=" . urlencode($committee_name);
                                                $googleCalendarUrl .= "&details=" . urlencode($detailsWithUrl);
                                                $googleCalendarUrl .= "&location=" . urlencode($location);
                                                $googleCalendarUrl .= "&dates=" . urlencode($eventStartDate . "/" . $eventEndDate);

                                            ?>
                                                <tr>
                                                    <td class="meet_date">
                                                        <?php echo $formattedDate; ?></td>
                                                    <td class="meet_time">
                                                        <?php echo $formattedTime; ?></td>
                                                    <td class="meet_location">
                                                        <?php echo $location; ?></td>
                                                    <td class="meet_link"><a target="_blank" href="<?php echo $meetingUrl; ?>" title="<?php echo $meetingUrl; ?>"><?php echo $meetingUrl; ?></a></td>
                                                    <td class="meet_actions">
                                                        <a href="<?php echo $googleCalendarUrl; ?>" target="_blank" title="Add to Calendar">
                                                            <p class="icon"><svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <g clip-path="url(#clip0_6692_5168)">
                                                                        <path d="M12.6901 2.20174H12.0234V1.53507C12.0234 1.35826 11.9532 1.18869 11.8282 1.06367C11.7032 0.938646 11.5336 0.868408 11.3568 0.868408C11.18 0.868408 11.0104 0.938646 10.8854 1.06367C10.7603 1.18869 10.6901 1.35826 10.6901 1.53507V2.20174H5.35677V1.53507C5.35677 1.35826 5.28653 1.18869 5.16151 1.06367C5.03648 0.938646 4.86692 0.868408 4.6901 0.868408C4.51329 0.868408 4.34372 0.938646 4.2187 1.06367C4.09368 1.18869 4.02344 1.35826 4.02344 1.53507V2.20174H3.35677C2.47304 2.2028 1.62581 2.55433 1.00092 3.17922C0.376025 3.80411 0.0244961 4.65135 0.0234375 5.53508L0.0234375 13.5351C0.0244961 14.4188 0.376025 15.266 1.00092 15.8909C1.62581 16.5158 2.47304 16.8674 3.35677 16.8684H12.6901C13.5738 16.8674 14.4211 16.5158 15.046 15.8909C15.6709 15.266 16.0224 14.4188 16.0234 13.5351V5.53508C16.0224 4.65135 15.6709 3.80411 15.046 3.17922C14.4211 2.55433 13.5738 2.2028 12.6901 2.20174ZM1.35677 5.53508C1.35677 5.00464 1.56748 4.49593 1.94256 4.12086C2.31763 3.74579 2.82634 3.53507 3.35677 3.53507H12.6901C13.2205 3.53507 13.7292 3.74579 14.1043 4.12086C14.4794 4.49593 14.6901 5.00464 14.6901 5.53508V6.20174H1.35677V5.53508ZM12.6901 15.5351H3.35677C2.82634 15.5351 2.31763 15.3244 1.94256 14.9493C1.56748 14.5742 1.35677 14.0655 1.35677 13.5351V7.53508H14.6901V13.5351C14.6901 14.0655 14.4794 14.5742 14.1043 14.9493C13.7292 15.3244 13.2205 15.5351 12.6901 15.5351Z" fill="#00242C" />
                                                                        <path d="M8.02344 11.8684C8.57572 11.8684 9.02344 11.4207 9.02344 10.8684C9.02344 10.3161 8.57572 9.86841 8.02344 9.86841C7.47115 9.86841 7.02344 10.3161 7.02344 10.8684C7.02344 11.4207 7.47115 11.8684 8.02344 11.8684Z" fill="#00242C" />
                                                                        <path d="M4.6875 11.8684C5.23978 11.8684 5.6875 11.4207 5.6875 10.8684C5.6875 10.3161 5.23978 9.86841 4.6875 9.86841C4.13522 9.86841 3.6875 10.3161 3.6875 10.8684C3.6875 11.4207 4.13522 11.8684 4.6875 11.8684Z" fill="#00242C" />
                                                                        <path d="M11.3594 11.8684C11.9117 11.8684 12.3594 11.4207 12.3594 10.8684C12.3594 10.3161 11.9117 9.86841 11.3594 9.86841C10.8071 9.86841 10.3594 10.3161 10.3594 10.8684C10.3594 11.4207 10.8071 11.8684 11.3594 11.8684Z" fill="#00242C" />
                                                                    </g>
                                                                    <defs>
                                                                        <clipPath id="clip0_6692_5168">
                                                                            <rect width="16" height="16" fill="white" transform="translate(0.0234375 0.868408)" />
                                                                        </clipPath>
                                                                    </defs>
                                                                </svg>
                                                            </p>
                                                            <span>add to calendar</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- <div class="com-single">
                            <h3 class="pro--title">Staff Members</h3>
                            <ul class="com-single--sMembers">
                                <li><strong>Tiffany Castro</strong>
                                    <a href="#" title="email@example.com"></a>
                                </li>
                                <li><strong>Grace Bell</strong>
                                    <a href="#" title="email@example.com"></a>
                                </li>
                                <li><strong>Benjamin Hamilton</strong>
                                    <a href="#" title="email@example.com"></a>
                                </li>
                            </ul>
                        </div> -->
                    </div>
                </div>
            </section>




<?php
        endif;
        get_footer();
    }
} else {
    die('The SaDirectoryAPI class is not defined.');
}
