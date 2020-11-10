<?php
namespace WpErpSync\Shortcodes;
class Today
{
    public function register($atts, $content = null)
    {
        return 'Chaim said: Today is ' . date("d/m/Y")."!!!";
    }
    public function init()
    {
        add_shortcode('display_todays_date', array($this, 'register'));
    }
}