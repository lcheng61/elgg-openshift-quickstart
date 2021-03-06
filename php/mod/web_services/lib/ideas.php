<?php
/**
 * Elgg Webservices plugin 
 * ideas
 * 
 * @package Webservice
 * @author Liang Cheng
 *
 */
 
 /**
 * Web service to get tips list by all users
 *
 * @param string $context eg. all, friends, mine, groups
 * @param int $limit  (optional) default 10
 * @param int $offset (optional) default 0
 * @param int $group_guid (optional)  the guid of a group, $context must be set to 'group'
 * @param string $category(optional) eg. fashion, gadget, etc
 * @param string $username (optional) the username of the user default loggedin user
 *
 * @return array $file Array of files uploaded
 */

function ideas_get_posts($context,  $limit = 10, $offset = 0, $group_guid, $category, $username) {
    if(!$username) {
        $user = get_loggedin_user();
    } else {
        $user = get_user_by_username($username);
        if (!$user) {
            throw new InvalidParameterException('registration:usernamenotvalid');
	}
    }
    if($context == "all"){
        $params = array(
            'types' => 'object',
            'subtypes' => 'ideas',
            'limit' => $limit,
            'full_view' => FALSE,
            'offset' => $offset,
            );
    }
    if($context == "mine" || $context ==  "user"){
        $params = array(
            'types' => 'object',
            'subtypes' => 'ideas',
            'owner_guid' => $user->guid,
            'limit' => $limit,
            'full_view' => FALSE,
            'offset' => $offset,
        );
    }
    if($context == "group"){
        $params = array(
            'types' => 'object',
            'subtypes' => 'ideas',
            'container_guid'=> $group_guid,
            'limit' => $limit,
            'full_view' => FALSE,
            'offset' => $offset,
        );
    }
    $latest_blogs = elgg_get_entities($params);
        
    if($context == "friends"){
        $latest_blogs = get_user_friends_objects($user->guid, 'market', $limit, $offset);
    }

    $return['total_number'] = count($latest_blogs);
    $return['category'] = $category;
    $return['offset'] = $offset;
    
    if($latest_blogs) {
        foreach($latest_blogs as $single ) {
            if (($single->ideascategory == $category) || 
                    ($category == "all")) {
                $blog['tip_id'] = $single->guid;
                $options = array(
                        'annotations_name' => 'generic_comment',
                        'guid' => $single->$guid,
                        'limit' => $limit,
                        'pagination' => false,
                        'reverse_order_by' => true,
                        );

                 $comments = elgg_get_annotations($options);
                 $num_comments = count($comments);

                 $blog['tip_title'] = $single->title;
                 $blog['tip_category'] = $single->ideascategory;
//                 $blog['tip_category'] = $single->tip_category;
                 $blog['tip_thumbnail_image_url'] = $single->tip_thumbnail_image_url;
//                         elgg_normalize_url("market/image/".$single->guid."/1/"."large/");
                 $blog['likes_number'] = likes_count(get_entity($single->guid));
                 $blog['comments_number'] = $num_comments;

                 $owner = get_entity($single->owner_guid);
                 $blog['tip_author']['user_id'] = $owner->guid;
                 $blog['tip_author']['user_name'] = $owner->username;
                 $blog['tip_author']['user_avatar_url'] = get_entity_icon_url($owner,'small');
                 $blog['tip_author']['is_seller'] = $owner->is_seller;

                 $blog['likes_number'] = likes_count(get_entity($single->guid));
                 $blog['comments_number'] = $num_comments;
                 $blog['products_number'] = $single->products;

//                $blog['container_guid'] = $single->container_guid;
//                $blog['access_id'] = $single->access_id;
//                $blog['time_created'] = (int)$single->time_created;
//                $blog['time_updated'] = (int)$single->time_updated;
//                $blog['last_action'] = (int)$single->last_action;
                 $return[] = $blog;
            }
        }
    }
    else {
        $msg = elgg_echo('ideas_post:none');
        throw new InvalidParameterException($msg);
    }

    return $return;

}

