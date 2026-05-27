<?php

if ( ! defined( 'ABSPATH' ) ) {
    die;
}

if ( ! class_exists('WPLB_Terms_Of_Service_Shortcode') ) {
    class WPLB_Terms_Of_Service_Shortcode extends WPLB_Base_Shortcode
    {
        protected $policy_type = 'terms_of_service';

        public function __construct()
        {
            $this->tag = wplb_get_policy_shortcode_tag($this->policy_type);
            $this->aliases = wplb_get_policy_shortcode_alias_tags($this->policy_type);

            parent::__construct();
        }

        /**
         * Generate the shortcode output
         * @param array $attrs shortcode attributes
         * @param string|null $content shortcode content
         * @return string shortcode output
         */
        protected function generate_output($attrs, $content)
        {
            return $this->generate_common_output(
                $attrs,
                $content,
                $this->policy_type,
                /* translators: Title for the Terms of Service document (CGV - Condizioni Generali di Vendita) */
                __('Condizioni Generali di Vendita', 'legalblink-policy'),
                /* translators: Message displayed when Terms of Service URL is not configured in plugin settings */
                __('Per visualizzare le CGV, configura l\'URL nelle impostazioni del plugin.', 'legalblink-policy'),
                /* translators: Fallback message for browsers that don't support iframes when displaying Terms of Service */
                __('Il tuo browser non supporta gli iframe. Puoi visualizzare le CGV qui:', 'legalblink-policy')
            );
        }
    }
}
