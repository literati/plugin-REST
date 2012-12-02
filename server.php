<?php

class Server {
    
    public $request;            //string
    public $response = null;    //json

    public function __construct($request){
        
        $this->request = $request;
    }
    
    private function handleRequest(){
        
        $this->response = file_get_contents('test.json');
        
    }
    
    public function getResponse(){
        
        $this->handleRequest();
        return $this->response;
    }
}


$server = new Server($_GET);

echo $server->getResponse();

?>
