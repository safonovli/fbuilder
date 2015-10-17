<?php defined('SYSPATH') or die('No direct script access.');

class Fbuilder_Core
{
	protected static $_instance	= NULL;
	
	private 	$config;
	private 	$defaults;
	
	protected 	$post	=	array();
	protected 	$post_original	=	array();
	protected 	$title	= 	'';
	protected 	$theme	= 	'';

	private 	$response	= '';
	
	public 		$errors	= 	NULL;
	protected 	$view	=	NULL;
	
	private $helper		= 	NULL;
	
	
	public static function instance($preset='default') {
	    if (!isset(self::$_instance))
			self::$_instance = new Fbuilder($preset);
		return self::$_instance;
	}
	
	public static function factory($preset='default'){
		return new Fbuilder($preset);
	}
	
	private function flatten_array($post,$parents = NULL){
		$result = array();
		if(is_array($post))
			foreach($post as $fkey => $fval){
				$_parents = $parents;
				$_parents[] = $fkey;
				$result = array_merge($result,$this->flatten_array($fval,$_parents));
			}
		else
			$result[implode('|',$parents)] = $post;
		return $result;
	}
	
	public function __construct($preset='default') {
		Arr::map('Security::xss_clean', $_POST);
		
		$settings = Kohana::$config->load('fbuilder.'.$preset);
		$this->view = View::factory($settings['view']);
		$this->defaults = $settings['params_default'];
		$this->theme = $settings['theme'];
						
		if($post=$_POST)
			$dta = $this->flatten_array($post);
		if(isset($dta) AND !empty($dta))
			$this->post=Arr::merge($this->post,$dta);
	}
	
	public function set_form($config_path=FALSE){
		if($config_path){
			if(is_array($config_path))
				$this->config   = $config_path;
			if(is_string($config_path))
				$this->config   = Kohana::$config->load($config_path);
		}
	}
	
	public function set_defaults($data){
		$this->post_original = $data;
		$data = $this->flatten_array($data);
		if(is_array($data))
			$this->post=Arr::merge($this->post,$data);
	}
	
	public function set_title($title){
		$this->title=$title;
	}
	
	public function build_form($no_form=FALSE){
		if(!empty($_POST))
			$this->check_form($this->post);
				
		ob_start();
		if(isset($this->config['fields'])){
			try{
				foreach($this->config['fields'] as $field => $type){
					$params=$this->get_params($field,$type);
					$this->prepare_response($field,$type,$params,0);
				}
			}
			catch (Exception $e){
				ob_end_clean();// Delete the output buffer
				throw $e;// Re-throw the exception
			}
		}
		$this->response=ob_get_clean();
		if(!$no_form)
			return $this->view
				->set('title',$this->title)
				->set('form',$this->config)
				->set('data',$this->post)
				->set('response',$this->response)
				->set('token',$this->get_csrf_token());
		else return $this->response;
	}
	
	private function get_params($name,$type){
		$params=isset($this->config['params_default'][$type])?$this->config['params_default'][$type]:NULL;
		if(!$params)
			$params=isset($this->defaults[$type])?$this->defaults[$type]:NULL;
		if($params AND isset($this->config['params'][$name]))
			$params= Arr::merge($params,$this->config['params'][$name]);
		if(!$params)
			$params=isset($this->config['params'][$name])?$this->config['params'][$name]:NULL;
		return $params;
	}
	
	private function flatten_name($name){
		$flat_name = $name;
		if(strpos($name, '[') > 0){
			$flat_name = str_replace('[','|',$flat_name);
			$flat_name = str_replace(array(']','\'','"'),'',$flat_name);
		}
		return $flat_name;
	}
	
	private function get_value($name,$type=''){
		$short_name=strstr($name, '[]', true);
		if(!$short_name OR $type=='text' OR $type=='image' OR $type=='image-p')
			$short_name=$name;
		$short_name = $this->flatten_name($short_name);
			
		$value=isset($this->post[$short_name])?$this->post[$short_name]:NULL;
		if($value==NULL)
			$value=isset($this->config['defaults'][$name])?$this->config['defaults'][$name]:NULL;
		return $value;
	}
	
	public function set_errors($errors=array()){
		$this->errors =isset($this->errors)?Arr::merge($this->errors,$errors):$errors;
	}
	
