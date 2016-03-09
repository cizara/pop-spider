<?php
$depth = 0;
foreach ($urls['200'] as $url => $value):
    if ((substr_count($url, '/') - 2) > $depth):
        $depth = (substr_count($url, '/') - 2);
    endif;
endforeach;
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL; ?>
<urlset
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
<!-- created with pop-spider https://github.com/nicksagona/pop-spider -->
<?php if (count($urls) > 0): ?>
<?php foreach ($urls['200'] as $url => $value): ?>
    <url>
        <loc><?=$url; ?></loc>
        <changefreq>monthly</changefreq>
        <priority><?php
$priority = round((($depth - (substr_count($url, '/') - 3)) / $depth), 2);
if ($priority > 1):
    $priority = 1;
endif;
echo number_format($priority, 2); ?></priority>
    </url>
<?php endforeach; ?>
<?php endif; ?>
</urlset>