expose_function('ideas.get_posts',
                "ideas_get_posts",
                array(
                      'context' => array ('type' => 'string', 'required' => false, 'default' => 'all'),
                      'limit' => array ('type' => 'int', 'required' => false, 'default' => 10),
                      'offset' => array ('type' => 'int', 'required' => false, 'default' => 0),
                      'group_guid' => array ('type'=> 'int', 'required'=>false, 'default' =>0),
                      'category' => array ('type' => 'string', 'required' => false, 'default' => 'all'),
                      'username' => array ('type' => 'string', 'required' => false),
                    ),
                "Get list of idea posts",
                'GET',
                false,
                false);

 /**
 * Web service to get idea detail
 *
 * @param int $tip_id
 * @param int $username (optional) default 0
 *
 * @return array $file Array of files uploaded
 */

function tip_get_detail($tip_id) {
    $return = array();

    $blog = get_entity($tip_id);

    if (!elgg_instanceof($blog, 'object', 'ideas')) {
        $return['content'] = elgg_echo('blog:error:post_not_found');
        return $return;
    }

    $return['tip_title'] = htmlspecialchars($blog->title);
    $return['tip_id'] = $tip_id;
    $return['tip_pages'] =           $blog->tip_pages;
    $return['tip_video_url'] =       $blog->tip_video_url;
    $return['tip_image_url'] =       $blog->tip_image_url;
    $return['tip_image_caption'] =   $blog->tip_image_caption;
    $return['tip_text'] =            $blog->tip_text;
    $return['tip_notes'] =           $blog->tip_notes;
    $return['products'] =            $blog->products;

    $owner = get_entity($blog->owner_guid);
    $return['tip_author']['user_id'] = $owner->guid;
    $return['tip_author']['user_name'] = $owner->username;
    $return['tip_author']['is_seller'] = $owner->is_seller;
    $return['tip_author']['user_avatar_url'] = get_entity_icon_url($owner,'small');
    $return['likes_number'] = likes_count(get_entity($tip_id));
    $return['comments_number'] = $num_comments;
    $return['products_number'] = $blog->products_number;
    $return['tip_tags'] = $blog->tags;

    return $return;
}
    
expose_function('ideas.get_detail',
        "tip_get_detail",
        array('tip_id' => array ('type' => 'string'),
             ),
        "Read an idea post",
        'GET',
        false,
        false);

/**
 * Web service for posting a tip to ideas
 *
 * @param string $username username of author
 * @param string $title    the title of blog
 * @param string $excerpt  the excerpt of blog
 * @param string $text     the content of blog
 * @param string $tags     tags for blog
 * @param string $access   Access level of blog
 *
 * @return bool
 */

function ideas_post_tip($title,
                    $tip_thumbnail_image_url,
                    $tip_pages,
                    $tip_video_url,
                    $tip_image_url,
                    $tip_image_caption,
                    $tip_text,
                    $tip_notes,
                    $tip_tags,
                    $tip_category,
                    $products,
                    $access) {

    $user = get_loggedin_user();
    if (!$user) {
        throw new InvalidParameterException('registration:usernamenotvalid');
    }
    
    $obj = new ElggObject();
    $obj->subtype = "ideas";
    $obj->owner_guid = $user->guid;
    $obj->access_id = strip_tags($access);
    $obj->method = "api";
    $obj->description = strip_tags($tip_text);
    $obj->title = elgg_substr(strip_tags($title), 0, 140);
    $obj->status = 'published';
    $obj->comments_on = 'On';

    $obj->tags = strip_tags($tip_tags);
    $obj->tip_thumbnail_image_url = strip_tags($tip_thumbnail_image_url);
    $obj->tip_pages =               strip_tags($tip_pages);
    $obj->tip_video_url =           strip_tags($tip_video_url);
    $obj->tip_image_url =           strip_tags($tip_image_url);
    $obj->tip_image_caption =       strip_tags($tip_image_caption);
    $obj->tip_text =                strip_tags($tip_text);
    $obj->tip_notes =               strip_tags($tip_notes);
    $obj->ideascategory =           strip_tags($tip_category);
    $obj->products =                strip_tags($products);

    $guid = $obj->save();
    add_to_river('river/object/ideas/create',
            'create',
            $user->guid,
            $obj->guid
    );

    // Parse products string to extract its individual product guid's.
    $product_id_array = explode(",", $obj->products);

    echo ("obj->products is $obj->products\n");

    foreach ($product_id_array as $id) {
        $id = intval($id);
        echo ("product_id = $id\n");
        $product_post = get_entity($id);
        if ($product_post) { // if the product id is a valid one
            echo ("$id is valid\n");
            // XXX: we assume the same product id can't be linked to the same tip.
            $product_post->tips_number ++;
            $product_post->tips .= "$guid,";
        }
    }

//    echo ("product_post->tips = $product_post->tips\n");

//    echo ("product_post->tips_number = $product_post->tips_number");
//    echo ("guid = $guid, product_post->tips = $product_post->tips\n");

    $return['success'] = true;
    $return['message'] = elgg_echo('ideas:message:saved');
    return $return;
} 
    
