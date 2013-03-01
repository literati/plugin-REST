<?php

//use the builtin, but think about diggging 
//deeper for better control of the output
if(get_class($item) == 'Item'){
    echo item_square_thumbnail();
}else{
    
    echo "";
}
?>
