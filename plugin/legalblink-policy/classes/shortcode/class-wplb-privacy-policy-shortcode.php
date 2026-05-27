<?php

if ( ! defined( 'ABSPATH' ) ) {
    die;
}

if ( ! class_exists( 'WPLB_Privacy_Policy_Shortcode' ) ) {
    class WPLB_Privacy_Policy_Shortcode extends WPLB_Base_Shortcode
    {
        protected $policy_type = 'privacy_policy';

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
                /* translators: Title for the Privacy Policy document */
                __('Privacy Policy', 'legalblink-policy'),
                /* translators: Message displayed when Privacy Policy URL is not configured in plugin settings */
                __('Per visualizzare la Privacy Policy, configura l\'URL nelle impostazioni del plugin.', 'legalblink-policy'),
                /* translators: Fallback message for browsers that don't support iframes */
                __('Il tuo browser non supporta gli iframe. Puoi visualizzare la privacy policy qui:', 'legalblink-policy')
            );
        }
    }
}
