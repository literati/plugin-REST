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
        $this->view->item = $this->getRelationshipMember('prl', 'isRepresentativeDepictionOf', null, $this->item, true);
    }
    
    public function eventsAction(){
        $items = $this->getRelationshipMember('prl', 'isSignificantElementOf', null, $this->item, false);

        if(!is_array($items)){
            $items = array($items);
        }
        $filtered = $this->filterByItemType($items, 'Event');
        
        $this->_sendJsonResponse($this->_makeTimeline($filtered), "storyjs_jsonp_data");
    }
    

    


}
?>
