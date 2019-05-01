<?php
require_once 'classes/HtmlGenerator.php';

$baseSourceUrls = array(
    'https://ld-wp.template-help.com/rockthemes/19504/' => 'index.html',
    'https://ld-wp.template-help.com/rockthemes/19504/about/' => 'about-us.html',
    'https://ld-wp.template-help.com/rockthemes/19504/blog/' => 'blog.html',
);

$hg = new HtmlGenerator($baseSourceUrls, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'project');
$hg->createHtmlFiles();
$hg->doConversion();