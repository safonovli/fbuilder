<?php defined('SYSPATH') or die('No direct script access.');?>
<?  echo isset($title)?'<h1 class="form-title">'.$title.'</h1>':NULL; ?>
<?
echo Form::open($form['action'], array('method' => $form['method'],'enctype'=>$form['enctype'],'class'=>'form-horizontal','id'=>$form['id']));
echo $response;
echo Form::hidden('action');
echo Form::hidden('token', isset($token)?$token:NULL);
echo Form::close();
?>
<script type="text/javascript">
	$('input,textarea,checkbox').attr('autocomplete', 'off'); 
</script> 
