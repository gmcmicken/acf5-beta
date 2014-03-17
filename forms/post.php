<?php 

class acf_controller_post {
	
	var $post_id	= 0,
		$typenow	= '',
		$style		= '';
	
	
	/*
	*  Constructor
	*
	*  This function will construct all the neccessary actions and filters
	*
	*  @type	function
	*  @date	23/06/12
	*  @since	3.1.8
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function __construct() {
	
		// actions
		add_action( 'admin_enqueue_scripts',				array($this, 'admin_enqueue_scripts') );
		add_action( 'save_post', 							array($this, 'save_post'), 10, 1 );
		
		
		// ajax
		add_action( 'wp_ajax_acf/post/get_field_groups',	array($this, 'get_field_groups') );
	}
	
	
	/*
	*  validate_page
	*
	*  This function will check if the current page is for a post/page edit form
	*
	*  @type	function
	*  @date	23/06/12
	*  @since	3.1.8
	*
	*  @param	n/a
	*  @return	(boolean)
	*/
	
	function validate_page()
	{
		// global
		global $post, $pagenow, $typenow;
		
		
		// vars
		$return = false;
		
		
		// validate page
		if( in_array($pagenow, array('post.php', 'post-new.php')) ) {
			
			$return = true;
			
		}
		
		
		// update vars
		if( !empty($post) ) {
		
			$this->post_id = $post->ID;
			$this->typenow = $typenow;
			
		}
		
		
		// validate post type
		if( in_array($typenow, array('acf-field-group', 'attachment')) ) {
			
			return false;
			
		}
		
		
		// validate page (Shopp)
		if( $pagenow == "admin.php" && isset( $_GET['page'] ) && $_GET['page'] == "shopp-products" && isset( $_GET['id'] ) )
		{
			$return = true;
			
			$this->post_id = absint( $_GET['id'] );
			$this->typenow = 'shopp_product';
		}
				
		
		// return
		return $return;
	}
	
	
	/*
	*  admin_enqueue_scripts
	*
	*  This action is run after post query but before any admin script / head actions. 
	*  It is a good place to register all actions.
	*
	*  @type	action (admin_enqueue_scripts)
	*  @date	26/01/13
	*  @since	3.6.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function admin_enqueue_scripts()
	{
		// validate page
		if( ! $this->validate_page() )
		{
			return;
		}

		
		// load acf scripts
		acf_enqueue_scripts();
		
		
		// actions
		add_action( 'acf/input/admin_head',		array($this,'admin_head') );
		add_action( 'acf/input/admin_footer',	array($this,'admin_footer') );
	}
	
	
	/*
	*  admin_head
	*
	*  This action will find and add field groups to the current edit page
	*
	*  @type	action (admin_head)
	*  @date	23/06/12
	*  @since	3.1.8
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function admin_head() {
		
		// vars
		$style_found = false;
		
		
		// get field groups
		$field_groups = acf_get_field_groups();
		
		
		// add meta boxes
		if( !empty($field_groups) )
		{
			foreach( $field_groups as $i => $field_group )
			{
				// vars
				$id = "acf-{$field_group['key']}";
				$title = $field_group['title'];
				$context = $field_group['position'];
				$priority = 'high';
				$args = array( 
					'field_group'	=> $field_group,
					'visibility'	=> false
				);
				
				
				// tweaks to vars
				if( $context == 'side' )
				{
					$priority = 'core';
				}
				
				
				// filter for 3rd party customization
				$priority = apply_filters('acf/input/meta_box_priority', $priority, $field_group);
				
				
				// visibility
				$args['visibility'] = acf_get_field_group_visibility( $field_group, array(
					'post_id'	=> $this->post_id, 
					'post_type'	=> $this->typenow
				));
				
				
				// add meta box
				add_meta_box( $id, $title, array($this, 'render_meta_box'), $this->typenow, $context, $priority, $args );
				
				
				// update style
				if( !$style_found && $args['visibility'] ) {
					
					$style_found = true;
					
					$this->style = acf_get_field_group_style( $field_group );
				}
				
			}
			// foreach($acfs as $acf)
		}
		// if($acfs)
		
		
		// Allow 'acf_after_title' metabox position
		add_action( 'edit_form_after_title', array($this, 'edit_form_after_title') );
	}
	
	
	/*
	*  edit_form_after_title
	*
	*  This action will allow ACF to render metaboxes after the title
	*
	*  @type	action
	*  @date	17/08/13
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function edit_form_after_title() {
		
		// globals
		global $post, $wp_meta_boxes;
		
		
		// render post data
		acf_form_data(array( 
			'post_id'	=> $this->post_id, 
			'nonce'		=> 'post',
			'ajax'		=> 1
		));
		
		
		// render
		do_meta_boxes( get_current_screen(), 'acf_after_title', $post);
		
		
		// clean up
		unset( $wp_meta_boxes['post']['acf_after_title'] );

	}
	
	
	/*
	*  render_meta_box
	*
	*  description
	*
	*  @type	function
	*  @date	20/10/13
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function render_meta_box( $post, $args ) {
		
		// extract args
		extract( $args ); // all variables from the add_meta_box function
		extract( $args ); // all variables from the args argument
		
		
		// classes
		$class = 'acf-postbox ' . $field_group['style'];
		$toggle_class = 'acf-postbox-toggle';
		
		
		// render fields, or render a replace-me div
		if( $visibility )
		{
			// load fields
			$fields = acf_get_fields( $field_group );
			
			
			// render
			if( $field_group['label_placement'] == 'left' )
			{
				?>
				<table class="acf-table">
					<tbody>
						<?php acf_render_fields( $this->post_id, $fields, 'tr', $field_group['instruction_placement'] ); ?>
					</tbody>
				</table>
				<?php
			}
			else
			{
				acf_render_fields( $this->post_id, $fields, 'div', $field_group['instruction_placement'] );
			}
			
			
		}
		else
		{
			// update classes
			$class .= ' acf-hidden';
			$toggle_class .= ' acf-hidden';
			
			echo '<div class="acf-replace-with-fields"><div class="acf-loading"></div></div>';
		}
		
		
		// inline script
		echo '<div class="acf-hidden">';
			?>
			<script type="text/javascript">
			(function($) {
				
				$('#<?php echo $id; ?>').addClass('<?php echo $class; ?>').removeClass('hide-if-js');
				$('#adv-settings label[for="<?php echo $id; ?>-hide"]').addClass('<?php echo $toggle_class; ?>');
				
			})(jQuery);	
			</script>
			<?php
		echo '</div>';
		
	}
	
	
	/*
	*  admin_footer
	*
	*  description
	*
	*  @type	function
	*  @date	21/10/13
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function admin_footer(){
		
		// get style of first field group
		echo '<style type="text/css" id="acf-style">' . $this->style . '</style>';
		
	}
	
	
	/*
	*  get_field_groups
	*
	*  This function will return all the JSON data needed to render new metaboxes
	*
	*  @type	function
	*  @date	21/10/13
	*  @since	5.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/

	function get_field_groups() {
		
		// vars
		$options = acf_parse_args($_POST, array(
			'nonce'		=> '',
			'post_id'	=> 0
		));
		
		
		$r = array();
		$nonce = acf_extract_var( $options, 'nonce' );
		
		
		// verify nonce
		if( ! wp_verify_nonce($nonce, 'acf_nonce') )
		{
			die;
		}
		
		
		// get field groups
		$field_groups = acf_get_field_groups( $options );
		
		
		// loop through field groups and build $r
		if( !empty($field_groups) )
		{
			foreach( $field_groups as $field_group )
			{
				// vars
				$class = 'acf-postbox ' . $field_group['style'];
				
				
				// load fields
				$fields = acf_get_fields( $field_group );


				// get field HTML
				ob_start();
				
				
				// render
				if( $field_group['label_placement'] == 'left' )
				{
					?>
					<table class="acf-table">
						<tbody>
							<?php acf_render_fields( $options['post_id'], $fields, 'tr', $field_group['instruction_placement'] ); ?>
						</tbody>
					</table>
					<?php
				}
				else
				{
					acf_render_fields( $options['post_id'], $fields, 'div', $field_group['instruction_placement'] );
				}
				
				
				$html = ob_get_clean();
				
				
				// get style
				$style = acf_get_field_group_style( $field_group );
				
				
				// append to $r
				$r[] = array(
					'ID'	=> $field_group['ID'],
					'key'	=> $field_group['key'],
					'title'	=> $field_group['title'],
					'html'	=> $html,
					'style' => $style,
					'class'	=> $class
				);
			}
		}
		
		
		// return
		echo json_encode( $r );
		die;
		
	}
	
	
	/*
	*  save_post
	*
	*  This function will validate and save the $_POST data
	*
	*  @type	function
	*  @date	23/06/12
	*  @since	1.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function save_post( $post_id )
	{	
		
		// do not save if this is an auto save routine
		if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
		{
			return $post_id;
		}
		
		
		// verify and remove nonce
		if( !acf_verify_nonce('post', $post_id) )
		{
			return $post_id;
		}
		
		
		// validate and save
		if( get_post_status($post_id) == 'publish' )
		{
			if( acf_validate_save_post(true) )
			{
				acf_save_post( $post_id );
			}
		}
		else
		{
			acf_save_post( $post_id );
		}
		
		
		// return
		return $post_id;
		
        
	}
	
			
}

new acf_controller_post();

?>