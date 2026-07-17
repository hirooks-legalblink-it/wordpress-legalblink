<?php
$file = WP_PLUGIN_DIR . '/legalblink-policy/classes/controller/api/class-wplb-auth-api-controller.php';
$code = file_get_contents($file);

$search = '    public function is_logged_in()
    {
        try {';

$replace = '    public function is_logged_in()
    {
        $auth_data = WPLB_Option_Helper::getOption(\'auth_data\');
        if (!empty($auth_data) && isset($auth_data[\'user\'][\'id\'])) {
            return $this->create_api_response(true, $auth_data);
        }
        try {';

$code = str_replace($search, $replace, $code);
file_put_contents($file, $code);
echo "PATCHED";
