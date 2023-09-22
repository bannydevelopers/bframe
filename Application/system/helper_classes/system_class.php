<?php
class system{
    public static function get_system_configuration(){
        $app_directory = realpath(__DIR__.'/../../system/config');
        return json_decode(file_get_contents("{$app_directory}/config.json"));
    }
    public static function get_db_connection(){
        $configuration = self::get_system_configuration();
        return db::initialize($configuration->database);
    }
    public static function start_session(){
        session_start();
    }
    public static function sync_plugins(){
        //to be continue....
    }
    public static function dispatch_event($event_name){
        //to be continue....
    }
    public static function load_page(){
        $request = storage::initialize()->request;
        $db = self::get_db_connection();
        /* page_special: 0 = normal, 1 = home, 2 = hidden, 3 = home + hidden */
        if(empty($request[0])){
            // Load home page
            $page = $db->select('pages')->where(['page_special'=>1])->or(['page_special'=>3])->limit(1)->fetch();
        }
        else{
            if(count($request) > 1){
                // Page has parent(s)
                $page_name = str_replace('-',' ', end($request));
                array_pop($request);
                $parents = array_reverse($request);
                //$query = "SELECT * FROM pages WHERE "
                $db->select('pages')->where(['page_name'=>$page_name]);
                foreach($parents as $parent){
                    $pagename = str_replace('-',' ', $parent);
                    $db->and('page_parent')->in("(SELECT page_id FROM pages WHERE page_name = '{$pagename}')");
                }
                $page = $db->fetch();

            }
            else{
                // Page has no parent(s)
                $page  = $db->select('pages')
                            ->where(['page_name'=>str_replace('-',' ', implode('',$request))])
                            ->and(['page_parent'=>0])
                            ->limit(1)
                            ->fetch();
            }
            if(!$page){
                // Page not found, 404 error
                $page = [
                    'page_name'=>'404 Not found',
                    'page_title'=>'404 Not found',
                    'page_template'=>404,
                    'page_content'=>'Page not found'
                ];
            }
        }
        storage::initialize()->page = $page;
    }
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
		$db = db::get_connection(storage::get_instance()->system_config->database);
		$widget = $db->select('system_widgets')->where(['widget_name'=>$name])->fetch();
		if(!$widget) return;

		$snippets = json_decode($widget['widget_content'], true);
		$pid = storage::init()->page['page_id'];

		$htz = '';
		foreach($snippets as $s){
			$s['visibility'] = explode(',', $s['visibility']);
			if($s['status'] == 'active' && (in_array($pid, $s['visibility']) or !$s['visibility'][0])){
				$htz .= $s['content'];
			}
		}
		echo $htz;
		return;
		$position = $name;
		if(!isset(storage::get_instance()->widgets[$position])) return;
		$cont = storage::get_instance()->widgets[$position];
		return is_array($cont) ? implode('', array_unique($cont, SORT_REGULAR)) : $cont; // Fix duplicates without array_unique
	}
	public static function register_nav($position, $content){
		return self::register_entity('navigation', $position, $content);
	}
	public static function get_navigation($reference){
		return self::get_nav($reference);
	}
	public static function get_nav($reference){
		$host = str_replace('.', '_', $_SERVER['HTTP_HOST']);
		$cache_nav = realpath(__DIR__.'/../../').'/storage/cache/nav/';
		if(!is_readable("{$cache_nav}/{$host}_nav_{$reference}.html")) return self::create_nav($reference);
        $filem = "{$cache_nav}/{$host}_nav_{$reference}.html";
        $filemtime = is_readable($filem) ? filemtime($filem) : 0;
		$expiry = ( time() - $filemtime ) / ( 60 * 60 * 24 ); // a day from seconds
		if($expiry > 1) return self::create_nav($reference); // cache older than a day is obselete
		return file_get_contents("{$cache_nav}/{$host}_nav_{$reference}.html");
	}
	public static function create_nav($reference){
		$db = db::initialize(storage::initialize()->system_config->database);
		$pages = $db->select('pages', 'page_id, page_name, page_parent, page_special')
					->where(['page_special'=>0])
					->or(['page_special'=>1])
					->order_by('page_order','asc')
					->fetchAll();

		$navs = self::create_array_tree($pages);
		//var_dump('<pre>',$navs);
		$host = str_replace('.','_',$_SERVER['HTTP_HOST']);
		$cache_nav = realpath(__DIR__.'/../').'/cache/nav/';
		if(!is_readable($cache_nav)) mkdir($cache_nav, 0777, true);
		$nav_list = self::array_to_list($navs);
        //return $cache_nav;
		file_put_contents("$cache_nav/{$host}_nav_$reference.html", $nav_list);
        return $nav_list;
		return is_readable("$cache_nav/{$host}_nav_$reference.html") ? $nav_list : '';
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
		$storage = storage::initialize();
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
			$ul .= "<li><a href=\"{$storage->install_dir}{$link}\">{$li['page_name']}</a>";
			if(isset($li['children'])) $ul .= self::array_to_list($li['children'], $prefix);
			$ul .= '</li>';
			array_pop($prefix); // last element may carry wrong parent, so pop it out.
		}
		$ul .= '</ul>';
		return $ul;
	}
    public static function display(){
        $path = realpath(__DIR__.'/../views');
        $storage = storage::initialize();
        $view = $storage->system_config->view;
        if(is_readable("{$path}/{$view}")){
            $view_holder = "{$path}/{$view}";
        }
        else{
            $view_holder = "{$path}/default";
        }
        
        if($storage->page){
            $page = $storage->page;
            extract((array) $page);
            extract((array) $storage->system_config);
            $title = (isset($page_title) && $page_title) ? $page_title : $page_name;
            $base = $storage->install_dir;
            if(!isset($page_template) or !$page_template) $page_template = 'index';
            ob_start(
                function($buffer){
                    $error = error_get_last();
                    $return = file_get_contents(__DIR__.'/../../storage/assets/html/fatal.html');
                    return str_replace('{$error}', $error['message'], $return);
                }
            );
            if(is_readable("{$view_holder}/{$page_template}.html")){
                include "{$view_holder}/{$page_template}.html";
            }
            else{
                include "{$view_holder}/index.html";
            }
            
            echo ob_get_clean();
            $exec_time = microtime(true) - $storage->start_time;
            echo "<!-- Excution time: {$exec_time} microseconds -->";
        }
        else{
            echo $view_holder;
        }
    }
}