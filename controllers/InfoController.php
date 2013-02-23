<?php
class Rest_InfoController extends Omeka_Controller_Action{
    function helloAction(){
        $arg = $this->_getParam('arg');
        $this->view->message = $arg;
    }
    
    function otherAction(){
        print_r($this);
        die();
    }
}
?>
