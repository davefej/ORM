<?php

require_once __DIR__.'/model/import_model.php';
model_base_require();

echo "<h1>Creating new empty user and persist</h1>";
echo "<br/>";

$user = new User();
$user->insert();
echo $user->toJson();

echo "<br/>";
echo "<h1>Updating user and persist</h1>";
echo "<br/>";

sleep(1);
$user->set("username","Username".strval(rand(100,100000)));
$user->set("password","password");
$user->set("created",new DateTime());
$user->update();

echo $user->toJson();
echo "<br/>";

echo "<h1>Load user from persistent db</h1>";
echo "<br/>";
$filter = new SqlFilter();
$filter->addand("id","=",$user->id());
$userList = User::select($filter);

echo $userList[0]->toJson();
echo "<br/>";





$id1 = rand(6,15);
$id2 = $id1 + rand(1,10);

echo "<br/>";
echo "<h1>1-1 relation set User (id".strval($id2).") father of User (id".strval($id1).") save and reload</h1>";
echo "<br/>";


$filter = new SqlFilter();
$filter->addand("id","=",$id1);
$user2 = User::selectOne($filter);

$filter = new SqlFilter();
$filter->addand("id","=",$id2);
$user6 = User::selectOne($filter);
$user2->set("father",$user6);
$user2->add("children",$user6);
$user2->save();


$filter = new SqlFilter();
$filter->addand("id","=",$id1);
$user1 = User::selectOne($filter);
echo $user1->toJson();




echo "<br/>";
echo "<h1>listAll - and deleting id 3 and 4 and resoting id 4</h1>";
echo "<br/>";



$filter = new SqlFilter();
$filter->addand("id","=",3);
$user = User::selectOne($filter);
$user->delete();

$filter = new SqlFilter();
$filter->addand("id","=",4);
$user = User::selectOne($filter);
$user->delete();

$filter = new SqlFilter();
$filter->addand("id","=",4);
$user = User::selectOne($filter);
$user->restore();

/*
$filter = new SqlFilter();
$userList = User::select($filter);
foreach ($userList as $user){
	echo $user->toJson();
	echo "<br/>";
}
*/

ObjectRegistry::getInstance()->log();



?>


