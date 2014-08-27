<?php
/**
 * PHP 5
 *
 * LucidFrame : Simple & Flexible PHP Development
 * Copyright (c), LucidFrame.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @package     LC.helpers 
 * @author		Sithu K. <cithukyaw@gmail.com>
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 */
 
/**
 * @access	private
 * ob_start callback function to output buffer
 * It also adds the conditional IE comments and class (ie6,...ie10..) to <html> 
 * @hook 	__flush() at app/helpers/utility_helper.php 
 * @param	string $buffer The ouput buffer
 * @return 	string
 */ 
function _flush($buffer, $mode){	
	# Add IE-specific class to the <html> tag
	$pattern = '/(<html.*class="([^"]*)"[^>]*>)/i';
	if(preg_match($pattern, $buffer)){
		$buffer = preg_replace_callback($pattern, '_htmlIEFix', $buffer);	
	}else{
		$replace = '<!--[if !IE]><!--><html$1 class="'._lang().'"><!--<![endif]-->	
		<!--[if IE 6]><html$1 class="ie ie6 '._lang().'"><![endif]-->
		<!--[if IE 7]><html$1 class="ie ie7 '._lang().'"><![endif]-->
		<!--[if IE 8]><html$1 class="ie ie8 '._lang().'"><![endif]-->
		<!--[if IE 9]><html$1 class="ie ie9 '._lang().'"><![endif]-->
		<!--[if gte IE 10]> <html$1 class="ie ie10 '._lang().'"> <![endif]-->';
		$buffer = preg_replace('/<html([^>]*)>/i', $replace, $buffer);
	}
	
	if(function_exists('__flush')) return __flush($buffer, $mode); # run the hook if any
	return $buffer;
}
/**
 * @access private 
 * This function is a callback for preg_replace_callback()
 * It adds the conditional IE comments and class (ie6,...ie10..) to <html> 
 * @return string
 */ 
function _htmlIEFix($matches) {
	$find 	 = 'class="'.$matches[2].'"';	
	$replace = 'class="'.$matches[2].' '._lang().'"';
	$tag   	 = str_replace($find, $replace, $matches[1]);	
	$html 	 = '<!--[if !IE]><!-->'.$tag.'<!--<![endif]-->';	
	$i = 0;
	$versions = range(6, 10);
	foreach($versions as $v){
		$replace = 'class="'.$matches[2].' ie ie'.$v.' '._lang().'"';	
		$tag   	 = str_replace($find, $replace, $matches[1]);				
		if($i == count($versions)-1) $html .= "<!--[if gte IE {$v}]>$tag<![endif]-->\n";		
		else $html .= "<!--[if IE {$v}]>$tag<![endif]-->\n";	
		$i++;
	}
	return $html;
} 
/**
 * Declare global JS variables
 * @hook __script() at app/helpers/utility_helper.php
 * @return void
 */ 
function _script(){
	$sitewideWarnings = _cfg('sitewideWarnings');
	?>
	<script type="text/javascript">
		var LC = {};
        <?php if(WEB_ROOT){ ?>
            var WEB_ROOT = '<?php echo WEB_ROOT; ?>';
			LC.root = WEB_ROOT;
        <?php } ?>
        <?php if(WEB_APP_ROOT){ ?>
            var WEB_APP_ROOT = '<?php echo WEB_APP_ROOT; ?>';
			LC.appRoot = WEB_ROOT;
        <?php } ?>		
		LC.self 		= '<?php echo _url(); ?>';
		LC.lang 		= '<?php echo _lang(); ?>';
		LC.baseURL 		= '<?php echo _cfg('baseURL'); ?>/';		
		LC.route		= '<?php echo _r(); ?>';
		LC.namespace	= '<?php echo LC_NAMESPACE; ?>';
		LC.sitewideWarnings = <?php echo (json_encode($sitewideWarnings)); ?>;
		<?php if(function_exists('__script')) __script(); ?>
    </script>
    <?php
}
/**
 * JS file include helper
 *
 * @param string $file An absolute file path or just file name
 *		The file name only will be prepended the folder name js/ and it will be looked in every sub-sites "js" folder
 *
 * @return void
 */
