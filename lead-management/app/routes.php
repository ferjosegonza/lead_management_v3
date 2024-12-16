<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Models\Database;  // Asegúrate de que esta ruta sea correcta
use GuzzleHttp\Client;  // Asegúrate de tener Guzzle instalado
//use Exception;

return function (App $app) {
    // Permitir CORS para solicitudes OPTIONS
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        return $response;
    });

    // Ruta GET principal
    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello Andres!');
        return $response;
    });

    // Ruta para crear un "lead" (POST /leads)
    $app->post('/leads', function (Request $request, Response $response) {
        $data = $request->getParsedBody();

        // Validar los datos recibidos
        if (!isset($data['name']) || strlen($data['name']) < 3 || strlen($data['name']) > 50) {
            return $response->withStatus(400)->getBody()->write('Invalid name');
        }

        if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $response->withStatus(400)->getBody()->write('Invalid email');
        }

        if (!isset($data['source']) || !in_array($data['source'], ['facebook', 'google', 'linkedin', 'manual'])) {
            return $response->withStatus(400)->getBody()->write('Invalid source');
        }

        // Conectar a la base de datos
        $pdo = Database::connect();

        // Insertar el "lead" en la base de datos
        $stmt = $pdo->prepare("INSERT INTO leads (name, email, phone, source) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['name'], $data['email'], $data['phone'] ?? null, $data['source']]);
        $leadId = $pdo->lastInsertId();

        // Notificar a una API externa (opcional)
        $externalApiUrl = $_ENV['EXTERNAL_API_URL'];
        $payload = [
            'lead_id' => $leadId,
            'name' => $data['name'],
            'email' => $data['email'],
            'source' => $data['source']
        ];

        // Intentar enviar la notificación a la API externa
        $client = new Client();
        $retries = 0;
        $success = false;

        while ($retries < 3 && !$success) {
            try {
                $client->post($externalApiUrl, ['json' => $payload]);
                $success = true;
            } catch (\Exception $e) {  // Usar la clase global Exception
                $retries++;

                // Esperar antes de reintentar
                sleep(2);

                // Ruta completa del archivo de log
                $logFile = __DIR__ . '/../logs/error.log';  // Ruta relativa correcta

                // Verificar si la carpeta 'logs' existe, si no, crearla
                if (!file_exists(dirname($logFile))) {
                    mkdir(dirname($logFile), 0777, true);  // Crear carpeta si no existe
                }

                // Verificar si el archivo 'error.log' existe, si no, crear uno vacío
                if (!file_exists($logFile)) {
                    file_put_contents($logFile, "");  // Crear archivo vacío si no existe
                }

                // Registrar el error en el archivo de log
                $errorMessage = date('Y-m-d H:i:s') . " - " . $e->getMessage() . PHP_EOL;
                file_put_contents($logFile, $errorMessage, FILE_APPEND);
                
                // Responder con un error si no se puede notificar a la API externa
                $response->getBody()->write('Error while notifying external system: ' . $e->getMessage());
                return $response->withStatus(500);
            }
        }

        if (!$success) {
            return $response->withStatus(500)->getBody()->write('Failed to notify external system');
        }

        return $response->withStatus(201)->getBody()->write('Lead created and notified');
    });
};
