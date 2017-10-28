<?php

require_once __DIR__.'/model/import_model.php';
model_base_require();


$id = rand(10,100);

$filter = new SqlFilter();
$filter->addand("id","=",$id);
$user = User::selectOne($filter);

$eye = new Eye();
$eye->set("color","red");
$eye->set("isright",false);
$eye->set("user",$user);

$eye2 = new Eye();
$eye2->set("color","red");
$eye2->set("isright",true);
$eye2->set("user",$user);

$eye->save();
$eye2->save();

$user->add("eyes",$eye);
$user->add("eyes",$eye2);
$user->save();

ObjectRegistry::getInstance()->log();



?>