function _js($file){
	if( strpos($file, 'http') === 0 ){
		echo '<script src="'. $file .'" type="text/javascript"></script>';
		return;
	}	
	$file = 'js/'.$file;
	$file = _i($file);
	if( strpos($file, 'http') === 0 ){
		echo '<script src="'. $file .'" type="text/javascript"></script>';
	}else{
		echo '<script src="'. WEB_ROOT . 'js/' . $file .'" type="text/javascript"></script>';
	}
}
/**
 * CSS file include helper
 *
 * @param string $file An absolute file path or file name only
 *		The file name only will be prepended the folder name css/ and it will be looked in every sub-sites "css" folder
 *
 * @return void 
 */
function _css($file){
	if( strpos($file, 'http') === 0 ){
		echo '<link href="'. $file .'" rel="stylesheet" type="text/css" />';
		return;
	}
	$file = 'css/'.$file;
	$file = _i($file);
	if( strpos($file, 'http') === 0 ){
		echo '<link href="'. $file .'" rel="stylesheet" type="text/css" />';
	}else{
		echo '<link href="'. WEB_ROOT . 'css/' . $file .'" rel="stylesheet" type="text/css" />';
	}
}
/**
 * Get the absolute image file name
 *
 * @param string $file An image file name only (no need directory path)
 *
 * @return void 
 */
function _img($file){
	return WEB_ROOT . 'images/' . $file;
}

if(!function_exists('_pr')){
/**
 * Convenience method for print_r.
 * Displays information about a variable in a way that's readable by humans. 
 * If given a string, integer or float, the value itself will be printed. If given an array, values will be presented in a format that shows keys and elements.
 * 
 * @param $input mixed The variable to debug
 * @param $pre boolean True to print using <pre>, otherwise False
 *
 * @return void
 */
	function _pr($input, $pre=true){
		if($pre) echo '<pre>';
		if(is_array($input) || is_object($input)){ 
			print_r($input); 
		}else{ 
			if(is_bool($input)) var_dump($input);
			else echo $input;
			if($pre == false) echo '<br>';
		}
		if($pre) echo '</pre>';
	}
}
/**
 * Convenience method to get/set a config variable without declaration global
 * within a function
 *
 * @param string $key The config variable name without prefix
 * $param mixed $value The value to set to the config variable
 * @return mixed The value of the config variable
 */
function _cfg($key='', $value=''){
	if(strrpos($key, 'lc_') === 0) $key = substr($key, 3);
	if(count(func_get_args()) == 2 && $key){ 
		if(is_array($GLOBALS['lc_'.$key])) $GLOBALS['lc_'.$key][] = $value;
		else $GLOBALS['lc_'.$key] = $value;
	}
	if(isset($GLOBALS['lc_'.$key]) && $GLOBALS['lc_'.$key]) return $GLOBALS['lc_'.$key];
	return NULL;
}
/**
 * Convenience method for htmlspecialchars.
 *
 * @param string $text Text to wrap through htmlspecialchars.
 * @return string Wrapped text
 */
function _h($text){		  
	return htmlspecialchars(stripslashes($text), ENT_QUOTES); # ENT_QUOTES will convert both double and single quotes.
}
/*
 * Get the current site language code
 * @return string
 */
function _lang(){
	global $lc_lang;
	return $lc_lang;
}
/*
 * Get the current site language code by converting dash (URL-friendly) to underscore (db-friendly)
 * @return string
 */
function _queryLang($lang=NULL){
	global $lc_lang;
	if(!$lang) $lang = $lc_lang;
	return str_replace('-', '_', $lang);	
}
/*
 * Get the current site language code by converting underscore (db-friendly) to dash (URL-friendly)
 * @return string
 */
function _urlLang($lang=NULL){
	global $lc_lang;
	if(!$lang) $lang = $lc_lang;
	return str_replace('_', '-', $lang);	
}
/*
 * Get the default site language code by converting dash to underscore
 * @return string
 */
function _defaultQueryLang(){
	global $lc_defaultLang;
	return str_replace('-', '_', $lc_defaultLang);	
}
/*
 * Get the current site language name of the given language code
 * If no given code, return the default language name
 * @return string
 */
