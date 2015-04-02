<?php

$answer = Array('status' => 'error', 'msg' => 'disabled'); echo json_encode($answer); exit();

header('Access-Control-Allow-Origin: http://devpgsv.com');
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

unset($answer);
$answer['fileList'] = Array();

if(is_array($_FILES)) {
   foreach ($_FILES['file']['name'] as $fileIndex => $value){
      //array_push($answer['fileList'], $value);
      $answer['fileList'][$fileIndex] = $value;
      if(is_uploaded_file($_FILES['file']['tmp_name'][$fileIndex])) {
         $sourcePath = $_FILES['file']['tmp_name'][$fileIndex];
         $targetPath = "files/".$_FILES['file']['name'][$fileIndex];
         if(move_uploaded_file($sourcePath,$targetPath)) {
            $answer['files'][$value]['status'] = true;
            $answer['files'][$value]['statusText'] = 'ok';
            $answer['files'][$value]['msg'] = 'File uploaded';
         } else {
            $answer['files'][$value]['status'] = false;
            $answer['files'][$value]['statusText'] = 'error';
            $answer['files'][$value]['msg'] = 'Error on moving file from temp to destination';
         }
      } else {
         $answer['files'][$value]['status'] = false;
         $answer['files'][$value]['statusText'] = 'error';
         $answer['files'][$value]['msg'] = 'File not uploaded';
      }
   }
} else {
   print_r($files);
}
echo json_encode($answer);
exit();