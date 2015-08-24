<?php
define( "COMPONENTS_DIR", plugin_dir_path( __FILE__ ) . "components/" );
define( 'PLUGIN_URI', plugins_url() . '/' );
global $wcbFilter;
class wcb_FilterWidget extends WP_Widget {
    /* $instance_params is used for run-time variable storage. It keeps information of active filters, while $instance holds information on the immutable state of the plugin
    As an example, $instance_params might have a field for the max and min price which will be used to affect the query that returns the posts to the user.
    On the other-hand, $instance might have a field for whether or not to enable filtering by price.
    In summary, $instance stores information provided in the Wordpress back-end, $instance_params holds information provided through the widget in the frontend */
    private $instance_params = array(
      'post_type' => 'product',
      'posts_per_page' => -1
    );

    /**
     * Sets up the widget's name etc.
     *
     * Also, sets up the instance_params by running a loop with the appropriate queries
     *
     */
    public function __construct() {
        parent::__construct(
          'wcb-FilterWidget', // Base ID
          'Brands Filter', // Name
          array(
            'description' => 'Front-end filter widget for the Woocommerce Brands plugin'
          ) // Args
        );

        /**
        * The following will be enabled in V1.2
        */
        // add_filter( 'posts_orderby', 'wcb_reOrder' );
        // add_filter( 'post_limits', 'wcb_adjustLimit' );

        // run_the_loop() does all the setup for the instance
        $this->run_the_loop();

        // Return the instance
        return $this;
    }