function _langName($lang=''){
	if(!_multilingual()) return '';
	global $lc_languages;
	$lang = str_replace('_', '-', $lang);	
	if(isset($lc_languages[$lang])) return $lc_languages[$lang];
	else return $lc_languages[_cfg('defaultLang')];
}
/*
 * Get the current site is multi-lingual or not
 * @return boolean
 */
function _multilingual(){
	if(_cfg('languages')){
		return (count(_cfg('languages')) > 1) ? true : false; 
	}else{
		return false;
	}
}
/*
 * Get the current routing path
 * For example, example.com/foo/bar would return foo/bar
 *	example.com/en/foo/bar would also return foo/bar
 *
 * @return string
 */
function _r(){
	return route_path();
}
/*
 * Get the server protocol
 * For example, http, https, ftp, etc.
 *
 * @return string
 */
function _protocol(){
	$protocol = current(explode('/', $_SERVER['SERVER_PROTOCOL']));	
	return strtolower($protocol);
}
/*
 * Check SSL or not
 *
 * @return boolean TRUE if https otherwise FALSE
 */
function _ssl(){
	$protocol = _protocol();
	return ($protocol == 'https') ? true : false;
}
/**
 * Get the absolute URL path
 * @param string 	$path		Routing path such as "foo/bar"; NULL for the current path
 * @param array 	$queryStr	Query string as array(
 *									$value1, // no key here
 *									'key1' => $value2,
 *									'key3' => $value3 or array($value3, $value4)
 *								 )
  * @param string	$lang		Languague code to be prepended to $path such as "en/foo/bar". It will be useful for site language switch redirect
 */
function _url($path=NULL, $queryStr=array(), $lang=''){
	return route_url($path, $queryStr, $lang);
}
/**
 * Header redirect to a specific location
 * @param string 	$path		Routing path such as "foo/bar"; NULL for the current path
 * @param array 	$queryStr	Query string as array(
 *									$value1, // no key here
 *									'key1' => $value2,
 *									'key3' => $value3 or array($value3, $value4)
 *							 	)
 * @param string 	$lang		Languague code to be prepended to $path such as "en/foo/bar". It will be useful for site language switch redirect 
 */
function _redirect($path=NULL, $queryStr=array(), $lang=''){
	$url = route_url($path, $queryStr, $lang);
	header('Location: ' . $url);
	exit;
}
/**
 * Header redirect to 404 page
 */
function _page404(){
	if(LC_NAMESPACE) _redirect(LC_NAMESPACE.'/404');
	else _redirect('404');
}
/**
 * Setter for canonical URL if the argument is given and print the canonical link tag if the argument is not given
 * @param string $url The specific URL
 * @return void
 */
$lc_canonical = '';
function _canonical($url=NULL){
	global $lc_canonical;
	if(!is_null($url)) $lc_canonical = $url;
	else{
		return (_cfg('canonical')) ? _cfg('canonical') : _url();
	}
}
/**
 * Print hreflang for language and regional URLs
 */
function _hreflang(){
	global $lc_languages;
	if(_multilingual()){ ?>
	<?php foreach($lc_languages as $hrefLang => $langDesc) {?>
		<?php 
		if(_canonical() == _url()){
			$alternate = _url('', NULL, $hrefLang);
			$xdefault  = _url('', NULL, false);			
		}else{
			$alternate = preg_replace('/\/'._lang().'\b/', '/'.$hrefLang, _canonical());
			$xdefault  = preg_replace('/\/'._lang().'\b/', '', _canonical());
		}
		?>
		<link rel="alternate" hreflang="<?php echo $hrefLang; ?>" href="<?php echo $alternate; ?>" />
	<?php } ?>
	<link rel="alternate" href="<?php echo $xdefault; ?>" hreflang="x-default" />
	<?php }
}
/**
 * Return a component of the current path.
 * When viewing a page at the path "foo/bar", for example, arg(0) returns "foo" and arg(1) returns "bar"

 * @param $index
 *   The index of the component, where each component is separated by a '/'
 *   (forward-slash), and where the first component has an index of 0 (zero).
 * @param $path
 *   A path to break into components. Defaults to the path of the current page.
 *
 * @return
 *   The component specified by $index, or NULL if the specified component was
 *   not found. If called without arguments, it returns an array containing all
 *   the components of the current path.
 */
