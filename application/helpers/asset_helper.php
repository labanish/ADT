<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Load JS
 * Creates the <script> tag that links all requested js files
 * @access  public
 * @param   array
 * @return  string
 */
if (!function_exists('load_js'))
{
    function load_js(array $files, $version = '')
    {
        //check if base url has a trailing slash, if not append it
        $base_url = substr(base_url(), -1) == '/' ? base_url() : base_url() . '/';
        foreach ($files as $index => $file)
        {
            //replace all backslash occurrences with tilde (~) to avoid URI confusion (gets converted back later in controller)
            $files[$index] = str_replace('/', '~', $file);
        }
        return '<script type="text/javascript" src="' . $base_url . 'loader/js/' . implode('|', $files) . '/' . $version . '"></script>';
    }
}

/**
 * Load CSS
 * Creates the <link> tag that links all requested css files
 * @access  public
 * @param   array
 * @return  string
 */
if (!function_exists('load_css'))
{
    function load_css(array $files, $version = '')
    {
        //check if base url has a trailing slash, if not append it
        $base_url = substr(base_url(), -1) == '/' ? base_url() : base_url() . '/';
        foreach ($files as $index => $file)
        {
            //replace all backslash occurrences with tilde (~) to avoid URI confusion (gets converted back later in controller)
            $files[$index] = str_replace('/', '~', $file);
        }
        return '<link type="text/css" rel="stylesheet" href="' . $base_url . 'loader/css/' . implode('|', $files) . '/' . $version . '" />';
    }
}


/* End of file asset_helper.php */
/* Location: ./system/application/helpers/asset_helper.php */