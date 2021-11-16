<?php
 function token($token,$apikey){
     if(password_verify($apikey,$token)){
     return true;
 }else{
     return false;
 }
 }
?>