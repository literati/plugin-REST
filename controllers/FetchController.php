<?php


class Rest_FetchController extends Omeka_Controller_Action {

    
    
    public function init() {
        
    }

    public function deHyphenize($string){
        $words = explode('-', $string);
        return implode(' ', $words);
    }
    /**
     * @TODO allow additional params to be considered
     * @param array $params
     * @return type
     */
    private function _makeMap(array $params = null) {
        $elements = $this->_getMetaElementIDs(array(
            'Dublin Core' => array(
                'Title' 
                ), 
            'LatLon'    => array(
                'Latitude', 'Longitude'
                )
            )
        );
        
        $items = $this->_getItemsWithMetadata($elements);

        $data = $this->_buildDataSet($items, $elements);

        $dataset = array();
        foreach ($data as $item) {
            $headline = $item->getAttVal('Title');
            $text     = $item->getAttVal('Description');
            $date     = $item->getAttVal('Date');
            $lat      = $item->getAttVal('Latitude');
            $lon      = $item->getAttVal('Longitude');
            
            
            $id = $item->item->id;
            
            $dataset[] = array(
                'id' => $id, 
                'headline' => $headline, 
                'date' => $date, 
                'text' => $text, 
                'geo' => array('lat' => $lat, 'lon' => $lon));
        }
        
        return $dataset;
    }
    
    /**
     * 
     * @param Item   $subject
     * @param string $vocabulary namespace prefix of the vocab
     * @param string $property local_part
     * @param Item   $object
     * @return Item|false  the other side of the relationship
     */
    protected function getRelationshipMember($vocabulary, $property, $subject=null, $object=null, $one=true){
        if(!$subject and !$object) return false;
        if( $subject and  $object) return false;
        $missing =  $subject ? 'object' : 'subject';
        $known   = !$subject ? 'object' : 'subject';
        
        $db = get_db();
        $vocab_record = $db->getTable('ItemRelationsVocabulary')->findBySql('namespace_prefix = ? ', array($vocabulary), true);
        $prpty_record = $db->getTable('ItemRelationsProperty')
                ->findBySql('local_part = ? and vocabulary_id = ?', array($property, $vocab_record->id), true);
        
        $relation = $db->getTable('ItemRelationsItemRelation')
                ->findBySql($known.'_item_id = ? and property_id = ?', array($$known->id, $prpty_record->id), $one);
        

        $more_than_one = (is_array($relation) and count($relation > 1));
        $right_type = $more_than_one ? get_class($relation[0]) == 'ItemRelationsItemRelation' : get_class($relation) == 'ItemRelationsItemRelation';

        if(!$right_type) return false;
        
        $field  = $missing.'_item_id';
        
        if($more_than_one){
            $members = array();
            foreach($relation as $r){
                $members[] = $db->getTable('Item')->find($r->$field);
            }
            return $members;
        }
        
        $member = $db->getTable('Item')->find($relation->$field);
        
        if(!$member) return false;
        
        return $member;
    }
    
    protected function _findByElementSetNameValue($set, $name, $value, $one=true){
        $db = get_db();
        $tbl = $db->getTable('Element');
        $dc = $tbl->findByElementSetNameAndElementName($set, $name);
//        echo sprintf("found element for DC::title with id %s", $dc->id);
        unset($tbl);
        
        $tbl = $db->getTable('ElementText');
        
        $result = $tbl->findBySql('element_id = ? and text = ?', array($dc->id, $value), $one);
        if(!$one){
            $items = array();
            foreach($result as $r){
                $items[] = $db->getTable('Item')->find($r->record_id);
            }
            return $items;
        }
        $item = $db->getTable('Item')->find($result->record_id);

        return $item;
    }
    
