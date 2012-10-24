<?php
/**
 * @package Simpul
 */
/*
Plugin Name: Simpul Blogs by Esotech
Plugin URI: http://www.esotech.org
Description: This plugin is designed to access a blog category feed and display it in a Wordpress Widget with featured image and standard info.
Version: 1.2.1
Author: Alexander Conroy
Author URI: http://www.esotech.org/people/alexander-conroy/
License: This code is released under the GPL licence version 3 or later, available here http://www.gnu.org/licenses/gpl.txt
*/


class SimpulBlogs extends WP_Widget 
{
	# The ID of the twitter feed we are trying to read	
	public function __construct()
	{
		$widget_ops = array('classname' => 'simpul-blogs', 
							'description' => 'A Simpul Blog Widget' );
							
		parent::__construct('simpul_blogs', // Base ID
							'Blogs', // Name
							$widget_ops // Args  
							);
							
	}
	public function widget( $args, $instance )
	{
		extract($args, EXTR_SKIP);
		
		$simpulthumb = plugin_dir_url( __FILE__ ) . 'includes/simpulthumb.php';
		
		$categories			= unserialize($instance['categories']);
		$taxonomies			= unserialize($instance['taxonomies']);
		$terms				= unserialize($instance['terms']);
		$post_types			= unserialize($instance['post_types']);	
		
		echo $before_widget;
		
		$query['post_status'] = 'publish';
		
		if($instance['post_orderby']) $query['orderby'] = $instance['post_orderby'];
		if($instance['post_order']) $query['order'] = $instance['post_order'];
		
		if ( !empty( $instance['title']) ) { echo $before_title . $instance['title']. $after_title; };
		
		if($instance['number']):
			$query['posts_per_page'] = $instance['number'];
		else:
			$query['posts_per_page'] = 5;
		endif;
		
		if($taxonomies && $terms):
				$query['tax_query']['relation'] = "OR";
			foreach($taxonomies as $taxonomy):
				$query['tax_query'][] = array('taxonomy' => $taxonomy,
												'field' => 'slug',
												'terms' => $terms,
												'operator' => 'IN');
			endforeach;
		endif;

		if($post_types):
			$query['post_type'] = $post_types;
		endif;
		
		if($categories):
			if( !in_array("post", (array)$query['post_type'] ) ) $query['post_type'][] = "post";
			$query['category'] = implode( ",", (array) $categories );
		endif;
		/*
		echo "<pre>";
		print_r($query); // For Debugging
		echo "</pre>";
		*/
		$simpul_query = new WP_Query($query);
	
		if($instance['class']):
			$ul_class = ' class="' . $instance['class'] . '"';
		endif;
		
		echo '<ul' . $ul_class . '>';
		
		while($simpul_query->have_posts()): $simpul_query->the_post();
			//$do_not_duplicate = $post->ID;
			$the_link = get_permalink();

			echo '<li>';
			//The Image
			if($instance['image']):
				if($instance['image_class']) $image_args['class'] = $instance['image_class'];
					
				if( has_post_thumbnail($post->ID) ):
					if($instance['image_width'] || $instance['image_height']):
						
						$thumb = get_the_post_thumbnail($post->ID, 'full', $image_args);
						
						if( $instance['image_quality'] >= 1 && $instance['image_quality'] <= 100): 
							$quality = $instance['image_quality']; 
						else:
							$quality = 100; 
						endif;
						
						$size = array($instance['image_width'], $instance['image_height']);
						
						$simpulthumb_link = $simpulthumb . '?q=' . $quality . '&w=' . $instance['image_width'] . '&h=' . $instance['image_height'] . '&src=';
						
						$size_pattern = '/(width|height)="[0-9]*"/i';
						
						$thumb = str_replace('src="', 'src="' . $simpulthumb_link, $thumb );
						$thumb = preg_replace('/width="[0-9]*"/i', 'width="' . $size[0] . '"', $thumb );
						$thumb = preg_replace('/height="[0-9]*"/i', 'height="' . $size[1] . '"', $thumb );
						
					else:
						$size = 'thumbnail';
						$thumb = get_the_post_thumbnail($post->ID, $size, $image_args);
					endif;
					
					if($instance['image_link']):
						$thumb = '<a href="' . $the_link . '">' . $thumb . '</a>';
					endif;
					
					if($instance['image_foreground']):
						$thumb = '<div class="simpul-blogs-image" style="position: relative;">
									<div style="position: absolute;" class="simpul-blogs-image-foreground"></div>
									' . $thumb . 
								'</div>';
					else:
						$thumb = '<div class="simpul-blogs-image">' . $thumb . '</div>';
					endif;
					
					//The Image Caption
					if($instance['image_caption']):
						$the_caption = self::the_post_thumbnail_caption($post->ID);
						//Link the Image Caption
						if($instance['image_caption_link']):		
							$the_caption = '<a href="' . $the_link . '">' . $the_caption . '</a>';
						endif;
						//Add View More to Image Caption
						if($instance['image_caption_view_more']):
							//Customize the View More Text
							if($instance['image_caption_view_more_text']):
								$the_caption = $the_caption . ' <a href="' . $the_link . '" class="simpul-blogs-image-caption-view-more">' . $instance['image_caption_view_more_text'] . '</a>';
							else:
								$the_caption = $the_caption . ' <a href="' . $the_link . '" class="simpul-blogs-image-caption-view-more">view more</a>';
							endif;
						endif;
						$caption 	= '<div class="simpul-blogs-image-caption">' . $the_caption . '</div>';
						//Attach the Thumb to the Caption as 1 Unit
						$thumb		 = $thumb . $caption;
					endif;
				else:
					$thumb = "";
				endif;	
			else:
				$thumb = "";
			endif;
			
			if($instance['image_location'] == "beginning" || !$instance['image_location']):
				echo $thumb;
			endif;
			//END The Image
			
			//The Title
			if($instance['post_title']):
				if($instance['post_title_element']):
					$post_title_element = $instance['post_title_element'];
				else:
					$post_title_element = "h4";
				endif;
				
				if($instance['post_title_link']):
					echo '<a href="' . $the_link . '">' . the_title('<' . $post_title_element . '>','</' . $post_title_element . '>',FALSE) . '</a>';
				else:
					echo the_title('<' . $post_title_element . '>','</' . $post_title_element . '>',FALSE);
				endif;
			endif;
			
			if($instance['image_location'] == "after_title") echo $thumb; 
			//The Date
			
			if($instance['date']):
				if($instance['date_format']):
					$date_format = $instance['date_format'];
				else:
					$date_format = "Y-m-d H:i:s";
				endif;
				echo "<div class='simpul-blogs-date'>" . get_the_date($date_format) . "</div>";
			endif;
			
			if($instance['image_location'] == "after_date") echo $thumb; 
			//The Author
			if($instance['author']):
				echo "<div class='simpul-blogs-author'>" . get_the_author() . "</div>";
			endif;
			
			if($instance['image_location'] == "after_author") echo $thumb; 
			//The Snippet
			if($instance['snippet']):
				//Decide Excerpt or Content
				if($post->post_excerpt):
					$content = get_the_excerpt();
				else:
					$content = get_the_content();
				endif;
				//Strip Tags
				$content = strip_tags($content);
				//The Length
				if($instance['snippet_length'] > 0):
					if($instance['snippet_truncation']):
						$truncate = strpos($content,' ', $instance['snippet_length']);
					else:
						$truncate = $instance['snippet_length'];
					endif;
					$content = substr($content, 0, $truncate); 
				endif;
				
				if($instance['image_location'] == "after_snippet") $content = $content .  $thumb; 
				//The Ellipses
				if($instance['ellipses']):
					$content = $content . '<span class="simpul-blogs-ellipses">...</span>';
				endif;
				
				if($instance['image_location'] == "after_ellipses") $content = $content .  $thumb; 
				//The Read More
				if($instance['read_more']):
					if($instance['read_more_text']):
						$content = $content . '<a href="' . $the_link . '" class="simpul-blogs-read-more">' . $instance['read_more_text'] . '</a>';
					else:
						$content = $content . '<a href="' . $the_link . '" class="simpul-blogs-read-more">read more</a>';
					endif;
				endif;
				
				if($instance['image_location'] == "after_read_more") $content = $content .  $thumb; 
				
				echo "<div class='simpul-blogs-snippet'>" . $content . "</div>";
			endif;
			
			if($instance['comments']):
				//get_comments( array();
			endif;
			
			if($instance['image_location'] == "after_comments") echo $thumb; 
			
			if($instance['image_location'] == "end") echo $thumb; 
			
			echo '</li>';
			
		endwhile;
		wp_reset_postdata();
		echo '</ul>';
		echo $widget;
		
		echo $after_widget;
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
		$instance = $old_instance;
		$instance['title'] 							= strip_tags($new_instance['title']);
		$instance['class']							= strip_tags($new_instance['class']);
		$instance['number'] 						= strip_tags($new_instance['number']);
		$instance['post_orderby'] 					= strip_tags($new_instance['post_orderby']);
		$instance['post_order'] 					= strip_tags($new_instance['post_order']);
		$instance['taxonomies']						= serialize( $new_instance['taxonomies']);
		$instance['terms']							= serialize( $new_instance['terms']);
		$instance['post_types']						= serialize( $new_instance['post_types']);
		$instance['post_title'] 					= strip_tags($new_instance['post_title']);
		$instance['post_title_link']				= strip_tags($new_instance['post_title_link']);
		$instance['post_title_element']				= strip_tags($new_instance['post_title_element']);
		$instance['author']							= strip_tags($new_instance['author']);
		$instance['date']							= strip_tags($new_instance['date']);
		$instance['date_format']					= strip_tags($new_instance['date_format']);
		$instance['comments']						= strip_tags($new_instance['comments']);
		$instance['snippet']						= strip_tags($new_instance['snippet']);
		$instance['snippet_length']					= strip_tags($new_instance['snippet_length']);
		$instance['snippet_truncation']				= strip_tags($new_instance['snippet_truncation']);
		$instance['ellipses']						= strip_tags($new_instance['ellipses']);
		$instance['read_more']						= strip_tags($new_instance['read_more']);
		$instance['read_more_text']					= strip_tags($new_instance['read_more_text']);
		$instance['image']							= strip_tags($new_instance['image']);
		$instance['image_link']						= strip_tags($new_instance['image_link']);
		$instance['image_foreground']				= strip_tags($new_instance['image_foreground']);
		$instance['image_location']					= strip_tags($new_instance['image_location']);
		$instance['image_caption']					= strip_tags($new_instance['image_caption']);
		$instance['image_caption_link']				= strip_tags($new_instance['image_caption_link']);
		$instance['image_caption_view_more']		= strip_tags($new_instance['image_caption_view_more']);
		$instance['image_caption_view_more_text']	= strip_tags($new_instance['image_caption_view_more_text']);	
		$instance['image_class']					= strip_tags($new_instance['image_class']);
		$instance['image_width']					= strip_tags($new_instance['image_width']);
		$instance['image_height']					= strip_tags($new_instance['image_height']);
		$instance['image_quality']					= strip_tags($new_instance['image_quality']);
		
		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$instance 			= wp_parse_args( (array) $instance );
		
		$title 				= strip_tags($instance['title']);
		
		$class 							= strip_tags($instance['class']);
		$number 						= strip_tags($instance['number']);
		$post_orderby					= strip_tags($instance['post_orderby']);
		$post_order						= strip_tags($instance['post_order']);
		$taxonomies						= unserialize($instance['taxonomies']);
		$terms							= unserialize($instance['terms']);
		$post_types						= unserialize($instance['post_types']);	
		$post_title						= strip_tags($instance['post_title']);
		$post_title_link				= strip_tags($instance['post_title_link']);
		$post_title_element				= strip_tags($instance['post_title_element']);
		$author							= strip_tags($instance['author']);
		$date 							= strip_tags($instance['date']);
		$date_format					= strip_tags($instance['date_format']);
		$comments 						= strip_tags($instance['comments']);
		$snippet 						= strip_tags($instance['snippet']);
		$snippet_length					= strip_tags($instance['snippet_length']);
		$snippet_truncation				= strip_tags($instance['snippet_truncation']);
		$ellipses						= strip_tags($instance['ellipses']);
		$read_more						= strip_tags($instance['read_more']);
		$read_more_text					= strip_tags($instance['read_more_text']);
		$image	 						= strip_tags($instance['image']);
		$image_foreground				= strip_tags($instance['image_foreground']);
		$image_link						= strip_tags($instance['image_link']);
		$image_caption 					= strip_tags($instance['image_caption']);
		$image_caption_link				= strip_tags($instance['image_caption_link']);
		$image_caption_view_more		= strip_tags($instance['image_caption_view_more']);
		$image_caption_view_more_text	= strip_tags($instance['image_caption_view_more_text']);
		$image_location 				= strip_tags($instance['image_location']);
		$image_class 					= strip_tags($instance['image_class']);
		$image_width					= strip_tags($instance['image_width']);
		$image_height					= strip_tags($instance['image_height']);
		$image_quality					= strip_tags($instance['image_quality']);
		
		echo self::formatField($this->get_field_name('title'), $this->get_field_id('title'),  $title, "Title");
		echo self::formatField($this->get_field_name('class'), $this->get_field_id('class'),  $class, "UL Class");
		echo self::formatField($this->get_field_name('number'), $this->get_field_id('number'), $number, "Number of blogs to be displayed");
		echo self::formatField($this->get_field_name('post_orderby'), $this->get_field_id('post_orderby'), $post_orderby, "Order Post By", "radio", array(), array('none','ID','author','title', 'name','date','modified','parent','rand','comment_count','menu_order' ,'meta_value' ,'meta_value_num') );
		echo self::formatField($this->get_field_name('post_order'), $this->get_field_id('post_order'), $post_order, "Sort", "radio", array(), array('ASC', 'DESC') );
		echo self::formatField($this->get_field_name('taxonomies'), $this->get_field_id('taxonomies'), $taxonomies, "Taxonomies", "taxonomy");
		echo self::formatField($this->get_field_name('terms'), $this->get_field_id('terms'), $terms, "Terms (Categories)", "term");	
		echo self::formatField($this->get_field_name('post_types'), $this->get_field_id('post_types'), $post_types, "Post Types", "post_type");
		echo self::formatField($this->get_field_name('post_title'), $this->get_field_id('post_title'), $post_title, "Show Post Title", "checkbox");
		echo self::formatField($this->get_field_name('post_title_link'), $this->get_field_id('post_title_link'), $post_title_link, "Link the Post Title", "checkbox");
		echo self::formatField($this->get_field_name('post_title_element'), $this->get_field_id('post_title_element'), $post_title_element, "Post Title Element (Default h4)");
		echo self::formatField($this->get_field_name('author'), $this->get_field_id('author'), $author, "Show Author", "checkbox");
		echo self::formatField($this->get_field_name('date'), $this->get_field_id('date'), $date, "Show Date", "checkbox");
		echo self::formatField($this->get_field_name('date_format'), $this->get_field_id('date_format'), $date_format, 'Date Format, see <a href="http://codex.wordpress.org/Formatting_Date_and_Time">Codex</a>' );
		echo self::formatField($this->get_field_name('comments'), $this->get_field_id('comments'), $comments, "Show Comments", "checkbox");
		echo self::formatField($this->get_field_name('snippet'), $this->get_field_id('snippet'), $snippet, "Show Snippet", "checkbox");
		echo self::formatField($this->get_field_name('snippet_length'), $this->get_field_id('snippet_length'), $snippet_length, "Snippet character length");
		echo self::formatField($this->get_field_name('snippet_truncation'), $this->get_field_id('snippet_truncation'), $snippet_truncation, "Truncate at nearest word", "checkbox");
		echo self::formatField($this->get_field_name('ellipses'), $this->get_field_id('ellipses'), $ellipses, "Show Ellipses (...)", "checkbox");
		echo self::formatField($this->get_field_name('read_more'), $this->get_field_id('read_more'), $read_more, "Show Read More", "checkbox");
		echo self::formatField($this->get_field_name('read_more_text'), $this->get_field_id('read_more_text'), $read_more_text, "Read More Appearance");
		echo self::formatField($this->get_field_name('image'), $this->get_field_id('image'), $image, "Show Image", "checkbox");
		echo self::formatField($this->get_field_name('image_foreground'), $this->get_field_id('image_foreground'), $image_foreground, "Show Image Foreground (Must Style!)", "checkbox");
		echo self::formatField($this->get_field_name('image_link'), $this->get_field_id('image_link'), $image_link, "Link Image", "checkbox");
		echo self::formatField($this->get_field_name('image_caption'), $this->get_field_id('image_caption'), $image_caption, "Show Image Caption", "checkbox");
		echo self::formatField($this->get_field_name('image_caption_link'), $this->get_field_id('image_caption_link'), $image_caption_link, "Link Image Caption", "checkbox");
		echo self::formatField($this->get_field_name('image_caption_view_more'), $this->get_field_id('image_caption_view_more'), $image_caption_view_more, "Show View More on Caption", "checkbox");
		echo self::formatField($this->get_field_name('image_caption_view_more_text'), $this->get_field_id('image_caption_view_more_text'), $image_caption_view_more_text, "View More Text");
		echo self::formatField($this->get_field_name('image_location'), $this->get_field_id('image_location'), $image_location, "Location of Image", "radio", array(), array('beginning','after_title','after_date','after_author','after_snippet','after_ellipses','after_read_more','after_comments','end') );
		echo self::formatField($this->get_field_name('image_class'), $this->get_field_id('image_class'), $image_class, "Image Class");
		echo self::formatField($this->get_field_name('image_width'), $this->get_field_id('image_width'), $image_width, "Image Width");
		echo self::formatField($this->get_field_name('image_height'), $this->get_field_id('image_height'), $image_height, "Image Height");
		echo self::formatField($this->get_field_name('image_quality'), $this->get_field_id('image_quality'), $image_quality, "Resized Image Quality (1-100)");
	}

