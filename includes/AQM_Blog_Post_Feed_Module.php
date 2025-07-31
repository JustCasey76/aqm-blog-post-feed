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
            'layout_type' => array(
                'label'           => esc_html__('Layout Type', 'aqm-blog-post-feed'),
                'type'            => 'select',
                'options'         => array(
                    'grid' => esc_html__('Grid View', 'aqm-blog-post-feed'),
                    'list' => esc_html__('List View', 'aqm-blog-post-feed'),
                ),
                'default'         => 'grid',
                'description'     => esc_html__('Choose between grid view (with images and content) or list view (titles only).', 'aqm-blog-post-feed'),
            ),
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
            'title_font_family' => array(
                'label'           => esc_html__('Title Font Family', 'aqm-blog-post-feed'),
                'type'            => 'font',
                'default'         => '',
                'description'     => esc_html__('Choose a font family for the post title.', 'aqm-blog-post-feed'),
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
            'content_font_family' => array(
                'label'           => esc_html__('Content Font Family', 'aqm-blog-post-feed'),
                'type'            => 'font',
                'default'         => '',
                'description'     => esc_html__('Choose a font family for the post content/excerpt.', 'aqm-blog-post-feed'),
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
            'list_title_font_size' => array(
                'label'           => esc_html__('List Title Font Size (px)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 16,
                'options'         => array(
                    'min'  => 12,
                    'max'  => 30,
                    'step' => 1,
                ),
                'show_if'         => array('layout_type' => 'list'),
                'description'     => esc_html__('Font size for post titles in list view.', 'aqm-blog-post-feed'),
            ),
            'list_title_color' => array(
                'label'           => esc_html__('List Title Color', 'aqm-blog-post-feed'),
                'type'            => 'color',
                'default'         => '#333333',
                'show_if'         => array('layout_type' => 'list'),
                'description'     => esc_html__('Color for post titles in list view.', 'aqm-blog-post-feed'),
            ),
            'list_title_hover_color' => array(
                'label'           => esc_html__('List Title Hover Color', 'aqm-blog-post-feed'),
                'type'            => 'color',
                'default'         => '#0073e6',
                'show_if'         => array('layout_type' => 'list'),
                'description'     => esc_html__('Hover color for post titles in list view.', 'aqm-blog-post-feed'),
            ),
            'list_item_spacing' => array(
                'label'           => esc_html__('List Item Spacing (px)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 10,
                'options'         => array(
                    'min'  => 5,
                    'max'  => 30,
                    'step' => 1,
                ),
                'show_if'         => array('layout_type' => 'list'),
                'description'     => esc_html__('Spacing between list items.', 'aqm-blog-post-feed'),
            ),
            'list_font_family' => array(
                'label'           => esc_html__('List Font Family', 'aqm-blog-post-feed'),
                'type'            => 'font',
                'default'         => '',
                'show_if'         => array('layout_type' => 'list'),
                'description'     => esc_html__('Choose a font family for the list titles.', 'aqm-blog-post-feed'),
            ),
            'list_font_weight' => array(
                'label'           => esc_html__('List Font Weight', 'aqm-blog-post-feed'),
                'type'            => 'select',
                'options'         => array(
                    '300' => esc_html__('Light', 'aqm-blog-post-feed'),
                    '400' => esc_html__('Normal', 'aqm-blog-post-feed'),
                    '500' => esc_html__('Medium', 'aqm-blog-post-feed'),
                    '600' => esc_html__('Semi Bold', 'aqm-blog-post-feed'),
                    '700' => esc_html__('Bold', 'aqm-blog-post-feed'),
                    '800' => esc_html__('Extra Bold', 'aqm-blog-post-feed'),
                ),
                'default'         => '400',
                'show_if'         => array('layout_type' => 'list'),
                'description'     => esc_html__('Font weight for list titles.', 'aqm-blog-post-feed'),
            ),
            'list_text_transform' => array(
                'label'           => esc_html__('List Text Transform', 'aqm-blog-post-feed'),
                'type'            => 'select',
                'options'         => array(
                    'none' => esc_html__('None', 'aqm-blog-post-feed'),
                    'uppercase' => esc_html__('Uppercase', 'aqm-blog-post-feed'),
                    'lowercase' => esc_html__('Lowercase', 'aqm-blog-post-feed'),
                    'capitalize' => esc_html__('Capitalize', 'aqm-blog-post-feed'),
                ),
                'default'         => 'none',
                'show_if'         => array('layout_type' => 'list'),
                'description'     => esc_html__('Text transformation for list titles.', 'aqm-blog-post-feed'),
            ),
            'list_line_height' => array(
                'label'           => esc_html__('List Line Height (em)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 1.4,
                'options'         => array(
                    'min'  => 1,
                    'max'  => 2.5,
                    'step' => 0.1,
                ),
                'show_if'         => array('layout_type' => 'list'),
                'description'     => esc_html__('Line height for list titles.', 'aqm-blog-post-feed'),
            ),
            'list_letter_spacing' => array(
                'label'           => esc_html__('List Letter Spacing (px)', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 0,
                'options'         => array(
                    'min'  => -2,
                    'max'  => 5,
                    'step' => 0.1,
                ),
                'show_if'         => array('layout_type' => 'list'),
                'description'     => esc_html__('Letter spacing for list titles.', 'aqm-blog-post-feed'),
            ),
            'list_posts_count' => array(
                'label'           => esc_html__('Number of Posts to Show in List', 'aqm-blog-post-feed'),
                'type'            => 'range',
                'default'         => 10,
                'options'         => array(
                    'min'  => 1,
                    'max'  => 50,
                    'step' => 1,
                ),
                'show_if'         => array('layout_type' => 'list'),
                'description'     => esc_html__('Maximum number of posts to display in list view.', 'aqm-blog-post-feed'),
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
            'show_meta_author' => array(
                'label'           => esc_html__('Show Author', 'aqm-blog-post-feed'),
                'type'            => 'yes_no_button',
                'options'         => array(
                    'on'  => esc_html__('Yes', 'aqm-blog-post-feed'),
                    'off' => esc_html__('No', 'aqm-blog-post-feed'),
                ),
                'default'         => 'on',
                'description'     => esc_html__('Toggle to show or hide the author in post meta.', 'aqm-blog-post-feed'),
            ),
            'show_meta_date' => array(
                'label'           => esc_html__('Show Date', 'aqm-blog-post-feed'),
                'type'            => 'yes_no_button',
                'options'         => array(
                    'on'  => esc_html__('Yes', 'aqm-blog-post-feed'),
                    'off' => esc_html__('No', 'aqm-blog-post-feed'),
                ),
                'default'         => 'on',
                'description'     => esc_html__('Toggle to show or hide the date in post meta.', 'aqm-blog-post-feed'),
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
            // Background size is hardcoded to 120% with hover at 135%
            
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
            'category_filter' => array(
                'label'           => esc_html__('Filter by Categories', 'aqm-blog-post-feed'),
                'type'            => 'categories',
                'description'     => esc_html__('Select specific categories to display posts from. Leave empty to show posts from all categories.', 'aqm-blog-post-feed'),
            ),
            'exclude_archived' => array(
                'label'           => esc_html__('Exclude Archived Posts', 'aqm-blog-post-feed'),
                'type'            => 'yes_no_button',
                'options'         => array(
                    'on'  => esc_html__('Yes', 'aqm-blog-post-feed'),
                    'off' => esc_html__('No', 'aqm-blog-post-feed'),
                ),
                'default'         => 'off',
                'description'     => esc_html__('Toggle to exclude posts from archived categories or with archived status.', 'aqm-blog-post-feed'),
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
        // Get layout type
        $layout_type = isset($this->props['layout_type']) ? $this->props['layout_type'] : 'grid';
        
        // List view specific variables
        $list_title_font_size = isset($this->props['list_title_font_size']) ? $this->props['list_title_font_size'] : 16;
        $list_title_color = isset($this->props['list_title_color']) ? $this->props['list_title_color'] : '#333333';
        $list_title_hover_color = isset($this->props['list_title_hover_color']) ? $this->props['list_title_hover_color'] : '#0073e6';
        $list_item_spacing = isset($this->props['list_item_spacing']) ? $this->props['list_item_spacing'] : 10;
        $list_font_family = isset($this->props['list_font_family']) ? $this->props['list_font_family'] : '';
        $list_font_weight = isset($this->props['list_font_weight']) ? $this->props['list_font_weight'] : '400';
        $list_text_transform = isset($this->props['list_text_transform']) ? $this->props['list_text_transform'] : 'none';
        $list_line_height = isset($this->props['list_line_height']) ? $this->props['list_line_height'] : 1.4;
        $list_letter_spacing = isset($this->props['list_letter_spacing']) ? $this->props['list_letter_spacing'] : 0;
        $list_posts_count = isset($this->props['list_posts_count']) ? intval($this->props['list_posts_count']) : 10;
        
        $columns = $this->props['columns'];
        $columns_tablet = $this->props['columns_tablet'];
        $columns_mobile = $this->props['columns_mobile'];
        $spacing = $this->props['spacing'];
        $item_border_radius = $this->props['item_border_radius'];
        $title_color = isset($this->props['title_color']) ? $this->props['title_color'] : '#ffffff';
        $title_font_size = $this->props['title_font_size'];
        $title_font_size_mobile = $this->props['title_font_size_mobile'];
        $title_line_height = $this->props['title_line_height'];
        $title_font_family = isset($this->props['title_font_family']) ? $this->props['title_font_family'] : '';
        $content_font_size = $this->props['content_font_size'];
        $content_color = isset($this->props['content_color']) ? $this->props['content_color'] : '#ffffff';
        $content_line_height = $this->props['content_line_height'];
        $content_font_family = isset($this->props['content_font_family']) ? $this->props['content_font_family'] : '';
        $content_padding = $this->format_padding($this->props['content_padding']);
        $meta_font_size = $this->props['meta_font_size'];
        $meta_line_height = $this->props['meta_line_height'];
        $meta_color = isset($this->props['meta_color']) ? $this->props['meta_color'] : '#ffffff';
        $show_meta_author = isset($this->props['show_meta_author']) ? $this->props['show_meta_author'] : 'on';
        $show_meta_date = isset($this->props['show_meta_date']) ? $this->props['show_meta_date'] : 'on';
        $read_more_padding = $this->format_padding($this->props['read_more_padding']);
        $read_more_border_radius = $this->props['read_more_border_radius'];
        $read_more_color = isset($this->props['read_more_color']) ? $this->props['read_more_color'] : '#ffffff';
        $read_more_bg_color = isset($this->props['read_more_bg_color']) ? $this->props['read_more_bg_color'] : '#0073e6';
        $read_more_font_size = isset($this->props['read_more_font_size']) ? $this->props['read_more_font_size'] : 14;
        $read_more_hover_color = isset($this->props['read_more_hover_color']) ? $this->props['read_more_hover_color'] : '#ffffff';
        $read_more_hover_bg_color = isset($this->props['read_more_hover_bg_color']) ? $this->props['read_more_hover_bg_color'] : '#005bb5';
        $read_more_uppercase = $this->props['read_more_uppercase'];
        $excerpt_limit = intval($this->props['excerpt_limit']);
        $read_more_text = $this->props['read_more_text'];
        // Hardcoded background sizes
        $background_size = 125;
        $background_zoom = 140; // 15% larger than default
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

        // Get filtering settings
        $category_filter = isset($this->props['category_filter']) ? $this->props['category_filter'] : '';
        $exclude_archived = isset($this->props['exclude_archived']) ? $this->props['exclude_archived'] : 'off';
        
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

        // Determine posts per page based on layout type
        if ($layout_type === 'list') {
            $posts_per_page = $list_posts_count;
        } else {
            $posts_per_page = ($enable_load_more === 'on') ? $initial_posts : $post_limit;
        }
        
        // Fetch posts
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => $posts_per_page,
            'orderby' => $orderby,
            'order' => $order,
        );
        
        // Add category filtering if specified
        if (!empty($category_filter)) {
            $category_ids = explode(',', $category_filter);
            $category_ids = array_map('intval', $category_ids);
            $category_ids = array_filter($category_ids); // Remove any invalid IDs
            
            if (!empty($category_ids)) {
                $args['category__in'] = $category_ids;
            }
        }
        
        // Exclude archived posts if enabled
        if ($exclude_archived === 'on') {
            // Get categories with 'archive' in the name or slug
            $archived_categories = get_categories(array(
                'hide_empty' => false,
                'name__like' => 'archive'
            ));
            
            // Also check for categories with 'archived' in slug
            $archived_categories_slug = get_categories(array(
                'hide_empty' => false,
                'slug__like' => 'archive'
            ));
            
            $archived_cat_ids = array();
            foreach ($archived_categories as $cat) {
                $archived_cat_ids[] = $cat->term_id;
            }
            foreach ($archived_categories_slug as $cat) {
                $archived_cat_ids[] = $cat->term_id;
            }
            
            // Remove duplicates
            $archived_cat_ids = array_unique($archived_cat_ids);
            
            if (!empty($archived_cat_ids)) {
                $args['category__not_in'] = $archived_cat_ids;
            }
            
            // Also exclude posts with 'draft' or 'private' status
            $args['post_status'] = 'publish';
        }
        
        // Exclude current post if we're on a single post page
        if (is_single()) {
            $args['post__not_in'] = array(get_the_ID());
        }
        
        $posts = new WP_Query($args);

        // Generate a unique ID for this instance
        $module_id = 'aqm-blog-' . wp_rand(1000, 9999);
        
        // Container based on layout type
        if ($layout_type === 'list') {
            $output = '<div id="' . $module_id . '" class="aqm-post-feed aqm-list-view" style="display: block;">';
        } else {
            $output = '<div id="' . $module_id . '" class="aqm-post-feed aqm-grid-view" style="display: grid; grid-template-columns: repeat(' . esc_attr($columns) . ', 1fr); gap: ' . esc_attr($spacing) . 'px;">';
        }

        if ($posts->have_posts()) {
            while ($posts->have_posts()) {
                $posts->the_post();
                
                if ($layout_type === 'list') {
                    // List view - only show title as link with comprehensive styling
                    $list_font_family_style = !empty($list_font_family) ? 'font-family: ' . esc_attr($list_font_family) . ';' : '';
                    
                    $list_title_style = 'color: ' . esc_attr($list_title_color) . '; '
                        . 'font-size: ' . esc_attr($list_title_font_size) . 'px; '
                        . 'font-weight: ' . esc_attr($list_font_weight) . '; '
                        . 'text-transform: ' . esc_attr($list_text_transform) . '; '
                        . 'line-height: ' . esc_attr($list_line_height) . 'em; '
                        . 'letter-spacing: ' . esc_attr($list_letter_spacing) . 'px; '
                        . $list_font_family_style
                        . 'text-decoration: none; '
                        . 'display: block; '
                        . 'transition: color 0.3s ease;';
                    
                    $output .= '<div class="aqm-list-item" style="margin-bottom: ' . esc_attr($list_item_spacing) . 'px;">';
                    $output .= '<a href="' . get_permalink() . '" class="aqm-list-title" style="' . $list_title_style . '">' . get_the_title() . '</a>';
                    $output .= '</div>';
                } else {
                    // Grid view - existing functionality
                    $author = get_the_author();
                    $date = get_the_date();
                    $thumbnail_url = get_the_post_thumbnail_url(null, 'large');


                // Post item with background image and zoom effect on hover
                $output .= '<div class="aqm-post-item" style="border-radius: ' . esc_attr($item_border_radius) . 'px; overflow: hidden; position: relative; min-height: 300px; background-image: url(' . esc_url($thumbnail_url) . '); background-size: ' . esc_attr($background_size) . '%; background-position: center; transition: background-size 0.5s ease;">';
                
                // Full height overlay
                $output .= '<div class="aqm-post-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; transition: background-color 0.3s ease;"></div>';
                
                // Full height post content with padding control
                $output .= '<div class="aqm-post-content" style="position: relative; z-index: 2; padding:' . esc_attr($content_padding) . '; color: #fff; display: flex; flex-direction: column; justify-content: flex-end;">';
                $output .= '<h3 class="aqm-post-title" style="color:' . esc_attr($title_color) . '; font-size:' . esc_attr($title_font_size) . 'px; line-height:' . esc_attr($title_line_height) . 'em; margin: 0;' . ($title_font_family ? ' font-family:' . esc_attr($title_font_family) . ';' : '') . '">' . get_the_title() . '</h3>';
                
                // Meta (author and date with icons)
                // Only display meta section if at least one of author or date is enabled
                if ($show_meta_author === 'on' || $show_meta_date === 'on') {
                    $output .= '<p class="aqm-post-meta" style="color:' . esc_attr($meta_color) . '; font-size:' . esc_attr($meta_font_size) . 'px; line-height:' . esc_attr($meta_line_height) . 'em;">';
                    
                    // Show author if enabled
                    if ($show_meta_author === 'on') {
                        $output .= '<i class="fas fa-user"></i> ' . esc_html($author);
                        // Only add separator if both author and date are shown
                        if ($show_meta_date === 'on') {
                            $output .= ' &nbsp;|&nbsp; ';
                        }
                    }
                    
                    // Show date if enabled
                    if ($show_meta_date === 'on') {
                        $output .= '<i class="fas fa-calendar-alt"></i> ' . esc_html($date);
                    }
                    
                    $output .= '</p>';
                }
                
                // Excerpt with line height control and word limit from content
$output .= '<p class="aqm-post-excerpt" style="color:' . esc_attr($content_color) . '; font-size:' . esc_attr($content_font_size) . 'px; line-height:' . esc_attr($content_line_height) . 'em;' . ($content_font_family ? ' font-family:' . esc_attr($content_font_family) . ';' : '') . '">' . wp_trim_words(has_excerpt() ? get_the_excerpt() : get_the_content(), $excerpt_limit, '...') . '</p>';


                
                $output .= '</div>'; // Close aqm-post-content
                
                // Read More Button positioned at lower left with same padding as content
                $content_padding_values = explode(' ', $content_padding);
                $bottom_padding = isset($content_padding_values[2]) ? $content_padding_values[2] : (isset($content_padding_values[0]) ? $content_padding_values[0] : '20px');
                $left_padding = isset($content_padding_values[3]) ? $content_padding_values[3] : (isset($content_padding_values[1]) ? $content_padding_values[1] : (isset($content_padding_values[0]) ? $content_padding_values[0] : '20px'));
                
                $output .= '<a class="aqm-read-more" href="' . get_permalink() . '" style="position: absolute; bottom: ' . esc_attr($bottom_padding) . '; left: ' . esc_attr($left_padding) . '; z-index: 3; transition: background-color 0.5s ease, color 0.5s ease; color:' . esc_attr($read_more_color) . '; background-color:' . esc_attr($read_more_bg_color) . '; padding:' . esc_attr($read_more_padding) . '; border-radius:' . esc_attr($read_more_border_radius) . 'px; display: inline-block; font-size:' . esc_attr($read_more_font_size) . 'px; text-decoration: none;' . $uppercase_style . '">' . esc_html($read_more_text) . '</a>';

                
                $output .= '</div>'; // Close aqm-post-item
                } // Close grid view else
            }
        }

        $output .= '</div>'; // Close aqm-post-feed
        
        // Check if there are more posts than the initial load amount
        $total_posts = wp_count_posts()->publish;
        $has_more_posts = ($total_posts > $initial_posts);
        
        // Add Load More button if enabled AND there are more posts to load
        if ($enable_load_more === 'on' && $has_more_posts) {
            
            $output .= '<div class="aqm-load-more-container" style="text-align: center; margin-top: 30px;">';
            $output .= '<button id="' . $module_id . '-load-more" class="aqm-load-more-button" style="background-color: ' . esc_attr($load_more_bg_color) . '; color: ' . esc_attr($load_more_text_color) . '; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: all 0.3s ease;">' . esc_html($load_more_text) . '</button>';
            $output .= '</div>';
            
            // Get current post ID if on a single post
            $current_post_id = is_single() ? get_the_ID() : 0;
            
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
                            title_font_family: "' . $title_font_family . '",
                            content_font_size: ' . $content_font_size . ',
                            content_color: "' . $content_color . '",
                            content_line_height: ' . $content_line_height . ',
                            content_font_family: "' . $content_font_family . '",
                            content_padding: "' . $content_padding . '",
                            meta_font_size: ' . $meta_font_size . ',
                            meta_line_height: ' . $meta_line_height . ',
                            meta_color: "' . $meta_color . '",
                            show_meta_author: "' . $show_meta_author . '",
                            show_meta_date: "' . $show_meta_date . '",
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
                            category_filter: "' . $category_filter . '",
                            exclude_archived: "' . $exclude_archived . '",
                            layout_type: "' . $layout_type . '",
                            list_title_font_size: ' . $list_title_font_size . ',
                            list_title_color: "' . $list_title_color . '",
                            list_title_hover_color: "' . $list_title_hover_color . '",
                            list_item_spacing: ' . $list_item_spacing . ',
                            list_font_family: "' . $list_font_family . '",
                            list_font_weight: "' . $list_font_weight . '",
                            list_text_transform: "' . $list_text_transform . '",
                            list_line_height: ' . $list_line_height . ',
                            list_letter_spacing: ' . $list_letter_spacing . ',
                            current_post_id: ' . $current_post_id . '
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
        		background-size: ' . esc_attr($background_size) . '% !important;
				background-position: center; 
				background-repeat: no-repeat;
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
            #' . $module_id . '.aqm-post-feed .aqm-post-item .aqm-post-content .aqm-read-more {
                color: ' . esc_attr($read_more_color) . ' !important;
                background-color: ' . esc_attr($read_more_bg_color) . ' !important;
            }
            #' . $module_id . '.aqm-post-feed .aqm-post-item .aqm-post-content .aqm-read-more:hover {
                background-color: ' . esc_attr($read_more_hover_bg_color) . ' !important;
                color: ' . esc_attr($read_more_hover_color) . ' !important;
            }
            
            /* List view styles */
            #' . $module_id . '.aqm-list-view .aqm-list-title {
                transition: color 0.3s ease;
            }
            #' . $module_id . '.aqm-list-view .aqm-list-title:hover {
                color: ' . esc_attr($list_title_hover_color) . ' !important;
            }
            
            /* Mobile styles for background image */
            @media (max-width: 767px) {
                .aqm-post-item {
                    background-size: cover !important;
                }
                .aqm-post-item:hover {
                    background-size: cover !important;
                }
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
            
            /* List view hover effects */
            #' . $module_id . ' .aqm-list-title:hover {
                color: ' . esc_attr($list_title_hover_color) . ' !important;
            }
        </style>';

        return $output;
    }
}
new AQM_Blog_Post_Feed_Module;
