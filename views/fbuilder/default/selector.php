<?php defined('SYSPATH') or die('No direct script access.');?>
<div class="form-group">
	<? echo isset($label)? Form::label($name, $label,$label_params):NULL; ?>
	<div class="col-md-<? echo isset($label)?"9":"12"?>">
		<?echo  Form::select(	$name,	$additional,	$value, 	$params);?>
	<?if(isset($errors)):?>
		<span class="error"><? echo $errors ?> </span>
	<? endif; ?>
	</div>
</div>
