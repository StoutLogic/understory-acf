<?php
/**
 * Until ACF Supports using the term_meta table instead of the options_meta table to
 * store meta values, use this patch to force the issue.
 */

if (function_exists('add_filter')) :
    add_filter( 'acf/update_value', function($value, $post_id, $field) {
        $term_id = abs(intval( filter_var( $post_id, FILTER_SANITIZE_NUMBER_INT ) ));
        $taxonomy = str_replace('_'.$term_id, '', $post_id);
        $field_group = acf_get_field_group( $field['parent'] );
        if (
            $field_group['location'][0][0]['param'] == 'taxonomy'
            && $field_group['location'][0][0]['value'] == $taxonomy
        ) {

            if( $term_id > 0 ) {
                update_term_meta( $term_id, $field['name'], $value );
            }
        }
        return $value;
    }, 10, 3 );

    add_filter( 'acf/load_value', function($value, $post_id, $field) {
        $term_id = abs(intval( filter_var( $post_id, FILTER_SANITIZE_NUMBER_INT ) ));
        $taxonomy = str_replace('_'.$term_id, '', $post_id);
        $field_group = acf_get_field_group( $field['parent'] );
        if (
            $field_group['location'][0][0]['param'] == 'taxonomy'
            && $field_group['location'][0][0]['value'] == $taxonomy
        ) {
            if( $term_id > 0 ) {
                $value = get_term_meta( $term_id, $field['name'], true );
            }
        }
        return $value;
    }, 10, 3 );
endif;