    protected function _makeTimeline($items) {

        $dates = array();
        foreach ($items as $item) {
            
            $headline = $this->getMetaFieldValue('Dublin Core', 'Title', $item);
            $text     = $this->getMetaFieldValue('Dublin Core', 'Description', $item);
            $date     = $this->getMetaFieldValue('Dublin Core', 'Date', $item);
            
            if ($date) {
                $fmtDate = Timeline_Util::bifurcate_date($date);
            } else {
                debug("no date provided to timeline for item id = ".$item->id);
                return null;
            }

            $d = new Timeline_Date(array(
                        'startDate' => $fmtDate->startDate,
                        'endDate' => $fmtDate->endDate,
                        'headline' => $headline,
                        'text' => $text,
//                'asset'     => $asset
                            )
            );
            $dates[] = $d;
        }
        $timeline = new Timeline(array(
                    "headline" => "Sample Timeline",
                    "type" => "default",
                    "text" => "Poe's Republic of Letters",
                    "startDate" => Timeline_Util::timeline_format_date("2012-01-26"),
                    "date" => $dates
                    )
        );

        return array('timeline' => $timeline);
    }

    /**
     * http://stv.whtly.com/2010/04/19/outputting-json-with-zend-framework/
     * "storyjs_jsonp_data = " is required for Verite to work with jsonp
     * https://github.com/VeriteCo/TimelineJS
     */
    protected function _sendJsonResponse($data, $callback=null){
        $jsonp = $callback ? $callback.'=' : null;
        $json   = Zend_Json::encode($data, false, array('enableJsonExprFinder' => true));
        $this->getResponse()
                ->setHeader('Content-Type', 'application/x-javascript')
                ->setBody($jsonp . $json)
                ->sendResponse();
        exit;
    }
    
    public function timelineAction() {
        $tale = $this->getRequest()->getParam('tale');
        $data = $this->_makeTimeline(array('tale' => $tale));
        $this->_sendJsonResponse($data, "storyjs_jsonp_data");
    }
    
    public function mapAction() {
        $tale = $this->getRequest()->getParam('tale');
        $data = $this->_makeMap(array('tale' => $tale));
        $this->_sendJsonResponse($data, "jsonp_data");
    }
    

    /**
     * 
     * @param type $items an array of items to be built
     * @param type $params and array of fields as ElementSet:Element key/value pairs
     * @return array prl_Item
     */
    private function _buildDataSet($items, $params) {
        assert(count($items > 0));
        
        $elements = $this->_hydrateElements($params);

        $db = get_db();
        $retItems = array();

        $tbl = $db->getTable('ElementText');
        
        foreach ($items as $item) {
            if (!$item instanceof Omeka_Record) {
                debug(sprintf("Item with id %d is not a record!?? or maybe, just not public...", $item->id));
                continue;
            }

            $prl = new prl_Item();
            $prl->item = $item;
            
            
            foreach ($elements as $element) {
                debug(sprintf("Current item id  = %s, current element id = %s",$prl->item->id, $element->id));
                $text = $tbl->findBySQL("`record_id` = ? AND `element_id` = ?", array($prl->item->id, $element->id), true);
                $attr = new prl_Attribute($element, $text['text']);
                
                $debug_txt = (strlen($text['text']) > 25) ? substr($text['text'], 0, 25) : $text['text'];
                debug(sprintf("looking for element text for record id = %d and element id = %d, got %s", $prl->item->id, $element->id, $debug_txt));
                
                $prl->atrributes[] = $attr;
                
            }

            $retItems[] = $prl;
//            print_r($retItems);
        }
        return $retItems;
    }

    /**
     * 
     * @param array $params element ids for which to search
     * @return type
     */
    protected function _getItemsWithMetadata(array $params) {
//        print_r($params);

        debug("begin getItems with Meta");
        $db = $this->getDb();
//        $all = array();
        $hits = array();

        $tbl = get_db()->getTable('ElementText');

        foreach ($params as $param) {
            $hits[$param] = array();
            $matches = $tbl->findBySQL("`element_id` = ?", array($param));
            foreach ($matches as $match) {
                $hits[$param][] = $match->record_id;
            }
            debug(sprintf("searching for items with `element_id` = %s; got %d matches", $param, count($matches)));
                
        }
        
        $all = array_values($hits);

        $merge = array();
        foreach($all as $a){
            $merge = array_merge($a, $merge);
        }
        $intersection = array_unique($merge);
        foreach ($all as $arr) {
            $intersection = array_intersect($intersection, $arr);
        }
        
        $items = array();

//        echo count($matches);
        foreach ($intersection as $match) {
            debug(sprintf("looking up item record for id= %d", $match));
            $item     = get_db()->getTable('Item')->find($match);
            $items[] = $item;
            unset($item);
        }
        debug(sprintf("returning %d items", count($items)));
        return $items;
    }
    
