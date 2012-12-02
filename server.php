<?php

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
     * @var Timeline_Date_Asset a media element for the main timeline 
     */
    public $asset;
    
    /**
     *
     * @var Date[] array of Timeline_Date objects
     */
    public $date;
    
    
}


class Timeline_Date {
    
    /**
     *
     * @var String $startdate representing the 
     * event start date in yyyy,m,d format 
     */
    public $startdate;
    
    /**
     *
     * @var String $enddate representing the 
     * event enddate date in yyyy,m,d format  
     */
    public $enddate;
    
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
    
}

class Server {
    
    public $request;            //string
    public $response = null;    //json

    /**
     * 
     * @param string $request params that the server will use in building its response
     * 
     */
    public function __construct($request){
        
        assert($request != null);
        $this->request = $request;
        $this->handleRequest();
    }
    
    /**
     * entry point for request handling
     */
    private function handleRequest(){
        
        $this->response = file_get_contents('test.json');
        
    }
    
    /**
     * 
     * @return String jsonified result set
     */
    public function getResponse(){
        
        return $this->response;
    }
}


$server = new Server($_GET);

echo $server->getResponse();

?>
