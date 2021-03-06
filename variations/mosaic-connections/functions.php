<?php


add_filter( 'posts_join',     'nh_clas_connections_alter_search_join',     9999, 2 );
add_filter( 'posts_where',    'nh_clas_connections_alter_search_where',    9999, 2 );
add_filter( 'posts_distinct', 'nh_clas_connections_alter_search_distinct', 9999, 2 );
add_filter( 'posts_orderby',  'nh_clas_connections_alter_search_orderby',  9999, 2 );


add_action( 'pre_get_posts', 'nh_clas_connections_alter_archive_order' );



// add_filter( 'pre_get_posts', 'nh_clas_connections_show_connections_as_posts' );


add_filter( 'wp_enqueue_scripts', 'nh_enqueue_mt_script' );

function nh_enqueue_mt_script()
{
	nh_enqueue_file( 'script', 'mt-more-tags', 'variations/connections/scripts/mt-more-tags.js' );
}

function nh_clas_connections_show_connections_as_posts( $wp_query )
{
	if( is_admin() || !$wp_query->is_main_query() || $wp_query->is_search ) return;

// 		nh_print($wp_query);

	if( $wp_query->get( 'page_id' ) ) return $wp_query;
	
	$post_type = $wp_query->get( 'post_type' );
	
	if( $post_type )
	{
		if( is_array($post_type) && !in_array('connection', $post_type) )
		{
			$post_type = array_unshift( $post_type, 'connection' );
		}
		elseif( $post_type !== 'connection' )
		{
			$post_type = array( 'connection', $post_type );
		}
	}
	else
	{
		$post_type = array( 'connection' );
	}
	
	$wp_query->set( 'post_type', $post_type );
	
	
	
	
	$pagename = $wp_query->get( 'pagename' );
	$name = $wp_query->get( 'name' );
	$n = '';
		
	if( $pagename && !$name )
	{
		$wp_query->set( 'name', $pagename );
		$n = $pagename;
	}
	if( $name && !$pagename )
	{
		$wp_query->set( 'pagename', $name );
		$n = $name;
	}
	
	$wp_query->set( 'connection', $n );
	
	
	return $wp_query;
}


function nh_clas_connections_alter_archive_order( $wp_query )
{
	if( is_admin() || !$wp_query->is_main_query() ) return;

    if( is_post_type_archive('connection') || is_tax( 'connection-group' ) || is_tax( 'connection-link' ) )
	{
		$wp_query->set( 'orderby', 'meta_value' );
		$wp_query->set( 'meta_key', 'sort-title' );
		$wp_query->set( 'order', 'asc' );
    }
}



/**
 * 
 */
function nh_clas_connections_get_search_term( $search_term = null, $sql = true )
{
	return NH_ConnectionCustomPostType::get_search_term( $search_term, $sql );
}



/**
 * 
 */
function nh_clas_connections_alter_search_join( $join, $wp_query )
{
	global $wpdb;

	if( is_admin() || !$wp_query->is_main_query() || !$wp_query->is_search ) return $join;

	$join .= "
		JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id 
		INNER JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id) 
		INNER JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
		INNER JOIN $wpdb->terms ON ($wpdb->term_taxonomy.term_id = $wpdb->terms.term_id) 
	";

	return $join;
}



/**
 * 
 */
function nh_clas_connections_alter_search_where( $where, $wp_query )
{
	global $wpdb;

	if( is_admin() || !$wp_query->is_main_query() || !$wp_query->is_search ) return $where;

	$search_term = nh_clas_connections_get_search_term( $wp_query->query_vars['s'] );
	$where = "
	  AND ($wpdb->posts.post_type = 'connection' AND $wpdb->posts.post_status = 'publish')
	  AND (
		$wpdb->posts.post_title LIKE '%".$search_term."%'
		OR
		($wpdb->postmeta.meta_key IN ('username','search_content') AND $wpdb->postmeta.meta_value LIKE '%".$search_term."%')
		OR
		$wpdb->terms.name LIKE '%".$search_term."%'
	  )";

	return $where;
}



/**
 * 
 */
function nh_clas_connections_alter_search_orderby( $order_by, $wp_query )
{
	global $wpdb;

	if( is_admin() || !$wp_query->is_main_query() || !$wp_query->is_search ) return $order_by;
	
	$search_term = nh_clas_connections_get_search_term( $wp_query->query_vars['s'] );
	$order_by = "
		CASE WHEN $wpdb->posts.post_title LIKE '%".$search_term."%' THEN 1000 ELSE 0 END +
		CASE WHEN $wpdb->terms.name LIKE '%".$search_term."%' THEN 100 ELSE 0 END +
		CASE WHEN $wpdb->postmeta.meta_value LIKE '%".$search_term."%' THEN 10 ELSE 0 END
		DESC
	";

    return $order_by;
}



/**
 * 
 */
function nh_clas_connections_alter_search_distinct()
{
	global $wp_query;
	$distinct = '';
	
	if( $wp_query->is_search && $wp_query->is_main_query )
	{
		$distinct = 'DISTINCT';
	}

	return $distinct;
}




