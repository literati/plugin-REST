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
    
    
    public function imageAction(){
        require_once('application/helpers/FileFunctions.php');
        require_once('application/helpers/StringFunctions.php');
        require_once('application/helpers/UrlFunctions.php');
        
        $db = get_db();
        
        //get the id of the relation for 'representativeDepictionOf'...
        $irp = $db->getTable('ItemRelationsProperty')->findBySql('label = ? ', array('Representative Depiction'), true);
        assert(get_class($irp) == 'ItemRelationsProperty');
        
        //get id of the local part of that relation
        $irt = $db->getTable('ItemRelationsItemRelation');
        $ir  = $irt->findBySql('object_item_id = ? and property_id = ?', array($this->item->id, $irp->id),true);
        assert(get_class($ir) == 'ItemRelationsItemRelation');
        
//        echo sprintf("looking for relation where object = %s and property = %s, got ir->id = %s", $tale->id, $irp->id, $ir->id);
        $curItem = $db->getTable('Item')->find($ir->subject_item_id);

        $this->view->item = $curItem;

        
        
    }

}
?>