	private function prepare_response($name,$type,$params,$depth=0){
		$flat_name = $this->flatten_name($name);
		$errors = (isset($this->errors[$flat_name]))?$this->errors[$flat_name]:NULL;
		$label_params = isset($this->defaults['label'])?$this->defaults['label']:NULL;
		$label = isset($this->config['labels'][$name])?$this->config['labels'][$name]:NULL;
		
		$value=$this->get_value($name,$type);
		
		$params=$this->get_params($name,$type);
		switch ($type){
			case 'selector':
				$additional=isset($this->config['selectors'][$name])?$this->config['selectors'][$name]:array('undefined'=>'undefined');
			break;
			case 'external':
				$value= isset($this->config['defaults'][$name])?$this->config['defaults'][$name]:NULL;
			break;
			default:break;
		}
		if (($path = Kohana::find_file('views', 'fbuilder/'.$this->theme.'/'.$type)) === FALSE){
			throw new View_Exception('The requested view :file could not be found', array(
				':file' =>  'fbuilder/'.$this->theme.'/'.$type,
			));
		}
		try{
			include $path;
		}
		catch (Exception $e){
			throw $e;// Re-throw the exception
		}
	}
	
	
	
	/**
	 * Get form expected
	 *
	 * @return array expected
	 */	
	public function get_expected(){
		if(!isset($this->config['expected']))return NULL;
		return $this->config['expected'];
	}

	/**
	 * Get form field
	 *
	 * @return array fields
	 */
	public function get_fields(){
		if(!isset($this->config['fields']))return NULL;
		return $this->config['fields'];
	}
	
	/**
	 * Validate posted array
	 *
	 * @param  array post
	 *
	 * @return bool result ans set errors to view
	 */
	public function check_form($post){
		if(!isset($post['token']) OR !$this->check_csrf_token($post['token'])){
			$this->errors['token']=__('Wrong token');
		}
		$post = $this->flatten_array($post);
		$validation = Validation::factory($post);
		if(!isset($this->config['validation']))return TRUE;
		foreach($this->config['validation'] as $field => $rules){
			foreach($rules as $rule){
				if($rule[0]=='unique' AND $this->config['model']!==NULL){
					$obj=ORM::factory($this->config['model']);
					$validation->rule($field,array($obj,$rule[0]),array($field,':value'));
				}
				elseif($rule[0]=='external'){
					$validation->rule($field,$rule[1],$rule[2]);
				}
				elseif($rule[0]=='model'){
					$obj=ORM::factory($this->config['model']);
					$validation->rule($field,array($obj,$rule[1]));
				}
				else $validation->rule($field,$rule[0],isset($rule[1])?$rule[1]:NULL);
			}
		}
		if ($validation->check()){
			return TRUE;
		}
		$this->errors = Arr::merge($this->errors,$validation->errors($this->config['model']));
		$this->view->set('errors',$this->errors); 
		return  FALSE;
	}
	
	
	/**
	 * Convert names like dta|key1|key2|key3 to dta[key1][key2][key3]
	 *
	 * @param  string name
	 * @param  string value
	 *
	 * @return array
	*/
	private function explode_array($name,$value = NULL){
		$result = array();
		$tmp = explode('|',$name);
		if(count($tmp) > 1){
			$key = $tmp[0];
			unset($tmp[0]);
			$result[$key] = $this->explode_array(implode('|',$tmp),$value);
		}
		else	
			$result[$tmp[0]] = $value;
		return $result;
	}
	
	/**
	 * Store new csrf token in session
	 *
	 * @return string
	 */
	public function set_csrf_token(){
		return Session::instance()->set('token', md5(Request::$user_agent . Session::instance()->id()));
	}
	/**
	 * Returns csrf token, if token is not saved in session, saves a new one
	 *
	 * @return string
	 */
	public function get_csrf_token(){
		if (($token = Session::instance()->get('token', NULL)))
			return $token;
		return $this->set_csrf_token();
	}
	/**
	 * Checks csrf token
	 *
	 * @param  string posted token
	 * @param  object route to redirect if token is not validated
	 */
	public function check_csrf_token($token = '', $redirect_page = NULL){
		if ($this->get_csrf_token() === $token)	return TRUE;
		else return FALSE;
	}
}