expose_function('ideas.post_tip',
                "ideas_post_tip",
                array(
                        'title' => array ('type' => 'string', 'required' => true),
                        'tip_thumbnail_image_url' => array ('type' => 'string', 'required' => false),
                        'tip_pages' => array ('type' => 'int', 'required' => false),
                        'tip_video_url' => array ('type' => 'string', 'required' => false),
                        'tip_image_url' => array ('type' => 'string', 'required' => false),
                        'tip_image_caption' => array ('type' => 'string', 'required' => false),
                        'tip_text' => array ('type' => 'string', 'required' => false),
                        'tip_notes' => array ('type' => 'string', 'required' => false),
                        'tip_tags' => array ('type' => 'string', 'required' => false, 'default' => "blog"),
                        'tip_category' => array ('type' => 'string', 'required' => false),
                        'products' => array ('type' => 'string', 'required' => false),
                        'access' => array ('type' => 'string', 'required' => false, 'default'=>ACCESS_PUBLIC),
                    ),
                "Post a blog post",
                'POST',
                true,
                false);

/**
 * Web service for delete a blog post
 *
 * @param string $guid     GUID of a blog entity
 * @param string $username Username of reader (Send NULL if no user logged in)
 * @param string $password Password for authentication of username (Send NULL if no user logged in)
 *
 * @return bool
 */

function ideas_delete_tip($tip_id, $username) {
    $return = array();
    $blog = get_entity($tip_id);
//    $return['success'] = false;
    if (!elgg_instanceof($blog, 'object', 'ideas')) {
        throw new InvalidParameterException('blog:error:post_not_found1');
    }

    if(!$username) {
        $user = get_loggedin_user();
    } else {
        $user = get_user_by_username($username);
        if (!$user) {
            throw new InvalidParameterException('registration:usernamenotvalid');
	}
    }

    $blog = get_entity($tip_id);
    if($user->guid!=$blog->owner_guid) {
        $return['message'] = elgg_echo('blog:message:notauthorized');
    }
    // Extract the product ids from this tip and remove the current tip id from the corresponding products

    $product_id_array = explode(",", $blog->products);
//    echo ("obj->products is $blog->products\n");
    foreach ($product_id_array as $id) {
        $id = intval($id);
//        echo ("product_id = $id\n");
        $product_post = get_entity($id);
        if ($product_post) { // if the product id is a valid one
//            echo ("$id is valid, to remove $tip_id from $id\n");

            $pattern = "/$tip_id".",/";
            $replacement = "";
            $match_limit = 1;
            $match_count = 0;
//            echo ("before preg_replace, $product_post->tips\n");
//            echo ("$pattern -- $replacement -- $product_post->tips\n");
            
            $product_post->tips = preg_replace($pattern, $replacement, $product_post->tips, $match_limit, $match_count);
            $product_post->tips_number -= $match_count;
	    // XXX: check if match_count is 0 which may indicate error.
//            echo ("count = $match_count, after preg_replace, $product_post->tips\n");
        }
    } // done with removing such tips from linked products

    // Remove the tip object itself.
    if (elgg_instanceof($blog, 'object', 'ideas') && $blog->canEdit()) {
        if ($blog->delete()) {
            $return['success'] = true;

            // decrease the tips_number from the corresponding product
            // XXX: change to array
            $product_post = get_entity($blog->products);
            $product_post->tips_number --;
            if ($product_post->tip_number < 0) {
                $product_post->tip_number == 0;
            }    

            $return['message'] = elgg_echo('blog:message:deleted_post');
        } else {
            $return['message'] = elgg_echo('blog:error:cannot_delete_post');
        }
    } else {
        $return['message'] = elgg_echo('blog:error:post_not_found2');
    }

    return $return;
}
    
