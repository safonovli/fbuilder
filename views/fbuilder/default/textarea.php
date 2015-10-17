<?php defined('SYSPATH') or die('No direct script access.');?>
<div class="form-group">
<? echo Form::label($name, isset($label)?$label:$name,$label_params) ?>
<div class="col-lg-9">

<?echo Form::textarea($name, $value,$params);?>

<?if(isset($errors)):?>
<span class="error"><? echo $errors ?> </span>
<? endif; ?>
</div>
</div>
