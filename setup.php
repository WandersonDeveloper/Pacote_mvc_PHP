<?php
// setup.php

// Cria o composer.json se ele não existir
if (!file_exists('composer.json')) {
    echo "Criando arquivo composer.json...\n";
    $composerJsonContent = <<<JSON
{
    "require": {
        "vlucas/phpdotenv": "^5.5"
    },
    "autoload": {
        "psr-4": {
            "App\\\\": "app/",
            "Core\\\\": "core/"
        }
    }
}
JSON;
    file_put_contents('composer.json', $composerJsonContent);
    echo "Arquivo composer.json criado com sucesso.\n";
}

// Instala as dependências se necessário
if (!file_exists('vendor/autoload.php')) {
    echo "Instalando dependências com o Composer...\n";
    exec('composer install', $output, $returnVar);
    if ($returnVar !== 0) {
        echo "Erro ao instalar dependências. Verifique se o Composer está instalado e disponível no PATH.\n";
        exit(1);
    }
    echo "Dependências instaladas com sucesso.\n";
}

// Carrega o autoload do Composer
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;

// Cria o arquivo .env padrão se ele não existir
if (!file_exists('.env')) {
    $envContent = <<<ENV
DB_HOST=localhost
DB_NAME=meu_banco
DB_USER=usuario
DB_PASS=senha
BASE_URL=http://localhost/meu_projeto/
ENV;
    file_put_contents('.env', $envContent);
    echo "Arquivo .env criado com variáveis padrão.\n";
}

// Carrega o .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Função para criar diretórios, se não existirem
function createDir($path) {
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
        echo "Diretório criado: $path\n";
    }
}

// Função para criar arquivos com conteúdo básico, se não existirem
function createFile($path, $content = '') {
    if (!file_exists($path)) {
        file_put_contents($path, $content);
        echo "Arquivo criado: $path\n";
    }
}

// Estrutura de Diretórios do Projeto
$directories = [
    'app/controllers',
    'app/models',
    'app/views',
    'core',
    'config',
    'public/assets/css',
    'public/assets/js',
    'public/assets/images',
    'routes'
];

// Cria os diretórios
foreach ($directories as $directory) {
    createDir($directory);
}

// Cria o arquivo config.php que carrega os dados do .env
$configContent = <<<PHP
<?php
use Dotenv\Dotenv;

\$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
\$dotenv->load();

return [
    'db' => [
        'host' => getenv('DB_HOST'),
        'name' => getenv('DB_NAME'),
        'user' => getenv('DB_USER'),
        'pass' => getenv('DB_PASS'),
    ],
    'base_url' => getenv('BASE_URL')
];
PHP;
createFile('config/config.php', $configContent);

// Classe para Conexão com o Banco de Dados usando config.php
$databaseContent = <<<PHP
<?php
class Database {
    private static \$instance = null;

    public static function getConnection() {
        \$config = require __DIR__ . '/../config/config.php';
        \$dbConfig = \$config['db'];

        if (!self::\$instance) {
            self::\$instance = new PDO(
                'mysql:host=' . \$dbConfig['host'] . ';dbname=' . \$dbConfig['name'],
                \$dbConfig['user'],
                \$dbConfig['pass']
            );
            self::\$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::\$instance;
    }
}
PHP;
createFile('core/Database.php', $databaseContent);

// Classe Base para Modelos
$modelContent = <<<PHP
<?php
class Model {
    protected \$db;

    public function __construct() {
        \$this->db = Database::getConnection();
    }
}
PHP;
createFile('core/Model.php', $modelContent);

// Classe Base para Controladores
$controllerContent = <<<PHP
<?php
class Controller {
    protected function view(\$view, \$data = []) {
        extract(\$data);
        require "../app/views/\$view.php";
    }
}
PHP;
createFile('core/Controller.php', $controllerContent);

// Controlador de Exemplo
$homeControllerContent = <<<PHP
<?php
require_once '../core/Controller.php';

class HomeController extends Controller {
    public function index() {
        \$data = ['title' => 'Página Inicial'];
        \$this->view('home', \$data);
    }
}
PHP;
createFile('app/controllers/HomeController.php', $homeControllerContent);

// Modelo de Exemplo
$userModelContent = <<<PHP
<?php
require_once '../core/Model.php';

class User extends Model {
    public function getAllUsers() {
        \$stmt = \$this->db->query("SELECT * FROM users");
        return \$stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
PHP;
createFile('app/models/User.php', $userModelContent);

// View de Exemplo
$homeViewContent = <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo \$title; ?></title>
    <link rel="stylesheet" href="<?php echo getenv('BASE_URL'); ?>assets/css/style.css">
</head>
<body>
    <h1><?php echo \$title; ?></h1>
    <p>Bem-vindo ao sistema MVC em PHP!</p>
    <script src="<?php echo getenv('BASE_URL'); ?>assets/js/script.js"></script>
</body>
</html>
HTML;
createFile('app/views/home.php', $homeViewContent);

// Arquivo de Roteamento
$indexContent = <<<PHP
<?php
require '../vendor/autoload.php';
require '../core/Controller.php';
require '../core/Model.php';
require '../core/Database.php';

\$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
\$dotenv->load();

\$url = \$_GET['url'] ?? 'home/index';
\$url = explode('/', \$url);
\$controllerName = ucfirst(\$url[0]) . 'Controller';
\$method = \$url[1] ?? 'index';

require "../app/controllers/\$controllerName.php";
\$controller = new \$controllerName;
\$controller->\$method();
PHP;
createFile('public/index.php', $indexContent);

// Arquivo de Rotas
$routesContent = <<<PHP
<?php
// Definição de rotas
return [
    'home' => 'HomeController@index',
];
PHP;
createFile('routes/web.php', $routesContent);

// CSS e JS Padrão
createFile('public/assets/css/style.css', '/* CSS padrão do projeto */');
createFile('public/assets/js/script.js', '// JavaScript padrão do projeto');

echo "Estrutura MVC criada com sucesso!\n";
