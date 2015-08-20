<?php
define( "COMPONENTS_DIR", plugin_dir_path( __FILE__ ) . "components/" );
define( 'PLUGIN_URI', plugins_url() . '/' );
global $wcbFilter;
class wcb_FilterWidget extends WP_Widget {
    private $instance_params = array( 'post_type' => 'product', 'posts_per_page' => -1 );
    /**
     * Sets up the widgets name etc
     */
    public function __construct() {
        parent::__construct( 'wcb-FilterWidget', // Base ID
            'Brands Filter', // Name
            array(
             'description' => 'Front-end filter widget for the Woocommerce Brands plugin'
        ) // Args
            );
        add_filter( 'posts_orderby', 'wcb_reOrder' );
        add_filter( 'post_limits', 'wcb_adjustLimit' );
        if ( taxonomy_exists( 'product_cat' ) )
            $this->run_the_loop();
        return $this;
    }
    public function get_params( $key = '' ) {
        if ( $key )
            return isset( $this->instance_params[ $key ] ) ? $this->instance_params[ $key ] : false;
        return $this->instance_params;
    }
    public function run_the_loop() {
        /*
        !!REMEMBER!!
        This query has none of the requested filters applied to it!
        The ONLY limits on this query are those by post_type (if it is 'product')
        and product_cat
        */
        // Are we restricted by category?
        $cat           = '';
        $category_slug = get_option( 'woocommerce_product_category_slug' ) ? get_option( 'woocommerce_product_category_slug' ) : _x( 'product-category', 'slug', 'woocommerce' );
        if ( strrpos( $_SERVER[ 'REQUEST_URI' ], $category_slug ) !== false ) {
            $pieces = explode( '/', $_SERVER[ 'REQUEST_URI' ] );
            if ( !$pieces[ count( $pieces ) - 1 ] || strrpos( $pieces[ count( $pieces ) - 1 ], '?' ) !== false || strrpos( $pieces[ count( $pieces ) - 1 ], '#' ) !== false )
                unset( $pieces[ count( $pieces ) - 1 ] );
            $pieces = array_slice( $pieces, array_search( $category_slug, $pieces ) );
            do {
                array_shift( $pieces );
            } while ( count( $pieces ) > 1 );
            $cat = $pieces[ 0 ];
            if ( strrpos( $cat, '?' ) !== false )
                $cat = substr( $cat, 0, strrpos( $cat, '?' ) );
        } //strrpos( $_SERVER[ 'REQUEST_URI' ], $category_slug ) !== false
        else if ( isset( $_GET[ 'product_cat' ] ) ) {
            $cat = $_GET[ 'product_cat' ];
        } //isset( $_GET[ 'product_cat' ] )
        if ( $cat ) {
            $toQuery                              = array(
                 array(
                     'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => array(
                         $cat
                    ),
                    'operator' => 'IN'
                ),
                array(
                     'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => array(
                         $cat
                    ),
                    'operator' => 'IN',
                    'include_children' => 0
                ),
                'relation' => 'OR'
            );
            $this->instance_params[ 'tax_query' ] = $toQuery;
        } //$cat
        // Declare vars
        $availablePrices = $availableBrands = $activeFilters = array();
        $loop            = $min_price = $max_price = $brandId = '';
        // Setup loop
        $loop            = new WP_Query( $this->instance_params );
        // Setup initial prices to sensible min & max (to be replaced by found product's)
        $min_price       = 999999;
        $max_price       = 0;
        // Run the loop
        while ( $loop->have_posts() ):
            $loop->the_post();
            global $product;
            // Get this product's brand id & add it to the list of found brand ids (if one is found)
            $brandId = get_post_meta( get_the_ID(), 'wcb_brand' );
            $brandId = is_array( $brandId ) && count( $brandId ) ? $brandId[ 0 ] : $brandId;
            if ( ( $brandId ) && ( !array_search( $brandId, $availableBrands ) ) )
                $availableBrands[ $brandId ] = isset( $availableBrands[ $brandId ] ) ? $availableBrands[ $brandId ] + 1 : 1;
            // increase / decrease the max/min price (if applicable)
            if (get_post_meta(get_the_ID(), '_price')[0] > $max_price) $max_price = get_post_meta(get_the_ID(), '_price')[0];
            if ( (0 < get_post_meta(get_the_ID(), '_price')[0]) && (get_post_meta(get_the_ID(), '_price')[0] < $min_price) ) $min_price = get_post_meta(get_the_ID(), '_price')[0];

        // End of loop
        endwhile;
        // Set the available brands to those found
        $this->instance_params[ 'availableBrands' ] = $availableBrands;
        if ( wcb_sort_queries( $_GET ) ) {
            // Set the list of filters that are currently being used (to those in the $_GET)
            $this->instance_params[ 'active_filters' ] = $activeFilters = array_keys( wcb_sort_queries( $_GET ) );
            // Push the values of the active filters into $instance_params
            foreach ( wcb_sort_queries( $_GET ) as $key => $value ) {
                $this->instance_params[ $key ] = $value;
            } //wcb_sort_queries( $_GET ) as $key => $value
        } //wcb_sort_queries( $_GET )
        // Set the min/max available prices of products found by this query
        $this->instance_params[ 'availablePrices' ] = array(
             0 => $min_price,
            1 => $max_price
        );
        if ( ( array_search( 'price', $activeFilters ) === false ) || ( !isset( $this->instance_params[ 'price' ] ) ) || ( $this->instance_params[ 'price' ][ 0 ] > $this->instance_params[ 'price' ][ 1 ] ) ) {
            // If we aren't filtering by price (or if the requested filter is invalid) then set the price limits to the min/max
            $this->instance_params[ 'price' ] = array(
                 0 => $min_price,
                1 => $max_price
            );
        } //( array_search( 'price', $activeFilters ) === false ) || ( !isset( $this->instance_params[ 'price' ] ) ) || ( $this->instance_params[ 'price' ][ 0 ] > $this->instance_params[ 'price' ][ 1 ] )
        else {
            // If we are trying to filter below the lowest found price, bring it within range
            if ( $this->instance_params[ 'price' ][ 0 ] < $min_price )
                $this->instance_params[ 'price' ][ 0 ] = $min_price;
            // or above the highest found price, bring it within range
            if ( $this->instance_params[ 'price' ][ 1 ] > $max_price )
                $this->instance_params[ 'price' ][ 1 ] = $max_price;
        }
        // Cleanup after ourselves
        wp_reset_query();
        // Force brand ids passed to us VIA $_GET to integers
        if ( isset( $this->instance_params[ 'brand' ] ) ) {
            for ( $i = 0; $i < count( $this->instance_params[ 'brand' ] ); $i++ ) {
                $this->instance_params[ 'brand' ][ $i ] = intval( $this->instance_params[ 'brand' ][ $i ] );
            } //$i = 0; $i < count( $this->instance_params[ 'brand' ] ); $i++
        } //isset( $this->instance_params[ 'brand' ] )
        // logit($this->instance_params);
        // AAAAND we're done.
    }
    /**
     * Outputs the content of the widget
     *
     * @param array $args
     * @param array $instance
     */
    public function widget( $args, $instance ) {
        if ( is_shop() || is_product_category() ) {
            echo $args[ 'before_widget' ];
            if ( !empty( $instance[ 'title' ] ) ) {
                echo $args[ 'before_title' ] . apply_filters( 'widget_title', $instance[ 'title' ] ) . $args[ 'after_title' ];
            } //!empty($instance['title'])
            $params = array();
            // Price slider
            if ( $instance[ 'filterBy-price' ] )
                $params[] = 'priceSlider';
            // Brands
            if ( $instance[ 'filterBy-brand' ] )
                $params[] = $instance[ 'filterBy-brand-layout' ] == 'tiles' ? 'brandsTiles' : 'brandsChecklist';
            echo self::get_widget_html( $params );
            echo $args[ 'after_widget' ];
        } //is_shop() || is_product_category()
    }
    /**
     * Outputs the options form on admin
     *
     * @param array $instance The widget options
     */
    public function form( $instance ) {
        $title         = ( !empty( $instance[ 'title' ] ) ? $instance[ 'title' ] : 'Product filter' );
        $priceTitle    = ( !empty( $instance[ 'filterBy-price-title' ] ) ? $instance[ 'filterBy-price-title' ] : 'Price' );
        $filterByPrice = ( !empty( $instance[ 'filterBy-price' ] ) ? $instance[ 'filterBy-price' ] : false );
        $brandTitle    = ( !empty( $instance[ 'filterBy-brand-title' ] ) ? $instance[ 'filterBy-brand-title' ] : 'Brand' );
        $filterByBrand = ( !empty( $instance[ 'filterBy-brand' ] ) ? $instance[ 'filterBy-brand' ] : false );
        $domSelector   = ( !empty( $instance[ 'dom-product-container' ] ) ? $instance[ 'dom-product-container' ] : '#main' );

        /* todo: change to an array.
        so
        $instance['filterBy-brand-layout'] = array(
        'checkboxes' => false,
        'tiles' => true
        );
        then
        $filterByBrandLayout = array_search('true', $instance['filterBy-brand-layout']) || 'tiles';
        */
        if ( !empty( $instance[ 'filterBy-brand-layout' ] ) ) {
            $filterByBrandLayout = $instance[ 'filterBy-brand-layout' ];
        } //!empty( $instance[ 'filterBy-brand-layout' ] )
        else {
            $filterByBrandLayout = 'tiles';
        }
        // Feed values generated above in to one of these arrays
        $filterBy = array(
             'price' => $filterByPrice,
            'brand' => $filterByBrand
        );
        $options  = array(
             'brandLayout' => $filterByBrandLayout
        );
        wp_register_script( 'wcb_widget-admin-widgets-js', PLUGIN_URI . 'woocommerce-brands/admin/js/wcb_widget-admin-widgets.js' );
        wp_enqueue_script( 'wcb_widget-admin-widgets-js' );
?>
        <fieldset>
            <p>
              <label for="<?php echo $this->get_field_id( 'title' ); ?>">
                  <?php echo 'Widget Title:'; ?>
              </label>
              <input class="widefat priceOptions-priceCheck" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
            </p>
        </fieldset>

          <fieldset>
              <p>
                  <label for="<?php echo $this->get_field_id( 'dom-product-container' ); ?>">
                      <?php echo 'Product Container Selector:'; ?>
                  </label>
                  <input type="text" name="<?php echo $this->get_field_name( 'dom-product-container' ); ?>" id="<?php echo $this->get_field_id( 'dom-product-container' ); ?>" class="widefat domContainer" value="<?php echo esc_attr( $domSelector );?>">
              </p>
          </fieldset>
          <h4>Filters:</h4>


          <?php/* PRICE FILTER */?>
          <fieldset>
            <p>
                <label class="filterLabel" for="<?php echo $this->get_field_id( 'filterBy-price' );?>">
                    <input class="widefat priceOptions-priceCheck" id="<?php echo $this->get_field_id( 'filterBy-price' );?>" name="<?php echo $this->get_field_name( 'filterBy-price' );?>" type="checkbox" <?php if ( esc_attr( $filterBy[ 'price' ] ) == true ) echo "checked";?>>
                    <?php echo 'Price';?>
                </label>
                <div class="priceOptions-container"<?php if ( !esc_attr( $filterBy[ 'price' ] ) ) echo ' style="display: none;"';?>>
                    <label for="<?php echo $this->get_field_id( 'filterBy-price-title' );?>">
                        <?php echo 'Title: ';?>
                        <input class="widefat priceOptions-priceTitle" type="text" id="<?php echo $this->get_field_id( 'filterBy-price-title' );?>" name="<?php echo $this->get_field_name( 'filterBy-price-title' );?>" value="<?php echo esc_attr( $priceTitle );?>">
                    </label>
                </div>
            </p>
          </fieldset>



          <?php/* BRAND FILTER */?>
          <fieldset>
              <p>
                  <label class="filterLabel" for="<?php echo $this->get_field_id( 'filterBy-brand' );?>">
                    <input class="widefat brandOptions-brandCheck" id="<?php echo $this->get_field_id( 'filterBy-brand' );?>" name="<?php echo $this->get_field_name( 'filterBy-brand' );?>" type="checkbox"<?php if ( esc_attr( $filterBy[ 'brand' ] ) ) echo " checked";?>>
                    <?php echo 'Brand';?>
                  </label>
                  <div class="brandOptions-container"<?php if ( !esc_attr( $filterBy[ 'brand' ] ) ) echo ' style="display:none;"';?>>
                    <label for="<?php echo $this->get_field_id( 'filterBy-brand-title' );?>">
                        <?php echo 'Title: ';?>
                        <input class="widefat brandOptions-brandTitle" id="<?php echo $this->get_field_id( 'filterBy-brand-title' );?>" name="<?php echo $this->get_field_name( 'filterBy-brand-title' );?>" type="text" value="<?php echo esc_attr( $brandTitle );?>">
                    </label><br>
                    <?php
                        if ( $options[ 'brandLayout' ] == 'checkboxes' ) {
                            $tilesChecked  = '';
                            $checksChecked = 'checked="checked"';
                            $layoutValue   = 'checkboxes';
                        } //$options[ 'brandLayout' ] == 'checkboxes'
                        else {
                            $tilesChecked  = 'checked="checked"';
                            $checksChecked = '';
                            $layoutValue   = 'tiles';
                        }
                    ?>

                    <label class="brandOptions-brandLayout" for="filterBy-brand-layout--tiles"><input class="widefat brandLayout" type="radio" name="filterBy-brand-layout" id="filterBy-brand-layout--tiles" value="tiles" <?php echo $tilesChecked;?>>Thumbnail tiles</label>
                    <label class="brandOptions-brandLayout" for="filterBy-brand-layout--checkboxes"><input class="widefat brandLayout" type="radio" name="filterBy-brand-layout" id="filterBy-brand-layout--checkboxes" value="checkboxes" <?php echo $checksChecked;?>>List of checkboxes</label>
                    <input class="brandLayoutVal" type="hidden" name="<?php echo $this->get_field_name( 'filterBy-brand-layout' );?>" id="<?php echo $this->get_field_id( 'filterBy-brand-layout' );?>" data-value="<?php echo $layoutValue;?>">
                  </div>
              </p>
          </fieldset>
        <?php
    }
    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
        $instance                            = array();
        $instance[ 'title' ]                 = ( !empty( $new_instance[ 'title' ] ) ) ? strip_tags( $new_instance[ 'title' ] ) : '';
        $instance[ 'filterBy-price' ]        = ( !empty( $new_instance[ 'filterBy-price' ] ) ) ? (bool) strip_tags( $new_instance[ 'filterBy-price' ] ) : false;
        $instance[ 'filterBy-price-title' ]  = ( !empty( $new_instance[ 'filterBy-price-title' ] ) ) ? strip_tags( $new_instance[ 'filterBy-price-title' ] ) : 'Price';
        $instance[ 'filterBy-brand' ]        = ( !empty( $new_instance[ 'filterBy-brand' ] ) ) ? (bool) strip_tags( $new_instance[ 'filterBy-brand' ] ) : false;
        $instance[ 'filterBy-brand-title' ]  = ( !empty( $new_instance[ 'filterBy-brand-title' ] ) ) ? strip_tags( $new_instance[ 'filterBy-brand-title' ] ) : 'Brand';
        $instance[ 'filterBy-brand-layout' ] = ( !empty( $new_instance[ 'filterBy-brand-layout' ] ) ) ? strip_tags( $new_instance[ 'filterBy-brand-layout' ] ) : 'tiles';
        $instance[ 'dom-container-selector' ]  = ( !empty( $new_instance[ 'dom-container-selector' ] ) ) ? strip_tags( $new_instance[ 'dom-container-selector' ] ) : '#main';
        return $instance;
    }
    private static function get_widget_html( $args ) {
        /* TODO: you're gunna have to use the following to dynamically build the admin form, the widget html, and the filter logic
        the html (admin and public) should be fairly trivial, but it will take some thought to get the filter logic working dynamically

        wcb_get_attributes();
        returns
        Array
        (
        [pa_color] =>
        [pa_speed] => Speed
        )
        */
        $output       = '';
        $module_count = 0;
        wp_register_script( 'wcb_widget-main-js', PLUGIN_URI . 'woocommerce-brands/public/js/wcb_widget-main.js' );
        wp_enqueue_script( 'wcb_widget-main-js' );
        wp_register_style( 'frontendMain-css', PLUGIN_URI . 'woocommerce-brands/public/css/jquery-ui.css' );
        wp_enqueue_style( 'frontendMain-css' );
        wp_register_script( 'frontendMain-js', PLUGIN_URI . 'woocommerce-brands/public/js/jquery-ui.js' );
        wp_enqueue_script( 'frontendMain-js' );
        $output .= '<form id="wcb_filterForm" class="wcb_form">';
        if ( is_array( $args ) ) {
            if ( in_array( 'priceSlider', $args ) ) {
                $output .= wcb_get_html_component( 'slider' );
                if ( wcb_get_html_component( 'slider' ) )
                    $module_count++;
            } //in_array('priceSlider', $args)
            if ( in_array( 'brandsTiles', $args ) ) {
                $output .= wcb_get_html_component( 'tiles' );
                if ( wcb_get_html_component( 'tiles' ) )
                    $module_count++;
            } //in_array( 'brandsTiles', $args )
            if ( in_array( 'brandsChecklist', $args ) ) {
                $output .= wcb_get_html_component( 'checkboxes' );
                if ( wcb_get_html_component( 'checkboxes' ) )
                    $module_count++;
            } //in_array( 'brandsChecklist', $args )
        } //is_array( $args )
        if ( $module_count > 0 ) {
            $output .= '<button id="wcb_form_reset_btn" disabled>Reset</button><button id="wcb_form_update_btn">Update</button>';
        } //$module_count > 0
        $output .= '</form>';
        return $output;
    }
}
if ( !function_exists( 'wcb_get_woocommerce_version' ) ) {
    /**
     * Public function to get WooCommerce version
     *
     * @return float|NULL
     */
    function wcb_get_woocommerce_version() {
        if ( !function_exists( 'get_plugins' ) )
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        $plugin_folder = get_plugins( '/' . 'woocommerce' );
        $plugin_file   = 'woocommerce.php';
        if ( isset( $plugin_folder[ $plugin_file ][ 'Version' ] ) ) {
            return $plugin_folder[ $plugin_file ][ 'Version' ];
        } //isset($plugin_folder[$plugin_file]['Version'])
        else {
            return NULL;
        }
    }
} //!function_exists('wcb_get_woocommerce_version')
if ( !function_exists( 'wcb_get_html_component' ) ) {
    function wcb_get_html_component( $componentName = '' ) {
        $componentHTML = '';
        if ( !$componentHTML && $componentName && file_exists( COMPONENTS_DIR . "{$componentName}.php" ) ) {
            $componentHTML = COMPONENTS_DIR . "{$componentName}.php";
        } //!$componentHTML && $componentName && file_exists(COMPONENTS_DIR . "{$componentName}.php")
        if ( $componentHTML ) {
            include( $componentHTML );
            return $componentMarkup ? $componentMarkup : false;
        } //$componentHTML
    }
} //!function_exists('wcb_get_html_component')
if ( !function_exists( 'wcb_reOrder' ) ) {
    /**
     * Public function to set the retrieved posts' order
     *
     * @return string
     */
    function wcb_reOrder( $orderBy ) {
        //TODO: return either 'post_title ASC' or other
        return $orderBy;
    }
} //!function_exists('wcb_reOrder')
if ( !function_exists( 'wcb_adjustLimit' ) ) {
    /**
     * Public function to set the limit on returned posts
     *
     * @return integer|NULL
     */
    function wcb_adjustLimit( $limit ) {
        //TODO: return either a blank string or whatever
        return $limit;
    }
} //!function_exists('wcb_adjustLimit')
if ( !function_exists( 'wcb_addFilters' ) ) {
    /**
     * Public function to apply all active filters
     *
     * @return objects
     */
    function wcb_addFilters( $query ) {
        global $wcbFilter;
        // todo only add filters on the shop pages
        if ( $query->is_main_query() && ( is_shop() || is_product_category() ) ) {
            $requestedFilters = wcb_sort_queries( $_GET );
            $params           = $wcbFilter->get_params();
            $toQuery          = array();
            // Price
            if ( $requestedFilters[ 'price' ] ) {
                $min_price = $params[ 'price' ][ 0 ];
                $max_price = $params[ 'price' ][ 1 ];
                $toQuery[] = array(
                     'key' => '_price',
                    'value' => array(
                         $min_price,
                        $max_price
                    ),
                    'type' => 'NUMERIC',
                    'compare' => "BETWEEN"
                );
            } //$requestedFilters[ 'price' ]
            // Brand
            if ( $requestedFilters[ 'brand' ] ) {
                $limitBrands = is_array( $params[ 'brand' ] ) ? $params[ 'brand' ] : array(
                     0 => $params[ 'brand' ]
                );
                $toQuery[]   = array(
                     'key' => 'wcb_brand',
                    'value' => $limitBrands,
                    'type' => 'NUMERIC',
                    'compare' => "IN"
                );
            } //$requestedFilters[ 'brand' ]
            if ( count( $toQuery ) )
                set_query_var( 'meta_query', $toQuery );
        } //$query->is_main_query()
    }
} //!function_exists('wcb_addFilters')
if ( !function_exists( 'wcb_get_attributes' ) ) {
    /**
     * Get all possible woocommerce attribute taxonomies
     *
     * @return mixed|void
     */
    function wcb_get_attributes() {
        $attribute_taxonomies = wc_get_attribute_taxonomies();
        $attributes           = array();
        if ( $attribute_taxonomies ) {
            foreach ( $attribute_taxonomies as $tax ) {
                $attributes[ wc_attribute_taxonomy_name( $tax->attribute_name ) ] = $tax->attribute_label;
            } //$attribute_taxonomies as $tax
        } //$attribute_taxonomies
        return apply_filters( 'wcb_A_get_attributes', $attributes );
    }
} //!function_exists('wcb_get_attributes')
if ( !function_exists( "wcb_sort_queries" ) ) {
    function wcb_sort_queries( $getObj ) {
        $arrOut = false;
        if ( $getObj && ( isset( $getObj[ 'wcb_filter' ] ) ) ) {
            $strGet = $getObj[ 'wcb_filter' ];
            $arrOut = array();
            do {
                $arrVals = $delimeters = array();
                $start   = $end = $strVals = $filterType = '';
                if ( strrpos( $strGet, '[' ) === false ) {
                    $delimeters[ 0 ] = '%5B';
                    $delimeters[ 1 ] = '%5D';
                } //strrpos( $strGet, '[' ) === false
                else {
                    $delimeters[ 0 ] = '[';
                    $delimeters[ 1 ] = ']';
                }
                //get first and last pos of ]
                $start = strrpos( $strGet, $delimeters[ 0 ] );
                $end   = strrpos( $strGet, $delimeters[ 1 ] );
                if ( $start && $end && ( $start < $end ) ) {
                    // Get the values
                    $strVals = substr( $strGet, $start, $end );
                    $strVals = substr( $strVals, 1, strlen( $strVals ) - 2 );
                    // Pop the values off the GET query
                    $strGet  = substr( $strGet, 0, $start );
                    // Deal with multiple values (if applicable)
                    if ( strrpos( $strVals, '_' ) > 0 ) {
                        $arrVals = explode( '_', $strVals );
                    } //strrpos($strVals, '_') > 0
                    // Get the type of filter the values apply to (called $filterType)
                    $start = strrpos( $strGet, $delimeters[ 1 ] );
                    if ( !$start ) {
                        $start      = 0;
                        $filterType = $strGet;
                        $strGet     = '';
                    } //!$start
                    $filterType = $filterType ? $filterType : substr( $strGet, $start == 0 ? 0 : $start + 1, strlen( $strGet ) );
                    // Pop the filter type off the GET query
                    $strGet     = $strGet ? substr( $strGet, 0, $start == 0 ? strlen( $strGet ) : $start + 1 ) : $strGet;
                    // If there are multiple values, create a nested array
                    if ( $arrVals ) {
                        $arrOut[ $filterType ] = array();
                        // Put the values into the nested array
                        $arrVals               = preg_replace( '/-/', ' ', $arrVals );
                        for ( $i = 0; $i < count( $arrVals ); $i++ ) {
                            $arrOut[ $filterType ][ $i ] = $arrVals[ $i ];
                        } //$i = 0; $i < count($arrVals); $i++
                        // Otherwise just add the value to the list, under a key called the filter type
                    } //$arrVals
                    else {
                        if ( strrpos( $strVals, '-' ) > 0 ) {
                            $strVals = preg_replace( '/-/', ' ', $strVals );
                        } //strrpos($strVals, '-') > 0
                        $arrOut[ $filterType ] = $strVals;
                    }
                } //$start && $end && ($start < $end)
            } while ( strlen( $strGet ) > 0 );
        } //$getObj && $getObj['wcb_filter']
        return $arrOut;
    }
} //!function_exists("wcb_sort_queries")
add_action( 'widgets_init', create_function( '', 'return register_widget("wcb_FilterWidget");' ) );
add_action( 'registered_taxonomy', 'runWidget' );
add_action( 'pre_get_posts', 'wcb_addFilters' );
function runWidget( $taxonomy ) {
    if ( $taxonomy === 'product_cat' ) {
        global $wcbFilter;
        $wcbFilter = new wcb_FilterWidget();
    } //$taxonomy === 'product_cat'
}
