<?php
require_once('FetchController.php');
class Rest_TalesController extends Rest_FetchController {
    
    public $title;
    public $item;
    
    public function init(){
        $title = $this->_getParam('title');
        if(!empty($title)){
            $this->title = $this->deHyphenize($title);
            $this->item  = $this->_findByElementSetNameValue('Dublin Core', 'Title', ucwords($this->title));
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
    }
    
    
    public function imageAction(){
        
        $item = $this->getRelationshipMember('prl', 'isRepresentativeDepictionOf', null, $this->item, true);
        
        if(get_class($item) !== 'Item'){
            debug(sprintf("no item returned to view for item->id = %s", $this->item->id));
        }else{
            $this->view->item = $item;
        }
    }
    
    public function eventsAction(){
        $items = $this->getRelationshipMember('prl', 'isSignificantElementOf', null, $this->item, false);

        if(!is_array($items)){
            $items = array($items);
        }
        $filtered = $this->filterByItemType($items, 'Event');
        
        $this->_sendJsonResponse($this->_makeTimeline($filtered), "storyjs_jsonp_data");
    }
    
    public function collectionsAction(){
        $db = get_db();
        $tbl = $db->getTable('Collection');
        $cols = $tbl->findAll();

        $this->view->collections = $cols;
    }
    


}
?>
