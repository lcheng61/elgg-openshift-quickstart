<?php
/**
 * Elgg Ideas Plugin
 * @package ideas
 */

$object = $vars['item']->getObjectEntity();
$excerpt = strip_tags($object->excerpt);
$excerpt = elgg_get_excerpt($excerpt);

$img = elgg_view('output/img', array(
				'src' => "ideas/image/{$object->guid}/1/medium/{$object->time_updated}",
				'class' => 'elgg-photo ideas-river-image',
				));
$image = elgg_view('output/url', array(
			'href' => "ideas/view/{$object->guid}/" . elgg_get_friendly_title($object->title),
			'text' => $img,
			));

echo elgg_view('river/item', array(
	'item' => $vars['item'],
	'message' => $excerpt,
	'attachments' => $image,
));
