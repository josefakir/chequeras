composer.phar require slim/slim "^3.0"
composer.phar require illuminate/database "~5.2"
echo "RewriteEngine On\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^index.php [QSA,L]" > .htaccess
echo '<?php
	require "vendor/autoload.php";
	include "bootstrap.php";

	use Mainclass\Models\Usuario;
	use Mainclass\Middleware\Logging as Logging;
	$app = new \Slim\App();
	$app->add(new Logging());
	$app->get("/",function($request, $response, $argas){
		return $response->write("hello");
		$usuario = new Usuario();
		$usuarios = $usuario->all();
		return $response->withStatus(200)->withJson($usuarios);
	});
	$app->run();' > index.php
echo '
<?php
	include "config/credentials.php";
	include "vendor/autoload.php";
	use Illuminate\Database\Capsule\Manager as Capsule;

	$capsule = new Capsule();
	$capsule->addConnection([
		"driver"    => "mysql",
		"host"      => $db_host,
		"database"  => $db_name,
		"username"  => $db_user,
		"password"  => $db_pass,
		"charset"   => "utf8",
		"collation" => "utf8_general_ci",
		"prefix"    => ""
	]);

	$capsule->bootEloquent();' > bootstrap.php
mkdir config
cd config
echo '<?php
		$db_host = "127.0.0.1";
		$db_name = "intranet";
		$db_user = "root";
		$db_pass = "root";' > credentials.php
cd ..
mkdir src
cd src
mkdir Mainclass
cd Mainclass
mkdir Middleware
cd Middleware
echo '<?php
	namespace Mainclass\Middleware;
	class Logging{
		public function __invoke($request, $response, $next){
			error_log($request->getMethod() . " -- ". $request->getUri());
			$response = $next($request, $response);
			return $response;
		}
	}
' > Logging.php
cd ..
mkdir Models
cd Models
echo '<?php
	namespace Mainclass\Models;
	class Usuario extends \Illuminate\Database\Eloquent\Model{

	}
'> Usuario.php
cd ../../../
echo '{
    "require": {
        "slim/slim": "^3.0",
        "illuminate/database": "~5.2"
    },
    "autoload" : {
    	"psr-4" : {"Mainclass\\\" : "src/Mainclass/"}
    }
}
' > composer.json
composer.phar update
