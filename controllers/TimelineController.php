<?php

class Rest_TimelineController extends Omeka_Controller_Action {

    public function init() {
        
    }

    private function _makeTimeline(array $params = null) {
        $elements = $this->_getMetaElementIDs(array('Dublin Core' => array('Date', 'Contributor', 'Title', 'Description'), ));
//        $elements = $this->_getMetaElementIDs(array('LatLon' => array('Lat', 'Lon')));
        

        $items = $this->getItemsWithMetadata($elements);

//        print_r($items);    
        //strip down these Omeka_Items to only what we need...
        $data = $this->buildDataSet($items, $elements);
//        print_r($data);
        //build Timeline_Date objects from our lightweight items
        $dates = array();
        foreach ($data as $item) {
//            print_r($item->atrributes);
//            die();
            $headline = $item->getAttVal('Title');
            $text     = $item->getAttVal('Description');
            $date     = $item->getAttVal('Date');
            
            if ($date) {

                $fmtDate = Timeline_Util::bifurcate_date($date);
            } else {
                debug("no date provided to timeline");
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
                    "headline" => "Sh*t People Say",
                    "type" => "default",
                    "text" => "People say stuff",
                    "startDate" => Timeline_Util::timeline_format_date("2012-01-26"),
                    "date" => $dates
                        )
        );
        $timeline = array('timeline' => $timeline);

        return $timeline;
    }

    public function findAction() {

        $tale = $this->getRequest()->getParam('tale');
//print_r($this->getRequest()->getParams());

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

    /**
     * 
     * @param type $items an array of items to be simplified
     * @param type $params and array of fields as ElementSet:Element key/value pairs
     * @return array prl_Item
     */
    private function buildDataSet($items, $params) {

        $elements = $this->_hydrateElements($params);
//        print_r($elements);
        $db = get_db();
        $retItems = array();

        $tbl = $db->getTable('ElementText');

        foreach ($items as $item) {
            if (!$item instanceof Omeka_Record) {
                echo "not a record!<br/>";
                continue;
            }

            $prl = new prl_Item();
            $prl->item = $item;
            
            
            foreach ($elements as $element) {
                debug(sprintf("Current item id  = %s, current element id = %s",$prl->item->id, $element->id));
                $text = $tbl->findBySQL("`record_id` = ? AND `element_id` = ?", array($prl->item->id, $element->id), true);
                $attr = new prl_Attribute($element, $text['text']);
                debug(sprintf("looking for element text for record id = %d and element id = %d, got %s", $prl->item->id, $element->id, $text['text']));
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
    private function getItemsWithMetadata(array $params) {
//        print_r($params);

        debug("begin getItems with Meta");
        $db = $this->getDb();
        $all = array();
        $hits = array();

        $tbl = get_db()->getTable('ElementText');

        foreach ($params as $param) {
            $hits[$param] = array();
            $matches = $tbl->findBySQL("`element_id` = ?", array($param));
            foreach ($matches as $match) {
                $hits[$param][] = $match->record_id;
            }
            debug(sprintf("searching for : `element_id` = %s", $param));
            debug(sprintf("got %d matches", count($matches)));
        }

        $all = array_values($hits);
        $intersection = array_pop($all);

        foreach ($all as $arr) {
            array_intersect($intersection, $arr);
        }
//        echo "<hr/>all:<br/>";
//        print_r($all);
//        echo "<hr/>hits:<br/>";
//        print_r($hits);
//        echo "<hr/>intersection:<br/>";
//        print_r($intersection);
//        

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

                debug(sprintf("metadata params are %s => %s", $elementSet, $el));
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