    /**
     * Sets up and runs a loop to get the available values of visible products
     *
     * This function does not use the user-specified values for the query. It gets things like the maximum & minimum
     * price of all products, the brands in use by the visible products, the available custom attributes, etc.
     *
     * As an example, on the main shop page, it will run a loop with a query for post_type=product, and not much else.
     * If the user is on a category page, it will run a loop with a query for products in that category
     */
    public function run_the_loop() {
      // Declare vars
      $availablePrices = $availableBrands = $activeFilters = $availableAttributes = array();
      $loop = $min_price = $max_price = $brandId = '';

      // Setup initial prices to sensible min & max (to be replaced by found product's)
      $min_price       = 999999;
      $max_price       = 0;

      // Get and set a tax query for category (where applicable)
      $requestedCategory = $this->get_the_category();
      if ( $requestedCategory ) {
        $toQuery = array(
          array(
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => array(
              $requestedCategory
            ),
            'operator' => 'IN'
          ),
          array(
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => array(
              $requestedCategory
            ),
            'operator' => 'IN',
            'include_children' => 0
          ),
          'relation' => 'OR'
        );
        $this->instance_params[ 'tax_query' ] = $toQuery;
      }

      // Setup loop
      $loop = new WP_Query( $this->instance_params );

      // Run the loop
      while ( $loop->have_posts() ): $loop->the_post();
        global $product;

        // Get the current product's brand id & add it to the list of found brand ids (if one is found)
        $brandId = get_post_meta( get_the_ID(), 'wcb_brand' );
        $brandId = is_array( $brandId ) && count( $brandId ) ? $brandId[ 0 ] : $brandId;
        if ( ( $brandId ) && ( !array_search( $brandId, $availableBrands ) ) )
            $availableBrands[ $brandId ] = isset( $availableBrands[ $brandId ] ) ? $availableBrands[ $brandId ] + 1 : 1;

        // Increase / decrease the max / min price (if applicable)
        if (get_post_meta(get_the_ID(), '_price')[0] > $max_price)
          $max_price = get_post_meta(get_the_ID(), '_price')[0];
        if ( (0 < get_post_meta(get_the_ID(), '_price')[0]) && (get_post_meta(get_the_ID(), '_price')[0] < $min_price) )
          $min_price = get_post_meta(get_the_ID(), '_price')[0];

        // Custom attributes
        $attributes = $product->get_attributes();
        if (is_array($attributes) && count($attributes)) {
          foreach ($attributes as $attributeName => $attributeInfo) {
            $attributeValues = get_the_terms(get_the_ID(), $attributeName);
            if (is_array($attributeValues) && count($attributeValues)) {
              if (!isset($availableAttributes[$attributeName])) $availableAttributes[$attributeName] = array();
              foreach ($attributeValues as $attributeValue) {
                $attributeValue = $attributeValue->slug;
                if (isset($availableAttributes[$attributeName][$attributeValue])) {
                  $availableAttributes[$attributeName][$attributeValue] = $availableAttributes[$attributeName][$attributeValue] + 1;
                } else {
                  $availableAttributes[$attributeName][$attributeValue] = 1;
                }
              }
            }

          }
        }

      // End of loop
      endwhile;


      // Set the available brands to those found
      $this->instance_params[ 'availableBrands' ] = $availableBrands;

      // Set the available custom attributes to those found
      $this->instance_params[ 'availableAttributes' ] = $availableAttributes;

      // Set the min/max available prices of products found
      $this->instance_params[ 'availablePrices' ] = array(
         0 => $min_price,
         1 => $max_price
      );

      $sortedQueries = wcb_sort_queries( $_GET );
      if ( $sortedQueries ) {
        // Set active_filters to those that are currently in $_GET
        $this->instance_params[ 'active_filters' ] = $activeFilters = array_keys( $sortedQueries );
        // Push the values of the active filters into $instance_params
        foreach ( $sortedQueries as $key => $value ) {
          $this->instance_params[ $key ] = $value;
        }
      }

      // If we aren't filtering by price (or if the requested filter is invalid) then set the price limits to the min/max
      if ( ( array_search( 'price', $activeFilters ) === false ) || ( !isset( $this->instance_params[ 'price' ] ) ) || ( $this->instance_params[ 'price' ][ 0 ] > $this->instance_params[ 'price' ][ 1 ] ) ) {
          $this->instance_params[ 'price' ] = array(
            0 => $min_price,
            1 => $max_price
          );
      } else {
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


      // AAAAND we're done.
    }


    /**
     * Return either the current category (as obtained from the URI) or undefined
     *
     *
     *
     */
    private static function get_the_category() {
      $cat = '';
      $valid_categories = $product_categories = array();
      $args = array(
        'number'     => $number,
        'orderby'    => $orderby,
        'order'      => $order,
        'hide_empty' => $hide_empty,
        'include'    => $ids
      );
      $product_categories = get_terms( 'product_cat', $args );
      for ($i=0; $i < count($product_categories); $i++) {
        if (!is_wp_error($product_categories)) {
          $valid_categories[] = $product_categories[$i]->term_id;
          $valid_categories[] = $product_categories[$i]->slug;
        }
      }

      // Figure out what to look in the request URI for
      $category_slug = get_option( 'woocommerce_product_category_slug' ) ? get_option( 'woocommerce_product_category_slug' ) : _x( 'product-category', 'slug', 'woocommerce' );

      // Find the slug in the URI
    if ( strrpos( $_SERVER[ 'REQUEST_URI' ], $category_slug ) !== false ) {

        // Split the URI at forward slashes, then grab the last piece (usually the deepest sub-category)
        $pieces = explode( '/', $_SERVER[ 'REQUEST_URI' ] );

        // Remove things like 'foo.com/bar/?abc=def' and 'foo.com/bar/#anchor'
        if ( !$pieces[ count( $pieces ) - 1 ] || strrpos( $pieces[ count( $pieces ) - 1 ], '?' ) !== false || strrpos( $pieces[ count( $pieces ) - 1 ], '#' ) === 0 )
          unset( $pieces[ count( $pieces ) - 1 ] );

        // Remove things like 'foo.com/bar#anchor'
        if (strrpos( $pieces[ count( $pieces ) - 1 ], '#' ) !== false )
          $pieces[ count( $pieces ) - 1 ] = explode( '#', $pieces[ count( $pieces ) - 1 ] )[0];

        // Get only the stuff after the "product_category" or the "category_id" or w/e the permalink structure is
        $pieces = array_slice( $pieces, array_search( $category_slug, $pieces ) );

        do {
          $cat = array_pop($pieces);

          // Make extra sure that there isn't a '#' or '?'
          if ( strrpos( $cat, '?' ) !== false )
            $cat = substr( $cat, 0, strrpos( $cat, '?' ) );
          if ( strrpos( $cat, '#' ) !== false )
            $cat = substr( $cat, 0, strrpos( $cat, '#' ) );

        } while ( (array_search($cat, $valid_categories) === false) && (count($pieces) > 0 ) );

      } else if ( isset( $_GET[ 'product_cat' ] ) ) {
        $cat = $_GET[ 'product_cat' ];
      }

      return $cat;
    }


    /**
     * Outputs the content of the widget
     *
     * First, we build an array of strings for the parts of the widget
     * that we want to render. Then we pass the array to a function
     * which will actually get the html, return it to us and we'll echo
     * it out to the page.
     *
     * @param array $args
     * @param array $instance
     *
     */
    public function widget( $args, $instance ) {
      if ( is_shop() || is_product_category() ) {
        wp_register_script( 'wcb_widget-main-js', PLUGIN_URI . 'woocommerce-brands/public/js/wcb_widget-main.js' );
        wp_enqueue_script( 'wcb_widget-main-js' );
        wp_register_style( 'frontendMain-css', PLUGIN_URI . 'woocommerce-brands/public/css/jquery-ui.css' );
        wp_enqueue_style( 'frontendMain-css' );
        wp_register_script( 'frontendMain-js', PLUGIN_URI . 'woocommerce-brands/public/js/jquery-ui.js' );
        wp_enqueue_script( 'frontendMain-js' );

        // Declare vars
        $params = array();

        // Widget title
        if ( !empty( $instance[ 'title' ] ) )
            echo $args[ 'before_title' ] . apply_filters( 'widget_title', $instance[ 'title' ] ) . $args[ 'after_title' ];
        // Price slider
        if ( $instance[ 'filterBy-price' ] )
            $params[] = 'priceSlider';
        // Brands
        if ( $instance[ 'filterBy-brand' ] )
            $params[] = $instance[ 'filterBy-brand-layout' ] == 'tiles' ? 'brandsTiles' : 'brandsChecklist';
        // Custom attributes
        $params['wcb_ca'] = array();
        foreach ($instance as $key => $value) {
          if (strrpos($key, 'wcb_ca-') !== false) $params['wcb_ca'][] = substr($key, 7, strlen($key));
        };

        // Get and echo the component html
        echo self::get_widget_html( $params, $instance );

        echo $args[ 'after_widget' ];
      }
    }






    /**
     * Outputs the options form on admin
     *
     * @param array $instance The widget options
     */
    public function form( $instance ) {
      wp_register_script( 'wcb_widget-admin-widgets-js', PLUGIN_URI . 'woocommerce-brands/admin/js/wcb_widget-admin-widgets.js' );
      wp_enqueue_script( 'wcb_widget-admin-widgets-js' );

      // Declare vars
      $title         = ( isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : 'Filter products by' );
      $priceTitle    = ( isset( $instance[ 'filterBy-price-title' ] ) ? $instance[ 'filterBy-price-title' ] : 'Price:' );
      $filterByPrice = ( isset( $instance[ 'filterBy-price' ] ) ? $instance[ 'filterBy-price' ] : false );
      $brandTitle    = ( isset( $instance[ 'filterBy-brand-title' ] ) ? $instance[ 'filterBy-brand-title' ] : 'Brand:' );
      $filterByBrand = ( isset( $instance[ 'filterBy-brand' ] ) ? $instance[ 'filterBy-brand' ] : false );
      $domSelector   = ( isset( $instance[ 'dom-container-selector' ] ) ? $instance[ 'dom-container-selector' ] : '#main:' );
      $otherAttribs  = wcb_get_attributes();
      $extraMarkup = '';

      // Get the layout for the brand filter
      if ( !empty( $instance[ 'filterBy-brand-layout' ] ) ) {
          $filterByBrandLayout = $instance[ 'filterBy-brand-layout' ];
      } else {
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

      // Attributes to print into the html tags
      if ( $options[ 'brandLayout' ] == 'checkboxes' ) {
        $tilesChecked  = '';
        $checksChecked = 'checked="checked"';
        $layoutValue   = 'checkboxes';
      } else {
        $tilesChecked  = 'checked="checked"';
        $checksChecked = '';
        $layoutValue   = 'tiles';
      } ?>

      <!-- Widget title -->
      <fieldset>
        <p>
          <label for="<?php echo $this->get_field_id( 'title' ); ?>">
            <?php echo 'Widget Title:'; ?>
          </label>
          <input class="widefat priceOptions-priceCheck" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" placeholder="e.g Filter products by">
        </p>
      </fieldset>

      <!-- Dom container selector -->
      <fieldset>
        <p>
          <label for="<?php echo $this->get_field_id( 'dom-container-selector' ); ?>">
            <?php echo 'Product Container Selector:'; ?>
          </label>
          <input type="text" name="<?php echo $this->get_field_name( 'dom-container-selector' ); ?>" id="<?php echo $this->get_field_id( 'dom-container-selector' ); ?>" class="widefat domContainer" value="<?php echo esc_attr( $domSelector );?>" placeholder="e.g #main">
        </p>
      </fieldset>

      <!-- All filters start -->
      <h4>Filters:</h4>

      <!-- PRICE FILTER -->
      <fieldset>
        <p>
          <label class="filterLabel" for="<?php echo $this->get_field_id( 'filterBy-price' );?>">
            <input class="widefat priceOptions-priceCheck" id="<?php echo $this->get_field_id( 'filterBy-price' );?>" name="<?php echo $this->get_field_name( 'filterBy-price' );?>" type="checkbox" <?php if ( esc_attr( $filterBy[ 'price' ] ) == true ) echo "checked";?>>
            <?php echo 'Price';?>
          </label>
          <div class="priceOptions-container"<?php if ( !esc_attr( $filterBy[ 'price' ] ) ) echo ' style="display: none;"';?>>
            <label for="<?php echo $this->get_field_id( 'filterBy-price-title' );?>">
              <?php echo 'Title: ';?>
              <input class="widefat priceOptions-priceTitle" type="text" id="<?php echo $this->get_field_id( 'filterBy-price-title' );?>" name="<?php echo $this->get_field_name( 'filterBy-price-title' );?>" value="<?php echo esc_attr( $priceTitle );?>" placeholder="e.g Price: ">
            </label>
          </div>
        </p>
      </fieldset>

      <!-- BRAND FILTER -->
      <fieldset>
        <p>
          <label class="filterLabel" for="<?php echo $this->get_field_id( 'filterBy-brand' );?>">
            <input class="widefat brandOptions-brandCheck" id="<?php echo $this->get_field_id( 'filterBy-brand' );?>" name="<?php echo $this->get_field_name( 'filterBy-brand' );?>" type="checkbox"<?php if ( esc_attr( $filterBy[ 'brand' ] ) ) echo " checked";?>>
            <?php echo 'Brand';?>
          </label>
          <div class="brandOptions-container"<?php if ( !esc_attr( $filterBy[ 'brand' ] ) ) echo ' style="display:none;"';?>>
            <label for="<?php echo $this->get_field_id( 'filterBy-brand-title' );?>">
              <?php echo 'Title: ';?>
              <input class="widefat brandOptions-brandTitle" id="<?php echo $this->get_field_id( 'filterBy-brand-title' );?>" name="<?php echo $this->get_field_name( 'filterBy-brand-title' );?>" type="text" value="<?php echo esc_attr( $brandTitle );?>" placeholder="e.g Brand: ">
            </label>
            <br>
            <label class="brandOptions-brandLayout" for="filterBy-brand-layout--tiles">
              <input class="widefat brandLayout" type="radio" name="filterBy-brand-layout" id="filterBy-brand-layout--tiles" value="tiles" <?php echo $tilesChecked;?>>
              Thumbnail tiles
            </label>
            <label class="brandOptions-brandLayout" for="filterBy-brand-layout--checkboxes">
              <input class="widefat brandLayout" type="radio" name="filterBy-brand-layout" id="filterBy-brand-layout--checkboxes" value="checkboxes" <?php echo $checksChecked;?>>
              List of checkboxes
            </label>
            <input class="brandLayoutVal" type="hidden" name="<?php echo $this->get_field_name( 'filterBy-brand-layout' );?>" id="<?php echo $this->get_field_id( 'filterBy-brand-layout' );?>" data-value="<?php echo $layoutValue;?>">
          </div>
        </p>
      </fieldset>

      <!-- CUSTOM ATTRIBUTE FILTER -->
      <?php if (count($otherAttribs)): ?>

        <fieldset>
          <p class="customAttributes clearfix">
            <label class="filterLabel">
              <?php echo 'Custom Attributes';?>
            </label>
          </p>
          <div class="customAttributes-container clearfix">
            <div class="customAttributes-containerInnter clearfix">
              <select class="customAttributes-select" name="custom_attributes">
                <option style="font-style: italic;" value="">[Please select]</option>
                <?php foreach ($otherAttribs as $key => $value) :
                  // Loop for each custom attribute, adding an option to the select and a text input for its title
                  $isHidden = ( !isset( $instance[ 'wcb_ca-'.$key ] ) ) ? 'style="display: none;"' : '';
                  $currentVal = ( isset( $instance[ 'wcb_ca-'.$key ] ) ) ? $instance[ 'wcb_ca-'.$key ] : '';
                  $identifier = $value ? $value : $key;
                  $extraMarkup .= '<div data-ca-key="'.$key.'" class="customAttributes-row clearfix" '.$isHidden.'>
                                    <label class="customAttributes-row-label" for="'.$this->get_field_id( 'wcb_ca-'.$key ).'">
                                      Title for '.$identifier.'
                                    </label>
                                    <button data-ca-key="'.$key.'" class="customAttributes-rm-btn">X</button>
                                    <input id="'.$this->get_field_id( 'wcb_ca-'.$key ).'" type="text" name="'.$this->get_field_name( 'wcb_ca-'.$key ).'" value="'.$currentVal.'" class="customAttributes-input" placeholder="e.g '.$identifier.'">
                                  </div>'; ?>
                  <option value="<?php echo $key; ?>"><?php echo $value ? $value : $key; ?></option>
                <?php endforeach; ?>

              </select>
              <button type="button" name="add_custom_attributes" class="customAttributes-btn">+</button>
            </div>
            <?php echo $extraMarkup; //(the text inputs for custom attribute titles generated a few lines above)?>
          </div>
        </fieldset>
      <?php endif;
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
        // Add basic fields
        $instance                            = array();
        $instance[ 'title' ]                 = ( isset( $new_instance[ 'title' ] ) ) ? strip_tags( $new_instance[ 'title' ] ) : '';
        $instance[ 'filterBy-price' ]        = ( isset( $new_instance[ 'filterBy-price' ] ) ) ? (bool) strip_tags( $new_instance[ 'filterBy-price' ] ) : false;
        $instance[ 'filterBy-price-title' ]  = ( isset( $new_instance[ 'filterBy-price-title' ] ) ) ? strip_tags( $new_instance[ 'filterBy-price-title' ] ) : '';
        $instance[ 'filterBy-brand' ]        = ( isset( $new_instance[ 'filterBy-brand' ] ) ) ? (bool) strip_tags( $new_instance[ 'filterBy-brand' ] ) : false;
        $instance[ 'filterBy-brand-title' ]  = ( isset( $new_instance[ 'filterBy-brand-title' ] ) ) ? strip_tags( $new_instance[ 'filterBy-brand-title' ] ) : '';
        $instance[ 'filterBy-brand-layout' ] = ( !empty( $new_instance[ 'filterBy-brand-layout' ] ) ) ? strip_tags( $new_instance[ 'filterBy-brand-layout' ] ) : 'tiles';
        $instance[ 'dom-container-selector' ]  = ( isset( $new_instance[ 'dom-container-selector' ] ) ) ? strip_tags( $new_instance[ 'dom-container-selector' ] ) : '';

        // Add custom attribute titles
        foreach ($new_instance as $key => $value) {
          if (strrpos($key, 'wcb_ca-') !== false) $instance[$key] = strip_tags($new_instance[$key]);
        }


        return $instance;
    }






    /**
     * Returns either a specified value from $instance_params, or all of them
     *
     * If the given key doesn't exist, it returns false
     *
     * @return {mixed} The value requested or false, if a value was requested but doesn't exist. Otherwise, returns all values from $instance_params
     */
    public function get_params( $key = '' ) {
        if ( $key )
            return isset( $this->instance_params[ $key ] ) ? $this->instance_params[ $key ] : false;
        return $this->instance_params;
    }





    /**
     * Takes a directive ($args) and generates/obtains the markup for the segments
     * listed in the directive. Returns the markup.
     *
     * @param array $args An array of strings for the segements to render
     * @param array $instance The saved settings for the widget
     *
     * @return {string} The markup generated as a result of the contents of $args
     */
    private static function get_widget_html( $args, $instance ) {
      // Declare vars
      $module_count = 0;
      $virtueOutput = (strtolower(wp_get_theme()->name) === 'virtue') ? ', "isVirtue" : true' : ', "isVirtue" : false';
      $domSelector  = $instance['dom-container-selector'] ? $instance['dom-container-selector'] : '#main';
      $output       = '<div id="oneTimeScript">
                          <script>
                            wcbGlobals = {"productContainerSelector": "'.trim($domSelector).'"'.$virtueOutput.'};
                            document.getElementById("oneTimeScript").remove();
                          </script>
                        </div>';

      if ( is_array( $args ) ) {

        // Price slider
        if ( in_array( 'priceSlider', $args ) ) {
          if ( wcb_get_html_component( 'slider' ) ) {
            $module_count++;
            $output .= $instance['filterBy-price-title'] ? $instance['filterBy-price-title'] : '';
            $output .= wcb_get_html_component( 'slider' );
          }
        }

        // Brand tiles
        if ( in_array( 'brandsTiles', $args ) ) {
          if ( wcb_get_html_component( 'tiles' ) ) {
            $module_count++;
            $output .= $instance['filterBy-brand-title'] ? $instance['filterBy-brand-title'] : '';
            $output .= wcb_get_html_component( 'tiles' );
          }
        }

        // Brand checklist
        if ( in_array( 'brandsChecklist', $args ) ) {
          if ( wcb_get_html_component( 'checkboxes' ) ) {
            $module_count++;
            $output .= $instance['filterBy-brand-title'] ? $instance['filterBy-brand-title'] : '';
            $output .= wcb_get_html_component( 'checkboxes' );
          }
        }

        // Custom attributes
        // we do the check slightly differently here, as the wcb_ca field of the $args array
        // is always present (so in_array() is useless), but only sometimes has any values in it.
        if (count($args['wcb_ca'])) {
          $module_count++;
          $output .= wcb_get_html_component( 'generic_checkboxes', $instance );
        }

      }

      // Check if the above code actually generated any html
      if ( $module_count > 0 ) {
        // Add the buttons
        $output .= '<div class="wcb_form-buttonWrapper">
                      <button id="wcb_form_reset_btn" class="sui-button--grey disabled" disabled>Reset</button>
                      <button id="wcb_form_update_btn" class="sui-button--grey">
                        <div class="wcbLoader" style="display: none;"></div>
                        Update
                      </button>
                    </div>';
        // Wrap in a form tag
        $output  = '<form id="wcb_filterForm" class="wcb_form clearfix">' . $output . '</form>';
      }

      return $output;
    }
}





/*            *\
| END OF CLASS |
\*            */





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
        } else {
            return NULL;
        }
    }
}





