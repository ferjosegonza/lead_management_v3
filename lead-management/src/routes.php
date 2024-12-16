<?php
$app->post('/leads', function ($request, $response) {
    $data = $request->getParsedBody();

    // Validación de los campos
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $phone = $data['phone'] ?? '';
    $source = $data['source'] ?? '';

    if (strlen($name) < 3 || strlen($name) > 50) {
        return $response->withJson(['error' => 'El nombre debe tener entre 3 y 50 caracteres'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $response->withJson(['error' => 'El correo electrónico no es válido'], 400);
    }

    // Insertar el lead en la base de datos
    $stmt = $this->db->prepare("INSERT INTO leads (name, email, phone, source) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $source]);

    $lead_id = $this->db->lastInsertId();

    // Notificar al sistema externo
    $externalApiUrl = getenv('EXTERNAL_API_URL');
    $data = [
        'lead_id' => $lead_id,
        'name' => $name,
        'email' => $email,
        'source' => $source
    ];

    $ch = curl_init($externalApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $responseExternal = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
        // Intentos de reintentar en caso de error
        $attempts = 0;
        $maxAttempts = 3;
        $success = false;
        while ($attempts < $maxAttempts && !$success) {
            sleep(2);
            $ch = curl_init($externalApiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $responseExternal = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode < 400) {
                $success = true;
            }
            $attempts++;
            curl_close($ch);
        }

        if (!$success) {
            file_put_contents('logs/error.log', 'Error al notificar al sistema externo.' . PHP_EOL, FILE_APPEND);
        }
    }

    return $response->withJson(['message' => 'Lead creado exitosamente'], 201);
});
