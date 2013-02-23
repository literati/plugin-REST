<?php
require_once('FetchController.php');
class Rest_TalesController extends Rest_FetchController {
    
    
    public function listAction(){
        $db = get_db();
        
        $type    = $db->getTable('ItemType')->findByName('Tale');
        $items   = $db->getTable('Item')->findBySql('item_type_id = ?', array($type->id));
        $dcTitle = $db->getTable('Element')->findByElementSetNameAndElementName('Dublin Core', 'Title');
        $titles  = array();
        
        foreach($items as $item){
            $text = $db->getTable('ElementText')->findBySql('record_id = ? and element_id = ?'
                                , array($item->id
                                , $dcTitle->id
                                )
                            , true);
            $titles[] = $text;
        }
        $this->view->titles = $titles;
        
    }
    
    public function descriptionAction(){
        $title = $this->_getParam('title');
        $this->view->test = sprintf("you asked for %s", $title);
    }
    
    
}
?>
