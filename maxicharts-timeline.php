<?php

/*
 * Plugin Name: MaxiCharts TimeLine
 * Plugin URI: https://maxicharts.com/
 * Description: Use Time Line inside Wordpress to display csv events, or blog posts history
 * Author: MaxiCharts
 * Version: 1.2.0
 * Author URI: https://maxicharts.com/
 */
if (! defined('ABSPATH')) {
    exit(); // Exit if accessed directly
}

if (! class_exists('MAXICHARTSAPI')) {
    define('MAXICHARTS_PLUGIN_PATH', plugin_dir_path(__DIR__));
    $toInclude = MAXICHARTS_PLUGIN_PATH . '/maxicharts/mcharts_utils.php';
    if (file_exists($toInclude)) {
        include_once ($toInclude);
    }
}

function MCTL_timeline_log($message)
{
    if (WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}

class MCTL_MaxiCharts_TimeLine
{

    public static $instance = NULL;

    private $timeline_post_name = 'Timeline Event';

    protected $timeline_event_post_id = 'mchartstl_event';

    private $menu_position = 5;

    public static $MCTL_timeline_logger = NULL;

    static function MCTL_getLogger($logger_category = "MCTIMELINE")
    {
        if (class_exists('MAXICHARTSAPI')) {
            return MAXICHARTSAPI::getLogger($logger_category);
        }
    }

    public function __construct()
    {
        if (! class_exists('MAXICHARTSAPI')) {
            $msg = __('Please install MaxiCharts before');
            return $msg;
        }
        self::MCTL_getLogger()->debug("Adding Module : " . __CLASS__);
        
        self::MCTL_getLogger()->debug("Create MaxiCharts Timeline Class");
        add_action('init', array(
            $this,
            'create_timeline_event_type'
        ));
        
        add_shortcode('maxicharts_timeline_csv', array(
            $this,
            'MCTL_csv_fn'
        ));
        
        add_shortcode('maxicharts_timeline_events', array(
            $this,
            'MCTL_mchartstl_event_fn'
        ));
        
        add_shortcode('maxicharts_timeline_post', array(
            $this,
            'MCTL_post_fn'
        ));
        
        add_shortcode('maxicharts_timeline_gravity_flow', array(
            $this,
            'MCTL_gf_fn'
        ));
        
        // tries to extract all possible dates inside html page and attach event descriptions to it
        add_shortcode('maxicharts_timeline_html', array(
            $this,
            'MCTL_html_fn'
        ));
        
        add_action('wp_enqueue_scripts', array(
            $this,
            'MCTL_frontend_stylesheet'
        ));
        
        add_action('wp_ajax_maxicharts_get_posts_list', array(
            $this,
            'getPostsList'
        ));
        
        add_action('wp_ajax_nopriv_maxicharts_get_posts_list', array(
            $this,
            'getPostsList'
        ));
        
        // get gravity flow events maxicharts_get_gf_activity
        add_action('wp_ajax_maxicharts_get_gf_activity', array(
            $this,
            'getGFActivityEventsList'
        ));
        
        add_action('wp_ajax_nopriv_maxicharts_get_gf_activity', array(
            $this,
            'getGFActivityEventsList'
        ));
    }

    function create_timeline_event_type()
    {
        self::MCTL_getLogger()->debug("create_timeline_event_type");
        $labels = array(
            'name' => '' . $this->timeline_post_name . 's',
            'singular_name' => '' . $this->timeline_post_name,
            'menu_name' => '' . $this->timeline_post_name . 's',
            'namemin_bar' => '' . $this->timeline_post_name,
            'add_new' => 'Add New',
            'add_new_item' => 'Add New ' . $this->timeline_post_name,
            'new_item' => 'New ' . $this->timeline_post_name,
            'edit_item' => 'Edit ' . $this->timeline_post_name,
            'view_item' => 'View ' . $this->timeline_post_name,
            'all_items' => 'All ' . $this->timeline_post_name . 's',
            'search_items' => 'Search ' . $this->timeline_post_name . 's',
            'parent_item_colon' => 'Parent ' . $this->timeline_post_name,
            'not_found' => 'No ' . $this->timeline_post_name . 's Found',
            'not_found_in_trash' => 'No ' . $this->timeline_post_name . 's Found in Trash'
        );
        
        /*
         * public => false, has_archive => false, publicaly_queryable => false, and query_var => false
         */
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'exclude_from_search' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_nav_menus' => true,
            'show_in_menu' => true,
            'show_inmin_bar' => true,
            'menu_position' => $this->menu_position,
            'menu_icon' => 'dashicons-calendar-alt',
            'capability_type' => 'post',
            // 'capability_type' => 'practitioner',
            'hierarchical' => false,
            'supports' => array(
                'title',
                'editor',
                'author',
                'thumbnail',
                'excerpt',
                'comments'
            ),
            'has_archive' => false,
            'rewrite' => array(
                'slug' => 'medapp'
            ),
            'query_var' => false
        );
        
        register_post_type($this->timeline_event_post_id, $args);
    }

    public function maybe_add_date_intervals($interval, $date_interval)
    {
        if ($date_interval->y > 0) {
            $years_format = _n('%d year', '%d years', $date_interval->y, 'gravityflow');
            $interval[] = esc_html(sprintf($years_format, $date_interval->y));
        }
        if ($date_interval->m > 0) {
            $months_format = _n('%d month', '%d months', $date_interval->m, 'gravityflow');
            $interval[] = esc_html(sprintf($months_format, $date_interval->m));
        }
        if ($date_interval->d > 0) {
            $days_format = esc_html__('%dd', 'gravityflow');
            $interval[] = sprintf($days_format, $date_interval->d);
        }
        
        return $interval;
    }

    public function maybe_add_time_intervals($interval, $date_interval)
    {
        if ($date_interval->h > 0) {
            $hours_format = esc_html__('%dh', 'gravityflow');
            $interval[] = sprintf($hours_format, $date_interval->h);
        }
        if ($date_interval->d == 0 && $date_interval->h == 0) {
            if ($date_interval->i > 0) {
                $minutes_format = esc_html__('%dm', 'gravityflow');
                $interval[] = sprintf($minutes_format, $date_interval->i);
            }
            if ($date_interval->s > 0) {
                $seconds_format = esc_html__('%ds', 'gravityflow');
                $interval[] = sprintf($seconds_format, $date_interval->s);
            }
        }
        
        return $interval;
    }

    public function format_duration($seconds)
    {
        if (method_exists('DateTime', 'diff')) {
            $dtF = new DateTime('@0');
            $dtT = new DateTime("@$seconds");
            $date_interval = $dtF->diff($dtT);
            $interval = array();
            
            $interval = $this->maybe_add_date_intervals($interval, $date_interval);
            
            if ($date_interval->y == 0 && $date_interval->m == 0) {
                $interval = $this->maybe_add_time_intervals($interval, $date_interval);
            }
            
            return join(', ', $interval);
        } else {
            return esc_html($seconds);
        }
    }

    function getGFActivityEventsList()
    {
        self::MCTL_getLogger()->info("getGFActivityEventsList");
        self::MCTL_getLogger()->info($_POST);
        $filtered_form_ids = sanitize_text_field($_POST['form_id']);
        $form_ids_array = explode(',', $filtered_form_ids);
        $filtered_entry_ids = sanitize_text_field($_POST['entry_id']);
        $entry_ids_array = explode(',', $filtered_entry_ids);
        $max_entries = sanitize_text_field($_POST['max_entries']);
        self::MCTL_getLogger()->info($form_ids_array);
        self::MCTL_getLogger()->info($entry_ids_array);
        self::MCTL_getLogger()->info($max_entries);
        $list = array();
        if (class_exists('Gravity_Flow_Activity') && class_exists('GFAPI')) {
            // public static function get_events( $limit = 400, $objects = array( 'workflow', 'step', 'assignee' ) ) {
            
            $events = Gravity_Flow_Activity::get_events($max_entries);
            if (sizeof($events) > 0) {
                foreach ($events as $event) {
                    if (! in_array($event->form_id, $form_ids_array)) {
                        self::MCTL_getLogger()->debug("Skips form id " . $event->form_id);
                        continue;
                    }
                    if (! in_array($event->lead_id, $entry_ids_array)) {
                        self::MCTL_getLogger()->debug("Skips entry id " . $event->lead_id);
                        continue;
                    }
                    self::MCTL_getLogger()->info($event);
                    $start = $event->date_updated;
                    $content = $event->log_object;
                    $title = $event->log_event;
                    
                    $form = GFAPI::get_form($event->form_id);
                    $base_url = $args['detail_base_url'];
                    $url_entry = $base_url . sprintf('&id=%d&lid=%d', $event->form_id, $event->lead_id);
                    $url_entry = esc_url_raw($url_entry);
                    $link = "<a href='%s'>%s</a>";
                    
                    $event_id = esc_html($event->id);
                    $event_date = esc_html(GFCommon::format_date($event->date_created));
                    $event_title = sprintf($link, $url_entry, $form['title']);
                    $event_entry_id = sprintf($link, $url_entry, $event->lead_id);
                    $event_type = esc_html($event->log_object);
                    $event_display = '';
                    
                    switch ($event->log_object) {
                        case 'workflow':
                            $event_display = $event->log_event;
                            break;
                        case 'step':
                            $event_display = esc_html($event->log_event);
                            break;
                        case 'assignee':
                            $event_display = esc_html($event->display_name) . ' <i class="fa fa-arrow-right"></i> ' . esc_html($event->log_value);
                            break;
                        default:
                            $event_display = esc_html($event->log_value);
                    }
                    
                    $event_step = '';
                    if ($event->feed_id) {
                        $step = gravity_flow()->get_step($event->feed_id);
                        if ($step) {
                            $step_name = $step->get_name();
                            $event_step = esc_html($step_name);
                        }
                    }
                    $event_duration = '';
                    
                    if (! empty($event->duration)) {
                        
                        $event_duration = self::format_duration($event->duration);
                    }
                    $event_end = null;
                    $startdatetime = $end = new DateTime($start);
                    if ($event->duration > 0) {
                        $i = DateInterval::createFromDateString($event->duration . ' seconds');
                        $end->add($i);
                        $event_end = $end->format('Y-m-d H:i:s');
                        // $end = $start + $event->duration;
                    }
                    $itemContent = $event_step . ' - ' . $event_display;
                    $itemTitle = $event_step . ' - ' . $start . ' -> ' . $event_end . ' / ' . $event_title;
                    $group = $event->log_object;
                    if ($event_end) {
                        $list[] = array(
                            // 'id' => $post->ID,
                            'start' => $start,
                            'end' => $event_end,
                            'content' => $itemContent,
                            'title' => $itemTitle,
                            'group' => $group
                        
                        );
                    } else {
                        $list[] = array(
                            
                            'start' => $start,
                            
                            'content' => $itemContent,
                            'title' => $itemTitle,
                            'group' => $group
                        
                        );
                    }
                }
            }
        } else {
            self::MCTL_getLogger()->error("Please install Gravity Flow first");
        }
        
        if (empty($list)){
            self::MCTL_getLogger()->warn("No item retrieved with parameters : ");
            self::MCTL_getLogger()->warn($_POST);
        }
        self::MCTL_getLogger()->debug($list);
        wp_send_json($list);
    }

    function getPostsList()
    {
        self::MCTL_getLogger()->info("getPostsList");
        self::MCTL_getLogger()->info($_POST);
        $list = array();
        $post_type = sanitize_text_field($_POST['post_type']);
        // if ($post_type == 'both')
        $args = array(
            'numberposts' => - 1,
            'category' => 0,
            'orderby' => 'date',
            'order' => 'DESC',
            'include' => array(),
            'exclude' => array(),
            'meta_key' => '',
            'meta_value' => '',
            'post_type' => $post_type,
            'suppress_filters' => true
        );
        
        if (is_multisite()) {
            $blog_id = get_current_blog_id();
            switch_to_blog($blog_id);
            // $forms = GFAPI::get_forms ();
            $posts = get_posts($args);
            restore_current_blog();
        } else {
            $posts = get_posts($args);
            // $forms = GFAPI::get_forms ();
        }
        self::MCTL_getLogger()->info(count($posts) . " posts retrieved");
        foreach ($posts as $post) {
            self::MCTL_getLogger()->info("add new item : " . $post->ID);
            
            $start_date = $post->post_date;
            self::MCTL_getLogger()->debug("Fetch post of type " . $post->post_type . ' | ' . $this->timeline_event_post_id);
            if ($post->post_type == $this->timeline_event_post_id) {
                
                $start_date = get_field("start", $post->ID);
                $tooltipContent = $start_date . ' | ' . $post->post_content;
                if (has_post_thumbnail($post->ID)) {
                    $featuredImage = get_the_post_thumbnail($post->ID, 'thumbnail');
                    $tooltipContent = $featuredImage . $tooltipContent;
                }
                
                $end = get_field("end", $post->ID);
                // self::MCTL_getLogger()->debug($end);
                if (empty($end)) {
                    $list[] = array(
                        'id' => $post->ID,
                        'start' => $start_date,
                        'content' => $post->post_title,
                        'title' => $tooltipContent,
                        'group' => get_field("group", $post->ID)
                    
                    );
                } else {
                    $list[] = array(
                        'id' => $post->ID,
                        'start' => $start_date,
                        'end' => $end,
                        'content' => $post->post_title,
                        'title' => $tooltipContent,
                        'group' => get_field("group", $post->ID)
                    
                    );
                }
            } else {
                /*
                 * Member Variable Variable Type Notes
                 * ID int The ID of the post
                 * post_author string The post author's user ID (numeric string)
                 * post_name string The post's slug
                 * post_type string See Post Types
                 * post_title string The title of the post
                 * post_date string Format: 0000-00-00 00:00:00
                 * post_date_gmt string Format: 0000-00-00 00:00:00
                 * post_content string The full content of the post
                 * post_excerpt string User-defined post excerpt
                 * post_status string See get_post_status for values
                 * comment_status string Returns: { open, closed }
                 * ping_status string Returns: { open, closed }
                 * post_password string Returns empty string if no password
                 * post_parent int Parent Post ID (default 0)
                 * post_modified string Format: 0000-00-00 00:00:00
                 * post_modified_gmt string Format: 0000-00-00 00:00:00
                 * comment_count string Number of comments on post (numeric string)
                 * menu_order string Order value as set through page-attribute when enabled (numeric string. Defaults to 0)
                 */
                $list[] = array(
                    'id' => $post->ID,
                    'start' => $start_date,
                    // 'end' => '',
                    'content' => $post->post_title,
                    'title' => $start_date . ' : ' . $post->post_title,
                    'group' => $post->post_status
                
                );
            }
        }
        
        // self::MCTL_getLogger()->debug($list);
        self::MCTL_getLogger()->debug($list);
        wp_send_json($list);
    }

    public function MCTL_mchartstl_event_fn($attributes)
    {
        if (! isset($attributes['type']) || empty($attributes['type'])) {
            $attributes['type'] = $this->timeline_event_post_id;
            /*
             * if (empty($attributes['groups'])) {
             * $attributes['groups'] = "publish";
             * }
             */
        }
        
        return $this->MCTL_post_fn($attributes);
    }

    public function MCTL_post_fn($attributes)
    {
        if (! isset($attributes['type']) || $attributes['type'] != $this->timeline_event_post_id || empty($attributes['type'])) {
            $attributes['type'] = 'post';
            if (empty($attributes['groups'])) {
                $attributes['groups'] = "publish";
            }
        }
        return $this->MCTL_csv_fn($attributes);
    }

    public function MCTL_csv_fn($attributes)
    {
        $result = "";
        $defaultData = plugins_url('/data/french_attacks.csv', __FILE__);
        $a = shortcode_atts(array(
            'type' => '', // post, page, CPT, both
            'category' => '',
            'data_path' => '',
            'separator' => ';',
            'delimiter' => '"',
            'show_buttons' => true,
            'color_set' => '',
            'colors' => '',
            'color_rand' => '',
            'width' => '100%',
            'height' => '500px',
            'groups' => ''
        ), $attributes);
        
        self::MCTL_getLogger()->debug($a);
        $type = isset($a['type']) ? $a['type'] : '';
        $category = isset($a['category']) ? $a['category'] : '';
        $colors = isset($a['colors']) ? str_replace(' ', '', $a['colors']) : '';
        $color_set = isset($a['color_set']) ? str_replace(' ', '', $a['color_set']) : '';
        $color_rand = isset($a['color_rand']) ? str_replace(' ', '', $a['color_rand']) : '';
        $show_buttons = isset($a['show_buttons']) ? boolval($a['show_buttons']) : true;
        $data_path = isset($a['data_path']) ? $a['data_path'] : '';
        $separator = isset($a['separator']) ? $a['separator'] : '';
        $delimiter = isset($a['delimiter']) ? $a['delimiter'] : '';
        $height = isset($a['height']) ? $a['height'] : '';
        $width = isset($a['width']) ? $a['width'] : '';
        $groups = isset($a['groups']) ? $a['groups'] : '';
        
        self::MCTL_getLogger()->debug($color_set);
        
        $finalColors = apply_filters('mcharts_modify_colors', $color_set, $color_rand, $colors);
        
        self::MCTL_getLogger()->debug($finalColors);
        
        $this->MCTL_enqueue_scripts();
        $timelineKey = 'timeline-frontend-js';
        wp_localize_script($timelineKey, 'maxicharts_timeline_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            // 'post_type' => $type,
            'height' => $height,
            'width' => $width,
            'groups' => $groups,
            'colors' => $finalColors,
            'all' => __('Show All')
        ));
        
        $buttons = '<input type="button" id="lastMonth" value="' . __('Last Month') . '"><br>
  <input type="button" id="lastYear" value="' . __('Last year') . '"><br>
  <input type="button" id="lastFiveYears" value="' . __('Last 5 years') . '"><br>
  <input type="button" id="fit" value="' . __('Show All', 'maxicharts-timeline') . '"><br>';
        
        $groupsArray = explode(',', $groups);
        foreach ($groupsArray as $group) {
            $buttons .= '<input type="button" id="' . $group . '" value="' . $group . '"><br>';
        }
        
        if ($show_buttons) {
            $result .= '<p class="maxicharts_timeline_button_bar">';
            $result .= $buttons;
            $result .= '</p>';
        }
        
        $result .= '<div id="visualization" data_path="' . esc_url($data_path);
        $result .= '" separator="' . esc_attr($separator);
        $result .= '" delimiter="' . esc_attr($delimiter);
        $result .= '" type="' . esc_attr($type);
        $result .= '" category="' . esc_attr($category);
        $result .= '"></div>';
        
        return $result;
    }

    public function MCTL_gf_fn($attributes)
    {
        $result = '';
        $a = shortcode_atts(array(
            'form_id' => '1',
            'entry_id' => '1',
            'type' => 'gravity_flow',
            'width' => '100%',
            'height' => '500px',
            'show_buttons' => true,
            'color_set' => '',
            'colors' => '',
            'color_rand' => '',
            'groups' => '',
            'maxentries' => 400,
        ), $attributes);
        self::MCTL_getLogger()->debug($a);
        $form_id = isset($a['form_id']) ? $a['form_id'] : '';
        $entry_id = isset($a['entry_id']) ? $a['entry_id'] : '';
        $height = isset($a['height']) ? $a['height'] : '';
        $width = isset($a['width']) ? $a['width'] : '';
        $type = isset($a['type']) ? $a['type'] : '';
        $groups = isset($a['groups']) ? $a['groups'] : '';
        $colors = isset($a['colors']) ? str_replace(' ', '', $a['colors']) : '';
        $color_set = isset($a['color_set']) ? str_replace(' ', '', $a['color_set']) : '';
        $color_rand = isset($a['color_rand']) ? str_replace(' ', '', $a['color_rand']) : '';
        $show_buttons = isset($a['show_buttons']) ? boolval($a['show_buttons']) : true;
        $maxentries = isset($a['maxentries']) ? $a['maxentries'] : '';
        $this->MCTL_enqueue_scripts();
        $finalColors = apply_filters('mcharts_modify_colors', $color_set, $color_rand, $colors);
        wp_localize_script('timeline-frontend-js', 'maxicharts_timeline_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'height' => $height,
            'width' => $width,
            'form_id' => $form_id,
            'entry_id' => $entry_id,
            'colors' => $finalColors,
            'groups' => $groups,
            'maxentries' => $maxentries
        ));
        
        $buttons = '<input type="button" id="lastMonth" value="' . __('Last Month') . '"><br>
  <input type="button" id="lastYear" value="' . __('Last year') . '"><br>
  <input type="button" id="lastFiveYears" value="' . __('Last 5 years') . '"><br>
  <input type="button" id="fit" value="' . __('Show All', 'maxicharts-timeline') . '"><br>';
        /*
         * $groupsArray = explode(',', $groups);
         * foreach ($groupsArray as $group) {
         * $buttons .= '<input type="button" id="' . $group . '" value="' . $group . '"><br>';
         * }
         */
        if ($show_buttons) {
            $result .= '<p class="maxicharts_timeline_button_bar">';
            $result .= $buttons;
            $result .= '</p>';
        }
        
        $result .= '<div id="visualization" ';
        /*
         * $result .= 'data_path="' . esc_url($data_path);
         * $result .= '" separator="' . esc_attr($separator);
         * $result .= '" delimiter="' . esc_attr($delimiter);
         */
        $result .= '" type="' . esc_attr($type);
        // $result .= '" category="' . esc_attr($category);
        $result .= '"></div>';
        
        return $result;
    }

    public function MCTL_html_fn($attributes)
    {
        $defaultData = 'https://fr.wikipedia.org/wiki/Histoire_de_la_m%C3%A9decine_dentaire';
        $a = shortcode_atts(array(
            'url' => $defaultData,
            'separator' => ';',
            'width' => '100%',
            'height' => '500px'
        ), $attributes);
        self::MCTL_getLogger()->debug($a);
        $url = isset($a['url']) ? $a['url'] : '';
        $height = isset($a['height']) ? $a['height'] : '';
        $width = isset($a['width']) ? $a['width'] : '';
        $this->MCTL_enqueue_scripts();
        wp_localize_script('timeline-frontend-js', 'maxicharts_timeline_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'height' => $height,
            'width' => $width
        ));
        $buttons = '<p class="maxicharts_timeline_button_bar">
  <input type="button" id="lastMonth" value="Last month"><br>
  <input type="button" id="lastYear" value="Last year"><br>
  <input type="button" id="lastFiveYears" value="Last 5 years"><br>
  <input type="button" id="fit" value="Fit all items"><br>
            