function _arg($index = NULL, $path = NULL) {		
	if(isset($_GET[$index])){
		return _get($_GET[$index]);
	}	
	
	if(is_null($path)) $path = route_path();
	$arguments = explode('/', $path);

	if(is_numeric($index)){
		if (!isset($index)) {
			return $arguments;
		}
		if (isset($arguments[$index])) {
			return strip_tags(trim($arguments[$index]));
		}
	}
	elseif(is_string($index)){
		$query = '-' . $index . '/';
		$pos = strpos($path, $query);
		if($pos !== false){
			$start 	= $pos + strlen($query);
			$path 	= substr($path, $start);
			$end 	= strpos($path, '/-');
			if($end) $path 	= substr($path, 0, $end);
			if(substr_count($path, '/')){
				return explode('/', $path);
			}else{
				return $path;
			}
		}
	}
	elseif(is_null($index)){
		return explode('/', str_replace('/-', '/', $path));
	}
	
	return '';
}
/**
 * Check if the URI has a language code and return it
 * @return mixed The 2-letter language code if it has one, otherwise return FALSE
 */
function _getLangInURI(){
	global $lc_baseURL;
	global $lc_languages;
	
	if( !is_array($lc_languages) ){
		$lc_languages = array('en' => 'English');
	}
		
	$request_path = urldecode(strtok($_SERVER['REQUEST_URI'], '?'));
	$request_path = str_replace($lc_baseURL, '', ltrim($request_path, '/'));
	$request_path = ltrim($request_path, '/');
	$regex = '/\b('.implode('|', array_keys($lc_languages)).'){1}\b(\/?)/i';
	if(preg_match($regex, $_SERVER['REQUEST_URI'], $matches)){
		return $matches[1];
	}
	return false;
}
/**
 * Validate that a hostname (for example $_SERVER['HTTP_HOST']) is safe.
 *
 * @param 	string $host The host name
 * @return 	boolean TRUE if only containing valid characters, or FALSE otherwise.
 */
function _validHost($host) {
  return preg_match('/^\[?(?:[a-zA-Z0-9-:\]_]+\.?)+$/', $host);
}
/**
 * Get the page title glued by a separator
 *
 * @param 	string|array
 * @return 	string The formatted page title
 */
function _title(){
	global $lc_siteName;
	global $lc_titleSeparator;
	$args = func_get_args();
		
	if(count($args) == 0){ 
		return $lc_siteName;
	}
	if(count($args) == 1){
		if(is_array($args[0])){
			$args = _filterArrayEmpty($args[0]); 
			$title = $args;
		}else{ 
			$title = ($args[0]) ? array($args[0]) : array();
		}
	}else{
		$args = _filterArrayEmpty($args);
		$title = $args;
	}
	
	$lc_titleSeparator = trim($lc_titleSeparator);
	if($lc_titleSeparator) $lc_titleSeparator = ' '.$lc_titleSeparator.' ';
	else $lc_titleSeparator = ' ';
	
	if(count($title)){ 
		$title = implode($lc_titleSeparator, $title);
		if($lc_siteName) $title .= ' | '.$lc_siteName;
		return $title;
	}
	return $lc_siteName;
}
/**
 * Filters elements of an array which have empty values
 *
 * @param 	array $input The input array
 * @return	array The filtered array
 */
function _filterArrayEmpty($input){
	return array_filter($input, '_notEmpty');
}
/**
 * Check the given value is not empty
 *
 * @param 	string $value The value to be checked
 * @return 	boolean TRUE if not empty; FALSE if empty
 */
function _notEmpty($value){
	$value = trim($value);
	return ($value !== '') ? true : false;
}
/**
 * Generate breadcrumb by a separator
 *
 * @param 	string|array
 * @return 	string The formatted page title
 */
