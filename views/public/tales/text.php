<?php 

    while(loop_files_for_item()){
        $file = get_current_file();
        
        //don't try to echo an image
        if($file->mime_browser == 'application/xml'){
            echo file_get_contents(FILES_DIR.'/'.$file->archive_filename);
        }
        
    }
    
?>


