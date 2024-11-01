<?php

// adapted from zanto browser redirect code/

class ZANTO_Country_Redirect {

    static function init() {
        if (!is_admin() && !isset($_GET['redirect_to']) && !preg_match('#wp-login\.php$#', preg_replace("@\?(.*)$@", '', $_SERVER['REQUEST_URI']))) {
            add_action('wp_print_scripts', array('ZANTO_Country_Redirect', 'scripts'));
        }
    }

    static function scripts() {
        global $zwt_language_switcher, $zcdp_plugin_base;

        $args['skip_missing'] = (isset($zcdp_plugin_base->settings['redirect']) && $zcdp_plugin_base->settings['redirect']==1)?0:1;

        // Build multi language urls array
        $languages = $zwt_language_switcher->get_current_ls($args);
        $language_urls = array();
        $redirect_lang = false;
        foreach ($languages as $locale=>$language) {
		
            if (strtolower($zcdp_plugin_base->country_code) == strtolower(substr($locale, 3, 2))) {
                $redirect_lang = $locale;
            }
			
			$language_urls[$locale] = $language['url'];
        }
		
		$redirect_lang = apply_filters('zcdp_redirect_lang',$redirect_lang, $languages);

        if ($redirect_lang) {
           
            wp_enqueue_script('zanto-country-redirect', ZCDP_URL . '/js/country-redirect.js', array('jquery', ZWT_Base::PREFIX . 'jquery_cookie'), GTP_ZANTO_VERSION);
			
            // Cookie parameters
            $http_host = $_SERVER['HTTP_HOST'] == 'localhost' ? '' : $_SERVER['HTTP_HOST'];
            $cookie = array(
                'name' => '_zwt_country_lang_js',
                'domain' => (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN ? COOKIE_DOMAIN : $http_host),
                'path' => (defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/'),
                'expiration' => 24 //well, assumption is that this person will stay in this country the next 24hrs
            );

            // Send params to javascript
            $params = array(
			    'ajaxurl'      => admin_url( 'admin-ajax.php', 'relative' ),
                'pageLanguage' => defined('GTP_LANGUAGE_CODE')? GTP_LANGUAGE_CODE : get_option('WPLANG'),
                'languageUrls' => $language_urls,
                'country_lang' => $redirect_lang,
                'cookie' => $cookie
            );
            wp_localize_script('zanto-country-redirect', 'zanto_country_redirect_params', $params);
			
        }
    }

}

add_action('init', array('ZANTO_Country_Redirect', 'init'));
?>
