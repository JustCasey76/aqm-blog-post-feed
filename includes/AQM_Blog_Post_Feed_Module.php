<?php
class AQM_Blog_Post_Feed_Module extends ET_Builder_Module {
    public $slug = 'aqm_blog_post_feed';
    public $vb_support = 'on';

    public function init() {
        $this->name = esc_html__('AQM Blog Post Feed', 'aqm-blog-post-feed');
        
        // Enqueue Font Awesome
        add_action('wp_enqueue_scripts', array($this, 'enqueue_font_awesome'));
    }
    
    // Function to load Font Awesome
    public function enqueue_font_awesome() {
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4');
    }

    public function get_fields() {
        return array(
            'columns' => array(
                'label'           => esc_html__('Number of Columns (Desktop)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 3,
                'options'         => array(
                    'min'  => 1,
                    'max'  => 6,
                    'step' => 1,
                ),
                'description'     => esc_html__('Adjust the number of columns for the post grid on desktop.', 'aqm-blog-post-feed'),
            ),
            'columns_tablet' => array(
                'label'           => esc_html__('Number of Columns (Tablet)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 2,
                'options'         => array(
                    'min'  => 1,
                    'max'  => 6,
                    'step' => 1,
                ),
                'description'     => esc_html__('Adjust the number of columns for the post grid on tablet.', 'aqm-blog-post-feed'),
            ),
            'columns_mobile' => array(
                'label'           => esc_html__('Number of Columns (Mobile)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 1,
                'options'         => array(
                    'min'  => 1,
                    'max'  => 6,
                    'step' => 1,
                ),
                'description'     => esc_html__('Adjust the number of columns for the post grid on mobile.', 'aqm-blog-post-feed'),
            ),
            'spacing' => array(
                'label'           => esc_html__('Column Gap (px)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 20,
                'options'         => array(
                    'min'  => 0,
                    'max'  => 50,
                    'step' => 1,
                ),
                'description'     => esc_html__('Set the space between post items.', 'aqm-blog-post-feed'),
            ),
            'overlay_color' => array(
                'label'           => esc_html__('Overlay Background Color', 'aqm-blog-post-feed'),
                'type'            => 'color',
                'default'         => 'rgba(0, 0, 0, 0.5)',
            ),
            'overlay_hover_color' => array(
                'label'           => esc_html__('Overlay Hover Background Color', 'aqm-blog-post-feed'),
                'type'            => 'color',
                'default'         => 'rgba(0, 0, 0, 0.3)',
            ),
            'title_color' => array(
                'label'           => esc_html__('Title Color', 'aqm-blog-post-feed'),
                'type'            => 'color',
                'default'         => '#ffffff',
            ),
            'title_font_size' => array(
                'label'           => esc_html__('Title Font Size (Desktop)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 18,
                'options'         => array(
                    'min'  => 10,
                    'max'  => 50,
                    'step' => 1,
                ),
            ),
            'title_font_size_mobile' => array(
                'label'           => esc_html__('Title Font Size (Mobile)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 16,
                'options'         => array(
                    'min'  => 10,
                    'max'  => 50,
                    'step' => 1,
                ),
            ),
            'title_line_height' => array(
                'label'           => esc_html__('Title Line Height (em)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 1.4,
                'options'         => array(
                    'min'  => 1,
                    'max'  => 2,
                    'step' => 0.1,
                ),
            ),
            'content_font_size' => array(
                'label'           => esc_html__('Content Font Size (px)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 14,
                'options'         => array(
                    'min'  => 10,
                    'max'  => 30,
                    'step' => 1,
                ),
            ),
            'content_color' => array(
                'label'           => esc_html__('Content Color', 'aqm-blog-post-feed'),
                'type'            => 'color',
                'default'         => '#ffffff',
            ),
            'content_line_height' => array(
                'label'           => esc_html__('Content Line Height (em)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 1.6,
                'options'         => array(
                    'min'  => 1,
                    'max'  => 3,
                    'step' => 0.1,
                ),
            ),
            'content_padding' => array(
                'label'           => esc_html__('Content Padding', 'aqm-blog-post-feed'),
                'type'            => 'custom_padding',
                'default'         => '20px|20px|20px|20px',
                'description'     => esc_html__('Set the padding for the post content (Top | Right | Bottom | Left).', 'aqm-blog-post-feed'),
            ),
            'item_border_radius' => array(
                'label'           => esc_html__('Item Border Radius (px)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 10,
                'options'         => array(
                    'min'  => 0,
                    'max'  => 50,
                    'step' => 1,
                ),
                'description'     => esc_html__('Set the border radius for the post items.', 'aqm-blog-post-feed'),
            ),
            'meta_font_size' => array(
                'label'           => esc_html__('Meta Font Size (px)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 12,
                'options'         => array(
                    'min'  => 10,
                    'max'  => 20,
                    'step' => 1,
                ),
            ),
            'meta_line_height' => array(
                'label'           => esc_html__('Meta Line Height (em)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 1.4,
                'options'         => array(
                    'min'  => 1,
                    'max'  => 2,
                    'step' => 0.1,
                ),
            ),
            'meta_color' => array(
                'label'           => esc_html__('Meta Color', 'aqm-blog-post-feed'),
                'type'            => 'color',
                'default'         => '#ffffff',
            ),
            // Updated field for excerpt word limit with up/down arrows (range type)
            'excerpt_limit' => array(
                'label'           => esc_html__('Excerpt Word Limit', 'aqm-blog-post-feed'),
                'type'            => 'range',  // Now a range input with arrows
                'default'         => 60,
                'options'         => array(
                    'min'  => 10,
                    'max'  => 100,
                    'step' => 10,
                ),
            ),
            'read_more_text' => array(
                'label'           => esc_html__('Read More Text', 'aqm-blog-post-feed'),
                'type'            => 'text',
                'default'         => 'Read More',
            ),
            'read_more_color' => array(
                'label'           => esc_html__('Read More Text Color', 'aqm-blog-post-feed'),
                'type'            => 'color',
                'default'         => '#ffffff',
            ),
            'read_more_bg_color' => array(
                'label'           => esc_html__('Read More Background Color', 'aqm-blog-post-feed'),
                'type'            => 'color',
                'default'         => '#0073e6',
            ),
            'read_more_hover_color' => array(
                'label'           => esc_html__('Read More Hover Text Color', 'aqm-blog-post-feed'),
                'type'            => 'color',
                'default'         => '#ffffff',
            ),
            'read_more_hover_bg_color' => array(
                'label'           => esc_html__('Read More Hover Background Color', 'aqm-blog-post-feed'),
                'type'            => 'color',
                'default'         => '#005bb5',
            ),
            'read_more_font_size' => array(
                'label'           => esc_html__('Read More Font Size (px)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 14,
                'options'         => array(
                    'min'  => 10,
                    'max'  => 30,
                    'step' => 1,
                ),
            ),
            'read_more_padding' => array(
                'label'           => esc_html__('Read More Padding', 'aqm-blog-post-feed'),
                'type'            => 'custom_padding',
                'default'         => '10px|20px|10px|20px',
                'description'     => esc_html__('Set the padding for the "Read More" link (Top | Right | Bottom | Left).', 'aqm-blog-post-feed'),
            ),
            'read_more_border_radius' => array(
                'label'           => esc_html__('Read More Border Radius (px)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 5,
                'options'         => array(
                    'min'  => 0,
                    'max'  => 50,
                    'step' => 1,
                ),
                'description'     => esc_html__('Set the border radius for the "Read More" link.', 'aqm-blog-post-feed'),
            ),
            'read_more_uppercase' => array(
                'label'           => esc_html__('Uppercase Read More Link', 'aqm-blog-post-feed'),
                'type'            => 'yes_no_button',
                'options'         => array(
                    'on'  => esc_html__('Yes', 'aqm-blog-post-feed'),
                    'off' => esc_html__('No', 'aqm-blog-post-feed'),
                ),
                'default'         => 'off',
                'description'     => esc_html__('Toggle to set the "Read More" link to uppercase.', 'aqm-blog-post-feed'),
            ),
			'background_zoom' => array(
                'label'           => esc_html__('Background Zoom Amount (%)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 125,
                'options'         => array(
                    'min'  => 100,
                    'max'  => 150,
                    'step' => 1,
                ),
                'description'     => esc_html__('Adjust the zoom amount for the background image when hovering over the post item.', 'aqm-blog-post-feed'),
            ),
            'post_limit' => array(
                'label'           => esc_html__('Maximum Number of Posts', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 10,
                'options'         => array(
                    'min'  => 1,
                    'max'  => 50,
                    'step' => 1,
                ),
                'description'     => esc_html__('Limit the number of posts displayed in the feed.', 'aqm-blog-post-feed'),
            ),
            'enable_load_more' => array(
                'label'           => esc_html__('Enable Load More Button', 'aqm-blog-post-feed'),
                'type'            => 'yes_no_button',
                'options'         => array(
                    'on'  => esc_html__('Yes', 'aqm-blog-post-feed'),
                    'off' => esc_html__('No', 'aqm-blog-post-feed'),
                ),
                'default'         => 'off',
                'description'     => esc_html__('Enable a button to load more posts.', 'aqm-blog-post-feed'),
            ),
            'initial_posts' => array(
                'label'           => esc_html__('Initial Posts to Load', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 6,
                'options'         => array(
                    'min'  => 1,
                    'max'  => 20,
                    'step' => 1,
                ),
                'description'     => esc_html__('Number of posts to display initially when Load More is enabled.', 'aqm-blog-post-feed'),
                'show_if'         => array('enable_load_more' => 'on'),
            ),
            'additional_posts' => array(
                'label'           => esc_html__('Additional Posts Per Load', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 3,
                'options'         => array(
                    'min'  => 1,
                    'max'  => 10,
                    'step' => 1,
                ),
                'description'     => esc_html__('Number of additional posts to load when clicking the Load More button.', 'aqm-blog-post-feed'),
                'show_if'         => array('enable_load_more' => 'on'),
            ),
            'load_more_text' => array(
                'label'           => esc_html__('Load More Button Text', 'aqm-blog-post-feed'),
                'type'            => 'text',
                'default'         => 'Load More',
                'description'     => esc_html__('Text to display on the Load More button.', 'aqm-blog-post-feed'),
                'show_if'         => array('enable_load_more' => 'on'),
            ),
            'load_more_bg_color' => array(
                'label'           => esc_html__('Load More Button Background Color', 'aqm-blog-post-feed'),
                'type'            => 'color',
                'default'         => '#0073e6',
                'show_if'         => array('enable_load_more' => 'on'),
            ),
            'load_more_text_color' => array(
                'label'           => esc_html__('Load More Button Text Color', 'aqm-blog-post-feed'),
                'type'            => 'color',
                'default'         => '#ffffff',
                'show_if'         => array('enable_load_more' => 'on'),
            ),
            'load_more_hover_bg_color' => array(
                'label'           => esc_html__('Load More Button Hover Background Color', 'aqm-blog-post-feed'),
                'type'            => 'color',
                'default'         => '#005bb5',
                'show_if'         => array('enable_load_more' => 'on'),
            ),
            'load_more_hover_text_color' => array(
                'label'           => esc_html__('Load More Button Hover Text Color', 'aqm-blog-post-feed'),
                'type'            => 'color',
                'default'         => '#ffffff',
                'show_if'         => array('enable_load_more' => 'on'),
            ),
            'sort_order' => array(
                'label'           => esc_html__('Sort Order', 'aqm-blog-post-feed'),
                'type'            => 'select',
                'options'         => array(
                    'date_asc' => esc_html__('Date (Ascending)', 'aqm-blog-post-feed'),
                    'date_desc' => esc_html__('Date (Descending)', 'aqm-blog-post-feed'),
                    'title_asc' => esc_html__('Title (Ascending)', 'aqm-blog-post-feed'),
                    'title_desc' => esc_html__('Title (Descending)', 'aqm-blog-post-feed'),
                ),
                'default'         => 'date_desc',
            ),
        );
    }

    // Helper function to format padding values correctly
    public function format_padding($padding) {
        $padding_values = explode('|', $padding);
        return implode(' ', array_slice($padding_values, 0, 4)); // Return "top right bottom left"
    }

public function render($attrs, $render_slug, $content = null) {
        $columns = $this->props['columns'];
        $columns_tablet = $this->props['columns_tablet'];
        $columns_mobile = $this->props['columns_mobile'];
        $spacing = $this->props['spacing'];
        $item_border_radius = $this->props['item_border_radius'];
        $title_color = isset($this->props['title_color']) ? $this->props['title_color'] : '#ffffff';
        $title_font_size = $this->props['title_font_size'];
        $title_font_size_mobile = $this->props['title_font_size_mobile'];
        $title_line_height = $this->props['title_line_height'];
        $content_font_size = $this->props['content_font_size'];
        $content_color = isset($this->props['content_color']) ? $this->props['content_color'] : '#ffffff';
        $content_line_height = $this->props['content_line_height'];
        $content_padding = $this->format_padding($this->props['content_padding']);
        $meta_font_size = $this->props['meta_font_size'];
        $meta_line_height = $this->props['meta_line_height'];
        $meta_color = isset($this->props['meta_color']) ? $this->props['meta_color'] : '#ffffff';
        $read_more_padding = $this->format_padding($this->props['read_more_padding']);
        $read_more_border_radius = $this->props['read_more_border_radius'];
        $read_more_color = $this->props['read_more_color'];
        $read_more_bg_color = $this->props['read_more_bg_color'];
        $read_more_font_size = $this->props['read_more_font_size'];
        $read_more_hover_color = $this->props['read_more_hover_color'];
        $read_more_hover_bg_color = $this->props['read_more_hover_bg_color'];
        $read_more_uppercase = $this->props['read_more_uppercase'];
        $excerpt_limit = intval($this->props['excerpt_limit']);
        $read_more_text = $this->props['read_more_text'];
        $background_zoom = $this->props['background_zoom'];
        $post_limit = intval($this->props['post_limit']);
        
        // Load More settings
        $enable_load_more = isset($this->props['enable_load_more']) ? $this->props['enable_load_more'] : 'off';
        $initial_posts = isset($this->props['initial_posts']) ? intval($this->props['initial_posts']) : 6;
        $additional_posts = isset($this->props['additional_posts']) ? intval($this->props['additional_posts']) : 3;
        $load_more_text = isset($this->props['load_more_text']) ? $this->props['load_more_text'] : 'Load More';
        $load_more_bg_color = isset($this->props['load_more_bg_color']) ? $this->props['load_more_bg_color'] : '#0073e6';
        $load_more_text_color = isset($this->props['load_more_text_color']) ? $this->props['load_more_text_color'] : '#ffffff';
        $load_more_hover_bg_color = isset($this->props['load_more_hover_bg_color']) ? $this->props['load_more_hover_bg_color'] : '#005bb5';
        $load_more_hover_text_color = isset($this->props['load_more_hover_text_color']) ? $this->props['load_more_hover_text_color'] : '#ffffff';

        // Apply uppercase style based on the setting
        $uppercase_style = $read_more_uppercase === 'on' ? 'text-transform: uppercase;' : '';

        // Get sort order from module settings
        $sort_order = isset($this->props['sort_order']) ? $this->props['sort_order'] : 'date_desc';
        
        // Determine order parameters based on sort_order
        $orderby = 'date';
        $order = 'DESC';
        
        switch ($sort_order) {
            case 'date_asc':
                $orderby = 'date';
                $order = 'ASC';
                break;
            case 'date_desc':
                $orderby = 'date';
                $order = 'DESC';
                break;
            case 'title_asc':
                $orderby = 'title';
                $order = 'ASC';
                break;
            case 'title_desc':
                $orderby = 'title';
                $order = 'DESC';
                break;
        }

        // Fetch posts
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => ($enable_load_more === 'on') ? $initial_posts : $post_limit,
            'orderby' => $orderby,
            'order' => $order,
        );
        $posts = new WP_Query($args);

        // Use CSS Grid layout
        $output = '<div class="aqm-post-feed" style="display: grid; grid-template-columns: repeat(' . esc_attr($columns) . ', 1fr); gap: ' . esc_attr($spacing) . 'px;">';

        if ($posts->have_posts()) {
            while ($posts->have_posts()) {
                $posts->the_post();
                $author = get_the_author();
                $date = get_the_date();
                $thumbnail_url = get_the_post_thumbnail_url(null, 'large');  // Change 'medium' to your preferred size, such as 'thumbnail', 'medium', 'large', or a custom size.


                // Post item with background image and zoom effect on hover
                $output .= '<div class="aqm-post-item" style="border-radius: ' . esc_attr($item_border_radius) . 'px; overflow: hidden; position: relative; min-height: 300px; background-image: url(' . esc_url($thumbnail_url) . '); background-size: 110%; background-position: center; transition: background-size 0.5s ease;">';
                
                // Full height overlay
                $output .= '<div class="aqm-post-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; transition: background-color 0.3s ease;"></div>';
                
                // Full height post content with padding control
                $output .= '<div class="aqm-post-content" style="position: relative; z-index: 2; padding:' . esc_attr($content_padding) . '; color: #fff; display: flex; flex-direction: column; justify-content: flex-end;">';
                $output .= '<h3 class="aqm-post-title" style="color:' . esc_attr($title_color) . '; font-size:' . esc_attr($title_font_size) . 'px; line-height:' . esc_attr($title_line_height) . 'em; margin: 0;">' . get_the_title() . '</h3>';
                
                // Meta (author and date with icons)
                $output .= '<p class="aqm-post-meta" style="color:' . esc_attr($meta_color) . '; font-size:' . esc_attr($meta_font_size) . 'px; line-height:' . esc_attr($meta_line_height) . 'em;">';
                $output .= '<i class="fas fa-user"></i> ' . esc_html($author) . ' &nbsp;|&nbsp; ';
                $output .= '<i class="fas fa-calendar-alt"></i> ' . esc_html($date);
                $output .= '</p>';
                
                // Excerpt with line height control and word limit from content
$output .= '<p class="aqm-post-excerpt" style="color:' . esc_attr($content_color) . '; font-size:' . esc_attr($content_font_size) . 'px; line-height:' . esc_attr($content_line_height) . 'em;">' . wp_trim_words(has_excerpt() ? get_the_excerpt() : get_the_content(), $excerpt_limit, '...') . '</p>';


                
// Read More Button with padding, border-radius, and inline-block styling, with uppercase toggle
$output .= '<a class="aqm-read-more" href="' . get_permalink() . '" style="transition: background-color 0.5s ease, color 0.5s ease; color:' . esc_attr($read_more_color) . '; background-color:' . esc_attr($read_more_bg_color) . '; padding:' . esc_attr($read_more_padding) . '; border-radius:' . esc_attr($read_more_border_radius) . 'px; display: inline-block; margin-top: 20px; font-size:' . esc_attr($read_more_font_size) . 'px; text-decoration: none; align-self: flex-start;' . $uppercase_style . '" onmouseover="this.style.color=\'' . esc_attr($read_more_hover_color) . '\'; this.style.backgroundColor=\'' . esc_attr($read_more_hover_bg_color) . '\';" onmouseout="this.style.color=\'' . esc_attr($read_more_color) . '\'; this.style.backgroundColor=\'' . esc_attr($read_more_bg_color) . '\';">' . esc_html($read_more_text) . '</a>';

                
                $output .= '</div>'; // Close aqm-post-content
                $output .= '</div>'; // Close aqm-post-item
            }
        }

        $output .= '</div>'; // Close aqm-post-feed
        
        // Add Load More button if enabled
        if ($enable_load_more === 'on') {
            // Generate a unique ID for this instance
            $module_id = 'aqm-blog-' . wp_rand(1000, 9999);
            
            $output .= '<div class="aqm-load-more-container" style="text-align: center; margin-top: 30px;">';
            $output .= '<button id="' . $module_id . '-load-more" class="aqm-load-more-button" style="background-color: ' . esc_attr($load_more_bg_color) . '; color: ' . esc_attr($load_more_text_color) . '; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: all 0.3s ease;">' . esc_html($load_more_text) . '</button>';
            $output .= '</div>';
            
            // Add JavaScript to handle AJAX loading
            $output .= '<script>
            jQuery(document).ready(function($) {
                var page = 1;
                var loading = false;
                var button = $("#' . $module_id . '-load-more");
                
                button.on("mouseover", function() {
                    $(this).css({
                        "background-color": "' . esc_attr($load_more_hover_bg_color) . '",
                        "color": "' . esc_attr($load_more_hover_text_color) . '"
                    });
                }).on("mouseout", function() {
                    $(this).css({
                        "background-color": "' . esc_attr($load_more_bg_color) . '",
                        "color": "' . esc_attr($load_more_text_color) . '"
                    });
                });
                
                button.on("click", function() {
                    if (loading) return;
                    loading = true;
                    
                    // Show loading state
                    button.text("Loading...").prop("disabled", true);
                    
                    $.ajax({
                        url: "' . admin_url('admin-ajax.php') . '",
                        type: "POST",
                        data: {
                            action: "aqm_load_more_posts",
                            page: ++page,
                            posts_per_page: ' . $additional_posts . ',
                            orderby: "' . $orderby . '",
                            order: "' . $order . '",
                            nonce: "' . wp_create_nonce('aqm_load_more_nonce') . '",
                            columns: ' . $columns . ',
                            columns_tablet: ' . $columns_tablet . ',
                            columns_mobile: ' . $columns_mobile . ',
                            spacing: ' . $spacing . ',
                            item_border_radius: ' . $item_border_radius . ',
                            title_color: "' . $title_color . '",
                            title_font_size: ' . $title_font_size . ',
                            title_font_size_mobile: ' . $title_font_size_mobile . ',
                            title_line_height: ' . $title_line_height . ',
                            content_font_size: ' . $content_font_size . ',
                            content_color: "' . $content_color . '",
                            content_line_height: ' . $content_line_height . ',
                            content_padding: "' . $content_padding . '",
                            meta_font_size: ' . $meta_font_size . ',
                            meta_line_height: ' . $meta_line_height . ',
                            meta_color: "' . $meta_color . '",
                            read_more_padding: "' . $read_more_padding . '",
                            read_more_border_radius: ' . $read_more_border_radius . ',
                            read_more_color: "' . $read_more_color . '",
                            read_more_bg_color: "' . $read_more_bg_color . '",
                            read_more_font_size: ' . $read_more_font_size . ',
                            read_more_hover_color: "' . $read_more_hover_color . '",
                            read_more_hover_bg_color: "' . $read_more_hover_bg_color . '",
                            excerpt_limit: ' . $excerpt_limit . ',
                            read_more_text: "' . $read_more_text . '",
                            read_more_uppercase: "' . $read_more_uppercase . '",
                            background_zoom: ' . $background_zoom . '
                        },
                        success: function(response) {
                            var data = JSON.parse(response);
                            if (data.success) {
                                $(".aqm-post-feed").append(data.html);
                                
                                // Re-enable button if there are more posts
                                if (data.has_more) {
                                    button.text("' . esc_html($load_more_text) . '").prop("disabled", false);
                                } else {
                                    button.text("No More Posts").prop("disabled", true);
                                }
                            } else {
                                button.text("Error Loading Posts").prop("disabled", true);
                            }
                            loading = false;
                        },
                        error: function() {
                            button.text("Error Loading Posts").prop("disabled", true);
                            loading = false;
                        }
                    });
                });
            });
            </script>';
        }
        
        wp_reset_postdata();

        // Inline styles for hover effects and responsive columns
        $output .= '<style>
		
 			.aqm-post-item {
				transition: background-size 0.5s ease;
        		background-size: 110% !important;
				background-position: center; 
				background-repeat:none;
    		}

 			.aqm-post-item .aqm-post-overlay {
        		background-color: ' . esc_attr($this->props['overlay_color']) . ' !important;
    		}
			.aqm-post-item:hover .aqm-post-overlay {
        		background-color: ' . esc_attr($this->props['overlay_hover_color']) . ' !important;
    		}
            .aqm-post-item:hover {
                background-size: ' . esc_attr($background_zoom) . '% !important;
				
            }
            .aqm-post-item .aqm-read-more:hover {
                background-color: ' . esc_attr($read_more_hover_bg_color) . ' !important;
                color: ' . esc_attr($read_more_hover_color) . ';
            }
			 .aqm-post-meta {
			    padding: 4px 0 14px 0 !important;
			}
            @media (max-width: 980px) {
                .aqm-post-feed {
                    grid-template-columns: repeat(' . esc_attr($columns_tablet) . ', 1fr);
                }
            }
            @media (max-width: 767px) {
                .aqm-post-feed {
                    grid-template-columns: repeat(' . esc_attr($columns_mobile) . ', 1fr) !important;
                }
                .aqm-post-title {
                    font-size:' . esc_attr($title_font_size_mobile) . 'px !important;
                }
            }
        </style>';

        return $output;
    }
}
new AQM_Blog_Post_Feed_Module;
