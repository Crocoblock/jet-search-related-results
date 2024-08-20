<?php
/**
 * Plugin Name: JetSearch - Related results
 * Plugin URI:  #
 * Description: Enhances the JetSearch functionality by adding related search results to the AJAX search results.
 * Version:     1.0.0
 * Author:      Crocoblock
 * Author URI:  https://crocoblock.com/
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 */

use Jet_Search_Results_Relations\Results_Relations;

/**
 * Prevent direct access to the file for security purposes.
 *
 * @since 1.0.0
 */
if ( ! defined( 'WPINC' ) ) {
	die();
}

add_action( 'init', function () {

	function plugin_path( $path = null ) {

		$plugin_path = trailingslashit( plugin_dir_path( __FILE__ ) );

		return $plugin_path . $path;
	}

	require plugin_path( 'results-relations.php' );

	add_action( 'jet-search/ajax-search/before-search-sources', 'jet_search_set_related_items', 10, 3 );

}, 12 );

/**
 * Add related items to the search results.
 *
 * @since 1.0.0
 *
 * @param array &$response The response array that will be returned.
 * @param array &$posts The current posts from the search results.
 * @param array $data The search query data.
 * @param array $sources Additional sources for the search results.
 */
function jet_search_set_related_items( &$response, &$posts, $data ) {

	$current_posts = $posts;
	$rel_ids = apply_filters( 'jet-search/ajax-search/relation_id', array(), $data );

	$results_relations = new Results_Relations( $rel_ids, $data );

	$related_items = $results_relations->get_related_items();

	if ( ! empty( $related_items ) ) {

		foreach ( $related_items as $type => $items ) {
			
			if ( 'posts' === $type ) {

				$posts = jet_search_merge_results_without_duplicates_by_ID( $related_items['posts'], $posts );
				$limit = ( int ) $data['limit_query_in_result_area'];
				$posts = array_slice( $posts, 0, $limit );

				$data['post_count'] = count( $posts );
				$data['columns']    = ceil( $data['post_count'] / $data['limit_query'] );

				$response['post_count']         = $data['post_count'];
				$response['columns']            = ceil( $data['post_count'] / $data['limit_query'] );
				$response['results_navigation'] = jet_search_ajax_handlers()->get_results_navigation( $data );

			} elseif ( ! empty( $items ) ) {
				add_filter(
					'jet-search/ajax-search/search-source/' . $type . '/search-result-list',
					function( $source_results ) use ( $items ) {
						
						$source_results = array_merge( $source_results, $items );
						$res = [];
						
						foreach ( $source_results as $result_item ) {
							$res[ $result_item['url'] ] = $result_item;
						}

						return array_values( $res );
					}
				);
			}

		}
		
	}
}

/**
 * Merge two arrays of objects and remove duplicates based on the object ID.
 *
 * @since 1.0.0
 *
 * @param array $array1 The first array of objects.
 * @param array $array2 The second array of objects.
 * @return array The merged array of unique objects by ID.
 */
function jet_search_merge_results_without_duplicates_by_ID( $array1, $array2 ) {
	$merged = array_merge( $array1, $array2 );
	$unique = array();
	$ids    = array();

	foreach ( $merged as $obj ) {
		
		$obj_id = false;

		if ( isset( $obj->ID ) ) {
			$obj_id = $obj->ID;
		} elseif ( isset( $obj->term_id ) ) {
			$obj_id = $obj->term_id;
		}

		if ( ! $obj_id ) {
			$unique[] = $obj;
		} elseif ( ! in_array( $obj_id, $ids ) ) {
			$ids[]    = $obj_id;
			$unique[] = $obj;
		}
	}

	return $unique;
}

add_filter( 'jet-search/ajax-search/relation_id', function( $rel_id, $query_data ) {
	return array( 10, 11, 12 );
}, 10, 2 );