<?php
$directoryLists = [
    [
        'name' => 'Senate',
        'slug' => 'senate',
        'url' => site_url('/pro/directories/advocacy/' . $state . '/senate')
    ],
    [
        'name' => 'House',
        'slug' => 'house',
        'url' => site_url('/pro/directories/advocacy/' . $state . '/house')
    ],
    
    [
        'name' => 'Committees',
        'slug' => 'committees',
        'url' => site_url('/pro/directories/advocacy/' . $state . '/committees')
    ]
    
]
?>
<aside class="directory--sidebar">
    <ul class="sidebarList">
        <?php foreach ($directoryLists as $item) { ?>
            <li><a class="<?php if ($item['slug'] == $activeAside) {
                                echo "active";
                            } ?>" href="<?php echo  $item['url']; ?>" title="<?php echo $item['name']; ?>"><?php echo $item['name']; ?></a></li>
        <?php } ?>
    </ul>
</aside>