expose_function('ideas.delete_tip',
                "ideas_delete_tip",
                array('tip_id' => array ('type' => 'string'),
                      'username' => array ('type' => 'string'),
                     ),
                "Delete a tip and remove it from associated products",
                'POST',
                true,
                false);


/**
 * Web service for list products linked by one tip
 *
 * @param string  $guid     GUID of a tip
 * @param integer $offset 
 * @param integer $number
 *
 * @return bool
 */

function ideas_get_products_by_tip($tip_id, $offset = 0, $limit = 10, $username) {
    $return = array();
    $tip_obj = get_entity($tip_id);
    if (!elgg_instanceof($tip_obj, 'object', 'ideas')) {
        throw new InvalidParameterException('blog:error:post_not_found1');
    }

    if(!$username) {
        $user = get_loggedin_user();
    } else {
        $user = get_user_by_username($username);
        if (!$user) {
            throw new InvalidParameterException('registration:usernamenotvalid');
	}
    }

    if($user->guid!=$tip_obj->owner_guid) {
        $return['message'] = elgg_echo('blog:message:notauthorized');
    }
    // Extract the product ids from this tip and list these products.

    $product_id_array = explode(",", $tip_obj->products);
    foreach ($product_id_array as $id) {
        $id = intval($id);
        if ($id == 0) {
            continue;
        }
        $product_post = get_entity($id);
//        echo ("after product_post, id = $id, product_post = $product_post<br>");
        if ($product_post) { // if the product id is a valid one
                echo ("after if<br>");
                $blog['product_id'] = $product_post->guid;
                $options = array(
                        'annotations_name' => 'generic_comment',
                        'guid' => $product_post->$guid,
                        'limit' => $limit,
                        'pagination' => false,
                        'reverse_order_by' => true,
                        );

                 $comments = elgg_get_annotations($options);
                 $num_comments = count($comments);

                 echo ("product_post = $product_post->title");

                 $blog['product_name'] = $product_post->title;
                 $blog['product_price'] = $product_post->price;
                 $blog['tips_number'] = $product_post->tips_number;
		 //XXX: hard-code sold_count;		 		 
                 $single->sold_count = 0;
                 $blog['sold_number'] = $product_post->sold_count;
                 $blog['product_category'] = $product_post->marketcategory;
                 $blog['product_image'] = elgg_normalize_url("market/image/".$product_post->guid."/1/"."large/");
                 $blog['likes_number'] = likes_count(get_entity($product_post->guid));
                 $blog['reviews_number'] = $num_comments;

                 $owner = get_entity($product_post->owner_guid);
                 $blog['product_seller']['user_id'] = $owner->guid;
                 $blog['product_seller']['user_name'] = $owner->username;
                 $blog['product_seller']['user_avatar_url'] = get_entity_icon_url($owner,'small');
                 $blog['product_seller']['is_seller'] = $owner->is_seller;
            
                 $return[] = $blog;
        }
    } // done with listing products of that tip.

    return $return;
}
    
expose_function('ideas.get_products_by_tip',
                "ideas_get_products_by_tip",
                array('tip_id' => array ('type' => 'string', 'required' => true),
                      'offset' => array ('type' => 'integer', 'required' => false, 'default' => 0),
                      'limit' => array ('type' => 'integer', 'required' => false, 'default' => 10),
                      'username' => array ('type' => 'string', 'required' => false),
                     ),
                "List products associated to one tip.",
                'GET',
                false,
                false);


/**
 * Performs a search for ideas
 *
 * @return array $results search result
 */
 