if ( !function_exists( 'wcb_get_html_component' ) ) {
    function wcb_get_html_component( $componentName = '', $instance = '' ) {
        $componentHTML = '';
        if ( !$componentHTML && $componentName && file_exists( COMPONENTS_DIR . "{$componentName}.php" ) ) {
            $componentHTML = COMPONENTS_DIR . "{$componentName}.php";
        }
        if ( $componentHTML ) {
            include( $componentHTML );
            return $componentMarkup ? $componentMarkup : false;
        }
    }
}





// Not currently in use
if ( !function_exists( 'wcb_reOrder' ) ) {
    /**
     * Public function to set the retrieved posts' order
     *
     * @return string
     */
    function wcb_reOrder( $orderBy ) {
        //TODO: Implement. See #28
        return $orderBy;
    }
}





// Not currently in use
if ( !function_exists( 'wcb_adjustLimit' ) ) {
    /**
     * Public function to set the limit on returned posts
     *
     * @return integer|NULL
     */
    function wcb_adjustLimit( $limit ) {
        //TODO: Implement. See #29
        return $limit;
    }
}





if ( !function_exists( 'wcb_addFilters' ) ) {
    /**
     * Public function to apply all active filters
     *
     * @return objects
     */
    function wcb_addFilters( $query ) {
        global $wcbFilter;
        if ( $query->is_main_query() && ( is_shop() || is_product_category() ) ) {
          $requestedFilters              = wcb_sort_queries( $_GET );
          $params                        = $wcbFilter->get_params();
          $toQuery_meta = $toQuery_tax   = array();

          // Price
          if ( $requestedFilters[ 'price' ] ) {
            $min_price = $params[ 'price' ][ 0 ];
            $max_price = $params[ 'price' ][ 1 ];
            $toQuery_meta[] = array(
              'key' => '_price',
              'value' => array(
                $min_price,
                $max_price
              ),
              'type' => 'NUMERIC',
              'compare' => "BETWEEN"
            );
          }
            // Brand
            if ( $requestedFilters[ 'brand' ] ) {
              $limitBrands = is_array( $params[ 'brand' ] ) ? $params[ 'brand' ] : array( 0 => $params[ 'brand' ] );
              $toQuery_meta[]   = array(
                'key' => 'wcb_brand',
                'value' => $limitBrands,
                'type' => 'NUMERIC',
                'compare' => "IN"
              );
            }

            if ( count( $toQuery_meta ) )
              set_query_var( 'meta_query', $toQuery_meta );

            if ( $requestedFilters[ 'attribute'] ) {
              $toQuery_tax = array();
              foreach($requestedFilters['attribute'] as $key => $value) {
                $toQuery_tax[] = array(
                 'taxonomy' => $key,
                 'field' => 'slug',
                 'terms' => $value,
                  'operator' => 'IN'
                );
                $toQuery_tax[] = array(
                  'taxonomy' => $key,
                  'field' => 'slug',
                  'terms' => $value,
                  'operator' => 'IN',
                  'include_children' => 0
                );
              }
              $toQuery_tax['relation'] = 'OR';
            }

            if ( count( $toQuery_tax ) )
              set_query_var( 'tax_query', $toQuery_tax );

        }
    }
}





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
        }
      }

      return apply_filters( 'wcb_A_get_attributes', $attributes );
    }
}