	# -----------------------------------------------------------------------------#
	# End Standard Wordpress Widget Section
	# -----------------------------------------------------------------------------#
	public function formatField($field, $id, $value, $description, $type = "text", $args = array(), $options = array() )	{
		if($type == "text"):
			return '<p>
					<label for="' . $id . '">
						' . $description . ': 
						<input class="widefat" id="' . $id . '" name="' . $field. '" type="text" value="' . attribute_escape($value) . '" />
					</label>
					</p>';
		elseif($type == "checkbox"):
			if( $value ) $checked = "checked";
			return '<p>
					<label for="' . $field . '">
						
						<input id="' . $field. '" name="' . $field . '" type="checkbox" value="1" ' . $checked . ' /> ' . $description . ' 
					</label>
					</p>';
		elseif($type == "radio"):
			$radio = '<p>
					<label for="' . $field . '">' . $description . '<br />';
					foreach($options as $option):
						if( $value == $option ): $checked = "checked"; else: $checked = ""; endif;						
						$radio .= '<input id="' . $field. '" name="' . $field . '" type="radio" value="' . $option . '" ' . $checked . ' /> ' . self::getLabel($option) . '<br />';
					endforeach; 
			$radio .= '</label>
					</p>';
			return $radio;
		elseif($type == "category"):
			$categories_list = get_categories('orderby=name');
			
			$cats .= '
					
					' . $description . ':
					<div class="widefat" style="height: 100px; overflow-y: scroll; margin-bottom: 10px;"> '; 
			foreach( $categories_list as $category ):
			
				if( in_array($category->cat_ID, (array)$value  ) ) $cat_checked = "checked"; else $cat_checked = "";
			
				$cats .= '<input type="checkbox" name="' . $field . '[]" value="' . $category->cat_ID . '" ' . $cat_checked . ' /> ' . $category->cat_name . '<br />';
			
			endforeach;
			$cats .= '</div>
						';
			return $cats;
		elseif($type == "term"):
			
			$taxonomies = get_taxonomies();
			
			$terms = get_terms($taxonomies, array( 'hide_empty' => 0 ));
		
			$tax_terms .= '
					
					' . $description . ':
					<div class="widefat" style="height: 100px; overflow-y: scroll; margin-bottom: 10px;"> '; 
			foreach( $terms as $term ):
			
				if( in_array($term->slug, (array)$value  ) ) $term_checked = "checked"; else $term_checked = "";
			
				$tax_terms .= '<input type="checkbox" name="' . $field . '[]" value="' . $term->slug . '" ' . $term_checked . ' /> ' . $term->name . '<br />';
			
			endforeach;
			$tax_terms .= '</div>
						';
			
			return $tax_terms;
		elseif($type == "taxonomy"):
			$taxonomies= get_taxonomies();
			$taxes .= '			
					' . $description . ':
					<div class="widefat" style="height: 100px; overflow-y: scroll;"> '; 
			foreach( $taxonomies as $taxonomy ):
			
				if( in_array($taxonomy, (array)$value  ) ) $tax_checked = "checked"; else $tax_checked = "";
			
				$taxes .= '<input type="checkbox" name="' . $field . '[]" value="' . $taxonomy . '" ' . $tax_checked . ' /> ' . self::getLabel($taxonomy) . '<br />';
			
			endforeach;
			$taxes .= '</div>
						';
			return $taxes;
		elseif($type == "post_type"):
			$post_types = get_post_types();
			
			$p_type .= '
					
					' . $description . ':
					<div class="widefat" style="height: 100px; overflow-y: scroll;"> '; 
			foreach( $post_types as $post_type ):
			
				if( in_array($post_type, (array)$value  ) ) $type_checked = "checked"; else $type_checked = "";
			
				$p_type  .= '<input type="checkbox" name="' . $field . '[]" value="' . $post_type. '" ' . $type_checked . ' /> ' . self::getLabel($post_type) . '<br />';
			
			endforeach;
			$p_type  .= '</div>
						';
			return $p_type ;
		endif;
	}
	public function getLabel($key){
		$glued = array();
		if( strpos( $key, "_" ) ) $pieces = explode( "_", $key );
		elseif( strpos( $key, "-" ) ) $pieces = explode( "-", $key );
		else $pieces = explode(" ", $key);
		foreach($pieces as $piece):
			if($piece == "id"):
				$glued[] = strtoupper($piece);
			else:
				$glued[] = ucfirst($piece);
			endif;
		endforeach;
		
		return implode(" ", $glued);
	}
	public function the_post_thumbnail_caption($post_id) {
		global $post;
		
		$thumbnail_id    = get_post_thumbnail_id($post_id);
		$thumbnail_image = get_posts(array('p' => $thumbnail_id, 'post_type' => 'attachment'));
		
		if ($thumbnail_image && isset($thumbnail_image[0])):
			return $thumbnail_image[0]->post_excerpt;
		endif;
	}
}
//Register the Widget
function simpul_blogs_widget() {
	register_widget( 'SimpulBlogs' );
}
//Add Widget to wordpress
add_action( 'widgets_init', 'simpul_blogs_widget' );	

function simpul_blogs_activation()
{
	chmod("/includes/cache/", 0755);
}
register_activation_hook(__FILE__, 'simpul_blogs_activation');
