<?php
define("COMPONENTS_DIR", plugin_dir_path(__FILE__) . "components/");
define('PLUGIN_URI', plugins_url() . '/');

global $wcbFilter;

class wcb_FilterWidget extends WP_Widget
{
    private $instance_params = array(
    	'post_type' => 'product', 
    	'posts_per_page' => -1
    );
    

    /**
     * Sets up the widgets name etc
     */
    public function __construct()
    {
        parent::__construct(
        	'wcb-FilterWidget', // Base ID
            'Brands Filter', // Name
            array(
            	'description' => 'Front-end filter widget for the Woocommerce Brands plugin'
	        ) // Args
        );
        
        add_filter('posts_orderby', 'wcb_reOrder');
        add_filter('post_limits', 'wcb_adjustLimit');

	    if (taxonomy_exists('product_cat')) $this->run_the_loop();
        return $this;
    }
    

    public function get_params($key = '')
    {
    	if ($key) return isset($this->instance_params[$key]) ? $this->instance_params[$key] : false;
    	return $this->instance_params;
    }



    public function run_the_loop() {
    	/*
    	!!REMEMBER!!
    	This query has none of the requested filters applied to it!
    	The ONLY limits on this query are those by post_type (if it is 'product')
    	and product_cat
    	*/

    	// Might regret this but cancel if we aren't using $_GET to query for products
    	if (!$_GET || ( !isset($_GET['post_type']) && !isset($_GET['product_cat']) ) ) return false;

		if (isset($_GET['product_cat'])) {
			$toQuery = array(
				array(
					'taxonomy' => 'product_cat',
					'field' => 'slug',
					'terms' => array($_GET['product_cat']),
					'operator' => 'IN'
				),
				array(
					'taxonomy' => 'product_cat',
					'field' => 'slug',
					'terms' => array($_GET['product_cat']),
					'operator' => 'IN',
					'include_children' => 0
				),
				'relation' => 'OR'
			);
			$this->instance_params['tax_query'] = $toQuery;
		};

    	// Declare vars
        $availablePrices = $availableBrands = $activeFilters = array();
        $loop = $min_price = $max_price = $brandId = '';
        // Setup loop
    	$loop = new WP_Query($this->instance_params);
    	// Setup initial prices to sensible min & max (to be replaced by found product's)
		$min_price = 999999;
        $max_price = 0;

        // Run the loop
        while ($loop->have_posts()): $loop->the_post();
            global $product;

            // Get this product's brand id & add it to the list of found brand ids (if one is found)
            $brandId = get_post_meta(get_the_ID(), 'wcb_brand');
            $brandId = is_array($brandId) && count($brandId) ? $brandId[0] : $brandId;  
            if ( ($brandId) && (!array_search($brandId, $availableBrands)) )
            	$availableBrands[$brandId] = isset($availableBrands[$brandId]) ? $availableBrands[$brandId] + 1 : 1;

			// increase / decrease the max/min price (if applicable)
            if (get_post_meta(get_the_ID(), '_price')[0] > $max_price) $max_price = get_post_meta(get_the_ID(), '_price')[0];
		    if ( (0 < get_post_meta(get_the_ID(), '_price')[0]) && (get_post_meta(get_the_ID(), '_price')[0] < $min_price) ) $min_price = get_post_meta(get_the_ID(), '_price')[0];

		// End of loop
        endwhile;

        // Set the available brands to those found 
        $this->instance_params['availableBrands'] = $availableBrands;

        if (wcb_sort_queries($_GET)) {
	        // Set the list of filters that are currently being used (to those in the $_GET)
			$this->instance_params['active_filters'] = $activeFilters = array_keys(wcb_sort_queries($_GET));

			// Push the values of the active filters into $instance_params
	        foreach (wcb_sort_queries($_GET) as $key => $value) {
	        	$this->instance_params[$key] = $value;
	        };
	    };

        // Set the min/max available prices of products found by this query
        $this->instance_params['availablePrices'] = array(
        	0 => $min_price,
        	1 => $max_price
    	);
        
        if ( (array_search('price', $activeFilters) === false) || (!isset($this->instance_params['price'])) || ($this->instance_params['price'][0] > $this->instance_params['price'][1]) ) {
	        // If we aren't filtering by price (or if the requested filter is invalid) then set the price limits to the min/max
	        $this->instance_params['price'] = array(
	        	0 => $min_price,
	        	1 => $max_price
	    	);
        } else {
        	// If we are trying to filter below the lowest found price, bring it within range
        	if ($this->instance_params['price'][0] < $min_price) $this->instance_params['price'][0] = $min_price;
        	// or above the highest found price, bring it within range
        	if ($this->instance_params['price'][1] > $max_price) $this->instance_params['price'][1] = $max_price;
        }

        // Cleanup after ourselves
        wp_reset_query();

        // Force brand ids passed to us VIA $_GET to integers
        if (isset($this->instance_params['brand'])) {
        	for ($i=0; $i < count($this->instance_params['brand']); $i++) { 
        		$this->instance_params['brand'][$i] = intval($this->instance_params['brand'][$i]);
        	}
		}

        // logit($this->instance_params);

		// AAAAND we're done.
    }


