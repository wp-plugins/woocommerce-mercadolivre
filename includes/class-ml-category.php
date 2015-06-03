<?php
/**
 * MercadoLivre Category
 *
 * This class handles the communication with ML for what concerns about categories.
 *
 * @author Carlos Cardoso Dias
 *
 */

/**
 * Anti cheating code
 */
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

if ( ! class_exists( 'ML_Category' ) ) :

final class ML_Category {

	public static function get_categories() {
		$site = 'MLB';

		if ( ! empty( ML()->ml_site ) ) {
			$site = ML()->ml_site;
		}
		
		return ML()->ml_communication->get_resource( "/sites/{$site}/categories" );
	}

	/**
	 * This function returns the category_id that matches with the product 
	 * attributes or an empty string.
	 * 
	 * @param  string
	 * @return string
	 */
	public static function get_category_id_for( $item_name ) {
		// check the argument
		if ( ! is_string( $item_name ) ) {
			return '';
		}

		$item_name = urlencode( $item_name );

		$site = 'MLB';

		if ( ! empty( ML()->ml_site ) ) {
			$site = ML()->ml_site;
		}

		// search
		$params = array( 'q' => $item_name , 'attributes' => 'filters,available_filters' );
		$search_results = ML()->ml_communication->get_resource( "/sites/{$site}/search" , $params );
		
		// check if the query had success
		if ( empty( $search_results ) ) {
			return '';
		}

		// If 'filters' has an id 'category' not empty
		foreach ( $search_results->filters as $filter ) {
			if ( ( strcmp( $filter->id , 'category' ) == 0 ) && ( ! empty( $filter->values[0] ) ) ) {
				return self::get_leaf_category( $filter->values[0]->id );
			}
		}

		// Search in 'available_filters' for the category with most results
		$categories = array();
		foreach ( $search_results->available_filters as $filter ) {
			if ( strcmp( $filter->id , 'category' ) == 0 ) {
				$categories = wp_list_pluck( $filter->values , 'results' , 'id' );
				break;
			}
		}

		arsort( $categories );

		return self::get_leaf_category( array_shift( array_keys( $categories ) ) );
	}

	public static function get_category_path( $category ) {
		if ( ! is_string( $category ) || empty( $category ) ) {
			return '';
		}
		
		$params   = array( 'attributes' => 'path_from_root' );
		$category = ML()->ml_communication->get_resource( "/categories/{$category}" , $params );
		
		if ( empty( $category ) ) {
			return '';
		}

		return $category->path_from_root;
	}

	public static function get_category_variations( $category_id ) {
		if ( ! is_string( $category_id ) || empty( $category_id ) ) {
			return '';
		}
		
		$params   = array( 'attributes' => 'attribute_types' );
		$category = ML()->ml_communication->get_resource( "/categories/{$category_id}" , $params );
		
		if ( empty( $category ) ) {
			return '';
		}

		if ( $category->attribute_types == 'variations' ) {
			return ML()->ml_communication->get_resource( "/categories/{$category_id}/attributes" );
		}

		return '';
	}

	public static function get_subcategories( $category ) {
		if ( ! is_string( $category ) || empty( $category ) ) {
			return '';
		}
		
		$params     = array( 'attributes' => 'children_categories' );
		$categories = ML()->ml_communication->get_resource( "/categories/{$category}" , $params );
		
		if ( empty( $categories ) ) {
			return '';
		}

		return $categories->children_categories;
	}

	/**
	 * This function recursively searchs for a leaf category with most items of some father category.
	 *
	 * @param  string
	 * @return string 
	 **/
	private static function get_leaf_category( $category ) {
		if ( empty( $category ) ) {
			return '';
		}
		
		$children_categories = self::get_subcategories( $category );
		
		if ( empty( $children_categories ) ) {
			return $category;
		}

		$categories = wp_list_pluck( $children_categories , 'total_items_in_this_category' , 'id' );
		
		arsort( $categories );

		return self::get_leaf_category( array_shift( array_keys( $categories ) ) );
	}
}

endif;