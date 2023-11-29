<?php
class template{
    public static function create_meta(){

    }
    public static function register_entity($entity_name, $position, $content){
		if(!is_array(storage::get_instance()->{$entity_name})) storage::get_instance()->{$entity_name} = [];
		$tmp = storage::get_instance()->{$entity_name};
		if(!isset($tmp[$position])) $tmp[$position] = [];
		$tmp[$position][] = $content;
		storage::get_instance()->{$entity_name} = $tmp;
    }
	public static function register_widget($position, $content){
		return self::register_entity('widgets', $position, $content);
	}
	public static function get_widget($name){
		$db = db::get_connection(storage::get_instance()->system_config->db_configs);
		$widget = $db->select('system_widgets')->where(['widget_name'=>$name])->fetch();
		if(!$widget) return;

		$snippets = json_decode($widget['widget_content'], true);
		$pid = storage::init()->page['page_id'];

		$htz = '';
		$pln = storage::init()->system_config->plugins;
		foreach($snippets as $s){
			$s['visibility'] = explode(',', $s['visibility']);
			if($s['status'] == 'active' && (in_array($pid, $s['visibility']) or !$s['visibility'][0])){
				$htz .= $s['content'];
			}
		}
		$bbcodes = self::find_bbcode($htz);
		$replaces = [];
		foreach($bbcodes as $bbc){
			foreach($pln as $p){
				if(is_callable([$p, $bbc])){
					$bc = '{$'.$bbc.'}';
					$replaces[$bc] = call_user_func([$p, $bbc]);
				}
			}
		}
		$htz = str_replace(array_keys($replaces), array_values($replaces), $htz);
		echo $htz;

		return $htz;
	}
	public static function register_nav($position, $content){
		return self::register_entity('navigation', $position, $content);
	}
	public static function get_nav($reference){
		$host = str_replace('.','_',$_SERVER['HTTP_HOST']);
		$cache_nav = realpath(__DIR__.'/../').'/cache/nav/';
		if(!is_readable("{$cache_nav}/{$host}_nav_{$reference}.html")) self::create_nav($reference);
		$expiry = ( time() - filemtime("{$cache_nav}/{$host}_nav_{$reference}.html") ) / ( 60 * 60 * 24 ); // a day from seconds
		if($expiry > 1) self::create_nav($reference); // cache older than a day is obselete
		return file_get_contents("{$cache_nav}/{$host}_nav_{$reference}.html");
	}
	public static function get_navigation($reference){
		return self::get_nav($reference);
	}
	public static function create_nav($reference){
		$host = str_replace('.','_',$_SERVER['HTTP_HOST']);
		$cache_nav = realpath(__DIR__.'/../').'/cache/nav/';
		if(!is_readable($cache_nav)) mkdir($cache_nav, 0777, true);
		$db = db::get_connection(storage::get_instance()->system_config->db_configs);
		$pages = $db->select('pages', 'page_id, page_name, page_parent, page_special, page_icon')
					->where(['page_special'=>0])
					->or(['page_special'=>1])
					->order_by('page_order','asc')
					->fetchAll();
		$navs = self::create_array_tree($pages);
		//var_dump('<pre>',$navs);
		$nav_list = self::array_to_list($navs);
		file_put_contents("$cache_nav/{$host}_nav_$reference.html", $nav_list);
		return is_readable("$cache_nav/{$host}_nav_$reference.html");
	}
	public static function create_array_tree(array $elements, $parentId = 0) {
		$branch = [];
		foreach ($elements as &$element){
			if ($element['page_parent'] == $parentId) {
				$children = self::create_array_tree($elements, $element['page_id']);
				if ($children) {
					$element['children'] = $children;
				}
				$branch[$element['page_id']] = $element;
				unset($element);
			}
		}
		return $branch;
	}
	public static function array_to_list(array $navs, $prefix = []){
		$ul = '<ul>';
		$storage = storage::get_instance();
		foreach($navs as $li){
			$link = str_replace(' ','-',$li['page_name']);
			if($link) $prefix[] = $link;
			$previous_parent = $li['page_id'];
			if($li['page_parent'] == 0) $prefix = [$link]; // overwrite previous prefix, if any
			$link = implode('/', $prefix);
			if($li['page_special'] == 1 or $li['page_special'] == 3) {
				$link = '';
				$li['page_name'] = 'Home';
			}
			$ul .= "<li><a href=\"{$storage->install_dir}{$link}\" data-icon=\"{$li['page_icon']}\">{$li['page_name']}</a>";
			if(isset($li['children'])) $ul .= self::array_to_list($li['children'], $prefix);
			$ul .= '</li>';
			array_pop($prefix); // last element may carry wrong parent, so pop it out.
		}
		$ul .= '</ul>';
		return $ul;
	}
    public static function display(){
		function get_widget($name){
			return template::get_widget($name);
		}
        $root = realpath(__DIR__.'/../').'/themes/';
		$storage = storage::init();
		$theme = $storage->system_config->theme;
		
		if(is_readable("$root/$theme")) $root = "$root/$theme";
		else $root = "$root/Default";
		$page = $storage->page;
		if($page) extract($page);
		$conf = (array) $storage->system_config;
		$title = (!isset($page_title) or !$page_title) ? $site_name : $page_title;
		//var_dump('<pre>', $conf);
		$site_host = $_SERVER['HTTP_HOST'];
		$base = $storage->install_dir;
		extract($conf);
		if(!isset($page_template)) $page_template = 'index';
		ob_start(
			function($buffer){
				$error = error_get_last();
				$return  = '<title>Fatal error</title>';
				$return .= '<body>';
				$return .= '<h1>Fatal error: Theme has issues</h1>';
				$return .= "<p>{$error['message']}</p>";
				$return .= '</body>';
				return $return;
			}
		);
		
		//$old_reporting = error_reporting(0); // cannot trust third-parties
		$page_template = $page_template && is_readable("{$root}/{$page_template}.html") ? $page_template : 'index';
		if(is_readable("{$root}/{$page_template}.html")) include("{$root}/{$page_template}.html");
		else {
			if(is_readable("{$root}/index.html")) include "{$root}/index.html";
			else echo system::translate('theme_corrupted_or_unsupported');
		}
		//error_reporting($old_reporting); // back in track
		$html = ob_get_clean();
		
		$html = htmlspecialchars_decode(stripslashes($html));
		preg_match_all('/(\{\$)(.*?)(\})/i', $html, $pg_matches);
		if(isset($pg_matches[2])){
			$searches = $replacement = [];
			foreach($pg_matches[2] as $k=>$v){
				if(isset(${$v})) {
					$searches[] = $pg_matches[0][$k];
					$parts = explode('[', rtrim(${$v}, ']'));
					$replacement[] = ${$v};
				}
			}
		}
		$html = str_replace($searches, $replacement, $html);
		echo $html;
        $time = microtime(true) - EXEC_START;
        echo '<!-- Execution started: ', EXEC_START, ', ended: ', microtime(true), ", Execution time: $time -->";
    }
	public static function find_funcs($func_name, $files){
		$pans = [];
		$fn = $func_name;//str_replace(['s','w','d'], ['\s','\w','\d'], $func_name);

		foreach($files as $file){
			$tdata = file_get_contents($file);
			$panels = preg_match_all("|(".$fn.")\((.*)\)|", $tdata, $ps);
			$pans = array_merge($pans, $ps[2]);
		}
		//var_dump('<pre>', $pans);
		$return = [];
		foreach($pans as $pan)
		{
			$return[] = trim($pan,'\',"');
		}
		//force single occurance of data
		return array_unique($return);
	}
    public static function find_bbcode($content){
        $html = htmlspecialchars_decode(stripslashes($content));
		preg_match_all('/(\{\$)(.*?)(\})/i', $html, $pg_matches);
		$searches = [];
		if(isset($pg_matches[2])){
			foreach($pg_matches[2] as $k=>$v) $searches[] = $v;
		}
		return $searches;
    }
    public static function parse_bbcode($html){
		$return = htmlspecialchars_decode(stripslashes($html));
		preg_match_all('/(\{\$)(.*?)(\})/i', $return, $matches);
		//var_dump('<pre>',$matches);
		if($matches && isset($matches[2])){
			$conf = storage::get_data('system_config');
			foreach($matches[0] as $k=>$v){
				if(@$matches[2][$k]){
					$parts = explode('/', @$matches[2][$k]);
					if(count($parts) > 0){
						if(is_callable($parts[0])) {
							ob_start();
							$rh = $parts[0]($parts);
							ob_end_clean();
						}
						else if(isset($conf[$matches[2][$k]])) $rh = $conf[$matches[2][$k]];
						else continue;
						$html = str_replace($matches[0][$k], $rh, $html);
					}
				}
			}
		}
		return $html;
	}
	
