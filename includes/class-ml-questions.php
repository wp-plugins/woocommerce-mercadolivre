<?php
/**
 * MercadoLivre Questions
 * 
 * @author Carlos Cardoso Dias
 *
 **/

/**
 * Anti cheating code
 */
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

if ( ! class_exists( 'ML_Questions' ) ) :

final class ML_Questions {
	public static function get_last_questions( $limit = 5 ) {
		$params = array(
			'sort_fields' => 'date_created',
			'sort_types'  => 'DESC',
			'limit'       => $limit
		);

		$last_questions = ML()->ml_communication->get_resource( 'my/received_questions/search' , $params );
		
		return $last_questions->questions;
	}

	public static function delete_question( $question_id ) {
		return ML()->ml_communication->delete_resource( 'questions/' . $question_id );
	}

	public static function answer_question( $question_id , $answer_text ) {
		$params = array( 'question_id' => $question_id, 'text' => $answer_text );
		return ML()->ml_communication->post_resource( 'answers' , $params );
	}
}

endif;