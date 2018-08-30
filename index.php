
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Logify Rastreo</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
	<style>
		body{font-family: 'Raleway', sans-serif;}
		#header{width: 100%; height: 300px; background: url(img/back.jpg); background-size: cover; position: relative;}
		#logo{position: absolute; left: 50%; top: 50%; margin-left: -63px; margin-top: -25px}
		.tac{text-align: center}
		#logos{width: 200px;margin: 0 auto;}
	</style>
	<link href="https://fonts.googleapis.com/css?family=Raleway" rel="stylesheet">

</head>
<body>
	<div id="header">
		<img id="logo" src="img/logo.png" alt="">
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
				<form method="GET" action="rastreo.php">
				  <div class="form-group">
				    <input type="text" class="form-control"  placeholder="Correo electrónico" name="correo" required><br>
				    <input type="password" class="form-control"  placeholder="Contraseña" name="password" required><br>
				    <p class="tac"><button type="submit" class="btn btn-primary" >Iniciar Sesión</button></p>
				  </div>
				</form>
			</div>
			<div class="col-md-6">
				
			</div>
		</div>
	</div>
</body>
</html>