<?php
require "../config/cors.php";
require '../vendor/autoload.php';
require '../config/database.php';

try {
    // Leer la entrada JSON
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar entrada
    if (isset($input['email']) && !empty($input['email'])) {
        $email = $input['email'];

        // Comprobar si el correo electrónico existe
        $checkEmailSql = "SELECT id FROM users WHERE email = ?";
        $checkStmt = $pdo->prepare($checkEmailSql);
        $checkStmt->execute([$email]);
        $userId = $checkStmt->fetchColumn();

        if ($userId) {
            // Obtener el ID del usuario admin
            $adminEmail = 'admin@example.com'; // Cambiar al email del admin real
            $checkAdminEmailSql = "SELECT id FROM users WHERE email = ?";
            $checkAdminStmt = $pdo->prepare($checkAdminEmailSql);
            $checkAdminStmt->execute([$adminEmail]);
            $adminId = $checkAdminStmt->fetchColumn();

            if ($adminId) {
                // Iniciar una transacción
                $pdo->beginTransaction();

                try {
                    // Actualizar los QR relacionados para que se asocien al usuario admin
                    $updateQrSql = "UPDATE qr_codes SET created_by = ? WHERE created_by = ?";
                    $updateQrStmt = $pdo->prepare($updateQrSql);
                    $updateQrStmt->execute([$adminId, $userId]);

                    // Eliminar el usuario
                    $deleteUserSql = "DELETE FROM users WHERE id = ?";
                    $deleteUserStmt = $pdo->prepare($deleteUserSql);
                    $deleteUserStmt->execute([$userId]);

                    // Confirmar la transacción
                    $pdo->commit();

                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'message' => "El usuario ha sido eliminado y sus QR han sido transferidos al admin",
                        'email' => $email
                    ]);
                } catch (Exception $e) {
                    // Revertir la transacción en caso de error
                    $pdo->rollBack();
                    throw $e;
                }
            } else {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['message' => 'El usuario admin no existe']);
            }
        } else {
            // El correo electrónico no existe en la base de datos
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['message' => 'El correo electronico no existe', 'email' => $email]);
        }
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => 'Datos incompletos']);
    }
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => 'Error: ' . $e->getMessage()]);
}
?>
