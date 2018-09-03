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
	/* PAQ */
	$file_handle = fopen($paqpath, "rb");
	$i=0;
	echo "<pre>";
	while (!feof($file_handle) ) {
	$line_of_text = fgets($file_handle);
	$parts = explode('|', $line_of_text);
	if($i!=0){
		// Guardar guía
		if(!empty($parts[11])){
			$guia = new Guiachequera();
			$guia->id = $parts[11];
			$guia->paq1 = $parts[0];
			$guia->paq2 = $parts[2];
			$guia->paq3 = $parts[3];
			$guia->paq4 = $parts[4];
			$guia->paq5 = $parts[5];
			$guia->paq6 = $parts[12];
			$guia->peso = $parts[1];
			$guia->cantidad = $parts[4];
			$guia->descripcion = $parts[6];
			$guia->referencia = $parts[7];
			$guia->remitente = $parts[10];
			$guia->save();
		}
	}
	$i++;
	}
	fclose($file_handle);
	unlink($paqpath);

	/* AGE */
	$file_handle = fopen($agepath, "rb");
	$i=0;
	echo "<pre>";
	while (!feof($file_handle) ) {
		$line_of_text = fgets($file_handle);
		$parts = explode('|', $line_of_text);
		$guias = new Guiachequera();
		$guia = $guias->find($parts[0]);
		if(!empty($line_of_text)){
			$guia->destinatario = utf8_decode($parts[2]);
			$guia->correo_destinatario = utf8_decode($parts[3]);
			$guia->ciudad = utf8_decode($parts[5]);
			$guia->direccion1 = utf8_decode($parts[6]);
			$guia->direccion2 = utf8_decode($parts[7]);
			$guia->colonia = utf8_decode($parts[9]);
			$guia->telefono = utf8_decode($parts[11]);
			$guia->estado = utf8_decode($parts[13]);
			$guia->municipio = utf8_decode($parts[14]);
			$guia->save();
			/* QR CODES */
			$qrCode = new QrCode($guia->id);
			$qrCode->setSize(300);

			// Set advanced options
			$qrCode->setWriterByName('png');
			$qrCode->setMargin(10);
			$qrCode->setEncoding('UTF-8');
			$qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH);
			$qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0]);
			$qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255]);
			$qrCode->writeFile('qrs/'.$guia->id.'.png');

			$html =
			      '
			<!DOCTYPE html>
			<html lang="en">
			<head>
				<meta charset="UTF-8">
				<title>Guía Logify</title>
				<style>
					html,body,div,span,applet,object,iframe,h1,h2,h3,h4,h5,h6,p,blockquote,pre,a,abbr,acronym,address,big,cite,code,del,dfn,em,img,ins,kbd,q,s,samp,small,strike,strong,sub,sup,tt,var,b,u,i,center,dl,dt,dd,ol,ul,li,fieldset,form,label,legend,table,caption,tbody,tfoot,thead,tr,th,td,article,aside,canvas,details,embed,figure,figcaption,footer,header,hgroup,menu,nav,output,ruby,section,summary,time,mark,audio,video{margin:0;padding:0;border:0;font-size:100%;font:inherit;vertical-align:baseline}article,aside,details,figcaption,figure,footer,header,hgroup,menu,nav,section{display:block}body{line-height:1}ol,ul{list-style:none}blockquote,q{quotes:none}blockquote:before,blockquote:after,q:before,q:after{content:"";content:none}table{border-collapse:collapse;border-spacing:0}
					body{font-family: Arial, sans-serif;}
					#wrap{width:300px; margin:0 auto;border:1px solid #e0e2e5;}
					#logo{width:100px; margin-left:100px}
					#qr{width:100px; margin-left:100px}
					#destinatario{padding:20px}
					h2{font-weight: bold; font-size: 25px;}
					#remitente{padding:20px}
					p {line-height: normal;}
					#extras{width: 92%;padding: 20px 4%;background: #e0e2e5; text-align: center;}
					#extras h2{font-size: 50px;text-align: center;}
					#no_guia{text-align:center; font-weight:bolc; font-size:20px}
				</style>
			</head>
			<body>
				<div id="wrap">
					<br>
					<br>
					<img id="logo" src="img/logo.png" alt=""> <br>
					<img id="qr" src="'.'qrs/'.$guia->id.'.png'.'" alt="">
					<div id="no_guia">'.$guia->id.'</div>
					<div id="remitente">
						<h2>REMITENTE</h2>
						<p>'.$guia->remitente.'</p>
					</div>
					<div id="destinatario">
						<h2>DESTINATARIO</h2>
						<p>'.$guia->destinatario .' <br> '.$guia->direccion1.' <br> '.$guia->direccion2.' '.$guia->colonia.' '.$guia->municipio.' '.$guia->estado.'<br>Tel. '.$guia->telefono.' <br></p>
						<p>Contenido del paquete: '.$cont_paquete.'</p>
						<p>Números de token: '.str_replace("|", " - ", $ids_tokens).'</p>
					</div>
					<div id="extras">
						<h2>'.$guia->cp.'</h2>
						<p>'.$guia->estado.'</p>
						<p>'.$guia->municipio.'</p>
						<p>'.$guia->colonia.'</p>
					</div>
				</div>
			</body>
			</html>
      		';
      		$html = TildesHtml($html);
		    $options = new Options();
			$options->setIsRemoteEnabled(true);
		    $dompdf = new DOMPDF($options);
		    $dompdf->load_html($html);
		    $dompdf->render();
		    $output = $dompdf->output();
		    file_put_contents('pdfs/'.$guia->id.'.pdf', $output);
		    $base64_pdf =  file_get_contents('pdfs/'.$guia->id.'.pdf');
		    $base64_pdf = base64_encode($base64_pdf);
		}
	}
	fclose($file_handle);
	unlink($agepath);
});
$app->run();



