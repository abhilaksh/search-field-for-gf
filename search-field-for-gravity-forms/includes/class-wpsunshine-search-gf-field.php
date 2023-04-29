<?php
// If Gravity Forms isn't loaded, bail.
if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class WPSunshine_GF_Field_Search
 *
 * Handles the behavior of Search field.
 *
 * @since Unknown
 */
class WPSunshine_GF_Field_Search extends GF_Field {

	/**
	 * Defines the field type.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @var string The field type.
	 */
	public $type = 'wpsunshine_search';

    public function __construct( $data = array() ) {
		parent::__construct( $data );
        //add_action( 'gform_editor_js', array( $this, 'editor_script' ) );
        //add_filter( 'gform_tooltips', array( $this, 'tooltips' ) );
        add_action( 'wp_ajax_wpsunshine_gf_search', array( $this, 'ajax_search' ) );
        add_action( 'wp_ajax_nopriv_wpsunshine_gf_search', array( $this, 'ajax_search' ) );
		//add_action( 'gform_enqueue_scripts', array( $this, 'enqueue_styles' ), 10, 2 );
    }

	/**
	 * Defines the field title to be used in the form editor.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFCommon::get_field_type_title()
	 *
	 * @return string The field title. Translatable and escaped.
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Search', 'gravityforms-search' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Search is performed when text entered in field.', 'gravityforms-search' );
	}

	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a gform-icon class.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return WPSUNSHINE_GF_SEARCH_URL . '/images/search.svg';
	}

	/**
	 * Defines the field settings available within the field editor.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return array The field settings available for the field.
	 */
	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'size_setting',
			'rules_setting',
			'visibility_setting',
			'duplicate_setting',
			'default_value_setting',
			'placeholder_setting',
			'description_setting',
			'wpsunshine_search_setting',
			'css_class_setting',
			'autocomplete_setting',
		);
	}

	public function get_form_editor_inline_script_on_page_render(){

		$script = "jQuery(document).bind( 'gform_load_field_settings', function(event, field, form){" . PHP_EOL;
		$post_types = WPSunshine_Search_Field_Addon::get_post_types();
		foreach ( $post_types as $post_type ) {
			$name = str_replace( '-', '_', $post_type->name );
			$script .= "jQuery( '#wpsunshine_search_" . $name . "_value' ).attr( 'checked', field.wpsunshine_search_" . $name . " == true);" . PHP_EOL;
		}
		$script .= "jQuery( '#wpsunshine_search_per_page_value' ).val( field.wpsunshine_search_per_page );" . PHP_EOL;
		$script .= "jQuery( '#wpsunshine_search_result_format_value' ).val( field.wpsunshine_search_result_format );" . PHP_EOL;
		$script .= "});";

		return $script;
	}


	/**
	 * Defines if conditional logic is supported in this field type.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormDetail::inline_scripts()
	 * @used-by GFFormSettings::output_field_scripts()
	 *
	 * @return bool true
	 */
	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * Returns the field input.
	 *
	 * @since  Unknown
	 * @access public
	 *	 *
	 * @param array      $form  The Form Object.
	 * @param string     $value The value of the input. Defaults to empty string.
	 * @param null|array $entry The Entry Object. Defaults to null.
	 *
	 * @return string The HTML markup for the field.
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {

		if ( is_array( $value ) ) {
			$value = '';
		}

		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$form_id  = $form['id'];
		$id       = intval( $this->id );
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$size          = $this->size;
		$disabled_text = $is_form_editor ? "disabled='disabled'" : '';
		$class_suffix  = $is_entry_detail ? '_admin' : '';
		$class         = $size . $class_suffix;
		$class         = esc_attr( $class );

		$instruction_div = '';

		$placeholder_attribute  = $this->get_field_placeholder_attribute();
		$required_attribute     = $this->isRequired ? 'aria-required="true"' : '';
		$invalid_attribute      = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
		$aria_describedby       = $this->get_aria_describedby();
		$autocomplete_attribute = $this->enableAutocomplete ? $this->get_field_autocomplete_attribute() : '';

		$tabindex = $this->get_tabindex();

        $allowed_posts = array();
        $post_types = WPSunshine_Search_Field_Addon::get_post_types();
        foreach ( $post_types as $post_type ) {
			$name = str_replace( '-', '_', $post_type->name );
            $key = 'wpsunshine_search_' . $name;
            if ( isset( $this->{$key} ) && $this->{$key} == 1 ) {
                $allowed_posts[] = $post_type->name;
            }
        }

        if ( !$is_form_editor && empty( $allowed_posts ) ) {
            return __( 'Please select which post types you want to be searched', 'gravityforms-search' );
        }

        $script = '<script>
        wpsunshine_search_' . $field_id . '_working = false;
        var wpsunshine_search_' . $field_id . '_timer;

        jQuery( document ).ready( function($) {

            jQuery( "#' . $field_id . '" ).on( "keyup", function(){

                clearTimeout( wpsunshine_search_' . $field_id . '_timer );
                wpsunshine_search_' . $field_id . '_timer = setTimeout( wpsunshine_search_' . $field_id . '_done_typing, 500 );

                function wpsunshine_search_' . $field_id . '_done_typing() {
                    var query = $( "#' . $field_id . '" ).val();
                    jQuery( "#wpsunshine-gf-search-' . $field_id . '-results" ).html( "" );
                    if ( query.length >= 3 && !wpsunshine_search_' . $field_id . '_working ) {
                        wpsunshine_search_' . $field_id . '_working = true;
						jQuery( "#field_' . $form_id . '_' . $id . ' .ginput_container_wpsunshine_search" ).addClass( "wpsunshine-gf-search-loading" );
                        var data = {
                            action: "wpsunshine_gf_search",
                            nonce: "' . wp_create_nonce( 'wpsunshine_gf_search_' . $field_id ) . '",
                            field_id: "' . $field_id . '",
                            form_id: "' . $form_id . '",
                            format: "' . esc_js( nl2br( $this->wpsunshine_search_result_format ) ) . '",
                            search: query,
                            per_page: "' . esc_js( $this->wpsunshine_search_per_page ) . '",
                            post_types: "' . join( ',', $allowed_posts ) . '"
                        };
                        jQuery.post( "' . admin_url( 'admin-ajax.php' ) . '", data, function( response ) {
							jQuery( "#field_' . $form_id . '_' . $id . ' .ginput_container_wpsunshine_search" ).removeClass( "wpsunshine-gf-search-loading" );
                            wpsunshine_search_' . $field_id . '_working = false;
                            if ( response == null || response.length == 0 ) {
								jQuery( "#wpsunshine-gf-search-' . $field_id . '-results" ).removeClass( "has-results" );
                                jQuery( "#wpsunshine-gf-search-' . $field_id . '-results" ).html( "<div class=\'wpsunshine-gf-search-noresults\'>' . addslashes( wp_kses_post( apply_filters( 'gravityforms_search_no_results', __( 'No results', 'gravityforms-search' ), $form_id, $id ) ) ) . '</div>" );
                            } else {
								jQuery( "#wpsunshine-gf-search-' . $field_id . '-results" ).addClass( "has-results" );
                                var results = JSON.parse( response );
                                $.each(results, function(index, result) {
                                    jQuery( "#wpsunshine-gf-search-' . $field_id . '-results" ).append( result );
                        		});
                            }
                        });
                    } else {
                        //console.log( "Not long enough" );
                    }
                }

            });

        });
        </script>';

        $field = sprintf( "<div class='ginput_container ginput_container_wpsunshine_search ginput_container_text'><input name='input_%d' id='%s' type='text' value='%s' class='%s' {$tabindex} {$placeholder_attribute} {$required_attribute} {$invalid_attribute} {$aria_describedby} {$autocomplete_attribute} %s/>{$instruction_div}</div>", $id, $field_id, esc_attr( $value ), esc_attr( $class ), $disabled_text );

        $results = '<div class="wpsunshine-gf-search-results" id="wpsunshine-gf-search-' . $field_id . '-results"></div>';

		return $script . $field . $results;

	}

	/**
	 * Gets the value of the submitted field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormsModel::get_field_value()
	 * @uses    GF_Field::get_value_submission()
	 * @uses    GF_Field_Phone::sanitize_entry_value()
	 *
	 * @param array $field_values             The dynamic population parameter names with their corresponding values to be populated.
	 * @param bool  $get_from_post_global_var Whether to get the value from the $_POST array as opposed to $field_values. Defaults to true.
	 *
	 * @return array|string
	 */
	public function get_value_submission( $field_values, $get_from_post_global_var = true ) {

		$value = parent::get_value_submission( $field_values, $get_from_post_global_var );
		$value = $this->sanitize_entry_value( $value, $this->formId );

		return $value;
	}

	/**
	 * Sanitizes the entry value.
	 *
	 * @since Unknown
	 * @access public
	 *
	 * @used-by GF_Field_Phone::get_value_save_entry()
	 * @used-by GF_Field_Phone::get_value_submission()
	 *
	 * @param string $value   The value to be sanitized.
	 * @param int    $form_id The form ID of the submitted item.
	 *
	 * @return string The sanitized value.
	 */
	public function sanitize_entry_value( $value, $form_id ) {
		$value = is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : sanitize_text_field( $value );
		return $value;
	}

	/**
	 * Gets the field value when an entry is being saved.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormsModel::prepare_value()
	 * @uses    GF_Field_Phone::sanitize_entry_value()
	 * @uses    GF_Field_Phone::$phoneFormat
	 *
	 * @param string $value      The input value.
	 * @param array  $form       The Form Object.
	 * @param string $input_name The input name.
	 * @param int    $lead_id    The Entry ID.
	 * @param array  $lead       The Entry Object.
	 *
	 * @return string The field value.
	 */
	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {
		$value = $this->sanitize_entry_value( $value, $form['id'] );
		return $value;
	}

    public function ajax_search() {
        if ( wp_verify_nonce( $_POST['nonce'], 'wpsunshine_gf_search_' . $_POST['field_id'] ) ) {
            // Do search
            $args = array(
                'post_type' => explode( ',', sanitize_text_field( $_POST['post_types'] ) ),
                's' => sanitize_text_field( $_POST['search'] ),
				'posts_per_page' => 10,
                'relevanssi' => true // Might as well let Relevanssi have a go at it if it exists
            );
            if ( !empty( $_POST['per_page'] ) ) {
                $args['posts_per_page'] = intval( sanitize_text_field( $_POST['per_page'] ) );
            }
            $posts = get_posts( $args );
            if ( !empty( $posts ) ) {
                $return = array();
                foreach ( $posts as $post ) {
					$thumbnail = get_the_post_thumbnail( $post->ID );
                    $search = array( '%id%', '%title%', '%url%', '%type%', '%excerpt%', '%thumbnail%' );
                    $replace = array(
                        'id' => $post->ID,
                        'title' => $post->post_title,
                        'url' => get_permalink( $post ),
                        'type' => $post->post_type,
                        'excerpt' => get_the_excerpt( $post ),
						'thumbnail' => $thumbnail
                    );
                    $format = '<a href="%url%" target="_blank">%title%</a>'; // Default format
                    if ( $_POST['format'] ) {
						// Sanitize then decode the custom format passed from on page JS
                        $format = html_entity_decode( sanitize_text_field( $_POST['format'] ) );
						// Look for a request for an excerpt with a set number of words
						if ( preg_match( '/(?i)%excerpt:(\d{1,12})/', $format, $matches ) ) {
							$search[] = '%excerpt:' . $matches[1] . '%';
							$replace[] = wp_trim_words( get_the_excerpt( $post ), $matches[1] );
						}
						if ( preg_match_all( '/(?i)%meta:([^%]+)%/', $format, $matches ) ) {
							foreach ( $matches[0] as $match ) {
								$search[] = $match;
							}
							foreach ( $matches[1] as $match ) {
								$replace[] = get_post_meta( $post->ID, $match, true );
							}
						}
                    }
					$classes = array(
						'wpsunshine-gf-search-result',
						'wpsunshine-gf-search-result-' . $post->post_type,
						'wpsunshine-gf-search-result-' . $post->ID
					);
					if ( !empty( $thumbnail ) ) {
						$classes[] = 'wpsunshine-gf-search-result-has-thumbnail';
					}
                    $return[] = '<div class="' . join( ' ', $classes ) . '">' . str_replace( $search, $replace, $format ) . '</div>';
                }
                echo json_encode( $return );
            }
        }
        wp_die();
    }

	function enqueue_styles( $form, $is_ajax ) {
		foreach ( $form['fields'] as $field ) {
			if ( $field['type'] == 'wpsunshine_search' ) {
				wp_enqueue_style( 'gravityforms-search', WPSUNSHINE_GF_SEARCH_URL . 'assets/style.css' );
				return;
			}
		}
	}

}

// Register the phone field with the field framework.
GF_Fields::register( new WPSunshine_GF_Field_Search() );
