<?php

if ( ! defined( 'ABSPATH' ) ) {
    die;
}

if ( ! class_exists( 'WPLB_Cookie_Policy_Shortcode' ) ) {
    class WPLB_Cookie_Policy_Shortcode extends WPLB_Base_Shortcode
    {
        protected $policy_type = 'cookie_policy';

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
                /* translators: Title for the Cookie Policy document */
                __('Cookie Policy', 'legalblink-policy'),
                /* translators: Message displayed when Cookie Policy URL is not configured in plugin settings */
                __('Per visualizzare la Cookie Policy, configura l\'URL nelle impostazioni del plugin.', 'legalblink-policy'),
                /* translators: Fallback message for browsers that don't support iframes */
                __('Il tuo browser non supporta gli iframe. Puoi visualizzare la cookie policy qui:', 'legalblink-policy')
            );
        }
    }
}
