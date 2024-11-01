<?php

class ZCD_Plugin_Settings {

    private $zcdp_setting;

    public function __construct() {

        $this->zcdp_setting = get_option('zcdp_setting', '');

        add_action('zwt_menu_main_end', array($this, 'admin_interface'));
        add_action('zwt_stgs_post_save', array($this, 'save_stgs'));

        add_action('admin_init', array($this, 'register_settings'));
    }

    public function admin_interface() {
        if (!is_array($this->zcdp_setting)) {
            $this->zcdp_setting = array();
        }
        ?>

        <tr>
            <th scope="row"><?php _e('Country Detector Addon Options', 'zanto-cd') ?></th>
            <td>
                <fieldset><legend class="screen-reader-text"><span><?php _e('Country Detector Addon Options', 'zanto-cd') ?></span></legend>
                    <label title="<?php _e('Disable browser Language Re-direct', 'zanto-cd') ?>">
                        <input  type="radio" name="zcdp_country_lang_redct" value="0" <?php if (!isset($this->zcdp_setting['redirect']) || empty($this->zcdp_setting))
            echo 'checked="checked"' ?> />
                        <span><?php _e('Disable country Language Re-direct', 'zanto-cd') ?> </span>
                    </label>
                    <br/>
                    <label title="<?php _e('Re-direct visitors to browser language if translation exists', 'zanto-cd') ?>">
                        <input  type="radio" name="zcdp_country_lang_redct" value="1" <?php checked($this->zcdp_setting['redirect'], '1') ?> />
                        <span><?php _e('Redirect visitors based on visitor country only if user locale translation exist', 'zanto-cd') ?> </span>
                    </label>
                    <br/>
                    <label title="<?php _e('Always redirect visitors based on browser language', 'zanto-cd') ?>">
                        <input  type="radio" name="zcdp_country_lang_redct" value="2"  <?php checked($this->zcdp_setting['redirect'], '2') ?> />
                        <span><?php _e('Always redirect visitors based on user country (redirect to home page if user locale translations are missing)', 'zanto-cd') ?> </span>
                    </label>
                    <br/>
                    <label title="<?php _e('Enable Footer Switcher with user country flag', 'zanto-cd') ?>">
                        <input  type="checkbox" name="zcdp_enable_country_footer" value="1"  <?php checked($this->zcdp_setting['show_in_footer']) ?> />
                        <span><?php _e('Enable Footer Switcher with user country flag', 'zanto-cd') ?> </span>
                    </label>
                    <p><a href="http://zanto.org/?p=61"><?php _e('Documentation on country detection and redirect', 'zanto-cd') ?></a>.</p>
            </td>
        </tr>

        <?php
    }

    public function register_settings() {
        register_setting('zcdp_setting', 'zcdp_setting', array($this, 'zcdp_validate_settings'));
    }

    function save_stgs($post) { //since the save button already has nonce attached to it by Zanto, there seems to be no need for another
        $settings = array();

        if (isset($post['zcdp_enable_country_footer'])) {
            $settings['show_in_footer'] = true;
        }
        if (isset($post['zcdp_country_lang_redct'])) {
            if ($post['zcdp_country_lang_redct'] == '1') {
                $settings['redirect'] = 1;
            } elseif ($post['zcdp_country_lang_redct'] == '2') {
                $settings['redirect'] = 2;
            }
        }
        update_option('zcdp_setting', $settings);
		$this->zcdp_setting = $settings;
    }

    /**
     * Helper Settings function if you need a setting from the outside.
     * @return boolean is enabled
     */
    public function is_enabled($option) {
        if (!empty($this->zcdp_setting[$option]) && isset($this->zcdp_setting[$option])) {
            return true;
        }
        return false;
    }

    /**
     * Validate Settings
     * 
     * Filter the submitted data as per your request and return the array
     * 
     * @param array $input
     */
    public function zcdp_validate_settings($input) {

        return $input;
    }

}