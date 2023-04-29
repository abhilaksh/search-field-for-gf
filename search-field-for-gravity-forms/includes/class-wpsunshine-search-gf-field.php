<?php
if ( ! class_exists( 'GF_Field' ) ) {
    return;
}

class WPSunshine_GF_Field_Search extends GF_Field {

    public $type = 'wpsunshine_search';

    public function get_form_editor_field_title() {
        return esc_attr__( 'Search', 'gravityforms' );
    }

    public function get_form_editor_button() {
        return array(
            'group' => 'advanced_fields',
            'text'  => $this->get_form_editor_field_title(),
        );
    }

    public function get_field_input( $form, $value = '', $entry = null ) {
        $form_id = absint( $form['id'] );
        $id      = absint( $this->id );

        $post_types   = $this->get_post_types();
        $roles        = $this->get_roles();
        $search_types = array_merge( $post_types, $roles, array( 'user' ) );

        $field_name = 'input_' . $this->id;

        $field_id = $form_id . '_' . $id;

        $search_label = esc_attr__( 'Search', 'gravityforms' );
        $loading      = esc_attr__( 'Loading', 'gravityforms' );

        $field_input = sprintf(
            '<div class="wpsunshine-gf-search-wrapper"><input type="text" name="%s" id="%s" value="%s" autocomplete="off" class="medium gfield_search" /><div class="wpsunshine-gf-search-results"></div></div>',
            $field_name,
            $field_id,
            $value
        );

        $field_input .= "<script type='text/javascript'>
            jQuery( document ).ready( function() {
                var ajaxurl = '" . admin_url( 'admin-ajax.php' ) . "';
                var search_field_id = '" . $field_id . "';
                var nonce = jQuery( '#gform_' + " . $form_id . " + '_nonce' ).val();
                var search_types = '" . implode( ',', $search_types ) . "';
                var post_types = '" . implode( ',', $post_types ) . "';
                var roles = '" . implode( ',', $roles ) . "';

                if ( search_types.indexOf( 'user' ) !== -1 ) {
                    jQuery( '#input_' + search_field_id ).autocomplete( {
                        source: function( request, response ) {
                            jQuery.post( ajaxurl, {
                                nonce: nonce,
                                action: 'gf_search_users',
                                search: request.term
                            }, function( data ) {
                                response( jQuery.parseJSON( data ) );
                            } );
                        },
                        minLength: 2,
                        delay: 500,
                        select: function( event, ui ) {
                            jQuery( '#input_' + search_field_id ).val( ui.item.label );
                            jQuery( '#input_' + search_field_id + '_id' ).val( ui.item.value );
                            return false;
                        }
                    } );
                }

                if ( search_types.indexOf( 'post' ) !== -1 ) {
                    jQuery( '#input_' + search_field_id ).autocomplete( {
                        source: function( request, response ) {
                            jQuery.post( ajaxurl, {
                                nonce: nonce,
                                action: 'gf_search_posts',
                                search: request.term,
                                post_types: post_types
                            }, function( data ) {
                                response( jQuery.parseJSON( data ) );
                            } );
                        },
                                   $args = array(
                'post_type' => explode( ',', sanitize_text_field( $_POST['post_types'] ) ),
                's' => sanitize_text_field( $_POST['search'] ),
                'posts_per_page' => 10,
                'relevanssi' => true // Might as well let Relevanssi have a go at it if it exists
            );

            // If we are searching for users
            if ( $this->search_for_users ) {
                $user_query = new WP_User_Query( array(
                    'search' => '*'.esc_attr( $_POST['search'] ).'*',
                    'search_columns' => array(
                        'user_login',
                        'user_nicename',
                        'user_email',
                        'user_url'
                    ),
                    'number' => 10,
                    'fields' => array( 'ID', 'user_login', 'user_email', 'display_name' ),
                    'role' => $this->search_user_role
                ) );

                $users = $user_query->get_results();
                if ( $users ) {
                    $return = array();
                    foreach ( $users as $user ) {
                        $search = array( '%id%', '%title%', '%url%', '%type%', '%excerpt%', '%thumbnail%', '%email_address%', '%username%', '%display_name%' );
                        $replace = array(
                            'id' => $user->ID,
                            'title' => $user->display_name,
                            'url' => get_edit_user_link( $user->ID ),
                            'type' => 'user',
                            'excerpt' => '',
                            'thumbnail' => '',
                            'email_address' => $user->user_email,
                            'username' => $user->user_login,
                            'display_name' => $user->display_name,
                        );
                        $format = '<a href="%url%" target="_blank">%display_name% (%email_address%)</a>'; // Default format
                        if ( $_POST['format'] ) {
                            // Sanitize then decode the custom format passed from on page JS
                            $format = html_entity_decode( sanitize_text_field( $_POST['format'] ) );
                            // Look for a request for an excerpt with a set number of words
                            if ( preg_match( '/(?i)%excerpt:(\d{1,12})/', $format, $matches ) ) {
                                $search[] = '%excerpt:' . $matches[1] . '%';
                                $replace[] = '';
                            }
                            if ( preg_match_all( '/(?i)%meta:([^%]+)%/', $format, $matches ) ) {
                                foreach ( $matches[0] as $match ) {
                                    $search[] = $match;
                                }
                                foreach ( $matches[1] as $match ) {
                                    $replace[] = get_user_meta( $user->ID, $match, true );
                                }
                            }
                        }
                        $classes = array(
                            'wpsunshine-gf-search-result',
                            'wpsunshine-gf-search-result-user',
                            'wpsunshine-gf-search-result-' . $user->ID
                        );
                        $return[] = '<div class="' . join( ' ', $classes ) . '">' . str_replace( $search, $replace, $format ) . '</div>';
                    }
                    echo json_encode( $return );
                }
            } else {
                // We are searching for posts
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
    private static function get_users( $args ) {
        $results = array();
        $search_term = $args['search_term'];
        $roles = $args['roles'];
        $exclude_user_ids = isset( $args['exclude_user_ids'] ) ? $args['exclude_user_ids'] : array();
        $users = get_users( array(
            'search' => "*$search_term*",
            'exclude' => $exclude_user_ids,
            'role__in' => $roles,
        ) );
        if ( $users ) {
            foreach ( $users as $user ) {
                $results[] = array(
                    'label' => $user->display_name . ' (' . $user->user_email . ')',
                    'value' => $user->ID,
                );
            }
        }
        return $results;
    }

    	public function sanitize_entry_value( $value, $form_id ) {
		if ( empty( $value ) ) {
			return '';
		}

		if ( is_array( $value ) ) {
			$value = array_map( array( $this, 'sanitize_entry_value' ), $value, array_fill( 0, count( $value ), $form_id ) );
		} else {
			$post_types = $this->get_post_types( $form_id );
			$user_roles = $this->get_user_roles( $form_id );

			if ( isset( $post_types[ $value ] ) ) {
				$value = $post_types[ $value ]['label'];
			} elseif ( isset( $user_roles[ $value ] ) ) {
				$value = $user_roles[ $value ]['label'];
			} else {
				$value = sanitize_text_field( $value );
			}
		}

		return $value;
	}

	public function get_post_types( $form_id ) {
		$form = GFFormsModel::get_form_meta( $form_id );
		$post_types = array();

		foreach ( $form['fields'] as $field ) {
			if ( $field->type == $this->_slug ) {
				$field_settings = $field->get_field_input( $form, true );
				if ( !empty( $field_settings['post_types'] ) ) {
					$post_types = $field_settings['post_types'];
					break;
				}
			}
		}

		if ( !is_array( $post_types ) ) {
			$post_types = explode( ',', $post_types );
		}

		$post_types = array_filter( $post_types, 'post_type_exists' );

		foreach ( $post_types as $post_type ) {
			$post_type_object = get_post_type_object( $post_type );
			if ( !empty( $post_type_object ) ) {
				$post_types[ $post_type ] = array(
					'name'  => $post_type,
					'label' => $post_type_object->label
				);
			}
		}

		return $post_types;
	}

	public function get_user_roles( $form_id ) {
		$form = GFFormsModel::get_form_meta( $form_id );
		$user_roles = array();

		foreach ( $form['fields'] as $field ) {
			if ( $field->type == $this->_slug ) {
				$field_settings = $field->get_field_input( $form, true );
				if ( !empty( $field_settings['user_roles'] ) ) {
					$user_roles = $field_settings['user_roles'];
					break;
				}
			}
		}

		if ( !is_array( $user_roles ) ) {
			$user_roles = explode( ',', $user_roles );
		}

		foreach ( $user_roles as $user_role ) {
			$user_roles[ $user_role ] = array(
				'name'  => $user_role,
				'label' => ucwords( str_replace( '_', ' ', $user_role ) )
			);
		}

		return $user_roles;
	}

	/**
	 * Get the search form for the frontend
	 *
	 * @param array $form The form object.
	 * @param string $value The value of the field.
	 * @param string $entry_id The ID of the current entry.
	 * @param int $field_id The ID of the current field.
	 * @param array $field The field object.
	public function get_form_editor_field_settings( $form ) {
		$choices = array();
		$post_types = $this->get_post_types();
		foreach ( $post_types as $post_type ) {
			$name = str_replace( '-', '_', $post_type->name );
			$choices[] = array(
				'label' => $post_type->label,
				'value' => 'wpsunshine_search_' . $name,
				'isSelected' => false,
				'icon' => 'fa-cog'
			);
		}
		$field_settings = array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'sub_label_setting',
			'admin_label_setting',
			'description_setting',
			'css_class_setting',
			'css_class_advanced_setting',
			'input_class_setting',
			'placeholder_setting',
			'default_value_setting',
			array(
				'label'   => esc_html__( 'Post Types', 'gravityforms-search' ),
				'type'    => 'checkbox',
				'name'    => 'post_types',
				'choices' => $choices,
			),
			array(
				'label'   => esc_html__( 'Max Results', 'gravityforms-search' ),
				'type'    => 'text',
				'name'    => 'per_page',
			),
			array(
				'label'   => esc_html__( 'Result Format', 'gravityforms-search' ),
				'type'    => 'textarea',
				'name'    => 'result_format',
				'tooltip' => '<h6>' . esc_html__( 'Merge Tags', 'gravityforms-search' ) . '</h6>' .
					esc_html__( '%id% - The post ID.', 'gravityforms-search' ) . '<br />' .
					esc_html__( '%title% - The post title.', 'gravityforms-search' ) . '<br />' .
					esc_html__( '%url% - The post URL.', 'gravityforms-search' ) . '<br />' .
					esc_html__( '%type% - The post type.', 'gravityforms-search' ) . '<br />' .
					esc_html__( '%excerpt% - The post excerpt.', 'gravityforms-search' ) . '<br />' .
					esc_html__( '%excerpt:#% - The post excerpt limited to # words.', 'gravityforms-search' ) . '<br />' .
					esc_html__( '%meta:meta_key% - The meta value for the specified meta key.', 'gravityforms-search' ) . '<br />' .
					esc_html__( '%thumbnail% - The post thumbnail.', 'gravityforms-search' )
			),
			array(
				'label'   => esc_html__( 'Placeholder Text', 'gravityforms-search' ),
				'type'    => 'text',
				'name'    => 'placeholder',
				'tooltip' => '<h6>' . esc_html__( 'Example', 'gravityforms-search' ) . '</h6><code>' . esc_html__( 'Type to search...', 'gravityforms-search' ) . '</code>',
			),
			array(
				'label'   => esc_html__( 'User Search', 'gravityforms-search' ),
				'type'    => 'checkbox',
				'name'    => 'user_search',
				'choices' => array(
					array(
						'label'         => esc_html__( 'Enable user search', 'gravityforms-search' ),
						'name'          => 'user_search',
						'default_value' => false,
						'tooltip' => '<h6>' . esc_html__( 'User Search', 'gravityforms-search' ) . '</h6>' . esc_html__( 'Check this box to enable user search instead of post search.', 'gravityforms-search' ) . '</br></br>',
),
),
),
);
		}

		public function sanitize_settings( $settings ) {
			$settings['user_search'] = isset( $settings['user_search'] ) ? true : false;
			return $settings;
		}

		/**
		 * Get the field input for the frontend
		 *
		 * @param string $value The value of the field.
		 * @param int $entry_id The ID of the current entry.
		 * @param int $field_id The ID of the current field.
		 * @param array $field The field object.
		 * @param array $form The form object.
		 */
		public function get_field_input( $value, $entry_id, $field_id, $field, $form ) {
			$choices = $field['choices'];
			$user_search_enabled = false;

			foreach ( $choices as $choice ) {
				if ( $choice['name'] === 'user_search' && $choice['isSelected'] ) {
					$user_search_enabled = true;
					break;
				}
			}

			if ( $user_search_enabled ) {
				echo '<input type="text" name="input_' . esc_attr( $field_id ) . '" value="' . esc_attr( $value ) . '">';
			} else {
				echo '<input type="text" class="wpsunshine_search_field_input" name="input_' . esc_attr( $field_id ) . '" id="input_' . esc_attr( $field_id ) . '" value="' . esc_attr( $value ) . '">';
			}
		}

		/**
		 * Sanitize the value of the field before storing it in the entry
		 *
		 * @param string $value The value of the field.
		 * @param int $form_id The ID of the current form.
		 *
		 * @return string The sanitized value.
		 */
		public function sanitize_entry_value( $value, $form_id ) {
			$value = sanitize_text_field( $value );
			return $value;
		}

		/**
		 * Retrieve a list of post types
		 *
		 * @return array An array of post types.
		 */
		private function get_post_types() {
			$args = array(
				'public'   => true,
				'_builtin' => false,
			);
			$output = 'objects';
			$operator = 'or';
			$post_types = get_post_types( $args, $output, $operator );
			return $post_types;
		}

		/**
		 * Retrieve a list of user roles
		 *
		 * @return array An array of user roles.
		 */
		private function get_user_roles() {
			global $wp_roles;
			$roles = $wp_roles->roles;
			return $roles;
		}

	}

	// Register the search field with the field framework.
	GF_Fields::register( new WPSunshine_GF_Field_Search() );

}     
