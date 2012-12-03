<?php



class Server {
    
    public $request;            //string
    public $response = null;    //json

    /**
     * 
     * @param string $request params that the server will use in building its response
     * 
     */
    public function __construct($request){
        
        //assert($request != null);
        $this->request = $request;
        $this->handleRequest();
    }
    
    /**
     * entry point for request handling
     * this should set the value of this->response 
     */
    private function handleRequest(){
        
        $dp = new Test_Timeline_Data_Provider();
        $this->response = $dp->get_data();
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
