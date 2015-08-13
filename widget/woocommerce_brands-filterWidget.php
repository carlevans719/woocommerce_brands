<?php
define("COMPONENTS_DIR", plugin_dir_path(__FILE__) . "components/");
global $wcbFilter;

class wcb_FilterWidget extends WP_Widget
{
    private $instance_params = array(
    	'post_type' => 'product', 
    	'posts_per_page' => -1, 
    	'product_cat' => -1
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
        
        define('PLUGIN_URI', plugins_url() . '/');
        add_filter('posts_orderby', 'wcb_reOrder');
        add_filter('post_limits', 'wcb_adjustLimit');

        $loop      = new WP_Query($this->instance_params);
        $min_price = $this->instance_params['price'][0] ? $this->instance_params['price'][0] : 999999;
        $max_price = $this->instance_params['price'][1] ? $this->instance_params['price'][1] : 0;
        while ($loop->have_posts()):
            $loop->the_post();
            global $product;
            if (get_post_meta(get_the_ID(), '_price')[0] > $max_price) $max_price = get_post_meta(get_the_ID(), '_price')[0];
		    if ( (0 < get_post_meta(get_the_ID(), '_price')[0]) && (get_post_meta(get_the_ID(), '_price')[0] < $min_price) ) $min_price = get_post_meta(get_the_ID(), '_price')[0];
        endwhile;
        
		$this->instance_params['active_filters'] = $activeFilters = array_keys(wcb_sort_queries($_GET));
        foreach (wcb_sort_queries($_GET) as $key => $value) {
        	$this->instance_params[$key] = $value;
        };

        $this->instance_params['absPrice'][0] = $min_price;
        $this->instance_params['absPrice'][1] = $max_price;
        
        if ( (array_search('price', $activeFilters) === false) || ($this->instance_params['price'][0] > $this->instance_params['price'][1]) ) {
	        $this->instance_params['price'][0] = $min_price;
	        $this->instance_params['price'][1] = $max_price;
        } else {
        	if ($this->instance_params['price'][0] < $min_price) {$this->instance_params['price'][0] = $min_price;}
        	if ($this->instance_params['price'][1] > $max_price) {$this->instance_params['price'][1] = $max_price;}
        }

        wp_reset_query();

	        
        // logit($this->instance_params);
        return $this;
    }
    

    public function get_params()
    {
        return $this->instance_params;
    }
    

    /**
     * Outputs the content of the widget
     *
     * @param array $args
     * @param array $instance
     */
    public function widget($args, $instance)
    {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        } //!empty($instance['title'])
        