</p>';
        
        $result = $buttons;
        
        // process url scraping
        $scriptFile = plugin_dir_path(__FILE__) . 'py/scrapeURL.py';
        self::MCTL_getLogger()->debug($scriptFile);
        $pyCmd = escapeshellcmd('python3 ' . $scriptFile . ' ' . $url);
        self::MCTL_getLogger()->debug($pyCmd);
        $output = '';
        // $retValue = shell_exec ( $pyCmd );
        exec($pyCmd, $output, $retValue);
        self::MCTL_getLogger()->debug($retValue);
        self::MCTL_getLogger()->debug($output);
        $completeUrlToFile = plugin_dir_url(__FILE__) . 'data/' . $output[0] . '.csv';
        self::MCTL_getLogger()->debug($completeUrlToFile);
        $result .= '<div id="visualization" data_path="' . esc_url($completeUrlToFile) . '" separator="' . esc_attr($separator) . '"></div>';
        
        return $result;
    }

    function MCTL_enqueue_scripts($type = null)
    {
        wp_enqueue_script('maxicharts_timeline_vis');
        // $timelineKey = 'timeline-frontend-js';
        wp_enqueue_script('timeline-frontend-js');
        
        wp_enqueue_script('timeline-jquery-csv');
        wp_enqueue_script('timeline-moment-js');
        
        wp_enqueue_style('timeline-css');
        wp_enqueue_style('timeline-vis-css');
        /*
         * if ($type == 'url'){
         * wp_enqueue_script('timeline-request-js');
         * wp_enqueue_script('timeline-cheerio-js');
         * wp_enqueue_script('timeline-chrono-js');
         * }
         */
    }

    function MCTL_frontend_stylesheet()
    {
        self::MCTL_getLogger()->debug("MCTL_frontend_stylesheet...");
        
        $timeline_vis_js = plugins_url('/libs/node_modules/vis/dist/vis.js', __FILE__);
        wp_register_script('maxicharts_timeline_vis', $timeline_vis_js);
        
        $timeline_script_js = plugins_url('/js/maxicharts-timeline-frontend.js', __FILE__);
        $timelineKey = 'timeline-frontend-js';
        self::MCTL_getLogger()->debug("+-+-+-+-+-+-");
        $pluginDeps = array(
            'jquery'
        );
        // $pluginDeps = array_merge($pluginDeps, $jsKeys);
        self::MCTL_getLogger()->debug($pluginDeps);
        self::MCTL_getLogger()->debug("+-+-+-+-+-+-");
        
        wp_register_script($timelineKey, $timeline_script_js, $pluginDeps, false);
        self::MCTL_getLogger()->debug("JS loaded : " . $timelineKey . ' ' . $timeline_script_js);
        // wp_enqueue_script('maxicharts-mc-query-builder-js');
        /*wp_localize_script($timelineKey, 'maxicharts_timeline_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));*/
        
        wp_register_style('timeline-css', plugins_url('/css/maxicharts-timeline.css', __FILE__));
        
        wp_register_style('timeline-vis-css', plugins_url('/libs/node_modules/vis/dist/vis-timeline-graph2d.min.css', __FILE__));
        self::MCTL_getLogger()->debug("...MCTL_frontend_stylesheet");
        
        // jQuery CSV
        
        // var/www/ilurn.com/wp-content/plugins/maxicharts-timeline/libs/jquery-csv-0.8.9/src
        
        // jquery.csv.min.js
        $jsquery_csv_version = '0.8.9';
        $timeline_jquery_csv_js = plugins_url('/libs/jquery-csv-' . $jsquery_csv_version . '/src/jquery.csv.min.js', __FILE__);
        wp_register_script('timeline-jquery-csv', $timeline_jquery_csv_js);
        
        // $jsmoment_version = '2.23.0';
        // $timeline_moment_js = plugins_url('/libs/node_modules/moment/min/moment.min.js', __FILE__);
        $timeline_moment_js = plugins_url('/libs/node_modules/moment/min/moment-with-locales.min.js', __FILE__);
        
        wp_register_script('timeline-moment-js', $timeline_moment_js);
        
        /*
         * // request
         * $timeline_request_js = plugins_url('/libs/node_modules/request/request.js', __FILE__);
         * wp_register_script('timeline-request-js', $timeline_request_js);
         *
         * // cheerio
         *
         * $timeline_cheerio_js = plugins_url('/libs/node_modules/cheerio/lib/cheerio.js', __FILE__);
         * wp_register_script('timeline-cheerio-js', $timeline_cheerio_js);
         *
         * //<script src="bower_components/chrono/chrono.min.js"></script>
         * $timeline_chrono_js = plugins_url('/libs/node_modules/chrono-node/chrono.min.js', __FILE__);
         * wp_register_script('timeline-chrono-js', $timeline_chrono_js);
         */
    }
}

new MCTL_MaxiCharts_TimeLine();