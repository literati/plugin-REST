<?php


class Timeline_Util{
    
    public static function normalize_date($date){
        if(preg_match('/^[0-9]{4}$/', $date)){
            debug("got a four-digit year as date input");
            $date = "01-01-".$date;
        }
        $time = strtotime($date);
        debug(sprintf("strftime yields %s", $time));
        return strftime("%F,%T",$time);
    }
    
    public static function timeline_format_date($date){
        
        $time = strtotime($date);
        return strftime('%Y,%m,%d',$time);
        
    }
    
    public static function bifurcate_date($date){
        debug(sprintf("bifurcating date %s", $date));
        $date = self::normalize_date($date);
        $dates = new stdClass();
        $s = $e = new DateTime(Timeline_Util::normalize_date($date));
        $interval = new DateInterval("P1D");
        
        $dates->startDate = $s->format('Y,m,d');
        $e->add($interval);
        $dates->endDate = $e->format('Y,m,d');
        debug(sprintf("returning start date %s end date %s", $dates->startDate, $dates->endDate));
        return $dates;
    
    }
}

/**
 * @TODO refactor to take advantage of PHP namespaces
 */
class Timeline {
    
    /**
     *
     * @var String main headline of the timeline
     */
    public $headline;
    
    /**
     *
     * @var String {default | twitter} 
     * the type of timeline we are building
     */
    public $type;
    
    /**
     *
     * @var String body text; 
     * may incude HTML 
     */
    public $text;
    
    /**
     *
     * @var String startdate; 
     */
    public $startDate;
    
    /**
     *
     * @var Timeline_Date_Asset a media element for the main timeline 
     */
    public $asset;
    
    /**
     *
     * @var Timeline_Date[] array of Timeline_Date objects
     */
    public $date;
    
    public function __construct(array $params = null){
        $this->headline = isset($params['headline']) ? $params['headline'] : null;
        $this->startDate= isset($params['startDate'])? $params['startDate']: null;
        $this->type     = isset($params['type'])     ? $params['type']     : null;
        $this->text     = isset($params['text'])     ? $params['text']     : null;
        $this->asset    = isset($params['asset'])    ? $params['asset']    : null;
        $this->date     = isset($params['date'])     ? $params['date']     : null;
    }
    
    
}

class Timeline_Date {
    
    /**
     *
     * @var String $startdate representing the 
     * event start date in yyyy,m,d format 
     */
    public $startDate;
    
    /**
     *
     * @var String $enddate representing the 
     * event enddate date in yyyy,m,d format  
     */
    public $endDate;
    
    /**
     *
     * @var String $headline headline text
     */
    public $headline;
    
    /**
     *
     * @var String $text HTML body text 
     */
    public $text;
    
    /**
     *
     * @var String $tag optional tag
     */
    public $tag;
    
    /**
     *
     * @var Timeline_Date_Asset $asset image or video 
     */
    public $asset;
    
    public function __construct(array $params = null){

        $this->startDate = isset($params['startDate']) ? $params['startDate']: null;
        $this->endDate   = isset($params['endDate'])   ? $params['endDate']  : null;
        $this->headline  = isset($params['headline'])  ? $params['headline'] : null;
        $this->text      = isset($params['text'])      ? $params['text']     : null;
        $this->tag       = isset($params['tag'])       ? $params['tag']      : null;
        $this->asset     = isset($params['asset'])     ? $params['asset']    : null;
    }
    
}

class Timeline_Date_Asset {
    
    /**
     *
     * @var String URL pointing to a video or image 
     */
    public $media;
    
    /**
     *
     * @var String URL pointing to a small image 
     */
    public $thumbnail;
    
    /**
     *
     * @var String $credit name of the person who should be credited 
     */
    public $credit;
    
    /**
     *
     * @var String $caption textual caption 
     */
    public $caption;
    
    public function __construct(array $params = null){
        $this->media        = isset($params['media'])       ? $params['media']      : null;
        $this->thumbnail    = isset($params['thumbnail'])   ? $params['thumbnail']  : null;
        $this->credit       = isset($params['credit'])      ? $params['credit']     : null;
        $this->caption      = isset($params['credit'])      ? $params['credit']     : null;
    }
    
}
/*---------------------------------------------------------------
 *
 * Data Providers
 *
 *--------------------------------------------------------------- 
 */
abstract class Timeline_Data_Provider {
    
    public $source;
    public $data;
    
    public function get_data(){
        return $this->data = $this->exec_query();
    }
    
    protected function exec_query(){
        return $this->data;
    }
    
}

class File_Timeline_Data_Provider extends Timeline_Data_Provider {
    
    public function __construct(){
        $this->source = 'test.json';
    }
    
    protected function exec_query(){
        return file_get_contents($this->source);
    }
}

class Test_Timeline_Data_Provider extends Timeline_Data_Provider {
    
    public function __construct(){
        $this->source = $this->make_json();
    }
    
    protected function exec_query(){
        return $this->source;
    }
    
    private function make_json(){
        
        $timeline = $this->build_test_data();

        return json_encode((array)$timeline);
    }
    
    private function build_test_data(){
        
        $asset = new Timeline_Date_Asset(array(
                    "media"     => "http://youtu.be/u4XpeU9erbg",
                    "credit"    => "",
                    "caption"   => ""
                )
        );
        
        $date   = array();
        $date[] = new Timeline_Date(array(
            'startDate' => '2012,1,26',
            'endDate'   => '2012,1,27',
            'headline'  => "Sh*t Politicians Say",
            'text'      => '<p>In true political fashion, his character rattles 
                off common jargon heard from people running for office.</p>',
            'asset'     => $asset
            )
        );
        
        $timeline = new Timeline(array(
            "headline"  => "Sh*t People Say",
            "type"      => "default",
            "text"      => "People say stuff",
            "startDate" => "2012,1,26",
            "date"      => $date
            )
        );

        
        return array('timeline' => $timeline);
    }
}

class Omeka_Timeline_Data_Provider extends Timeline_Data_Provider {
    
    public function __construct(){

        $this->source = get_db();

    }
    
    protected function exec_query(){

        $source->get_table('');
    }
    
}

?>
