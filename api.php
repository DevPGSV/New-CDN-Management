<?php
ini_set('display_errors','1'); error_reporting(E_ALL);
header('Content-Type: application/json');
require_once ('db.php');

unset($answer);
$answer['status'] = 'error';
$answer['msg'] = 'Error: unknown';

if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != 'username' || $_SERVER['PHP_AUTH_PW'] != 'password') {
   header('WWW-Authenticate: Basic realm="DevPGSV_Services_CDN"');
   header('HTTP/1.0 401 Unauthorized');
   $answer['status'] = 'error';
   $answer['msg'] = 'Error: auth';
   echo json_encode($answer);
   exit();
}

//echo '{"status":"ok", "msg": "OK!"};


if (!isset($_POST['files']) || empty ($_POST['files'])) {
   $answer['status'] = 'error';
   $answer['msg'] = 'Error: no files';
   echo json_encode($answer);
   exit();
}

$fileArray = json_decode($_POST['files'], true);

if (!isset($fileArray['list']) || empty ($fileArray['list'])) {
   $answer['status'] = 'error';
   $answer['msg'] = 'Error: no file list';
   echo json_encode($answer);
   exit();
}

/*
echo '<pre>', print_r($fileArray, true), '</pre>';
exit();*/


try {
   
   $db->beginTransaction();
   //throw new Exception('Exc');
   $fileListId = '';
   $smtpFiles = $db->prepare("INSERT INTO files (fileName, fileCode, filePath, mimeType, status, timestamp) VALUES(?,?,?,?,?,?)");
   foreach ($fileArray['list'] as $fileItem) {
      
      if (isset($fileItem['name']) && !empty($fileItem['name'])) {
         if (isset($fileItem['mime']) && !empty($fileItem['mime'])) {
            
            do {
               $fileCode = randStr(60);
               $stmt = $db->prepare("SELECT fileCode FROM files WHERE fileCode=?");
               $stmt->execute(array($fileCode));
            } while ($stmt->rowCount() != 0);
            
            
            $smtpFiles->execute(array(preg_replace("~[^[:alnum:]]~ui", '', $fileItem['name']), $fileCode, 'files/[$fileCode]', preg_replace("~[^[:alnum:/]]~ui", '', $fileItem['mime']), 'requested', time()));
            $insertId = $db->lastInsertId();
            if (!empty($fileListId)) {$fileListId .= ',';}
            $fileListId .= $insertId;
            
            $answer['files'][$fileItem['name']] = Array(
               'status' => 'ok',
               'msg' => 'ok',
               'urlCode' => $fileCode
            );
         } else {
            $answer['files'][$fileItem['name']] = Array(
               'status' => 'error',
               'msg' => 'Error: no mime type specified'
            );
         }
      }
   }
   $smtp_upload_requests = $db->prepare("INSERT INTO upload_requests(code, ip, files, timestamp) VALUES(?, ?, ?, ?)");
   $smtp_upload_requests->execute(array(randStr(50), $_SERVER['REMOTE_ADDR'], $fileListId, time()));
   if ($db->commit()) {
      $answer['status'] = 'ok';
      $answer['msg'] = 'ok';
   } else {
      $answer['status'] = 'error';
      $answer['msg'] = 'Error: SQL Transaction';
   }
   
   echo json_encode($answer);
   exit();
} catch (PDOException $e) {
   $db->rollback();
   //$e->getMessage();
   $answer['status'] = 'error';
   $answer['msg'] = 'Error: SQL Error';
   echo json_encode($answer);
   echo '<br><br><br>'.$e->getMessage();
} catch (Exception $e) {
   $db->rollback();
   $answer['status'] = 'error';
   $answer['msg'] = 'Error: '.$e->getMessage().' ';
   echo json_encode($answer);
}

exit();













function randStr($lenght) {
   global $uniqueString;
   $str = "";
   $tmp = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!"#$%^&*()_+{}@:><'.time().mt_rand().$uniqueString;
   while (strlen($str) < $lenght) {
      $str = $str.sha1(str_shuffle($tmp));
   }
   return str_shuffle(substr($str, 0, $lenght));
}