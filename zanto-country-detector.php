<?php
/**
 * Plugin Name: Zanto Country Detector
 * Description: Detects the user country and shows the right user flag. Has options to redirect user to their country pages basing on the geographical part in the language locale
 * Plugin URI: http://shop.zanto.org
 * Author: Ayebare Mucunguzi Brooks
 * Author URI: http://zanto.org
 * Version: 0.1
 * Text Domain: zanto-cd
 * License: GPL2

  /**
 * Get some constants ready for paths when your plugin grows 
 * 
 */
define('ZCDP_VERSION', '0.1');
define('ZCDP_PATH', dirname(__FILE__));
define('ZCDP_PATH_INCLUDES', dirname(__FILE__) . '/inc');
define('ZCDP_FOLDER', basename(ZCDP_PATH));
define('ZCDP_URL', plugins_url() . '/' . ZCDP_FOLDER);
define('ZCDP_URL_INCLUDES', ZCDP_URL . '/inc');

/**
 * 
 * The plugin base class - the root of all WP goods!
 * 
 * @author nofearinc
 *
 */
class zcdp_Plugin_Base {

    private $user_ip = '';

    /**
     * 
     * Assign everything as a call from within the constructor
     */
    function __construct() {

        global $geo_data;
        $this->user_ip = $this->get_user_ip();
        //$this->user_ip = "2.15.255.255"; //testing
        $this->zcdp_register_settings();
        if (!class_exists('GeoIP')) {
            include(ZCDP_PATH_INCLUDES . "/geoip.inc");
        }
        $geo_data = geoip_open(ZCDP_PATH_INCLUDES . "/GeoIP.dat", GEOIP_STANDARD);

        $this->country_code = geoip_country_code_by_addr($geo_data, $this->user_ip);
        $this->country_name = geoip_country_name_by_addr($geo_data, $this->user_ip);
        geoip_close($geo_data);
        $this->settings = get_option('zcdp_setting', '');

        add_action('init', array($this, 'init'));

        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, 'zcdp_on_activate_callback');
        register_deactivation_hook(__FILE__, 'zcdp_on_deactivate_callback');

        // Translation-ready
        add_action('plugins_loaded', array($this, 'zcdp_add_textdomain'));
        add_filter('plugin_row_meta', array($this, 'plugin_support_link'), 10, 2);

        if (isset($this->settings['redirect'])) {
            add_action('wp_enqueue_scripts', array($this, 'remove_unwanted_scripts'), 15);
            require_once ZCDP_PATH_INCLUDES . '/country-redirect.php';
        }
    }

    function get_user_ip() {

        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip); // just to be safe

                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
    }

    function init() {
        if (isset($this->settings['show_in_footer'])) {
            add_action('wp_footer', array(&$this, 'language_selector_footer'), 19);
            // add_action('wp_head', array(&$icl_language_switcher, 'language_selector_footer_style'));
        }
        add_action('zcdp_lang_switcher', array($this, 'lang_switcher'));
    }

    function remove_unwanted_scripts() {
        wp_deregister_script('zanto-browser-redirect');
    }

    function plugin_support_link($links, $file) {
        if ($file == ZCDP_FOLDER . '/zanto-country-detector.php') {
            return array_merge($links, array(sprintf('<a href="http://zanto.org/support">%s</a>', __('Support', 'Zanto'))), array(sprintf('<a href="http://shop.zanto.org">%s</a>', __('Addons', 'Zanto')))
            );
        }
        return $links;
    }

    /**
     *  widget langswitcher
     */
    function lang_switcher($ls_type) {
        global $show_flag, $show_native_name, $show_translated_name;
        $languages = zwt_get_languages('skip_missing=0');

        if (!empty($languages)) {
            foreach ($languages as $lang_details) {
                if ($lang_details['active'] === 1)
                    $active_lang = $lang_details;
            }
            ?>

            <?php if ($ls_type == 'drop_down') { ?>
                <div class="lang_switcher">
                    <ul>
                        <li class="dropdown">
                            <a class="dropdown-toggle" href="#"><?php echo '<img class="drop-arrow" src="'.$this->get_flag_url().'"/>' ?> <span><?php echo $active_lang['translated_name'] ?></span></a>

                            <ul class="dropdown-menu">
                <?php
                foreach ($languages as $lang):
                    if ($lang['active'] === 1)
                        continue;
                    $lang_native = ($show_native_name) ? $lang['native_name'] : false;
                    $lang_translated = ($show_translated_name) ? $lang['translated_name'] : false;
                    ?>
                                    <li><a rel="alternate"  hreflang="<?php echo $lang['language_code'] ?>"  href="<?php echo $lang['url'] ?>">
                                    <?php echo zwt_disp_language($lang_native, $lang_translated); ?>
                                        </a></li>
                                        <?php endforeach; ?>
                            </ul>

                        </li>
                    </ul>
                </div>

            <?php } else { ?>
                <div class="zwt_<?php echo $ls_type ?>">
                    <ul class="zwt_ls_list">
					<li><a  href="#"><?php echo '<img class="countr_flag" src="'.$this->get_flag_url().'"/>' ?> </a></li>

                <?php
                foreach ($languages as $lang):
                    if ($ls_type == 'drop_down') {
                        if ($lang['active'] === 1)
                            continue;
                    }
                    $lang_native = ($show_native_name) ? $lang['native_name'] : false;
                    $lang_translated = ($show_translated_name) ? $lang['translated_name'] : false;
                    ?>       
                            <li>
                                <a rel="alternate" hreflang="<?php echo $lang['language_code'] ?>" style="padding-left:5px" href="<?php echo $lang['url'] ?>">
                            <?php echo zwt_disp_language($lang_native, $lang_translated); ?>
                                </a>
                            </li>
                <?php endforeach; ?>                      
                    </ul>
                    <div style="clear:both"></div>

                </div>

            <?php
            }
        }
    }

    function language_selector_footer() {
        global $show_flag, $show_native_name, $show_translated_name;

        $languages = zwt_get_languages('skip_missing=0');
        if (!empty($languages)) {
            // This is used in display of the footer Language Switcher
            ?>
            <div id="lang_sel_footer">
                <ul>
				<li><a  href="#"><?php echo '<img style="vertical-align:inherit" class="countr_flag" src="'.$this->get_flag_url().'"/>' ?> </a></li>
            <?php foreach ($languages as $lang) { ?>
                        <li>
                            <a style="padding-left:5px" rel="alternate" 
                               hreflang="<?php echo $lang['language_code'] ?>" 
                               href="<?php echo apply_filters('zcdp_filter_link', $lang['url'], $lang) ?>" class="<?php echo ($lang['active']) ? 'lang_sel_sel' : 'lang_sel'; ?>">

                <?php
                $lang_native = ($show_native_name) ? $lang['native_name'] : false;

                $lang_translated = ($show_translated_name) ? $lang['translated_name'] : false;

                echo zwt_disp_language($lang_native, $lang_translated);
                ?>
                            </a>
                        </li>

                            <?php } ?>
                </ul>

            </div>
            <?php
        }
    }

    function get_flag_url() {
	    $flag_url='';
		
        if ($this->country_code) {
		
		$flag_locale_map=array('BG'=>'bg_BG','CZ'=>'cs_CZ','DK'=>'da_DK','DE'=>'de_DE','US'=>'en_US','ES'=>'es_ES',
                              'IR'=>'fa_IR','FI'=>'fi_FI','FR'=>'fr_FR','IL'=>'he_IL','HU'=>'hu_HU','ID'=>'id_ID',
                              'IS'=>'is_IS','IT'=>'it_IT','NO'=>'nb_NO','PT'=>'pt_PT','RO'=>'ro_RO','RU'=>'ru_RU',
                              'SK'=>'sk_SK','RS'=>'sr_RS','SE'=>'sv_SE','AU'=>'uk_AU','UA'=>'uk_UA','UZ'=>'uz_UZ',
                              'CN'=>'zh_CN');
            
        
		$flag = $this->country_code;
		if(array_key_exists($flag, $flag_locale_map)){
		 $flag = $flag_locale_map[$this->country_code];
		}else{
		$flag=strtolower($flag);
		}
        $flag_url = GTP_PLUGIN_URL . 'images/flags/' . $flag . '.png';
		}
       return apply_filters('zcdp_get_flag', $flag_url, $this->country_code );

    }

    /**
     * Initialize the Settings class
     * 
     * Register a settings section with a field for a secure WordPress admin option creation.
     * 
     */
    function zcdp_register_settings() {
        require_once( ZCDP_PATH . '/zcd-plugin-settings.class.php' );
        new ZCD_Plugin_Settings();
    }

    /**
     * Add textdomain for plugin
     */
    function zcdp_add_textdomain() {
        load_plugin_textdomain('mudbase', false, dirname(plugin_basename(__FILE__)) . '/lang/');
    }

}

