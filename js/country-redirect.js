jQuery(document).ready(function(){
    if(jQuery.cookie != undefined) {
        // Check if cookie are enabled
        jQuery.cookie('zanto_country_redirect_test', '1');
        var cookie_enabled = jQuery.cookie('zanto_country_redirect_test') == 1;
        jQuery.removeCookie('zanto_country_redirect_test');
        
        if (cookie_enabled) {
            var cookie_params = zanto_country_redirect_params.cookie
            var cookie_name = cookie_params.name;
            // Check if we already did a redirect
            
            if (!jQuery.cookie(cookie_name)) {
                // Get page language and browser language
                var pageLanguage = zanto_country_redirect_params.pageLanguage;
                
                countryLanguage = zanto_country_redirect_params.country_lang;
                
                // Build cookie options
                var cookie_options = {
                    expires: cookie_params.expiration / 24,
                    path: cookie_params.path? cookie_params.path : '/',
                    domain: cookie_params.domain? cookie_params.domain : ''
                };
                
                // Set the cookie so that the check is made only on the first visit
                jQuery.cookie(cookie_name, countryLanguage, cookie_options);

                // Compare page language and browser language
                if (pageLanguage != countryLanguage) {
                    var redirectUrl;
                    // First try to find the redirect url from parameters passed to javascript
                    var languageUrls = zanto_country_redirect_params.languageUrls;
                    if (languageUrls[countryLanguage] != undefined) {
                        redirectUrl = languageUrls[countryLanguage];
                    }
                    // Finally do the redirect
                    if (redirectUrl != undefined) {
                        window.location = redirectUrl;
                    }    
                }
            }
        }
    }
});