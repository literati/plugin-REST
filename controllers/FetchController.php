<?php

class Rest_FetchController extends Omeka_Controller_Action {

    public function init() {
        
    }

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
    private function _makeTimeline(array $params = null) {
        $elements = $this->_getMetaElementIDs(array('Dublin Core' => array('Date', 'Title', 'Description'), ));

        /**
         * @TODO what happens if we get back zero items?
         */
        $items = $this->_getItemsWithMetadata($elements);
        
        if($items){
            $data = $this->_buildDataSet($items, $elements);
            debug(sprintf("building dataset from %d items for %d elements; %d data records returned", count($items), count($elements), count($data)));
        }else{
            debug("no items returned for given metadata");
        }
        
        $dates = array();
        foreach ($data as $item) {
            $headline = $item->getAttVal('Title');
            $text     = $item->getAttVal('Description');
            $date     = $item->getAttVal('Date');
            
            if ($date) {

                $fmtDate = Timeline_Util::bifurcate_date($date);
            } else {
                debug("no date provided to timeline for item id = ".$item->item->id);
                return null;
            }

            $date = new Timeline_Date(array(
                        'startDate' => $fmtDate->startDate,
                        'endDate' => $fmtDate->endDate,
                        'headline' => $headline,
                        'text' => $text,
//                'asset'     => $asset
                            )
            );
            $dates[] = $date;
        }
        $timeline = new Timeline(array(
                    "headline" => "Sample Timeline",
                    "type" => "default",
                    "text" => "Poe's Republic of Letters",
                    "startDate" => Timeline_Util::timeline_format_date("2012-01-26"),
                    "date" => $dates
                        )
        );
        $timeline = array('timeline' => $timeline);

        return $timeline;
    }

    public function timelineAction() {

        $tale = $this->getRequest()->getParam('tale');

        $timeline = $this->_makeTimeline(array('tale' => $tale));

        /**
         * http://stv.whtly.com/2010/04/19/outputting-json-with-zend-framework/
         * "storyjs_jsonp_data = " is required for Verite to work with jsonp
         * https://github.com/VeriteCo/TimelineJS
         */
        $jsonData = Zend_Json::encode($timeline, false, array('enableJsonExprFinder' => true));
        $this->getResponse()
                ->setHeader('Content-Type', 'application/x-javascript')
                ->setBody("storyjs_jsonp_data = " . $jsonData)
                ->sendResponse();
        exit;
    }
    
    public function mapAction() {

        $tale = $this->getRequest()->getParam('tale');

        $timeline = $this->_makeMap(array('tale' => $tale));

        /**
         * http://stv.whtly.com/2010/04/19/outputting-json-with-zend-framework/
         * "storyjs_jsonp_data = " is required for Verite to work with jsonp
         * https://github.com/VeriteCo/TimelineJS
         */
        $jsonData = Zend_Json::encode($timeline, false, array('enableJsonExprFinder' => true));
        $this->getResponse()
                ->setHeader('Content-Type', 'application/x-javascript')
                ->setBody("jsonp_data = " . $jsonData)
                ->sendResponse();
        exit;
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
    private function _getItemsWithMetadata(array $params) {
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

        $intersection = array();
        foreach($all as $a){
            $intersection = array_merge($a, $intersection);
        }
        $intersection = array_unique($intersection);
        
        

        foreach ($all as $arr) {
            $intersection = array_intersect($intersection, $arr);
        }
        
        $items = array();

//        echo count($matches);
        foreach ($intersection as $match) {
            debug(sprintf("looking up item record for id= %d", $match));
            $item = get_db()->getTable('Item')->find($match);
//            echo sprintf("item match, id = %s<br/>", $match->record_id);
//            echo "matched item class == ".get_class($item)."<br/>";
//            echo "item is instance of Omeka_Record? ". ($item instanceof Omeka_Record)."<br/>";
//            
            $items[] = $item;

            unset($item);
        }
        debug(sprintf("returning %d items", count($items)));
        return $items;
    }

    /**
     * 
     * @param array $params key value pairs where
     *  $key = array of Element Sets (ie Dublin Core)
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

            $x = new prl_Element($id, $elementR->name, null, $elemSetR->name);

            $eArr[] = $x;
        }

        return $eArr;
    }

}

class prl_Element {

    public $id;
    public $name;
    public $elementSetID;
    public $elementSetName;

    public function __construct($id, $name, $elementSetID = null, $elementSetName) {
        $this->id = $id;
        $this->name = $name;
        $this->elementSetID = $elementSetID;
        $this->elementSetName = $elementSetName;
    }

}

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

class prl_Attribute {

    public $element; //prl_Element
    public $value; //string

    public function __construct($element, $value) {
        $this->element = $element;
        $this->value = $value;
    }

}

?>
