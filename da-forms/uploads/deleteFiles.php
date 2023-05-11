<?php 

// get all excel files
$files = glob('*.xlsx');

foreach($files as $file){
  if(is_file($file)) {
    unlink($file);
  }
}