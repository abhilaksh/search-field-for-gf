<?php
GFForms::include_addon_framework();

class WPSunshine_Search_Field_Addon extends GFAddOn {

    protected $_version = WPSUNSHINE_GF_SEARCH_VERSION;
    protected $_min_gravityforms_version = '2.4';
    protected $_slug = 'gravityforms-search';
    protected $_path = 'gravityforms-search/gravityforms-search.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Search Field for Gravity Forms';
    protected $_short_title = 'Search Field';

    private static $_instance = null;

    public static function get_instance() {
        if ( self::$_instance == null ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function pre_init() {
        parent::pre_init();

        if ( $this->is_gravityforms_supported() && class_exists( 'GF_Field' ) ) {
            require_once( 'includes/class-wpsunshine-search-gf-field.php' );
        }
    }

    public function init_admin() {
        parent::init_admin();

        add_filter( 'gform_tooltips', array( $this, 'tooltips' ) );
        add_action( 'gform_field_standard_settings', array( $this, 'field_standard_settings' ), 10, 2 );
        //add_action( 'gform_editor_js', array( $this, 'editor_script' ) );

    }

    public function tooltips( $tooltips ) {
        $tooltips['wpsunshine_gf_search_result_format'] = '<strong>%title%</strong> = Post title<br /><strong>%url%</strong> = URL<br /><strong>%type%</strong> = Post type<br /><strong>%excerpt%</strong> = Excerpt<br /><strong>%excerpt:#%</strong> = # Words Excerpt<br />';
        return $tooltips;
    }

    public function field_standard_settings( $position, $form_id ) {
        if ( $position == 20 ) {
            ?>
            <li class="wpsunshine_search_setting field_setting">
                <label for="field_admin_label">
                    <?php esc_html_e( 'Select Custom Post Type(s)', 'gravityforms-search' ); ?>
                </label>
                <?php
                $post_types = self::get_post_types();
                foreach ( $post_types as $post_type ) {
                    $name = str_replace( '-', '_', $post_type->name );
                ?>
                    <input type="checkbox" id="wpsunshine_search_<?php echo esc_attr( $name ); ?>_value" onclick="SetFieldProperty('wpsunshine_search_<?php echo esc_attr( $name ); ?>', this.checked);" />
                    <label for="wpsunshine_search_<?php echo esc_attr( $name ); ?>_value" class="inline"><?php echo esc_html( $post_type->label ); ?></label><br />
                <?php } ?>
            </li>
            <li class="wpsunshine_search_setting field_setting">
                <label for="field_admin_label">
                    <?php esc_html_e( 'Max results', 'gravityforms-search' ); ?>
                </label>
                <input id="wpsunshine_search_per_page_value" type="text" onkeyup="SetFieldProperty('wpsunshine_search_per_page', this.value );" onchange="SetFieldProperty('wpsunshine_search_per_page', this.value );" />
            </li>
            <li class="wpsunshine_search_setting field_setting">
                <label for="field_admin_label">
                    <?php esc_html_e( 'Result format', 'gravityforms-search' ); ?>
                    <?php gform_tooltip( 'wpsunshine_gf_search_result_format' ); ?>
                </label>
                <textarea id="wpsunshine_search_result_format_value" onkeyup="SetFieldProperty('wpsunshine_search_result_format', this.value );" onchange="SetFieldProperty('wpsunshine_search_result_format', this.value );" /></textarea>
            </li>
            <?php
        }
    }

    /**
     * Include CSS when the form contains this field.
     *
     * @return array
     */
    public function styles() {
        $styles = array(
            array(
                'handle'  => 'wpsunshine_field_search',
                'src'     => $this->get_base_url() . '/assets/style.css',
                'version' => $this->_version,
                'enqueue' => array(
                    array( 'field_types' => array( 'wpsunshine_search' ) )
                )
            )
        );

        return array_merge( parent::styles(), $styles );
    }

    public static function get_post_types() {
        $args = array(
           'public'   => true,
           '_builtin' => false,
        );
        $output = 'objects';
        $operator = 'or';
        $post_types = get_post_types( $args, $output, $operator );
        return $post_types;
    }


}
