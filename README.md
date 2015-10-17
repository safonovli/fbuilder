# fbuilder
Form builder for kohana 3.x

#Using Fbuilder

To use Fbuilder, download and extract module into your Kohana instances modules folder. Enable the module within the application bootstrap.

#Quick example

The following is a quick example of how to use Fbuilder.

#Form
Create new form file somewhere in config folder /application/config/form/...

Example of Registration form:

<?php defined('SYSPATH') or die('No direct script access.');

return array(
	'title'=>'New user Registration',
	'style'=>'horizontal',
	'action'=>'',
	'method'=>'POST',
	'enctype'=>false,
	'model'=>'user',
	'id'=>'registration-form',
	
	'fields'=>array(
				'name'=>'text',
				'sex'=>'selector',
				'phone'=>'text',
				'email'=>'email',
				'password'=>'password',
				'confirm_password'=>'password',
				'submit'=>'button',
	),
	'selectors'=>array(
		'sex'=>array('-1' => 'Select gender','1'=>'Male','0'=>'Female'),
	),
	'labels'=>array(
		'name'=>__('First name').'<span>*</span>',
		'phone'=>__('Phone').'<span>*</span>',
		'password'=>__('Password').'<span>*</span>',
		'email'=>__('Email').'<span>*</span>',
		'sex'=>__('Sex'),
		'confirm_password'=>'',
	),
	'params'=>array(
		'name'=>array('placeholder'=>'First,Last name',),
		'email'=>array('placeholder'=>'Enter email'),
		'phone'=>array('placeholder'=>'phone'),
		'password'=>array('placeholder'=>'Password',),
		'confirm_password'=>array('placeholder'=>'Repeat password'),
		'submit'=>array('class'=>'registration-submit enter-btn'),
	),
	'expected'=>array('name','email','password'),
	'validation'=>array(
		'name' => array(
				array('not_empty'),
				array('min_length', array(':value', 3)),
				array('max_length', array(':value', 128)),
				array('regex', array(':value', '/^([\w\s])+$/ui')),
			),
			'confirm_password'=>array(
				array('matches', array(':validation', 'password', 'confirm_password')),
			),
			'email' => array(
				array('not_empty'),
				array('min_length', array(':value', 6)),
				array('max_length', array(':value', 65)),
				array('email'),
				array('unique'),
			),
			'password' => array(
				array('not_empty'),
			),
	),
	'defaults'=>array(
		'submit'=>'Sign Up',	
		'gender'=>1,
	);

#Controller

To init form:
// Call instance of Fbuilder

$form = Fbuilder::instance();
// Or call factory to select template 

$form = Fbuilder::factory('default');

// Set form file

$form->set_form('path to form');

To set default values

$data = array();// -- Some default values
$form->set_defaults($data);

To check form

if(!$form->check_form($_POST)){
  return;
}

To quick save form data to ORM model
$model->values($_POST,$form->get_expected());
