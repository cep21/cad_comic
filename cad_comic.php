<?php
/**
 * Shows penny arcade rss feed w/ comics inline
 *
 * @author Jack Lindamood
 * @license Apache License, Version 2.0
 */
header('Content-Type: text/xml');
$subreddit = $_GET['s'];
if (!$subreddit) {
	$subreddit = '';
}

$url_rss  = 'http://cdn.cad-comic.com/rss.xml';
$ch = curl_init($url_rss);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_TIMEOUT, 6);
$res_rss = curl_exec($ch);
curl_close($ch);
$res_rss = softcoded_gzdecode($res_rss);

$x_obj = simplexml_load_string($res_rss);
$x_obj->channel->title = "Jackmod: " . $x_obj->channel->title;
$toremove = array();
$count = 0;
foreach ($x_obj->channel->item as $obj) {
	$count++;
	// Find the first <a link and assume it's a comic
	if (!preg_match('#<a href="(.*?)">#', $obj->description, $matches)) {
		continue;
	}
	$link = trim($matches[1]);
	$obj->link = $link;
	$ch = curl_init($link);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_TIMEOUT, 6);
	$image_post_content = curl_exec($ch);
	curl_close($ch);
	$matches = array();
	$lines = explode("\n", $image_post_content);
	$found_image = null;
	foreach ($lines as $line) {
		if (preg_match('#src="(http://[\w\.]*?cad-comic.com[\w\.\-/]*?)"#s', $line, $matches)) {
			$found_image = $matches[1];
			break;
		}
	}
	if ($found_image) {
		$obj->description .= '<br /><img src="' . $found_image . '" />';
	} else {
		$obj->description .= '<br /> ---- NO IMAGE FOUND ----';
	}

}

// Need to unset in reverse order so I don't mess up indexes
sort($toremove);
foreach (array_reverse($toremove) as $k) {
	unset($x_obj->channel->item[$k]);
}
print $x_obj->asXML();
die;
//// ----end

// My PHP provider doesn't ahve gzdecode.  Taken from PHP.net
function softcoded_gzdecode($data){
    $g=tempnam('./','gz');
    file_put_contents($g,$data);
    ob_start();
    readgzfile($g);
    $d=ob_get_clean();
    unlink($g);
    return $d;
}