function ideas_search($query, $category, $offset, $limit, 
        $sort, $order, $search_type, $entity_type,
        $entity_subtype, $owner_guid, $container_guid){
    
    $params = array(
                    'query' => $query,
                    'offset' => $offset,
                    'limit' => $limit,
                    'sort' => $sort,
                    'order' => $order,
                    'search_type' => $search_type,
                    'type' => $entity_type,
                    'subtype' => $entity_subtype,
                    'owner_guid' => $owner_guid,
                    'container_guid' => $container_guid,
                    );
                    
    $type = $entity_type;
    $results = elgg_trigger_plugin_hook('search', $type, $params, array());
    if ($results === FALSE) {
        // search plugin returns error.
        continue;
    }
    if($results['count']){
        foreach($results['entities'] as $single){
            if (($single->ideascategory == $category) || 
                    ($category == "all")) {
                $blog['tip_id'] = $single->guid;
                $options = array(
                        'annotations_name' => 'generic_comment',
                        'guid' => $single->$guid,
                        'limit' => $limit,
                        'pagination' => false,
                        'reverse_order_by' => true,
                        );

                 $comments = elgg_get_annotations($options);
                 $num_comments = count($comments);

                 $blog['tip_title'] = $single->title;
                 $blog['tip_category'] = $single->ideascategory;
                 $blog['tip_thumbnail_image_url'] = $single->tip_thumbnail_image_url;
//                         elgg_normalize_url("market/image/".$single->guid."/1/"."large/");
                 $blog['likes_number'] = likes_count(get_entity($single->guid));
                 $blog['comments_number'] = $num_comments;

                 $owner = get_entity($single->owner_guid);
                 $blog['tip_author']['user_id'] = $owner->guid;
                 $blog['tip_author']['user_name'] = $owner->username;
                 $blog['tip_author']['user_avatar_url'] = get_entity_icon_url($owner,'small');
                 $blog['tip_author']['is_seller'] = $owner->is_seller;

                 $blog['likes_number'] = likes_count(get_entity($single->guid));
                 $blog['comments_number'] = $num_comments;
                 $blog['products_number'] = $single->products;

                 $return[] = $blog;
            }
        }
    }

    return $return;
}
expose_function('ideas.search',
                "ideas_search",
                array(  'query' => array('type' => 'string'),
                        'category' => array('type' => 'string', 'required'=>false, 'default' => 'all'),
                        'offset' =>array('type' => 'int', 'required'=>false, 'default' => 0),
                        'limit' =>array('type' => 'int', 'required'=>false, 'default' => 10),
                        'sort' =>array('type' => 'string', 'required'=>false, 'default' => 'relevance'),
                        'order' =>array('type' => 'string', 'required'=>false, 'default' => 'desc'),
                        'search_type' =>array('type' => 'string', 'required'=>false, 'default' => 'all'),
                        'entity_type' =>array('type' => 'string', 'required'=>false, 'default' => "object"),
                        'entity_subtype' =>array('type' => 'string', 'required'=>false, 'default' => "ideas"),
                        'owner_guid' =>array('type' => 'int', 'required'=>false, 'default' => ELGG_ENTITIES_ANY_VALUE),
                        'container_guid' =>array('type' => 'int', 'required'=>false, 'default' => ELGG_ENTITIES_ANY_VALUE),
                        ),
                "Perform a search for ideass",
                'GET',
                false,
                false);


/**
 * Web service to retrieve comments on a product post
 *
 * @param string $guid market product guid
 * @param string $limit    Number of users to return
 * @param string $offset   Indexing offset, if any
 *
 * @return array
 */
/*                    
function product_get_comments_by_id($product_id, $limit = 10, $offset = 0){
    $market = get_entity($product_id);
    $options = array(
        'annotations_name' => 'generic_comment',
        'guid' => $product_id,
        'limit' => $limit,
        'pagination' => false,
        'reverse_order_by' => true,
    );
    $comments = elgg_get_annotations($options);

    if($comments){
        foreach($comments as $single){
            $comment['guid'] = $single->id;
            $comment['description'] = strip_tags($single->value);
        
            $owner = get_entity($single->owner_guid);
            $comment['owner']['guid'] = $owner->guid;
            $comment['owner']['name'] = $owner->name;
            $comment['owner']['username'] = $owner->username;
            $comment['owner']['avatar_url'] = get_entity_icon_url($owner,'small');
        
            $comment['time_created'] = (int)$single->time_created;
            $return[] = $comment;
        }
    } else {
        $msg = elgg_echo('generic_comment:none');
        throw new InvalidParameterException($msg);
    }
    return $return;
}
expose_function('product.get_comments_by_id',
    "product_get_comments_by_id",
    array('product_id' => array ('type' => 'string'),
          'limit' => array ('type' => 'int', 'required' => false, 'default' => 10),
          'offset' => array ('type' => 'int', 'required' => false, 'default' => 0),
         ),
    "Get comments for a market post",
    'GET',
    false,
    false);    
*/