	/////
	//template::enquire_script('css', 'main.css');
	//var_dump(storage::get_instance()->scripts->css->top);
	/////
    static public function enquire_script($type, $src, $position = 'top'){
        if(!storage::get_instance()->scripts or !is_object(storage::get_instance()->scripts)){
            storage::get_instance()->scripts = new stdClass();
        }
        if(!isset(storage::get_instance()->scripts->{$type}) or !is_object(storage::get_instance()->scripts->{$type})){
            storage::get_instance()->scripts->{$type} = new stdClass();
        }
        if(!isset(storage::get_instance()->scripts->{$type}->{$position}) or !is_array(storage::get_instance()->scripts->{$type}->{$position})){
            storage::get_instance()->scripts->{$type}->{$position} = [];
        }
        storage::get_instance()->scripts->{$type}->{$position}[] = $src;
    }
    function minify($html){
		/* $pattern = '/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/';//$pattern = '/^\h*\/\/.*$/m'; */
		$html = preg_replace('/^\h*\/\/.*$/m', '', $html); 
		$html = str_replace(array('</script>','</style>'),array('[script]','[style]'),$html);
		$return = $this->minify_html($html);
		return str_replace(array('[script]','[style]'),array('</script>','</style>'),$return);
	}
	private function __minify_x($input) {
    	return str_replace(array("\n", "\t", ' '), array(X . '\n', X . '\t', X . '\s'), $input);
	}
	private function __minify_v($input) {
    	return str_replace(array(X . '\n', X . '\t', X . '\s'), array("\n", "\t", ' '), $input);
	}
	private function _minify_html($input) {
    	return preg_replace_callback('#<\s*([^\/\s]+)\s*(?:>|(\s[^<>]+?)\s*>)#', function($m) 
		{
        	if(isset($m[2])) 
			{
            	// Minify inline CSS declaration(s)
            	if(stripos($m[2], ' style=') !== false && version_compare(PHP_VERSION,5.4,'>=')) // There is a bug with PHP <= 5.3
				{
                	$m[2] = preg_replace_callback('#( style=)([\'"]?)(.*?)\2#i', function($m) {	return $m[1] . $m[2] . $this->minify_css($m[3]) . $m[2];}, $m[2]);
            	}
            	return '<' . $m[1] . preg_replace(
                	array(
                    // From `defer="defer"`, `defer='defer'`, `defer="true"`, `defer='true'`, `defer=""` and `defer=''` to `defer` [^1]
                    '#\s(checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|ismap|loop|multiple|open|readonly|required|scoped)(?:=([\'"]?)(?:true|\1)?\2)#i',
                    // Remove extra white-space(s) between HTML attribute(s) [^2]
                    '#\s*([^\s=]+?)(=(?:\S+|([\'"]?).*?\3)|$)#',
                    // From `<img />` to `<img/>` [^3]
                    '#\s+\/$#'
                ),
                array(
                    // [^1]
                    ' $1',
                    // [^2]
                    ' $1$2',
                    // [^3]
                    '/'
                ),
            	str_replace("\n", ' ', $m[2])) . '>';
        	}
        	return '<' . $m[1] . '>';
    	}, $input);
	}
	function minify_html($input) {
    	if( !$input = trim($input)) return $input;
    	// Keep important white-space(s) after self-closing HTML tag(s)
    	$input = preg_replace('#(<(?:img|input)(?:\s[^<>]*?)?\s*\/?>)\s+#i', '$1' . X . '\s', $input);
    	// Create chunk(s) of HTML tag(s), ignored HTML group(s), HTML comment(s) and text
    	$input = preg_split('#(' . $this->CH . '|<pre(?:>|\s[^<>]*?>)[\s\S]*?<\/pre>|<code(?:>|\s[^<>]*?>)[\s\S]*?<\/code>|<script(?:>|\s[^<>]*?>)[\s\S]*?<\/script>|<style(?:>|\s[^<>]*?>)[\s\S]*?<\/style>|<textarea(?:>|\s[^<>]*?>)[\s\S]*?<\/textarea>|<[^<>]+?>)#i', $input, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    	$output = "";
    	foreach($input as $v) 
		{
        	if($v !== ' ' && trim($v) === "") continue;
        	if($v[0] === '<' && substr($v, -1) === '>') 
			{
            	if($v[1] === '!' && strpos($v, '<!--') === 0) 
				{ // HTML comment ...
                	// Remove if not detected as IE comment(s) ...
                	if(substr($v, -12) !== '<![endif]-->') continue;
                	$output .= $v;
            	} 
				else 
				{
                	$output .= $this->__minify_x($this->_minify_html($v));
            	}
        	} 
			else 
			{
            	// Force line-break with `&#10;` or `&#xa;`
            	$v = str_replace(array('&#10;', '&#xA;', '&#xa;'), X . '\n', $v);
            	// Force white-space with `&#32;` or `&#x20;`
            	$v = str_replace(array('&#32;', '&#x20;'), X . '\s', $v);
            	// Replace multiple white-space(s) with a space
            	$output .= preg_replace('#\s+#', ' ', $v);
        	}
    	}
    	// Clean up ...
    	$output = preg_replace(
        	array(
            	// Remove two or more white-space(s) between tag [^1]
            	'#>([\n\r\t]\s*|\s{2,})<#',
            	// Remove white-space(s) before tag-close [^2]
            	'#\s+(<\/[^\s]+?>)#'
        	),
        	array(
            	// [^1]
            	'><',
            	// [^2]
            	'$1'
        	),
    	$output);
    	$output = $this->__minify_v($output);
		// The next preg_replace is troublesome, hence for now let's stop here, till next time
		return $output;
    	// Remove white-space(s) after ignored tag-open and before ignored tag-close (except `<textarea>`)
    	return preg_replace('#<(code|pre|script|style)(>|\s[^<>]*?>)\s*([\s\S]*?)\s*<\/\1>#i', '<$1$2$3<$1>', $output);
	}
	/* Minify CSS*/
	private function _minify_css($input) {
    	// Keep important white-space(s) in `calc()`
    	if(stripos($input, 'calc(') !== false) 
		{
        	$input = preg_replace_callback('#\b(calc\()\s*(.*?)\s*\)#i', function($m) 
			{
            	return $m[1] . preg_replace('#\s+#', X . '\s', $m[2]) . ')';
        	}, $input);
    	}
    	// Minify ...
    	return preg_replace(
        array(
            // Fix case for `#foo [bar="baz"]` and `#foo :first-child` [^1]
            '#(?<![,\{\}])\s+(\[|:\w)#',
            // Fix case for `[bar="baz"] .foo` and `@media (foo: bar) and (baz: qux)` [^2]
            '#\]\s+#', '#\b\s+\(#', '#\)\s+\b#',
            // Minify HEX color code ... [^3]
            '#\#([\da-f])\1([\da-f])\2([\da-f])\3\b#i',
            // Remove white-space(s) around punctuation(s) [^4]
            '#\s*([~!@*\(\)+=\{\}\[\]:;,>\/])\s*#',
            // Replace zero unit(s) with `0` [^5]
            '#\b(?:0\.)?0([a-z]+\b|%)#i',
            // Replace `0.6` with `.6` [^6]
            '#\b0+\.(\d+)#',
            // Replace `:0 0`, `:0 0 0` and `:0 0 0 0` with `:0` [^7]
            '#:(0\s+){0,3}0(?=[!,;\)\}]|$)#',
            // Replace `background(?:-position)?:(0|none)` with `background$1:0 0` [^8]
            '#\b(background(?:-position)?):(0|none)\b#i',
            // Replace `(border(?:-radius)?|outline):none` with `$1:0` [^9]
            '#\b(border(?:-radius)?|outline):none\b#i',
            // Remove empty selector(s) [^10]
            '#(^|[\{\}])(?:[^\{\}]+)\{\}#',
            // Remove the last semi-colon and replace multiple semi-colon(s) with a semi-colon [^11]
            '#;+([;\}])#',
            // Replace multiple white-space(s) with a space [^12]
            '#\s+#'
        ),
        array(
            // [^1]
            X . '\s$1',
            // [^2]
            ']' . X . '\s', X . '\s(', ')' . X . '\s',
            // [^3]
            '#$1$2$3',
            // [^4]
            '$1',
            // [^5]
            '0',
            // [^6]
            '.$1',
            // [^7]
            ':0',
            // [^8]
            '$1:0 0',
            // [^9]
            '$1:0',
            // [^10]
            '$1',
            // [^11]
            '$1',
            // [^12]
            ' '
        ),
    	$input);
	}
	function minify_css($input) {
    	if( ! $input = trim($input)) return $input;
    	// Keep important white-space(s) between comment(s)
    	$input = preg_replace('#(' . $this->CC . ')\s+(' . $this->CC . ')#', '$1' . X . '\s$2', $input);
    	// Create chunk(s) of string(s), comment(s) and text
    	$input = preg_split('#(' . $this->SS . '|' . $this->CC . ')#', $input, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    	$output = "";
    	foreach($input as $v) 
		{
        	if(trim($v) === "") continue;
        	if(($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'") || (strpos($v, '/*') === 0 && substr($v, -2) === '*/') ) 
			{
            	// Remove if not detected as important comment ...
            	if($v[0] === '/' && strpos($v, '/*!') !== 0) continue;
            	$output .= $v; // String or comment ...
        	} 
			else 
			{
            	$output .= $this->_minify_css($v);
        	}
    	}
    	// Remove quote(s) where possible ...
    	$output = preg_replace(
        array(
            // '#(' . $this->CC . ')|(?<!\bcontent\:|[\s\(])([\'"])([a-z_][-\w]*?)\2#i',
            '#(' . $this->CC . ')|\b(url\()([\'"])([^\s]+?)\3(\))#i'
        	),
        array(
            // '$1$3',
            '$1$2$4$5'
        	),
    	$output);
    	return $this->__minify_v($output);
	}
	/**
 	*  JAVASCRIPT MINIFIER
 	*/
	private function _minify_js($input) {
    	return preg_replace(
        array(
            // Remove inline comment(s) [^1]
            '#\s*\/\/.*$#m',
            // Remove white-space(s) around punctuation(s) [^2]
            '#\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#',
            // Remove the last semi-colon and comma [^3]
            '#[;,]([\]\}])#',
            // Replace `true` with `!0` and `false` with `!1` [^4]
            '#\btrue\b#', '#\bfalse\b#', '#\breturn\s+#'
        ),
        array(
            // [^1]
            "",
            // [^2]
            '$1',
            // [^3]
            '$1',
            // [^4]
            '!0', '!1', 'return '
        ),
    	$input);
	}

	function minify_js($input) {
    	if( ! $input = trim($input)) return $input;
    	// Create chunk(s) of string(s), comment(s), regex(es) and text
    	$input = preg_split('#(' . $this->SS . '|' . $this->CC . '|\/[^\n]+?\/(?=[.,;]|[gimuy]|$))#', $input, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    	$output = "";
    	foreach($input as $v) 
		{
        	if(trim($v) === "") continue;
        	if( ($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'") || ($v[0] === '/' && substr($v, -1) === '/') ) 
			{
            	// Remove if not detected as important comment ...
            	if(strpos($v, '//') === 0 || (strpos($v, '/*') === 0 && strpos($v, '/*!') !== 0 && strpos($v, '/*@cc_on') !== 0)) continue;
            	$output .= $v; // String, comment or regex ...
        	} 
			else 
			{
            	$output .= $this->_minify_js($v);
        	}
    	}
    	return preg_replace(
        array(
            // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}` [^1]
            '#(' . $this->CC . ')|([\{,])([\'])(\d+|[a-z_]\w*)\3(?=:)#i',
            // From `foo['bar']` to `foo.bar` [^2]
            '#([\w\)\]])\[([\'"])([a-z_]\w*)\2\]#i'
        ),
        array(
            // [^1]
            '$1$2$4',
            // [^2]
            '$1.$3'
        ),
    	$output);
	}
}