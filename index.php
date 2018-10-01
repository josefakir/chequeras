<?php
require "vendor/autoload.php";
include "bootstrap.php";

use Mainclass\Models\Usuario;
use Mainclass\Models\Guiachequera;
use Mainclass\Middleware\Logging as Logging;

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;


define("URL_WS", "https://desarrollo.api.logify.com.mx/");
function TildesHtml($cadena) 
    { 
        return str_replace(array("á","é","í","ó","ú","ñ","Á","É","Í","Ó","Ú","Ñ"),
                                         array("&aacute;","&eacute;","&iacute;","&oacute;","&uacute;","&ntilde;",
                                                    "&Aacute;","&Eacute;","&Iacute;","&Oacute;","&Uacute;","&Ntilde;"), $cadena);     
    }

$app = new \Slim\App();
$app->add(new Logging());
$app->get("/",function($request, $response, $argas){
	?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Logify Chequeras</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
	<link rel="stylesheet" href="css/style.css">
	<link href="https://fonts.googleapis.com/css?family=Raleway" rel="stylesheet">

</head>
<body>
	<div id="header">
	</div>
	<div class="container">
		<div class="row">
			<div class="col-md-12">
				<p>&nbsp;</p>
				<div id="logos">
				<p><img src="img/logo.png" alt="" style="width: 100px"><img src="img/bancoppel.png" alt="" style="width: 100px"></p>
				<h1 class="tac">Chequeras</h1>
				<p class="tac">Iniciar Sesión:</p>
				</div>
			</div>
			<div class="col-md-3">
			</div>
			<div class="col-md-6">
				<form method="POST" action="<?php echo BASE_URL ?>/login">
				  <div class="form-group">
				    <input type="text" class="form-control"  placeholder="Correo electrónico" name="email" required><br>
				    <input type="password" class="form-control"  placeholder="Contraseña" name="password" required><br>
				    <p class="tac"><button type="submit" class="btn btn-primary" >Iniciar Sesión</button></p>
				    <p class="tac error"><?php echo base64_decode(htmlentities($_GET['m'])) ?></p>
				  </div>
				</form>
			</div>
			<div class="col-md-6">
				
			</div>
		</div>
	</div>
</body>
</html>
	<?php
});


$app->post('/login',function($request, $response, $args){
	$email = $request->getParsedBodyParam('email');
	$pass = md5($request->getParsedBodyParam('password'));
	$user = new Usuario();
	$users = $user::where('correo', $email)->where('contrasena', $pass)->get();
	if($users->count()>0){
		$_SESSION['auth'] = true;
		$_SESSION['id_usuario'] = $users[0]->id;
		$_SESSION['nombre'] = $users[0]->nombre.' '.$users[0]->paterno.' '.$users[0]->materno;
		$_SESSION['rol'] = $users[0]->rol;
		return $response->withHeader('Location', BASE_URL.'/inicio' );
	}else{
		return $response->withHeader('Location', BASE_URL."?m=".base64_encode('Usuario o contraseña incorrectos') );
	}
});