function _breadcrumb(){
	global $lc_breadcrumbSeparator;
	$args = func_get_args();
	if(!$lc_breadcrumbSeparator) $lc_breadcrumbSeparator = '&raquo;';
	if(count($args) == 1 && is_array($args[0])){
		$args = $args[0];
	}
	echo implode(" {$lc_breadcrumbSeparator} ", $args);
}
/**
 * Shorten a string for the given length
 *
 * @param string  $str A plain text string to be shorten
 * @param integer $length The character count
 * @param boolean $trail To append "..." or not. NULL to not show
 *
 * @return string The shortent text string
 */ 
function _shorten($str, $length=50, $trail='...'){
	$str = strip_tags(trim($str));
	if(strlen($str) <= $length) return $str;
	$short = trim(substr($str, 0, $length));
	$lastSpacePos = strrpos($short, ' ');	
	if($lastSpacePos !== false){
		$short = substr($short, 0, $lastSpacePos);		
	}
	if($trail) $short = rtrim($short, '.').$trail;
	return $short;
}
/**
 * Format a string
 *
 * @param string $value A text string to be formatted
 * @param string $glue The glue string between each element
 * @param string $lastGlue The glue string between the last two elements
 *
 * @return string The formatted text string
 */ 
if(!function_exists('_fstr')){
	function _fstr($value, $glue=', ', $lastGlue='and'){
		global $lc_nullFill;
		if(!is_array($value)){
			return ($value == '') ? $lc_nullFill : nl2br($value);
		}elseif(is_array($value) && sizeof($value) > 1){
			$last 			= array_slice($value, -2, 2);
			$lastImplode 	= implode(' '.$lastGlue.' ', $last);
			$first 			= array_slice($value, 0, sizeof($value)-2);
			$firstImplode 	= implode($glue, $first);
			$finalImplode 	= ($firstImplode)? $firstImplode.$glue.$lastImplode : $lastImplode;
			return $finalImplode;
		}else{
			return nl2br($value[0]);
		}
	}
}
/**
 * Format a number
 *
 * @param int 	 $value A number to be formatted
 * @param int	 $decimal The decimal places. Default is 2.
 * @param string $unit The unit appended to the number (optional)
 *
 * @return string The formatted number
 */ 
if(!function_exists('_fnum')){
	function _fnum($value, $decimals=2, $unit=''){
		global $lc_nullFill;
		if($value === ''){
			return $lc_nullFill;
		}elseif(is_numeric($value)){
			$value = number_format($value, $decimals, '.', ',');
			if(!empty($unit)){
				return $value.' '.$unit;
			}else{
				return $value;
			}
		}
		return $value;
	}
}
/**
 * Format a number in a smarter way, i.e., decimal places are omitted where necessary. 
 * Given the 2 decimal places, the value 5.00 will be shown 5 whereas the value 5.01 will be shown as it is.
 *
 * @param int 	 $value A number to be formatted
 * @param int	 $decimal The decimal places. Default is 2.
 * @param string $unit The unit appended to the number (optional)
 *
 * @return string The formatted number
 */ 
if(!function_exists('_fnumSmart')){
	function _fnumSmart($value, $decimals=2, $unit=''){
		global $lc_nullFill;
		$value = _fnum($value, $decimals, $unit);
		$v = explode('.', $value);		
		if($decimals > 0 && isset($v[1])){
			if(preg_match('/0{'.$decimals.'}/i', $v[1])){ 
				$value = $v[0];
			}
		}
		return $value;
	}
}
/**
 * Remove the number formatting (e.g., thousand separator) from the given number
 *
 * @param  mixed $num A number to remove the formatting
 * @return mixed The number
 */ 

if(!function_exists('_fnumReverse')){
	function _fnumReverse($num){
		return str_replace(',', '', $num);
	}
}
/**
 * Format a date
 *
 * @param  string $date A date to be formatted
 * @param  string $format The date/time format; The config variable will be used if it is not passed
 * @return string The formatted date
 */ 
if(!function_exists('_fdate')){
	function _fdate($date, $format=''){
		if(!$format) $format = _cfg('dateFormat');
		if(is_string($date)) return date($format, strtotime($date));
		else return date($format, $date);
	}
}
/**
 * Format a date/time
 *
 * @param 	string $date A date/time to be formatted
 * @param	string $format The date/time format; The config variable will be used if it is not passed
 * @return 	string The formatted date/time
 */ 
