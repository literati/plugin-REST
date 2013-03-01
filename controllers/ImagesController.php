<?php
require_once('FetchController.php');

class Rest_ImagesController extends Rest_FetchController {
    
    public function listAction(){
        $db = get_db();
        
        $items = $this->_getList();
        $dcTitle = $db->getTable('Element')->findByElementSetNameAndElementName('Dublin Core', 'Title');
        $titles  = array();
        assert(is_array($items));
        assert(count($items)>0);
        foreach($items as $item){
            
            assert(get_class($item) == 'Item');
            assert(is_numeric($item->id));
            
            $text = $db->getTable('ElementText')->findBySql('record_id = ? and element_id = ?'
                                , array($item->id
                                , $dcTitle->id
                                )
                            , true);
            $titles[] = $text;
        }
        
        $this->view->titles = $titles;
        
    }
    
    
    public function thumbsAction(){
        $db = get_db();
        
        $items = $this->_getList();
        $this->view->thumbs = "";
        foreach($items as $item){
            $this->view->thumbs .= item_square_thumbnail(array(), 0, $item);
        }
    }
    
    private function _getList(){
        $db      = get_db();
        $type    = $db->getTable('ItemType')->findByName('Still Image');
        return $db->getTable('Item')->findBySql('item_type_id = ?', array($type->id));
        
        
    }
    
    
}


?>