        // Price slider
        if ($instance['filterBy-price'] == true) {
            echo self::get_widget_html(array(
                0 => 'priceSlider'
            ));
        } //$instance['filterBy-price'] == true
        
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
        $filterBy      = array(
            'price' => $filterByPrice
        );
		?>
	    <p>
	      <label for="<?php echo $this->get_field_id('title'); ?>">
		      <?php echo 'Title:'; ?>
	      </label>
	      <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
	    </p>
	    <p>
	      <h4>Filter products by:</h4>
	      <fieldset>
	        <label for="<?php echo $this->get_field_id('filterBy-price'); ?>">
		        <?php echo 'Price:'; ?>
	        </label>
	        <input class="widefat" id="<?php echo $this->get_field_id('filterBy-price'); ?>" name="<?php echo $this->get_field_name('filterBy-price'); ?>" type="checkbox" <?php if (esc_attr($filterBy['price']) == true) echo "checked"; ?>>
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
        return $instance;
    }
    
    
    private static function get_widget_html($args)
    {
        $output       = '';
        $module_count = 0;
        
        wp_register_script('wcb_widget-main-js', PLUGIN_URI . 'woocommerce-brands/public/js/wcb_widget-main.js');
        wp_enqueue_script('wcb_widget-main-js');

        $output .= '<form id="wcb_filterForm" class="wcb_form">';

        if (in_array('priceSlider', $args)) {
            wp_register_style('priceSlider-css', PLUGIN_URI . 'woocommerce-brands/public/css/jquery-ui.css');
            wp_enqueue_style('priceSlider-css');
            wp_register_script('priceSlider-js', PLUGIN_URI . 'woocommerce-brands/public/js/jquery-ui.js');
            wp_enqueue_script('priceSlider-js');
            $output .= wcb_get_html_component('slider');
            $module_count++;
        } //in_array('priceSlider', $args)
        
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
            return $componentMarkup;
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


if (!function_exists('wcb_set_price_filter')) {
    /**
     * Public function to set the min & max price of products returned
     *
     * @return object
     */
    function wcb_set_price_filter($filtered_posts)
    {
        //TODO: get some details about the query
        global $wpdb;
        
        if (@$_POST['price']) {
            $matched_products = array( 0 );
            $min              = floatval($_POST['price'][0]);
            $max              = floatval($_POST['price'][1]);
            
            $matched_products_query = apply_filters('woocommerce_price_filter_results', $wpdb->get_results($wpdb->prepare("
                SELECT DISTINCT ID, post_parent, post_type FROM $wpdb->posts
                INNER JOIN $wpdb->postmeta ON ID = post_id
                WHERE post_type IN ( 'product', 'product_variation' ) AND post_status = 'publish' AND meta_key = %s AND meta_value BETWEEN %d AND %d
            ", '_price', $min, $max), OBJECT_K), $min, $max);
            
            if ($matched_products_query) {
                foreach ($matched_products_query as $product) {
                    if ($product->post_type == 'product')
                        $matched_products[] = $product->ID;
                    if ($product->post_parent > 0 && !in_array($product->post_parent, $matched_products))
                        $matched_products[] = $product->post_parent;
                } //$matched_products_query as $product
            } //$matched_products_query
            
            // Filter the id's
            if (sizeof($filtered_posts) == 0) {
                $filtered_posts = $matched_products;
            } //sizeof($filtered_posts) == 0
            else {
                $filtered_posts = array_intersect($filtered_posts, $matched_products);
            }
            
        } //@$_POST['price']
        
        return (array) $filtered_posts;
    }
} //!function_exists('wcb_set_price_filter')


if (!function_exists('wcb_addFilters')) {
    /**
     * Public function to apply all active filters
     *
     * @return objects
     */
    function wcb_addFilters($query)
    {
    	global $wcbFilter;
        if ($query->is_main_query() && !is_admin() ) {
        	if (wcb_sort_queries($_GET)['price']) {
	        	$params = $wcbFilter->get_params();
	        	$min_price = $params['price'][0];
	        	$max_price = $params['price'][1];
	        	set_query_var('meta_query', array(
	        		array(
	        			'key' => '_price',
		        		'value' => array($min_price, $max_price),
		        		'type' => 'NUMERIC',
		        		'compare' => "BETWEEN"
		        		),
	        		));
	        }
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
        
        
        return apply_filters('wcb_A_get_attributes', $attributes);
    }
} //!function_exists('wcb_get_attributes')


if (!function_exists("wcb_sort_queries")) {
    function wcb_sort_queries($getObj)
    {
        if ($getObj && $getObj['wcb_filter']) {
            $strGet = $getObj['wcb_filter'];
            $arrOut = array();
            do {
                $arrVals = array();
                $start   = $end = $strVals = $filterType = '';
                
                //get first and last pos of ]
                $start = strrpos($strGet, '[');
                $end   = strrpos($strGet, ']');
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
                    $start = strrpos($strGet, ']');
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


add_action('widgets_init', create_function('', 'global $wcbFilter; $wcbFilter = new wcb_FilterWidget(); return register_widget("wcb_FilterWidget");'));
add_action('pre_get_posts', 'wcb_addFilters');
