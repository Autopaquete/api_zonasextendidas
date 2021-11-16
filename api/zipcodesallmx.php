<?php
header('Access-Control-Allow-Origin: *');

include ("../src/server/conecction.php");
include '../src/verification/verification.php';

switch($_SERVER["REQUEST_METHOD"]) {
    case 'GET': 
        if(!isset($_GET['cp']) || !isset($_GET['token']) || strlen($_GET['cp']) === 0|| strlen($_GET['token']) === 0){
            error('Argumentos invalidos - Advertencia -> El uso de esta api sin previa autorizacion de company puede ser causa de un delito'); 
            return;
        }
            $cp = $_GET['cp'];
            $token = $_GET['token'];

            $empresa = explode('_',$token);
            
            if(sizeof($empresa) !== 2 || !is_numeric($empresa[1])) {error('Token Invalido - Solicite su acceso a su asesor'); break;}
            
            $conexion = connectDB();
            mysqli_set_charset($conexion, "utf8");
            
            $sql = "SELECT token, activo FROM verificacion WHERE id_empresa = '$empresa[1]'";
            
            
            if (!$result = mysqli_query($conexion, $sql)) die(); //si la conexión cancelar programa
 
            $row = mysqli_fetch_assoc($result);
            
            if(!$row) {error('Token Invalido - Solicite su acceso a su asesor'); break;}
            $verificado = token($row['token'],$empresa[0]);
            
            if(!$verificado){
                error('Token Invalido - Solicite su acceso a su asesor');
                break;
            }
            if(!$row['activo']) {error('Suscripcion expirada - Comuniquese con su asesor'); break;}
            
            $existing = searchCP($conexion, $cp);

            if($existing['cp']) {
                    addHistory($conexion,$empresa[1],$cp,'1','codigo encontrado');
            } else {
                    addHistory($conexion,$empresa[1],$cp,'0','codigo no se encuentra en la db');
                    error('Codigo postal no encontrado');
                    return;
                
            }       
            
            break;
            case 'POST':
                error('Metodo no permitido');
            break;
}
            
function searchCP($conexion, $cp){
                mysqli_set_charset($conexion, "utf8");

                $sql = "SELECT * FROM `mxcp` WHERE codigo = '$cp'";
                
                if (!$result = mysqli_query($conexion, $sql)) die(); //si la conexión cancelar programa
                
                if($result->num_rows === 0) return array('cp' => false);
                $array = [];
                while($row = mysqli_fetch_assoc($result)) {
                    $colonias[] = $row['colonia'];
                    $municipio[] = $row['municipio'];
                    $estado[] = $row['estado'];
                    $ciudad[] = strlen($row['ciudad']) > 0 ? $row['ciudad'] : $row['municipio'] ;
                }

                echo json_encode(array("location" => array(
                        "municipio" => $municipio[0],
                        "estado" => $estado[0],
                        "ciudad" => $ciudad[0],
                ),
                "colonias" =>  $colonias,
                "conflicts" => array(
                                "problems" => "0",
                                "description" => "empty",
                            ),
                ));
                return array('cp' => true);
}



function error($error) {
    echo json_encode(array(
        "location" => array(
                        "municipio" => "-",
                        "estado" => "-",
        ),
        "conflicts" => array(
            "problems" => "1",
            "description" => $error,
        ),
    ));
}

function addHistory($conexion,$empresa,$cp,$status,$descripcion) {
    
    mysqli_set_charset($conexion, "utf8");

    $sql = "INSERT INTO historial (`id_empresa`, `cp`, `status`,`descripcion`) VALUES ('$empresa','$cp','$status','$descripcion')";
            
            
    if (!$result = mysqli_query($conexion, $sql)) die(); //si la conexión cancelar programa
}

?>