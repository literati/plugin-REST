<?php


class Rest_TimelineController extends Omeka_Controller_Action {
    public function init()
    {
//        $contextSwitch = $this->_helper->getHelper('contextSwitch');
//        $contextSwitch->setAutoJsonSerialization(true);
//        $contextSwitch->addActionContext('find', 'omeka-json');
//        $contextSwitch->initContext();        
    }
    public function findAction(){
        
        $tale   = $this->getRequest()->getParam('tale');
        $items  = $this->getItemsWithDates();
        
        //strip down these Omeka_Items to only what we need...
        $events  = $this->cleanItems($items);
        
        //build Timeline_Date objects from our lightweight items
        $dates = array();
        foreach($events as $event){
          
            $headline = isset($event['headline'])  ? $event['headline'] : null;
            $text     = isset($event['text'])      ? $event['text'] : null;
            $date     = isset($event['date'])      ? $event['date'] : null;
            
            if($date){
                
                $fmtDate = Timeline_Util::bifurcate_date($date);
                
            }else{
                debug("no date provided to timeline");
                return null;
            }
            
            $date = new Timeline_Date(array(
                'startDate' => $fmtDate->startDate,
                'endDate'   => $fmtDate->endDate,
                'headline'  => $headline,
                'text'      => $text,
//                'asset'     => $asset
                )
            );
            $dates[] = $date;
        }
        $timeline = new Timeline(array(
            "headline"  => "Poe's Republic of Letters",
            "type"      => "default",
            "text"      => "Antebellum Print Culture",
            "startDate" => Timeline_Util::timeline_format_date("2012-01-26"),
            "date"      => $dates
            )
        );
        $timeline = array('timeline' => $timeline);

        /**
         * http://stv.whtly.com/2010/04/19/outputting-json-with-zend-framework/
         * "storyjs_jsonp_data = " is required for Verite to work with jsonp
         * https://github.com/VeriteCo/TimelineJS
         */
        //if ($this->_request->isXmlHttpRequest()) {

            $jsonData = Zend_Json::encode($timeline,false,
                array('enableJsonExprFinder' => true));
            $this->getResponse()
                ->setHeader('Content-Type', 'application/x-javascript')
                ->setBody("storyjs_jsonp_data = ".$jsonData)
                ->sendResponse();
            exit;
        
        
        //$this->view->timeline   = $items;
        
    }
    
    private function cleanItems($items){
        
        $db = get_db();
        $clean      = array();
        
        $tblEl      = $db->getTable('Element');
        $el_dc_tle  = $tblEl->findByElementSetNameAndElementName('Dublin Core', 'Title');
        $el_dc_desc = $tblEl->findByElementSetNameAndElementName('Dublin Core', 'Description');
        $el_dc_date = $tblEl->findByElementSetNameAndElementName('Dublin Core', 'Date');
        
        $tblElTex   = $db->getTable('ElementText');
        
        
        foreach($items as $item){
            if(!$item instanceof Omeka_Record){
//                echo "not a record!<br/>";
                continue;
                
            }
//            print_r($item);
//            echo get_class($item);
//            echo "foreach item<br/>";
            $i = array();
            $fields_raw     = $tblElTex->findByRecord($item);
            
            foreach($fields_raw as $f){
                if($f['element_id'] == $el_dc_tle->id){
                    $i['headline'] = $f['text'];        
                }elseif($f['element_id'] == $el_dc_desc->id){
                    $i['text'] = $f['text'];
                }elseif($f['element_id'] == $el_dc_date->id){
                    $i['date'] = $f['text'];
                }
                else continue;
                
                
            }
            
            $clean[] = $i;
            
        }
        return $clean;
    }
    
    private function getItemsWithDates(){
        $db         = $this->getDb();
        
        //get the id of the DC:date field
        $tblEl      = $db->getTable('Element');
        $el_dc_date = $tblEl->findByElementSetNameAndElementName('Dublin Core', 'Date');
        
        $tblElText  = get_db()->getTable('ElementText');
        $matches    = $tblElText->findBySQL("`element_id` = ?", array($el_dc_date->id));
        

        $items      = array();
//        echo count($matches);
        foreach($matches as $match){
            
            $item = get_db()->getTable('Item')->find($match->record_id);
//            echo sprintf("item match, id = %s<br/>", $match->record_id);
//            echo "matched item class == ".get_class($item)."<br/>";
//            echo "item is instance of Omeka_Record? ". ($item instanceof Omeka_Record)."<br/>";
//            
            $items[] = $item;
            unset($item);
        }
        
        return $items;
    
        
    }
}

?>
