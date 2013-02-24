<?php
require_once('FetchController.php');
class Rest_TalesController extends Rest_FetchController {
    
    public $title;
    public $item;
    
    public function init(){
        $title = $this->_getParam('title');
        if(!empty($title)){
            $this->title = $this->deHyphenize($title);
            $this->item  = $this->_findByDCTitle(ucwords($this->title));
        }
    }
    
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
        $this->view->test = $this->getMetaFieldValue('Dublin Core', 'Description', $this->item);
    }
    
    public function textAction(){
        set_current_item($this->item);
//        $this->view->uri = item_file('permalink');
    }
    
}
?>
