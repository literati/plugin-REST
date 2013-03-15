<?php

$list = "<ul>";

foreach($collections as $c){
    $li = "<li>";
    $anchor = sprintf("<a href=\"collections/show/%s\">%s</a>", $c->id, $c->name);
    $list .= $li.$anchor."</li>";
    
}

echo $list."</ul>";

?>