class Country_Detector_Widget extends WP_Widget {

    function __construct() {
        $widget_ops = array(
            'classname' => 'zcdp_ls_widget_class',
            'description' => __('Country language Switcher.', 'zanto-cd')
        );
        $this->WP_Widget('zcdp_multilingual_ls', __('Zanto Country Detector Switcher', 'zanto-cd'), $widget_ops);
    }

    function form($instance) {
        $defaults = array(
            'title' => __('Choose Language', 'zanto-cd'),
            'lang_switcher_type' => ''
        );

        $zcdp_ls_types = array(
            'drop_down' => __('drop down menu ', 'zanto-cd'),
            'horizontal' => __('horizontal list ', 'zanto-cd'),
        );

        $instance = wp_parse_args((array) $instance, $defaults);
        $title = strip_tags($instance['title']);
        $ls_type = $instance['lang_switcher_type'];
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>
        <p>
            <label for="<?php echo $this->get_field_id('lang_switcher_type'); ?>"><?php _e('Type:'); ?></label>
            <select name="<?php echo $this->get_field_name('lang_switcher_type'); ?>" id="<?php echo $this->get_field_id('lang_switcher_type'); ?>" class="widefat">
        <?php foreach ($zcdp_ls_types as $type => $description): ?>
                    <option value="<?php echo $type ?>"<?php selected($instance['lang_switcher_type'], $type); ?>><?php echo $type ?></option>
        <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['lang_switcher_type'] = $new_instance['lang_switcher_type'];
        return $instance;
    }

    function widget($args, $instance) {
        extract($args);
        $ls_type = $instance['lang_switcher_type'];
        echo $before_widget;
        $title = apply_filters('widget_title', $instance['title']);
        if (!empty($title)) {
            echo $before_title . $title . $after_title;
        }

        do_action('zcdp_lang_switcher', $ls_type);

        echo $after_widget;
    }

}

function zcdp_widgets_init() {
    register_widget('Country_Detector_Widget');
}

add_action('widgets_init', 'zcdp_widgets_init');

/**
 * Register activation hook
 *
 */
function zcdp_on_activate_callback() {
    // do something on activation
}

/**
 * Register deactivation hook
 *
 */
function zcdp_on_deactivate_callback() {
    // do something when deactivated
}

// Initialize everything
$zcdp_plugin_base = new zcdp_Plugin_Base();
