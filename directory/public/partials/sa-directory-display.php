<?php
global $directory_seo_title;
global $directory_seo_desc;

$directory_seo_title = returnDirectorySeoDetails()['title'] ?? '';
$directory_seo_desc = returnDirectorySeoDetails()['description'] ?? '';

get_header();
?>
<section class="directory--section pageSection">
    <div class="o-section__wrapper">
        <div class="home--block">
            <div class="title">
                <h1><img src="<?php echo plugins_url('assets/directory-icon.png', dirname(__FILE__)); ?>" alt=""> Directories</h1>
                <p>Discover Your Government: The Ultimate Directories for House, Senate, Judiciary, Congress, and Committees</p>
            </div>
            <?php
            $fullurl = null;

            if (function_exists('getDirectoryFullUrl')) {
                $fullurl = getDirectoryFullUrl();
            }
            $siteStates = array(
                'in' => 'Indiana',
                'ks' => 'Kansas',
            );
            if ($siteStates && count($siteStates) > 0) :
            ?>

                <div class="states">
                    <h5>Select a state</h5>
                    <select data-fullurl="<?php echo $fullurl; ?>" name="states" id="directory-states-select" onchange="handleDirStateChange(event)" class="large gfield_select select2-hidden-accessible custom-select">

                        <?php foreach ($siteStates as $key => $state) : ?>
                            <option <?php selected($key, returnDefaultDirectoryUrl('sc') ?? ''); ?> value="<?php echo $key; ?>">
                                <?php echo $state; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>
        <style>
            #stateErrorBlock {
                border: 1px solid #fd443f;
                min-height: 50px;
                align-items: center;
                justify-content: flex-start;
                padding: 0.5625em 1.25em;
                margin: 16px 0 0 0;
                color: #fd443f;
            }
        </style>
        <p id="stateErrorBlock" style="display: none;">
            Please select a State.
        </p>
        <?php

        $directoryLists = [
            [
                'name' => 'Senate',
                'slug' => 'senate',
                'image' => plugins_url('assets/images/senate.png', dirname(__FILE__)),
                'is_dir' => 1
            ],
            [
                'name' => 'House',
                'slug' => 'house',
                'image' => plugins_url('assets/images/house.png', dirname(__FILE__)),
                'is_dir' => 1
            ],
            [
                'name' => 'Committees',
                'slug' => 'committees',
                'image' => plugins_url('assets/images/committee.png', dirname(__FILE__)),
                'is_dir' => 0
            ]
        ];

        ?>
        <div class="dir-landing--items">
            <?php foreach ($directoryLists as $item) {
            ?>
                <a href="<?php echo $item['slug']; ?>" class="dir-landing--item" data-dir="<?php echo $item['is_dir']; ?>">
                    <figure><img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>"></figure>
                    <h6><?php echo $item['name']; ?></h6>
                </a>
            <?php } ?>
        </div>
    </div>
</section>
<?php
get_footer();
