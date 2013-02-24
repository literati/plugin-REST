<?php 

$i=0;
    while(loop_files_for_item()){
        //if there's more than one 
        //xml file attached, do nothing
        if ($i > 0) break;
        
        
        $file = get_current_file();
        
        //don't try to echo an image
        if($file->mime_browser == 'application/xml'){
            
            //blatant borrowing from TeiDisplay
            $xp          = new XsltProcessor();
            $xsl         = new DomDocument;
            $xml_doc     = new DomDocument;
            $stylesheet  = TEI_DISPLAY_STYLESHEET_FOLDER.'/default.xsl';
            $displayType = 'entire';
	
            $xml_doc->load(FILES_DIR.'/'.$file->archive_filename);
            $xsl->load($stylesheet);
            $xp->importStylesheet($xsl);
	
            //set query parameter to pass into stylesheet
            $xp->setParameter('', 'display', $displayType);
            
            try { 
                if ($doc = $xp->transformToXML($xml_doc)) {			
                        echo $doc;
                        debug("echoing successful transform");
                }
            } catch (Exception $e){
                    $this->view->error = $e->getMessage();
                    debug("problems transforming xml");
            }
            $i++; 
            
        }
        
    }
    
?>