if(!function_exists('_fdatetime')){
	function _fdatetime($dateTime, $format=''){		
		if(!$format) $format = _cfg('dateTimeFormat');
		return date($format, strtotime($dateTime));
	}
}
/**
* Display elapsed time in wording
* 
* @param timestamp|string 	$time	The elapsed time in unix timestamp or date/time string
* @param string 			$format The date/time format to show when 4 days passed	
* @return string
*/
if(!function_exists('_ftimeAgo')){
	function _ftimeAgo($time, $format = 'M j Y'){
		$now = time();
		if(!is_numeric($time)) $time = strtotime($time);
		
		$secElapsed = $now - $time;
		if($secElapsed <= 60){		
			return _t('just now');
		}
		elseif($secElapsed <= 3540){
			$min = $now - $time;
			$min = round($min/60);		
			return _t('%d minutes ago', $min);
		}
		elseif($secElapsed <= 3660 ){
			return _t('1 hour ago');
		}
		elseif(date('j-n-y', $now) == date('j-n-y', $time)){
			return date("g:i a", $time);
		}
		elseif(date('j-n-y', mktime(0, 0, 0, date('n', $now),date('j', $now)-1, date('Y', $now))) == date('j-n-y',$time)){
			return _t('yesterday');	
		}
		elseif($secElapsed <= 345600 ){
			return date('l', $time);
		}
		else{
			return date($format, $time);
		}
	}
}
/**
 * Print or return the message formatted with HTML
 *
 * @param  	mixed 	$msg A message string or Array of message strings
 * @param	string 	$class The CSS class name
 * @param	bool	$return What is expected to return from this function:
 *						NULL - no return; just print it
 *						html - return HTML
 * @param	string	$display Display the message on the spot or not
 *
 * @return 	string 	The formatted date
 */ 
if(!function_exists('_msg')){
	function _msg($msg, $class='error', $return=NULL, $display='display:block'){
		if(empty($msg)) $html = '';
		if(empty($class)) $class = 'error';
		$return = strtolower($return);
		$html = '<div class="message';
		if($class) $html .= ' '.$class.'"';
		$html .= ' style="'.$display.'">';		
		if(is_array($msg)){
			if(count($msg) > 0){
				$html .= '<ul>';
				foreach($msg as $m){
					if(is_string($m)) $html .= '<li>'.$m.'</li>';
					elseif(is_array($msg) && isset($m['msg'])) $html .= '<li>'.$m['msg'].'</li>';
				}
				$html .= '</ul>';				
			}else{
				$html = '';
			}
		}else{
			$html .= $msg;
		}
		if($html) $html .= '</div>';
		
		if($return == 'html' || $return === true){ 
			return $html;
		}else{ 
			echo $html;
		}
	}
}
/*
 * Find the size of the given file.
 * @param string 	$file File name or path to a file
 * @param int 		$precision Digits to display after decimal
 * @param array		$sizes Array of size units, e.g., array("TB","GB","MB","KB","B"). Default is array("MB","KB","B")
 * @return string|bool Size (B, KiB, MiB, GiB, TiB, PiB, EiB, ZiB, YiB) or boolean
 */
function _filesize($file, $digits = 2, $sizes = array("MB","KB","B")) {
	if (is_file($file)) {
		$filePath = $file;
		if (!realpath($filePath)) {
			$filePath = $_SERVER["DOCUMENT_ROOT"].$filePath;
       	}
		$filePath = $file;
		$fileSize = filesize($filePath);
		$total = count($sizes);
		while ($total-- && $fileSize > 1024) {
			$fileSize /= 1024;
		}
		return round($fileSize, $digits)." ".$sizes[$total];
	}
	return false;
}

if(!function_exists('_randomCode')){
/**
 * Generate a random string from the given array of letters.
 * @param	int 	$length	The length of required random string 
 * @param	array 	$letters Array of letters from which randomized string is derived from. Default is a to z and 0 to 9.
 * @return	string 	Random string of requried length
 */ 
	function _randomCode($length=5, $letters = array()){
		# Letters & Numbers for default
		if ( sizeof($letters) == 0 ) { 
			$letters = array_merge(range(0, 9), range('a', 'z'));
		}
		
		shuffle($letters); # Shuffle letters	
		$randArr = array_splice($letters, 0, $length);
		
		return implode('', $randArr);
	}
}

