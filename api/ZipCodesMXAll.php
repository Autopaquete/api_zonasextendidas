<?php
header('Access-Control-Allow-Origin: *');

include ("../src/server/conecction.php");
include '../src/verification/verification.php';

switch($_SERVER["REQUEST_METHOD"]) {
    case 'GET':
        if(!isset($_GET['cp']) || !isset($_GET['token']) ){error('argumentos invalidos'); return;}

            $cp = $_GET['cp'];
            $token = $_GET['token'];
            $empresa = explode('_',$token);
            $conexion = connectDB();
            mysqli_set_charset($conexion, "utf8");

            $sql = "SELECT token FROM verificacion WHERE id_empresa = '$empresa[1]'";
            
            
            if (!$result = mysqli_query($conexion, $sql)) die(); //si la conexión cancelar programa
 
            $row = mysqli_fetch_assoc($result);
            
            $verificado = token($row['token'],$empresa[0]);
            $existing = searchCP($conexion, $cp);

            if(!$verificado){
                error('Token Invalid');
                return;
            }
            
            if(!$existing['cp']) {
                error('Codigo postal no encontrado');
                return;
            }
            
            $search1 = searchParcel($conexion, $cp);
            $search2 = searchAutopaquete($conexion, $cp);
            
            if($search1['ze'] === 'no' && $search2['ze'] === 'no') {
                echo json_encode(array(
                    "location" => array(
                                    "municipio" => $existing[0]['d_mnpio'],
                                    "estado" => $existing[0]['d_estado'],
                    ),
                    "parcel" => array(
                        "fedex" => false,
                        "redpack" => false,
                        "autoencargo" => "ex",
                    ),
                    "conflicts" => array(
                        "problems" => "0",
                        "description" => "empty",
                    ),
                ));
            } else if($search1['ze'] === 'no' && $search2['ze'] === 'si'){
                echo json_encode(array(
                    "location" => array(
                                    "municipio" => $existing[0]['d_mnpio'],
                                    "estado" => $existing[0]['d_estado'],
                    ),
                    "parcel" => array(
                        "fedex" => false,
                        "redpack" => false,
                        "autoencargo" => $search2[0]['concepto'],
                    ),
                    "conflicts" => array(
                        "problems" => "0",
                        "description" => "empty",
                    ),
                ));
            } else if($search1['ze'] === 'si' && $search2['ze'] === 'si') {
                echo json_encode(array(
                    "location" => array(
                                    "municipio" => $existing[0]['d_mnpio'],
                                    "estado" => $existing[0]['d_estado'],
                    ),
                    "parcel" => array(
                        "fedex" => $search1['0']['fedex'],
                        "redpack" => $search1['0']['redpack'],
                        "autoencargo" => $search2[0]['concepto'],
                    ),
                    "conflicts" => array(
                        "problems" => "0",
                        "description" => "empty",
                    ),
                ));
            } else if($search1['ze'] === 'si' && $search2['ze'] === 'no') {
                echo json_encode(array(
                    "location" => array(
                                    "municipio" => $existing[0]['d_mnpio'],
                                    "estado" => $existing[0]['d_estado'],
                    ),
                    "parcel" => array(
                        "fedex" => $search1['0']['fedex'],
                        "redpack" => $search1['0']['redpack'],
                        "autoencargo" => "ex",
                    ),
                    "conflicts" => array(
                        "problems" => "0",
                        "description" => "empty",
                    ),
                ));
            }

            // echo "Paqueteria con sona extendida Fedex [*" . $search1['0']['fedex']. "*] y [*". $search1['0']['redpack'] . "*]\n";
            // echo "con zona autoencargo de tipo: ".$search2['0']['concepto'];            
        
            
                

        break;
    case 'POST':
        break;
}

function searchCP($conexion, $cp){
    mysqli_set_charset($conexion, "utf8");

    $sql = "SELECT * FROM `cp` WHERE c_codigo = '$cp'";
              
    if (!$result = mysqli_query($conexion, $sql)) die(); //si la conexión cancelar programa
    
    if($row = mysqli_fetch_assoc($result)){
        return array("cp" => true, $row);
    } else {
        return array("cp" => false);
    }
}

function searchParcel($conexion, $cp) {
    
    mysqli_set_charset($conexion, "utf8");

    $sql = "SELECT c.c_codigo, c.d_mnpio, c.d_estado, e.fedex,e.redpack FROM `cp` AS c, `extendidas` AS e WHERE c.c_codigo = '44330' AND c.c_codigo = e.id_zip";
              
    if (!$result = mysqli_query($conexion, $sql)) die(); //si la conexión cancelar programa
    
    if($row = mysqli_fetch_assoc($result)){
        return array("ze" => "si",$row);
    } else {
        return array("ze" => "no");
    }
}

function searchAutopaquete($conexion, $cp) {
    mysqli_set_charset($conexion, "utf8");

    $sql = "SELECT c.c_codigo, c.d_mnpio, c.d_estado, a.concepto FROM `cp` AS c, `autopaquete` AS a WHERE c.c_codigo = '$cp' AND c.c_codigo = a.id_zip";
              
    if (!$result = mysqli_query($conexion, $sql)) die(); //si la conexión cancelar programa
            
    if($row = mysqli_fetch_assoc($result)){
        return array("ze" => "si",$row);
    } else {
        return array("ze" => "no");
    }
}

function error($error) {
    echo json_encode(array(
        "location" => array(
                        "municipio" => "-",
                        "estado" => "-",
        ),
        "parcel" => array(
            "fedex" => "-",
            "redpack" => "-",
            "autoencargo" => "-",
        ),
        "conflicts" => array(
            "problems" => "1",
            "description" => $error,
        ),
    ));
}

?>