    /**
     * Outputs the content of the widget
     *
     * @param array $args
     * @param array $instance
     */
    public function widget($args, $instance)
    {
    	// todo only show widget on the shop pages

        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        } //!empty($instance['title'])
        
        $params = array();
        // Price slider
        if ($instance['filterBy-price']) $params[] = 'priceSlider';
        // Brands
        if ($instance['filterBy-brand']) $params[] = $instance['filterBy-brand-layout'] == 'tiles' ? 'brandsTiles' : 'brandsChecklist';
        echo self::get_widget_html($params);
        
        echo $args['after_widget'];
    }

    
    /**
     * Outputs the options form on admin
     *
     * @param array $instance The widget options
     */
    public function form($instance)
    {
        $title         = (!empty($instance['title']) ? $instance['title'] : 'Product filter');
        $filterByPrice = (!empty($instance['filterBy-price']) ? $instance['filterBy-price'] : false);
        $filterByBrand = (!empty($instance['filterBy-brand']) ? $instance['filterBy-brand'] : false);

        /* todo: change to an array.
        so 
        $instance['filterBy-brand-layout'] = array(
	        'checkboxes' => false,
	        'tiles' => true
        );
        then
        $filterByBrandLayout = array_search('true', $instance['filterBy-brand-layout']) || 'tiles';
        */
    	if (!empty($instance['filterBy-brand-layout'])) {
			$filterByBrandLayout = $instance['filterBy-brand-layout'];
		} else {
			$filterByBrandLayout = 'tiles';
		}


		// Feed values generated above in to one of these arrays
        $filterBy = array(
            'price' => $filterByPrice,
            'brand' => $filterByBrand
        );
        $options = array(
        	'brandLayout' => $filterByBrandLayout
        );

        wp_register_script('wcb_widget-admin-widgets-js', PLUGIN_URI . 'woocommerce-brands/admin/js/wcb_widget-admin-widgets.js');
        wp_enqueue_script('wcb_widget-admin-widgets-js');

		?>
	    <p>
	      <label for="<?php echo $this->get_field_id('title'); ?>">
		      <?php echo 'Title:'; ?>
	      </label>
	      <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
	    </p>
	    <p>
	      <h4>Filter products by:</h4>

	      <?php /* PRICE FILTER */ ?>
	      <fieldset>
	        <label for="<?php echo $this->get_field_id('filterBy-price'); ?>">
		        <?php echo 'Price:'; ?>
	        </label>
	        <input class="widefat" id="<?php echo $this->get_field_id('filterBy-price'); ?>" name="<?php echo $this->get_field_name('filterBy-price'); ?>" type="checkbox" <?php if (esc_attr($filterBy['price']) == true) echo "checked"; ?>>
	      </fieldset>



	      <?php /* BRAND FILTER */ ?>
	      <fieldset>
		      <label for="<?php echo $this->get_field_id('filterBy-brand'); ?>">
				<?php echo 'Brand:'; ?>
		      </label>
		      <input class="widefat brandCheck" id="<?php echo $this->get_field_id('filterBy-brand'); ?>" name="<?php echo $this->get_field_name('filterBy-brand'); ?>" type="checkbox"<?php if (esc_attr($filterBy['brand'])) echo " checked"; ?>>

		      <div class="brandOptions-container"<?php if (!esc_attr($filterBy['brand'])) echo ' style="display:none;"' ?>>			
		      	<?php if ($options['brandLayout'] == 'checkboxes') {
		      		$tilesChecked = '';
		      		$checksChecked = 'checked="checked"';
		      		$layoutValue = 'checkboxes';
		      	} else {
		      		$tilesChecked = 'checked="checked"';
		      		$checksChecked = '';
		      		$layoutValue = 'tiles';
		      	}; ?>

		      		<input class="widefat brandLayout" type="radio" name="filterBy-brand-layout" id="filterBy-brand-layout--tiles" value="tiles" <?php echo $tilesChecked; ?>><span>Thumbnail tiles</span>
					<input class="widefat brandLayout" type="radio" name="filterBy-brand-layout" id="filterBy-brand-layout--checkboxes" value="checkboxes" <?php echo $checksChecked; ?>><span>List of checkboxes</span>
			      	<input class="brandLayoutVal" type="hidden" name="<?php echo $this->get_field_name('filterBy-brand-layout'); ?>" id="<?php echo $this->get_field_id('filterBy-brand-layout'); ?>" data-value="<?php echo $layoutValue; ?>">
			  </div>
	      </fieldset>
	    </p>
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
    public function update($new_instance, $old_instance)
    {
        $instance                   = array();
        $instance['title']          = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['filterBy-price'] = (!empty($new_instance['filterBy-price'])) ? (bool) strip_tags($new_instance['filterBy-price']) : false;
        $instance['filterBy-brand'] = (!empty($new_instance['filterBy-brand'])) ? (bool) strip_tags($new_instance['filterBy-brand']) : false;
        $instance['filterBy-brand-layout'] = (!empty($new_instance['filterBy-brand-layout'])) ? strip_tags($new_instance['filterBy-brand-layout']) : false;
        return $instance;
    }
    
    
    private static function get_widget_html($args)
    {
    	/*
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
        
        wp_register_script('wcb_widget-main-js', PLUGIN_URI . 'woocommerce-brands/public/js/wcb_widget-main.js');
        wp_enqueue_script('wcb_widget-main-js');
        wp_register_style('frontendMain-css', PLUGIN_URI . 'woocommerce-brands/public/css/jquery-ui.css');
        wp_enqueue_style('frontendMain-css');
        wp_register_script('frontendMain-js', PLUGIN_URI . 'woocommerce-brands/public/js/jquery-ui.js');
        wp_enqueue_script('frontendMain-js');

        $output .= '<form id="wcb_filterForm" class="wcb_form">';

        if (is_array($args)) {

	        if (in_array('priceSlider', $args)) {
	            $output .= wcb_get_html_component('slider');
	            if (wcb_get_html_component('slider')) $module_count++;
	        } //in_array('priceSlider', $args)

			if (in_array('brandsTiles', $args)) {
	            $output .= wcb_get_html_component('tiles');
	            if (wcb_get_html_component('tiles')) $module_count++;
	        }

	        if (in_array('brandsChecklist', $args)) {
	            $output .= wcb_get_html_component('checkboxes');
	            if (wcb_get_html_component('checkboxes')) $module_count++;
	        }

        }

        if ($module_count > 0) {
            $output .= '<button id="wcb_form_update_btn">Update</button>';
        } //$module_count > 0
        $output .= '</form>';

        return $output;
    }
      
}

if (!function_exists('wcb_get_woocommerce_version')) {
    /**
     * Public function to get WooCommerce version
     *
     * @return float|NULL
     */
    function wcb_get_woocommerce_version()
    {
        if (!function_exists('get_plugins'))
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        
        $plugin_folder = get_plugins('/' . 'woocommerce');
        $plugin_file   = 'woocommerce.php';
        
        if (isset($plugin_folder[$plugin_file]['Version'])) {
            return $plugin_folder[$plugin_file]['Version'];
        } //isset($plugin_folder[$plugin_file]['Version'])
        else {
            return NULL;
        }
    }
} //!function_exists('wcb_get_woocommerce_version')


if (!function_exists('wcb_get_html_component')) {
    function wcb_get_html_component($componentName = '')
    {
        $componentHTML = '';
        if (!$componentHTML && $componentName && file_exists(COMPONENTS_DIR . "{$componentName}.php")) {
            $componentHTML = COMPONENTS_DIR . "{$componentName}.php";
        } //!$componentHTML && $componentName && file_exists(COMPONENTS_DIR . "{$componentName}.php")

        if ($componentHTML) {
            include($componentHTML);
            return $componentMarkup ? $componentMarkup : false;
        } //$componentHTML
    }
} //!function_exists('wcb_get_html_component')


if (!function_exists('wcb_reOrder')) {
    /**
     * Public function to set the retrieved posts' order
     *
     * @return string
     */
    function wcb_reOrder($orderBy)
    {
        //TODO: return either 'post_title ASC' or other
        return $orderBy;
    }
} //!function_exists('wcb_reOrder')


if (!function_exists('wcb_adjustLimit')) {
    /**
     * Public function to set the limit on returned posts
     *
     * @return integer|NULL
     */
    function wcb_adjustLimit($limit)
    {
        //TODO: return either a blank string or whatever
        return $limit;
    }
} //!function_exists('wcb_adjustLimit')


if (!function_exists('wcb_addFilters')) {
    /**
     * Public function to apply all active filters
     *
     * @return objects
     */
    function wcb_addFilters($query)
    {
    	global $wcbFilter;
    	// todo only add filters on the shop pages
        if ($query->is_main_query() && !is_admin() ) {
        	$requestedFilters = wcb_sort_queries($_GET);
        	$params = $wcbFilter->get_params();
        	$toQuery = array();

        	// Price
        	if ($requestedFilters['price']) {
	        	$min_price = $params['price'][0];
	        	$max_price = $params['price'][1];
	        	$toQuery[] = array(
			        			'key' => '_price',
				        		'value' => array($min_price, $max_price),
				        		'type' => 'NUMERIC',
				        		'compare' => "BETWEEN"
				        	);
	        }
	        // Brand
	        if ($requestedFilters['brand']) {
	        	$limitBrands = is_array($params['brand']) ? $params['brand'] : array(0 => $params['brand']);
	        	$toQuery[] = array(
			        			'key' => 'wcb_brand',
				        		'value' => $limitBrands,
				        		'type' => 'NUMERIC',
				        		'compare' => "IN"
			        		);
	        }

			if (count($toQuery)) set_query_var('meta_query', $toQuery);

        } //$query->is_main_query()
    }
} //!function_exists('wcb_addFilters')


if (!function_exists('wcb_get_attributes')) {
    /**
     * Get all possible woocommerce attribute taxonomies
     *
     * @return mixed|void
     */
    function wcb_get_attributes()
    {
        $attribute_taxonomies = wc_get_attribute_taxonomies();
        $attributes           = array();
        
        if ($attribute_taxonomies) {
            foreach ($attribute_taxonomies as $tax) {
                $attributes[wc_attribute_taxonomy_name($tax->attribute_name)] = $tax->attribute_label;
            } //$attribute_taxonomies as $tax
        } //$attribute_taxonomies
        
        logit($attributes);
        return apply_filters('wcb_A_get_attributes', $attributes);
    }
} //!function_exists('wcb_get_attributes')


if (!function_exists("wcb_sort_queries")) {
    function wcb_sort_queries($getObj)
    {
    	$arrOut = false;
        if ($getObj && (isset($getObj['wcb_filter'])) ) {
            $strGet = $getObj['wcb_filter'];
            $arrOut = array();
            do {
                $arrVals = $delimeters = array();
                $start   = $end = $strVals = $filterType = '';
                
                if (strrpos($strGet, '[') === false) {
                	$delimeters[0] = '%5B';
                	$delimeters[1] = '%5D';
                } else {
                	$delimeters[0] = '[';
                	$delimeters[1] = ']';
                }

                //get first and last pos of ]
                $start = strrpos($strGet, $delimeters[0]);
                $end   = strrpos($strGet, $delimeters[1]);
                if ($start && $end && ($start < $end)) {
                    // Get the values
                    $strVals = substr($strGet, $start, $end);
                    $strVals = substr($strVals, 1, strlen($strVals) - 2);
                    
                    // Pop the values off the GET query
                    $strGet = substr($strGet, 0, $start);
                    // Deal with multiple values (if applicable)
                    if (strrpos($strVals, '_') > 0) {
                        $arrVals = explode('_', $strVals);
                    } //strrpos($strVals, '_') > 0
                    // Get the type of filter the values apply to (called $filterType)
                    $start = strrpos($strGet, $delimeters[1]);
                    if (!$start) {
                        $start      = 0;
                        $filterType = $strGet;
                        $strGet     = '';
                    } //!$start
                    $filterType = $filterType ? $filterType : substr($strGet, $start == 0 ? 0 : $start + 1, strlen($strGet));
                    // Pop the filter type off the GET query
                    $strGet     = $strGet ? substr($strGet, 0, $start == 0 ? strlen($strGet) : $start + 1) : $strGet;
                    // If there are multiple values, create a nested array
                    if ($arrVals) {
                        $arrOut[$filterType] = array();
                        // Put the values into the nested array
                        $arrVals             = preg_replace('/-/', ' ', $arrVals);
                        for ($i = 0; $i < count($arrVals); $i++) {
                            $arrOut[$filterType][$i] = $arrVals[$i];
                        } //$i = 0; $i < count($arrVals); $i++
                        // Otherwise just add the value to the list, under a key called the filter type
                    } //$arrVals
                    else {
                        if (strrpos($strVals, '-') > 0) {
                            $strVals = preg_replace('/-/', ' ', $strVals);
                        } //strrpos($strVals, '-') > 0
                        $arrOut[$filterType] = $strVals;
                    }
                } //$start && $end && ($start < $end)
            } while (strlen($strGet) > 0);
        } //$getObj && $getObj['wcb_filter']
        return $arrOut;
    }
} //!function_exists("wcb_sort_queries")


add_action('widgets_init', create_function('', 'return register_widget("wcb_FilterWidget");'));
add_action( 'registered_taxonomy', 'runWidget' );
add_action('pre_get_posts', 'wcb_addFilters');
function runWidget($taxonomy) {
	if ($taxonomy === 'product_cat') {
		global $wcbFilter; 
		$wcbFilter = new wcb_FilterWidget(); 
	}
}