if(!function_exists('_slug')){
/**
 * Generate a slug of human-readable keywords
 * 
 * @param string 		$string 	Text to slug
 * @param string 		$table 		Table name to check in. If it is empty, no check in the table
 * @param string|array	$condition 	Condition to append table check-in, e.g, 'fieldName != value' or array('fieldName !=' => value)
 *
 * @return string The slug
 */
	function _slug($string, $table='', $condition=NULL){
		$specChars = array('`','~','!','@','#','$','%','\^','&','*','(',')','=','+','x','{','}','[',']',':',';',"'",'"','<','>','\\','|','?','/',',');
		$table 	= ltrim($table, db_prefix());
		$slug 	= strtolower(trim($string));
		$slug 	= trim($slug, '-');	
		# clear special characters
		$slug 	= preg_replace('/(&amp;|&quot;|&#039;|&lt;|&gt;)/i', '', $slug);
		$slug 	= str_replace($specChars, '', $slug); 
		$slug 	= str_replace(array(' ', '.'), '-', $slug);
		
		if(is_array($condition)){
			$condition = db_condition($condition);
		}
		
		while(1 && $table){
			$sql = 'SELECT slug FROM '.$table.' WHERE slug = ":alias"';
			if($condition) $sql .= ' AND '.$condition;
			if($result = db_query($sql, array(':alias' => $slug))){
				if(db_numRows($result) == 0) break;			
				$segments = explode('-', $slug);
				if( sizeof($segments) > 1 && is_numeric($segments[sizeof($segments)-1]) ){
					$index = array_pop($segments);
					$index++;
				}else{
					$index = 1;
				}
				$segments[] = $index;			
				$slug = implode('-', $segments);
			}
		}
		$slug = preg_replace('/[\-]+/', '-', $slug);
		return trim($slug, '-');	
	}
}
/**
 * Return the SQL date (Y-m-d) from the given date and format
 *
 * @param string $date Date to convert
 * @param string $givenFormat Format for the given date
 * @param string $separator Separator in the date. Default is dash "-"
 *
 * @return string the SQL date string if the given date is valid, otherwise NULL
 */
function _sqlDate($date, $givenFormat='dmy', $separator='-'){
	$dt 	= explode($separator, $date);
	$format = str_split($givenFormat);
	$ft 	= array_flip($format);
	
	$y = $dt[$ft['y']];
	$m = $dt[$ft['m']];
	$d = $dt[$ft['d']];
	
	if(checkdate($m, $d, $y)){
		return $y.'-'.$m.'-'.$d;
	}else{
		return NULL;
	}
}
/**
 * Encrypts the given text using security salt if mcrypt extension is enabled, otherwise using md5()
 *
 * @param  string $text Text to be encrypted
 * @return string The encrypted text
 */
function _encrypt($text){
	global $lc_securitySalt;
	if(!$lc_securitySalt || !function_exists('mcrypt_encrypt')) return md5($text);
	return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $lc_securitySalt, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
}
/**
 * Decrypts the given text using security salt if mcrypt extension is enabled, otherwise return the original encrypted string
 *
 * @param 	string $text Text to be decrypted
 * @return 	string The decrypted text
 */
function _decrypt($text){
	global $lc_securitySalt;
	if(!$lc_securitySalt || !function_exists('mcrypt_encrypt')) return $text;
	return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $lc_securitySalt, base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
}

$_meta = array();
/**
 * Simple quick helper function for <meta> tag attribute values
 *
 * @param  string $key 		The <meta> tag name
 * @param  string $value 	If the value is empty, this is a Getter fuction; otherwise Setter function
 * @return string The content
 */