if ( !function_exists( "wcb_sort_queries" ) ) {
    /**
     * Pull out and return an array of any filter arguments from a request URI or false
     *
     * @return array|boolean
     */
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
                } else {
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
                    if ( strrpos( $strVals, ':' ) > 0) {
                      $arrVals = explode( ':', $strVals);
                      // $arrVals[0] is the attribute name,
                      // the rest are values
                      $workingArr = array();
                      // IDEA: maybe think about a way to put multiple :'s in one [section]
                      // second, are there any _s
                      if ( strrpos( $arrVals[1], '_') > 0) {
                        $workingArr[$arrVals[0]] = explode( '_', $arrVals[1] );
                      } else {
                        $workingArr[$arrVals[0]] = array(
                          0 => $arrVals[1]
                        );
                      }
                      $arrVals = $workingArr;

                      /* now we have $arrVals which look like this:
                      array(
                        pa_speed => array(
                          0 => "1mph",
                          1 => "2mph",
                          [...]
                        )
                      ) */
                    } else {
                      if ( strrpos( $strVals, '_' ) > 0 ) {
                        $arrVals = explode( '_', $strVals );
                      }
                    }
                    // Get the type of filter the values apply to (called $filterType)
                    $start = strrpos( $strGet, $delimeters[ 1 ] );
                    if ( !$start ) {
                        $start      = 0;
                        $filterType = $strGet;
                        $strGet     = '';
                    }
                    $filterType = $filterType ? $filterType : substr( $strGet, $start == 0 ? 0 : $start + 1, strlen( $strGet ) );
                    // Pop the filter type off the GET query
                    $strGet     = $strGet ? substr( $strGet, 0, $start == 0 ? strlen( $strGet ) : $start + 1 ) : $strGet;
                    // If there are multiple values, create a nested array
                    if ( $arrVals ) {
                        array_walk_recursive($arrVals,
                          function(&$v) {
                            preg_replace( '/-/', ' ', $v );
                          }
                        );
                        if (!isset($arrOut[ $filterType ])) {
                          // Put the values into the nested array
                          $arrOut[$filterType] = array();
                          foreach ($arrVals as $key => $value) {
                            $arrOut[$filterType][$key] = $value;
                          }
                        } else {
                          foreach ($arrVals as $key => $value) {
                            $arrOut[$filterType][$key] = $value;
                          }
                        }
                    } else {
                        // Otherwise just add the value to the list, under a key called the filter type
                        if ( strrpos( $strVals, '-' ) > 0 ) {
                            $strVals = preg_replace( '/-/', ' ', $strVals );
                        }
                        $arrOut[ $filterType ] = $strVals;
                    }
                }
            } while ( strlen( $strGet ) > 0 );
        }
        return $arrOut;
    }
}









add_action( 'widgets_init', create_function( '', 'return register_widget("wcb_FilterWidget");' ) );
add_action( 'wp_loaded', create_function('', 'global $wcbFilter; $wcbFilter = new wcb_FilterWidget();') );
add_action( 'pre_get_posts', 'wcb_addFilters' );
