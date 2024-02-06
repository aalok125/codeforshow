<?php
if (class_exists('SaDirectoryAPI')) {
    global $directory_seo_title;
    global $directory_seo_desc;
    $id = get_query_var("representative");
    // $data = $api->get_directories_data("api/legislators/" . $id);
    $data = $prodApi->get_directories_data("api/legislators/" . $id);

    if (is_wp_error($data)) {
        echo 'Error fetching directories: ' . $data->get_error_message();
    } elseif (empty($data)) {

        echo 'No Data found.';
    } else {

        if ($legislator = $data->legislator) :

            $firstName = $legislator->first_name ?? '';
            $lastName = $legislator->last_name ?? '';
            $party = $legislator->party_type->name ?? '';
            $district = $legislator->district ?? '';
            $email = $legislator->email ?? '';
            $phone = $legislator->work_phone[0] ?? '';
            $website = $legislator->website ?? '';
            $profilePicture = $legislator->profile_picture_path ?? null;
            $title = $legislator->title ?? '';
            $wordpress_member_tag = $legislator->wordpress_member_tag ?? '';
            $stateCode = $legislator->address_state->code ?? '';
            $directory = $legislator->directory->name ?? '';
            $twitter_url = $legislator->social_media_link->twitter ?? '';
            $bio = $legislator->bio ?? '';
            $checkProMember = checkProMember(strtolower($stateCode));
            $committeesList = $legislator->committees ?? null;
            $active_until_date = $legislator->active_until_date ?? null;

            $directory_seo_title = $legislator->seo_title ?? '';
            $directory_seo_desc = $legislator->seo_description ?? '';

            if ($profilePicture) {
                // $profilePicture = $api->get_profile_picture($profilePicture);
                $profilePicture = $prodApi->get_profile_picture($profilePicture);
            } else {
                $profilePicture = plugins_url('assets/images/profile.png', dirname(__FILE__));
            }


            get_header();
?>
            <section class="pageSection directory--section">

                <div class="o-section__wrapper">
                    <?php if ($stateCode && $directory) : ?>
                        <div class="backButton">
                            <a href="<?php echo site_url('/pro/directories/' . strtolower($stateCode) . '/' . strtolower($directory)) ?>" title="">
                                <p class="icon"><svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M11.4651 16L19.4651 8L20.8984 9.43333L14.3318 16L20.8984 22.5667L19.4651 24L11.4651 16Z" fill="#0D0D0D" />
                                    </svg></p>
                                <span>Back to <?php echo $directory; ?></span>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="dir--profile">
                        <?php
                        $fileExists = false;
                        if (file_exists(ABSPATH . $legislator->profile_picture_path)) {
                            $fileExists = true;
                        }
                        if ($profilePicture && $fileExists) : ?>
                            <figure><img src='<?php echo $profilePicture ?>' alt='<?php echo $firstName ?>'></figure>
                        <?php else : ?>
                            <figure><img src='<?php echo home_url(); ?>/wp-content/uploads/legislators_profile_pictures/legislator_placeholder.png' alt='<?php echo $firstName ?>'></figure>
                        <?php endif; ?>
                        <h1><?php echo $firstName ?> <?php echo $lastName ?></h1>
                        <ul class="info">
                            <li> District <?php echo $district ?></li>
                            <li class="col-turq"><?php echo $party ?></li>
                            <?php if ($twitter_url) : ?>
                                <li><a class="icon" href="<?php echo $twitter_url; ?>" target="_blank">
                                        <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="15" cy="15" r="15" fill="#838F9A" />
                                            <path d="M21.9109 11.2515C21.3998 11.4787 20.8498 11.6319 20.2726 11.7005C20.8619 11.3477 21.3137 10.7889 21.5268 10.1223C20.9756 10.4494 20.3655 10.6868 19.7155 10.8149C19.1954 10.2606 18.454 9.91406 17.6336 9.91406C16.0585 9.91406 14.7814 11.1911 14.7814 12.7665C14.7814 12.9897 14.8065 13.2073 14.8554 13.4164C12.4847 13.2973 10.3827 12.162 8.97583 10.4362C8.7303 10.8572 8.58975 11.3472 8.58975 11.8704C8.58975 12.8599 9.09324 13.7331 9.85862 14.2445C9.39126 14.2295 8.95128 14.1011 8.56661 13.8875C8.56633 13.8993 8.56633 13.9115 8.56633 13.9236C8.56633 15.3053 9.54959 16.4579 10.8546 16.7204C10.6153 16.7853 10.3632 16.8203 10.103 16.8203C9.91902 16.8203 9.74037 16.8025 9.56624 16.7689C9.92946 17.9021 10.9827 18.727 12.2307 18.7499C11.2545 19.515 10.0249 19.971 8.68825 19.971C8.45824 19.971 8.23105 19.9575 8.00781 19.931C9.27047 20.7407 10.7696 21.2125 12.3803 21.2125C17.6271 21.2125 20.4958 16.8663 20.4958 13.097C20.4958 12.9733 20.4933 12.85 20.4879 12.7275C21.045 12.3262 21.5288 11.8236 21.9109 11.2515Z" fill="white" />
                                        </svg>
                                    </a></li>
                            <?php endif; ?>
                        </ul>
                        <?php if($active_until_date) : ?>
                            <span>
                                <p>(Until <?php echo date('m/d/Y', strtotime($active_until_date)); ?>)</p>
                            </span>
                        <?php endif; ?>
                        <p>
                            <!-- <span>Majority Leader</span>
                            <span>-</span> -->
                            <span><?php echo $title; ?></span>
                        </p>
                        <div class="contactBlock <?php if ($checkProMember['status'] == false) {
                                                        echo "notPro";
                                                    } ?>">
                            <ul class="contact ">
                                <?php if ($website) : ?>
                                    <li><a href="<?php echo $website; ?>" title="<?php echo $website; ?>"><?php echo $website; ?></a></li>
                                <?php endif;
                                if ($email) :
                                ?>
                                    <li>Email: <a href="mailto:<?php echo $email; ?>" title="<?php echo $email; ?>"><?php echo $email; ?></a></li>
                                <?php endif;
                                if ($phone) :
                                ?>
                                    <li>Phone Number: <a href="tel:<?php echo $phone; ?>" title="<?php echo $phone; ?>"><?php echo $phone; ?></a></li>
                                <?php endif;
                                ?>
                            </ul>
                            <?php if ($checkProMember['status'] == false) : ?>
                                <div class="profileInfoMessage">
                                    <p><?php echo $checkProMember['message'] ?? ''; ?></p>
                                    <?php if (!is_user_logged_in()) : ?>
                                        <a href="<?php echo home_url('/pro/login/'); ?>" title="Sign in">
                                            Sign in
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dir-pro--main">
                        <aside class="dir-pro--sidebar">
                            <button class="aboutTrigger" onclick="handleAboutSidebarClick(event)">
                                <h6>About</h6>
                            </button>
                            <ul class="items">
                                <li>
                                    <h5>Bio</h5>
                                    <p>
                                        <?php echo $bio; ?>
                                    </p>
                                </li>

                            </ul>
                        </aside>
                        <div class="dir-pro--box">
                            <div class="dir-pro--news">
                                <h3 class="pro--title">news coverage</h3>
                                <?php if ($wordpress_member_tag) :
                                    $taxonomy_name = 'legislators_tags';
                                    $term = get_term_by('name', $wordpress_member_tag, $taxonomy_name);



                                    if ($term !== false && !is_wp_error($term)) {

                                        $args = array(
                                            'post_type' => 'post',
                                            'posts_per_page' => 4,
                                            'post_status' => 'publish',
                                            'tax_query' => array(
                                                array(
                                                    'taxonomy' => $taxonomy_name,
                                                    'field'    => 'term_id',
                                                    'terms'    => $term->term_id,
                                                ),
                                            ),

                                        );

                                        $query = new WP_Query($args);

                                        if ($query->have_posts()) {
                                ?>
                                            <div class="pro-news--items">
                                                <?php

                                                while ($query->have_posts()) {
                                                    $query->the_post();
                                                ?>
                                                    <article class="pro-news--item">
                                                        <figure><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_post_thumbnail(); ?></figure>
                                                        <h5><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h5>
                                                        <ul>
                                                            <?php if (has_term('free', 'article_access', get_the_ID())) { ?>
                                                                <li class="tag">free article</li>
                                                            <?php } ?>
                                                            <li><?php echo get_the_date('F d, Y'); ?></li>
                                                        </ul>
                                                    </article>
                                                <?php }
                                                wp_reset_postdata();

                                                ?>
                                            </div>
                                            <?php

                                            /**
                                             * to do - Removing see more button temporary 
                                             * until more articles can be loaded
                                             */

                                            // if ($term->count && $term->count > 4) {
                                            ?>
                                            <!-- <div class="seeMore">
                                                    <a href="<?php // echo get_term_link($term->slug, $taxonomy_name); 
                                                                ?>" title="See More">See More</a>
                                                </div> -->
                                <?php
                                            //}
                                        } else {
                                            echo "No Articles found";
                                        }
                                    }
                                endif; ?>
                            </div>

                            <?php
                            if ($committeesList && is_array($committeesList) && count($committeesList) > 0) :
                            ?>
                                <div class="dir-pro--committee">
                                    <h3 class="pro--title">COMMITTEES</h3>
                                    <div class="pro-committee--items">
                                        <?php foreach ($committeesList as $committee) {
                                            $parties = [];
                                            foreach ($committee->partiesMembersCount as $party => $count) {
                                                if ($count > 0) {
                                                    $parties[$count] = "{$party} {$count}";
                                                }
                                            }
                                            krsort($parties);

                                            $committee_slug = null;
                                            if($committee->url){
                                                $committee_slug = site_url('/pro/directories/committee' . $committee->url);
                                            }
                                        ?>
                                            <div class="pro-committee--item">
                                                <a href="<?php echo $committee_slug;  ?>">
                                                    <h5><?php echo $committee->name ?? null ?></h5>
                                                    <ul>
                                                        <?php
                                                        if (count($parties) > 0) :
                                                            $parties_str = implode(' / ', $parties);
                                                        ?>
                                                            <li><?php echo htmlspecialchars($parties_str); ?></li>
                                                        <?php
                                                        endif;
                                                        ?>


                                                    </ul>
                                                </a>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <div class="seeMore">
                                        <a href="<?php echo site_url('/pro/directories/committees/' . strtolower($stateCode) . '/all/2023/') ?>" title="">See More</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <!-- <div class="dir-pro--staff">
                                <h3 class="pro--title">STAFF MEMBERS</h3>
                                <ul class="pro-staff--lists">
                                    <li>
                                        <p>Tiffany Castro</p>
                                        <a href="">email@example.com</a>
                                    </li>
                                    <li>
                                        <p>Grace Bell</p>
                                        <a href="">email@example.com</a>
                                    </li>
                                    <li>
                                        <p>Benjamin Hamilton</p>
                                        <a href="">email@example.com</a>
                                    </li>
                                </ul>
                                <div class="staffNotPro">
                                    <h5>Content exclusive for Pro users</h5>
                                    <p>Subscribe to Pro Account to have access to all Directories</p>
                                    <a href="#" class="subscribe" title="subscribe to state affairs pro">subscribe to state affairs pro</a>
                                    <p>Already a member?</p>
                                    <a href="#" class="signIn" title="Sign in">Sign in</a>
                                </div>
                            </div> -->
                        </div>
                    </div>


                </div>

            </section>
<?php
            get_footer();
        endif;
    }
} else {
    die('The SaDirectoryAPI class is not defined.');
}
