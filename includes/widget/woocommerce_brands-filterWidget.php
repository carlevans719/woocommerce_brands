<?php

class wcb_FilterWidget extends WP_Widget {


  /**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
    parent::__construct(
      'wcb-FilterWidget', // Base ID
      'Brands Filter', // Name
      array( 'description' => 'Front-end filter widget for the Woocommerce Brands plugin', ) // Args
    );

		define( 'PLUGIN_URI', plugins_url().'/' );
		add_filter('posts_orderby', 'wcb_reOrder' );
		add_filter('post_limits', 'wcb_adjustLimit' );

	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
    echo $args['before_widget'];
    if ( ! empty( $instance['title'] ) ) {
      echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
    };

		// Price slider
		if ($instance['filterBy-price'] == true) {
			echo self::get_widget_html(array(0=>'priceSlider'));
		}

		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form($instance) {
    $title = (!empty($instance['title']) ? $instance['title'] : 'Product filter');
		$filterByPrice = (!empty($instance['filterBy-price']) ? $instance['filterBy-price'] : false);
    $filterBy = array('price' => $filterByPrice);
    ?>
    <p>
      <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:' ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
    </p>
    <p>
      <h4>Filter products by:</h4>
      <fieldset>
        <label for="<?php echo $this->get_field_id('filterBy-price'); ?>"><?php _e('Price:'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('filterBy-price'); ?>" name="<?php echo $this->get_field_name('filterBy-price'); ?>" type="checkbox" <?php if ( esc_attr($filterBy['price']) == true ) echo "checked"; ?>>
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
	public function update($new_instance, $old_instance) {
    $instance = array();
		$instance['title'] = ( !empty($new_instance['title']) ) ? strip_tags($new_instance['title']) : '';
    $instance['filterBy-price'] = ( !empty($new_instance['filterBy-price']) ) ? (bool) strip_tags($new_instance['filterBy-price']) : false;
		return $instance;
	}





	private static function get_widget_html($args) {
	  $output = '';
		$module_count = 0;

		wp_register_script( 'wcb_widget-main-js', PLUGIN_URI .'woocommerce-brands/public/js/wcb_widget-main.js' );
		wp_enqueue_script( 'wcb_widget-main-js' );

		if ( in_array( 'priceSlider', $args ) ) {
			wp_register_style( 'priceSlider-css', PLUGIN_URI .'woocommerce-brands/public/css/jquery-ui.css' );
			wp_enqueue_style( 'priceSlider-css' );
			wp_register_script( 'priceSlider-js', PLUGIN_URI .'woocommerce-brands/public/js/jquery-ui.js' );
			wp_enqueue_script( 'priceSlider-js' );
		  $output .= self::get_slider_html();
			$module_count ++;
	  }

		if ($module_count > 0) {
			$output .= self::get_submit_button();
		}
		return $output;
	}

	private static function get_slider_html() {
		$min_price = '0';
		$max_price = '500';

	  $js = '<script>
						$ = jQuery;
					  $(function() {
					    $( "#slider-range" ).slider({
					      range: true,
					      min: '. $min_price .',
					      max: '. $max_price .',
					      values: [ '. $min_price .', '. $max_price .' ],
					      slide: function( event, ui ) {
									$( "#wcb_price_min" ).val( $( "#slider-range" ).slider( "values", 0 ) );
									$($( "span.ui-slider-handle.ui-state-default.ui-corner-all")[0]).attr("data-content", "£" + $( "#slider-range" ).slider( "values", 0 ) );
									$( "#wcb_price_max" ).val( $( "#slider-range" ).slider( "values", 1 ) );
									$($( "span.ui-slider-handle.ui-state-default.ui-corner-all")[1]).attr("data-content", "£" + $( "#slider-range" ).slider( "values", 1 ) );
					      }
					    });
					    $( "#wcb_price_min" ).val( $( "#slider-range" ).slider( "values", 0 ) );
							$( "#wcb_price_max" ).val( $( "#slider-range" ).slider( "values", 1 ) );
							$($( "span.ui-slider-handle.ui-state-default.ui-corner-all")[0]).attr("data-content", "£" + $( "#slider-range" ).slider( "values", 0 ) );
							$($( "span.ui-slider-handle.ui-state-default.ui-corner-all")[1]).attr("data-content", "£" + $( "#slider-range" ).slider( "values", 1 ) );
					  });
			  </script>';

		$html = '<input type="hidden" id="wcb_price_min">
						<input type="hidden" id="wcb_price_max">
							<div id="slider-range"></div>';

		$output = $js . $html;
	  return $output;
	}

	private static function get_submit_button() {
		$output = '<button onclick="wcb_update_filter()">Update</button>';
		return $output;
	}

}
add_action('widgets_init',
 create_function('', 'return register_widget("wcb_FilterWidget");')
);