$app->get("/inicio",function($request, $response, $argas){
	?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Logify Chequeras</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
	<link rel="stylesheet" href="css/style.css">
	<link href="https://fonts.googleapis.com/css?family=Raleway" rel="stylesheet">

</head>
<body>
	<div id="header">
	</div>
	<div class="container">
		<div class="row">
			<div class="col-md-12">
				<p>&nbsp;</p>
				<div id="logos">
				<p><img src="img/logo.png" alt="" style="width: 100px"><img src="img/bancoppel.png" alt="" style="width: 100px"></p>
				
				</div>
				<h1 class="tac">GENERACIÓN DE GUÍAS</h1>
				<p class="tac">Por favor ingrese los archivos PAQ y AGE:</p>
			</div>
			<div class="col-md-3">
			</div>
			<div class="col-md-6">
				<form method="POST" action="<?php echo BASE_URL ?>/procesar"  enctype="multipart/form-data">
				  <div class="form-group">
				  	<label for="paq">PAQ:</label>
				    <input type="file" class="form-control"  placeholder="PAQ" name="paq" required><br>
				  	<label for="age">AGE:</label>
				    <input type="file" class="form-control"  placeholder="AGE" name="age" required><br>
				    <p class="tac"><button type="submit" class="btn btn-primary" >Generar archivos</button></p>
				    <p class="tac error"><?php echo base64_decode(htmlentities($_GET['m'])) ?></p>
				  </div>
				</form>
			</div>
			<div class="col-md-6">
				
			</div>
		</div>
	</div>
</body>
</html>
	<?php
});
$app->post("/procesar",function($request, $response, $argas){
	echo "<pre>";

	$files = $request->getUploadedFiles();
	$paq = $files['paq'];
	if($paq->getError() === UPLOAD_ERR_OK){
		$upload_paq_name = $paq->getClientFileName();
		$paq->moveTo("assets/txt/".date('YmdHis').$upload_paq_name);
		$paqpath = "assets/txt/".date('YmdHis').$upload_paq_name;
	}
	$age = $files['age'];
	if($age->getError() === UPLOAD_ERR_OK){
		$upload_age_name = $age->getClientFileName();
		$age->moveTo("assets/txt/".date('YmdHis').$upload_age_name);
		$age = "assets/txt/".date('YmdHis').$upload_age_name;
		$agepath = "assets/txt/".date('YmdHis').$upload_age_name;
	}
	$file_handle = fopen($paqpath, "rb");
	$i=0;
	$array = array();
	$array2 = array();
	echo "<pre>";
	while (!feof($file_handle) ) {
	$line_of_text = fgets($file_handle);
	if($i!=0){
		if(!empty($line_of_text)){
			array_push($array, $line_of_text);
		}
	}
	$i++;
	}
	fclose($file_handle);
	$file_handle = fopen($agepath, "rb");
	$i=0;
	while (!feof($file_handle) ) {
		$line_of_text = fgets($file_handle);
		if(!empty($line_of_text)){
			$var1 = $array[$i];
			$var2 = $line_of_text;
			array_push($array2, $var1.$var2);
		}
		$i++;
	}
	fclose($file_handle);
	unlink($agepath);
	unlink($paqpath);
	echo "<pre>";
	foreach ($array2 as $a) {
		$datos = explode("|", $a);
		$nombres =  explode(" ", $datos[22]);
		$tamano_nombres = count($nombres);
		$nombre = "";
		$paterno = "";
		$materno = "";
		if($tamano_nombres>=4){
			$paterno = $nombres[$tamano_nombres-2];
			$materno = $nombres[$tamano_nombres-1];
			for ($j=0; $j < $tamano_nombres -2 ; $j++) { 
				$nombre.=$nombres[$j]." ";
			}
		}else{
			$nombre = $nombres[0]." ";
			$paterno = $nombres[1];
			$materno = $nombres[2];
		}
		/* obtener id de estado */ 
		$cp = file_get_contents(URL_WS."info_cp/".$datos[26]);
		$cp = json_decode($cp);
		$id_edo_dest = $cp[0]->idEstado;
		//print_r($nombres);
		$url = URL_WS."createLabel";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$request_headers = array();
		$data = array(
			'nombre_dest' => $nombre, 
			'paterno_dest' => $paterno, 
			'materno_dest' => $materno, 
			'dir1_dest' => $datos[23], 
			'dir2_dest' => $datos[24], 
			'id_edo_dest' => $id_edo_dest, 
			'edo_dest' => $datos[29], 
			'mun_dest' =>  $datos[30], 
			'asent_dest' => $datos[25], 
			'cp_dest' => $datos[26],
			'tel_dest' => $datos[27], 
			'nombre_remit' => 'LUIS MANUEL', 
			'paterno_remit' => 'IBARRA' , 
			'materno_remit' => 'HURTADO' , 
			'dir1_remit' =>  'INSURGENTES SUR 533', 
			'dir2_remit' => 'PISO 3', 
			'id_edo_remit' => 9, 
			'edo_remit' => 'CDMX', 
			'mun_remit' => 'MIGUEL HIDALGO', 
			'asent_remit' => 'ESCANDÓN I SECCIÓN', 
			'cp_remit' => '11800', 
			'tel_remit' => '015552780000', 
			'compania_remit' => 'BANCOPPEL', 
			'client_code' => 'BCL', 
			'branch_number' => '0001', 
			'servicio' => 'Terrestre', 
			'cont_paquete' => $datos[6],  
			'peso' => $datos[1], 
			'ids_tokens' => $datos[7], 
			'fecha_atencionv' => date('Y-m-d H:i:s'),
			'status' => 'solicitado'
		);
		$request_headers[] = 'apikey: 17c1f771550d3a3488cc20ca9f428aea';
		curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result = curl_exec ($ch);
		curl_close ($ch);
		$result = json_decode($result);
		print_r($result);
	}
	/* PASO 1 GENERAR ARCHIVOS DE GUÍAS */


});
$app->run();