function _meta($key, $value=''){
	global $_meta;
	$value = trim($value);
	if(empty($value)){
		if(isset($_meta[$key])) return $_meta[$key];
		else return '';		
	}else{
		if(in_array($key, array('description', 'og:description', 'twitter:description'))){
			$value = trim(substr($value, 0, 200));
		}
		$_meta[$key] = $value;
	}
}
/**
 * Simple mail helper function
 *
 * @param string $from 		The sender of the mail
 * @param string $to 		The receiver or receivers of the mail 
 * @param string $subject 	Subject of the email to be sent. 
 * @param string $message 	Message to be sent
 * @param string $cc 		The CC receiver or receivers of the mail 
 * @param string $bcc 		The Bcc receiver or receivers of the mail  
 * 	The formatting of $from, $to, $cc and $bcc must comply with RFC 2822. Some examples are: 
 * 		user@example.com 
 * 		user@example.com, anotheruser@example.com 
 * 		User <user@example.com> 
 * 		User <user@example.com>, Another User <anotheruser@example.com>  
 *  
 * @return boolean Returns TRUE if the mail was successfully accepted for delivery, FALSE otherwise
 */
function _mail($from, $to, $subject='', $message='', $cc='', $bcc=''){
	$charset = mb_detect_encoding($message);	
	$message = nl2br(stripslashes($message));
	
	$EEOL = PHP_EOL; //"\n";	
	$headers  = 'From: ' . $from . $EEOL;	
	$headers .= 'MIME-Version: 1.0' . $EEOL;
	$headers .= 'Content-type: text/html; charset=' . $charset  . $EEOL;	
	$headers .= 'Reply-To: ' . $from . $EEOL;
	$headers .= 'Return-Path:'.$from . $EEOL;
	if($cc){
		$headers .= 'Cc: ' . $cc . $EEOL;
	}
	if($bcc){
		$headers .= 'Bcc: ' . $bcc . $EEOL;
	}
	$headers .= 'X-Mailer: PHP';
				
	return mail($to, $subject, $message, $headers);
}
/**
 * Get translation strings from the POST array 
 * and prepare to insert or update into the table according to the specified fields
 *
 * @param array  $post The POST array
 * @param array  $fields The array of field name and input name mapping, e.g., array('fieldName' => 'inputName')
 * @param string $lang The language code to fetch (if it is not provided, all languages will be fetched)
 *
 * @return array The data array
 */
function _postTranslationStrings($post, $fields, $lang=NULL){
	global $lc_defaultLang;
	global $lc_languages;
	$data = array();
	foreach($fields as $key => $name){
		if($lang){
			$lcode = _queryLang($lang);
			if(isset($post[$name.'_'.$lcode])){
				$data[$key.'_'.$lcode] = $post[$name.'_'.$lcode];
			}			
		}else{
			if(isset($post[$name])) $data[$key.'_'._defaultLang()] = $post[$name];
			foreach($lc_languages as $lcode => $lname){
				$lcode = _queryLang($lcode);
				if(isset($post[$name.'_'.$lcode])){
					$data[$key.'_'.$lcode] = $post[$name.'_'.$lcode];
				}
			}
		}
	}
	return $data;	
}
/**
 * Get translation strings from the query result
 * and return the array of $i18n[fieldName][lang] = $value
 *
 * @param object|array $data The query result
 * @param array|string $fields The array of field names to get data, e.g., 'fieldName' or array('fieldName1', 'fieldName2')
 * @param string $lang The language code to fetch (if it is not provided, all languages will be fetched)
 * 
 * @return array The array of translation strings
 */
function _getTranslationStrings($data, $fields, $lang=NULL){
	global $lc_defaultLang;
	global $lc_languages;
	$data = (array)$data;
	$i18n = array();
	if(is_string($fields)) $fields = array($fields);
	foreach($fields as $name){
		if($lang){
			$lcode = _queryLang($lang);
			if(isset($data[$name.'_'.$lcode])){
				$i18n[$name.'_i18n'][$lcode] = $data[$name.'_'.$lcode];
			}			
		}else{		
			foreach($lc_languages as $lcode => $lname){
				$lcode = _queryLang($lcode);
				if(isset($data[$name.'_'.$lcode])){
					$i18n[$name.'_i18n'][$lcode] = $data[$name.'_'.$lcode];
				}
			}
		}
	}
	return $i18n;	
}