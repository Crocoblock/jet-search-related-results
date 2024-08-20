<?php

namespace Jet_Search_Results_Relations;

/**
 * Prevent direct access to the file for security purposes.
 *
 * @since 1.0.0
 */
if ( ! defined( 'WPINC' ) ) {
	die();
}

class Results_Relations {

	protected $rel_ids = array();
	protected $data    = array();
	protected $search_string = null;
	protected $related_items = array();

	/**
	 * Constructor for the Results_Relations class.
	 *
	 * @since 1.0.0
	 *
	 * @param array $rel_ids Array of relation IDs.
	 * @param array $posts Array of posts.
	 * @param array $data Search settings.
	 */
	public function __construct( array $rel_ids, array $data = array() ) {

		$this->rel_ids = $rel_ids;
		$this->data    = $data;

		if ( isset( $data['value'] ) ) {
			$this->set_search_string( $data['value'] );
		}

	}

	public function set_search_string( $search_string ) {
		$this->search_string = urldecode( esc_sql( $search_string ) );
	}

	public function get_related_ids_for_source( $source = [] ) {

		$rel_ids = [];

		switch ( $source[0] ) {
			case 'posts':

				$rel_data = new \WP_Query( [
					'post_type' => $source[1],
					's' => $this->search_string,
					'fields' => 'ids',
				] );
				
				if ( ! empty( $rel_data->posts ) ) {
					$rel_ids = $rel_data->posts;
				}

				break;

			case 'terms':
				
				$rel_ids = get_terms( [
					'taxonomy' => $source[1],
					'search' => $this->search_string,
					'fields' => 'ids',
				] );

				break;
			
			default:
				// code...
				break;
		}

		return $rel_ids;
	}

	public function prepare_result_objects( $result, $source, $prop ) {

		if ( empty( $result ) ) {
			return $result;
		}

		$prepared_result = [];

		foreach ( $result as $row ) {
			
			$object = jet_engine()->relations->sources->get_source_object_by_id(
				$source,
				$row[ $prop ]
			);

			$source_data = jet_engine()->relations->types_helper->type_parts_by_name( $source );

			switch ( $source_data[0] ) {
				case 'terms':

					$object = [
						'name' => $object->name,
						'url'  => get_term_link( $object->term_id ),
					];

					break;

				case 'mix':

					if ( 'users' === $source_data[1] ) {

						$profile_builder = false;

						if ( jet_engine()->modules->is_module_active( 'profile-builder' ) ) {
							$profile_builder = jet_engine()->modules->get_module( 'profile-builder' );
						}

						$page_url = '';

						if ( $profile_builder ) {
							add_filter( 'jet-engine/profile-builder/query/pre-get-queried-user', function( $user ) use ( $object ) {
								return $object;
							} );

							$page_url = $profile_builder->instance->settings->get_page_url( 'single_user_page' );

							if ( false === $page_url ) {
								$page_url = get_author_posts_url( $object->ID );
							}
						} else {
							$page_url = get_author_posts_url( $object->ID );
						}

						$object = [
							'name' => $object->data->display_name,
							'url'  => $page_url,
						];
					}
					

					break;
				
				default:
					// code...
					break;
			}

			$prepared_result[] = $object;
		}

		return $prepared_result;

	}

	public function get_related_items_by_object( $relation, $object_type, $object_name ) {

		$search_in      = false;
		$result         = [];
		$get_items_from = false;
		$base_object    = jet_engine()->relations->types_helper->type_name_by_parts( $object_type, $object_name );

		if ( $base_object === $relation->get_args( 'parent_object' ) ) {
			$search_in = $relation->get_args( 'child_object' );
			$get_items_from = 'parent_object_id';
		} elseif ( $base_object === $relation->get_args( 'child_object' ) ) {
			$search_in = $relation->get_args( 'parent_object' );
			$get_items_from = 'child_object_id';
		}

		if ( ! $search_in ) {
			return $result;
		}

		$search_data = jet_engine()->relations->types_helper->type_parts_by_name( $search_in );

		if ( ! empty( $search_data ) ) {

			$rel_ids = $this->get_related_ids_for_source( $search_data );

			if ( ! empty( $rel_ids ) ) {

				foreach ( $rel_ids as $rel_id ) {
					switch ( $get_items_from ) {
						case 'parent_object_id':
							$result = array_merge( $result, $relation->get_parents( $rel_id ) );
							break;
						
						case 'child_object_id':
							$result = array_merge( $result, $relation->get_children( $rel_id ) );
							break;
					}
				}

			}

		}

		return $this->prepare_result_objects( $result, $base_object, $get_items_from );

	}

	/**
	 * Get the related items.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of related items.
	 */
	public function get_related_items() {
		
		$related_results = array(
			'posts' => []
		);

		if ( ! function_exists( 'jet_engine' ) ) {
			return $related_results;
		}

		$relations = $this->rel_ids;

		if ( empty( $relations ) ) {
			return $related_results;
		}

		foreach ( $relations as $key => $rel_id ) {

			$relation = jet_engine()->relations->get_active_relations( $rel_id );

			if ( ! $relation ) {
				continue;
			}

			if ( ! empty( $this->data['search_source'] ) ) {
				foreach ( $this->data['search_source'] as $source ) {
					$related_results['posts'] = array_merge(
						$related_results['posts'], 
						$this->get_related_items_by_object( $relation, 'posts', $source )
					);
				}
			}

			if ( 
				! empty( $this->data['search_source_terms'] )
				&& ! empty( $this->data['search_source_terms_taxonomy'] )
			) {
				$tax = $this->data['search_source_terms_taxonomy'];
				$related_results['terms'] = isset( $related_results['terms'] ) ? $related_results['terms'] : [];

				$related_results['terms'] = array_merge(
					$related_results['terms'],
					$this->get_related_items_by_object( $relation, 'terms', $tax )
				);
			}

			if ( ! empty( $this->data['search_source_users'] ) ) {
				
				$related_results['users'] = isset( $related_results['users'] ) ? $related_results['users'] : [];

				$related_results['users'] = array_merge(
					$related_results['users'],
					$this->get_related_items_by_object( $relation, 'mix', 'users' )
				);
			}

		}

		return $related_results;

	}

}