<?php
    
     function connectDB(){
            include 'server.php';
             $conexion = mysqli_connect($server, $user, $pass,$database);

        if($conexion){
            echo '';
        }else{
            echo 'Ha sucedido un error inexperado en la conexion de la base de datos';
        }
    
    return $conexion;
    }

function disconnectDB($conexion){
    include 'server.php';
    $close = mysqli_close($conexion);

        if($close){
            echo '';
        }else{
            echo 'Ha sucedido un error inexperado en la desconexion de la base de datos';
        }
}
?>