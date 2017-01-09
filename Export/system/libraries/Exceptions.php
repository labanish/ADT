<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Exceptions.php at CodeIgniter - Free PHP Code</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="Description" content="View Exceptions.php source code at CodeIgniter online." />
        <meta name="keywords" content="php scripts, php projects, php tips, php web hosting, php software, php source, php code" />
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
        <script type="text/javascript" src="/media/js/syntaxhighlighter/scripts/shCore.js"></script>
        <script type="text/javascript" src="/media/js/syntaxhighlighter/scripts/shBrushPhp.js"></script>
        <link type="text/css" rel="stylesheet" href="/media/js/syntaxhighlighter/styles/shCore.css"/>
        <link type="text/css" rel="stylesheet" href="/media/js/syntaxhighlighter/styles/shThemeDefault.css"/>
        <script type="text/javascript">
            SyntaxHighlighter.config.clipboardSwf = '/media/js/syntaxhighlighter/scripts/clipboard.swf';
            SyntaxHighlighter.all();
        </script>
        <style type="text/css">
            * {
                margin: 0;
                padding: 0;
            }
            body {
                color: #2e2e2e;
                font-family: Tahoma, Geneva, sans-serif;
                font-size: 14px;
                line-height: 18px;
                background-color: #FFF;
            }
            #wrapper {
                width: 100%;
                margin: 0 auto;
                background-color: #FFF;
            }
            .source_title {
                font-size: 12px;
                text-indent: 5px;
                border-bottom-width: 2px;
                border-bottom-style: solid;
                border-bottom-color: #CCC;
                height: 22px;
                padding-top: 6px;
                padding-right: 2px;
                padding-left: 2px;
                margin-bottom: 4px;
                background-color: #EEE;
            }
            .status {
                font-size: 12px;
                text-indent: 5px;
                border-top-width: 2px;
                border-top-style: solid;
                border-top-color: #CCC;
                height: 22px;
                padding-top: 6px;
                padding-right: 6px;
                padding-left: 2px;
                margin-top: 4px;
                text-align: right;
                background-color: #EEE;
            }
            .title {
                text-transform: capitalize;
            }
            pre {
                margin: 0px;
                padding: 0px;
            }
            .txtads {
                text-align:left;
                float:left;
                background-image: url(/media/images/icon_ad.gif);
                background-repeat: no-repeat;
                background-position: 2px 5px;
                padding-left: 22px;
            }

        </style>
    </head>
    <body>
        <div id="wrapper">
            <div class="source_title"> Location:  <a href="http://www.phpkode.com/" target="_parent">PHPKode</a> &gt; <a href="http://www.phpkode.com/projects/" target="_parent" class="title">projects</a> &gt; <a href="http://www.phpkode.com/projects/item/codeigniter/" target="_parent">CodeIgniter</a> &gt; CodeIgniter_1.7.2/system/libraries/Exceptions.php</div>
            <div style="height: 530px; overflow: auto;position:relative;"><pre class="brush: php; ">&lt;?php  if ( ! defined(&#39;BASEPATH&#39;)) exit(&#39;No direct script access allowed&#39;);
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2010, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Exceptions Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Exceptions
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/exceptions.html
 */
class CI_Exceptions {
	var $action;
	var $severity;
	var $message;
	var $filename;
	var $line;
	var $ob_level;

	var $levels = array(
						E_ERROR				=&gt;	&#39;Error&#39;,
						E_WARNING			=&gt;	&#39;Warning&#39;,
						E_PARSE				=&gt;	&#39;Parsing Error&#39;,
						E_NOTICE			=&gt;	&#39;Notice&#39;,
						E_CORE_ERROR		=&gt;	&#39;Core Error&#39;,
						E_CORE_WARNING		=&gt;	&#39;Core Warning&#39;,
						E_COMPILE_ERROR		=&gt;	&#39;Compile Error&#39;,
						E_COMPILE_WARNING	=&gt;	&#39;Compile Warning&#39;,
						E_USER_ERROR		=&gt;	&#39;User Error&#39;,
						E_USER_WARNING		=&gt;	&#39;User Warning&#39;,
						E_USER_NOTICE		=&gt;	&#39;User Notice&#39;,
						E_STRICT			=&gt;	&#39;Runtime Notice&#39;
					);


	/**
	 * Constructor
	 *
	 */	
	function CI_Exceptions()
	{
		$this-&gt;ob_level = ob_get_level();
		// Note:  Do not log messages from this constructor.
	}
  	
	// --------------------------------------------------------------------

	/**
	 * Exception Logger
	 *
	 * This function logs PHP generated error messages
	 *
	 * @access	private
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	function log_exception($severity, $message, $filepath, $line)
	{	
		$severity = ( ! isset($this-&gt;levels[$severity])) ? $severity : $this-&gt;levels[$severity];
		
		log_message(&#39;error&#39;, &#39;Severity: &#39;.$severity.&#39;  --&gt; &#39;.$message. &#39; &#39;.$filepath.&#39; &#39;.$line, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * 404 Page Not Found Handler
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	function show_404($page = &#39;&#39;)
	{	
		$heading = &quot;404 Page Not Found&quot;;
		$message = &quot;The page you requested was not found.&quot;;

		log_message(&#39;error&#39;, &#39;404 Page Not Found --&gt; &#39;.$page);
		echo $this-&gt;show_error($heading, $message, &#39;error_404&#39;, 404);
		exit;
	}
  	
	// --------------------------------------------------------------------

	/**
	 * General Error Page
	 *
	 * This function takes an error message as input
	 * (either as a string or an array) and displays
	 * it using the specified template.
	 *
	 * @access	private
	 * @param	string	the heading
	 * @param	string	the message
	 * @param	string	the template name
	 * @return	string
	 */
	function show_error($heading, $message, $template = &#39;error_general&#39;, $status_code = 500)
	{
		set_status_header($status_code);
		
		$message = &#39;&lt;p&gt;&#39;.implode(&#39;&lt;/p&gt;&lt;p&gt;&#39;, ( ! is_array($message)) ? array($message) : $message).&#39;&lt;/p&gt;&#39;;

		if (ob_get_level() &gt; $this-&gt;ob_level + 1)
		{
			ob_end_flush();	
		}
		ob_start();
		include(APPPATH.&#39;errors/&#39;.$template.EXT);
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

	// --------------------------------------------------------------------

	/**
	 * Native PHP error handler
	 *
	 * @access	private
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	function show_php_error($severity, $message, $filepath, $line)
	{	
		$severity = ( ! isset($this-&gt;levels[$severity])) ? $severity : $this-&gt;levels[$severity];
	
		$filepath = str_replace(&quot;\\&quot;, &quot;/&quot;, $filepath);
		
		// For safety reasons we do not show the full file path
		if (FALSE !== strpos($filepath, &#39;/&#39;))
		{
			$x = explode(&#39;/&#39;, $filepath);
			$filepath = $x[count($x)-2].&#39;/&#39;.end($x);
		}
		
		if (ob_get_level() &gt; $this-&gt;ob_level + 1)
		{
			ob_end_flush();	
		}
		ob_start();
		include(APPPATH.&#39;errors/error_php&#39;.EXT);
		$buffer = ob_get_contents();
		ob_end_clean();
		echo $buffer;
	}


}
// END Exceptions Class

/* End of file Exceptions.php */
/* Location: ./system/libraries/Exceptions.php */</pre></div>
            <div class="status"><div class="txtads"><a href="http://phpnewsletter.org/download.html" target="_blank">100% Free PHP Newsletter Script! Download now!</a></div><div style="float:right;">Return current item: <a href="http://www.phpkode.com/projects/item/codeigniter/" target="_parent">CodeIgniter</a></div></div>
        </div>
        <script type="text/javascript">
            var _gaq = _gaq || [];
            _gaq.push(['_setAccount', 'UA-18505574-1']);
            _gaq.push(['_setDomainName', '.phpkode.com']);
            _gaq.push(['_trackPageview']);
            (function() {
                var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
            })();
        </script>
    </body>
</html>
