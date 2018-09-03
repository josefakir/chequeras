<?php
require "vendor/autoload.php";
include "bootstrap.php";

use Mainclass\Models\Usuario;
use Mainclass\Middleware\Logging as Logging;

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

	while (!feof($file_handle) ) {

	$line_of_text = fgets($file_handle);
	$parts = explode('|', $line_of_text);
	echo "<pre>";
	print_r($parts);

	}

	fclose($file_handle);
});
$app->run();