    /**
     * 
     * @param array Item $items
     * @param string $type
     * @return array Item
     */
    protected function filterByItemType($items,$type){
        
        $item_type = get_db()->getTable('ItemType')->findByName($type);
        
        $filtered_items = array();
        foreach($items as $item){
            if($item->item_type_id == $item_type->id){
                $filtered_items[] = $item;
            }
        }
        
        return $filtered_items;
    }

    
    
    /**
     * 
     * @param  string $set the metadata set, ie 'Dublin Core'
     * @param  string $element the element name, ie 'Title'
     * @param  Item   $item an Omeka Item
     * @return string |false the text value of the field
     */
    protected function getMetaFieldValue($set, $element, $item){
        $db = get_db();
        $el = $db->getTable('Element')->findByElementSetNameAndElementName($set, $element);
        
        if(!$el) return false;
        
        $text = $db->getTable('ElementText')->findBySql("element_id = ? and record_id = ?",array($el->id, $item->id), true);
        
        if(empty($text)) return false;
        
        return $text->text;
    }
    
    
    /**
     * 
     * @param array $params key value pairs where
     *  $key is the name of an Element Set (ie Dublin Core)
     *  $value = array of Element names (ie Date)
     * @return array of element ids or FALSE
     * the ids each point to an array containing the element 'set' and 'name'
     */
    private function _getMetaElementIDs(array $params) {
//        print_r($params);
        
        $eArr = false;
        $db = get_db();
        $tbl = $db->getTable('Element');

        foreach ($params as $elementSet => $elements) {

            foreach ($elements as $el) {

                debug(sprintf("metadata params are %s => %s\n", $elementSet, $el));
//                die("What?".$elementSet.$el);
                $e = $tbl->findByElementSetNameAndElementName($elementSet, $el);

                if ($e->id) {
                    $eArr[] = $e->id;
                }
            }
        }
//        print_r($eArr);
//        die();

        return $eArr;
    }

    private function _hydrateElements(array $ids) {


        $eArr = array();
        $es = get_db()->getTable('ElementSet');
        $e = get_db()->getTable('Element');

        foreach ($ids as $id) {
            $elementR = $e->find($id);

            $elemSetR = $es->find($elementR->element_set_id);

            $x = new prl_Element($id, $elementR->name, $elemSetR->name);

            $eArr[] = $x;
        }

        return $eArr;
    }

}

/**
 * This class is a top-level element of a data abstraction layer
 *  for Omeka data.
 *  The prl_Item defined here is composed of an array of subtype 
 *  prl_Attribute, whicih itself is composed of prl_element:value pairs
 */
class prl_Item {

    public $item; //Omeka Item
    public $atrributes; //array of ItemAttribute

    public function getAttVal($name){
        foreach($this->atrributes as $attribute){
            if($attribute->element->name == $name){
                return $attribute->value;
            }
        }
        return false;
    }
}

/**
 * prl_Attribute is a unit of metadata where the value is a simple string
 * and the $element is composed of Omeka element_set:element pairs
 */
class prl_Attribute {

    public $element; //prl_Element
    public $value; //string

    public function __construct($element, $value) {
        $this->element = $element;
        $this->value = $value;
    }

}


class prl_Element {

    public $id;
    public $name;
    public $elementSetID;
    public $elementSetName;

    public function __construct($id, $name, $elementSetName, $elementSetID = null) {
        $this->id = $id;
        $this->name             = $name;
        $this->elementSetID     = $elementSetID;
        $this->elementSetName   = $elementSetName;
    }

}

?>
