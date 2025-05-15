<?php
// Configuración de auditoría
// Cambia la ubicación fuera del directorio público
define('AUDIT_LOG_FILE', __DIR__ . '/../private/audit_log.txt');
define('MAX_LOG_SIZE', 10485760); // 10MB

/**
 * Registra una acción en el log de auditoría
 * 
 * @param string $module Módulo donde ocurrió la acción
 * @param string $action Tipo de acción (Login, Create, Update, Delete, etc.)
 * @param string $description Descripción detallada de la acción
 * @param array|null $data Datos adicionales relevantes (opcional)
 */


 
function logAuditAction($module, $action, $description, $data = null) {
    // Obtener información del usuario
    $userId = $_SESSION['id_usuario'] ?? 'guest';
    $username = $_SESSION['usuario'] ?? 'guest';
    $userIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Construir el registro
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [USER:$userId:$username] [IP:$userIp] [MODULE:$module] [ACTION:$action] $description";
    
    // Agregar datos adicionales si existen
    if ($data !== null && is_array($data)) {
        $logEntry .= " | DATA: " . json_encode($data);
    }
    
    // Agregar información del navegador
    $logEntry .= " | AGENT: $userAgent\n";
    
 // Clave de encriptación (debería estar en configuración segura)
 $encryptionKey = 'tu_clave_secreta_aqui'; // Mejor usar una variable de entorno
    
 // Encriptar el contenido
 $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
 $encrypted = openssl_encrypt($logEntry, 'aes-256-cbc', $encryptionKey, 0, $iv);
 $encryptedData = base64_encode($iv . $encrypted);
 
 // Rotar el log si es necesario
 if (file_exists(AUDIT_LOG_FILE) && filesize(AUDIT_LOG_FILE) > MAX_LOG_SIZE) {
     $backupFile = __DIR__ . '/../private/audit_log_' . date('Ymd_His') . '.enc';
     rename(AUDIT_LOG_FILE, $backupFile);
 }
 
 // Escribir el contenido encriptado
 file_put_contents(AUDIT_LOG_FILE, $encryptedData . "\n", FILE_APPEND | LOCK_EX);
}
/**
 * Registra el acceso a un módulo
 */
function logModuleAccess($module) {
    $description = "Accedió al módulo $module";
    logAuditAction($module, 'Access', $description);
}

/**
 * Registra la visualización de un registro
 */
function logViewRecord($module, $recordId, $recordDetails = null) {
    $description = "Visualizó el registro $recordId";
    $data = $recordDetails ? ['record_id' => $recordId, 'details' => $recordDetails] : ['record_id' => $recordId];
    logAuditAction($module, 'View', $description, $data);
}

/**
 * Registra la creación de un registro
 */
function logCreateRecord($module, $recordId, $data) {
    $description = "Creó un nuevo registro ($recordId)";
    logAuditAction($module, 'Create', $description, $data);
}

/**
 * Registra la actualización de un registro
 */
function logUpdateRecord($module, $recordId, $oldData, $newData) {
    $description = "Actualizó el registro $recordId";
    $changes = array_diff_assoc($newData, $oldData);
    $data = [
        'record_id' => $recordId,
        'old_data' => $oldData,
        'new_data' => $newData,
        'changes' => $changes
    ];
    logAuditAction($module, 'Update', $description, $data);
}

/**
 * Registra la eliminación de un registro
 */
function logDeleteRecord($module, $recordId, $deletedData) {
    $description = "Eliminó el registro $recordId";
    logAuditAction($module, 'Delete', $description, $deletedData);
}

/**
 * Registra intentos de acceso no autorizado
 */
function logUnauthorizedAttempt($module, $action, $details = '') {
    $description = "Intento no autorizado: $details";
    logAuditAction($module, 'Unauthorized', $description);
}

/**
 * Registra errores del sistema
 */
function logSystemError($module, $errorMessage, $errorData = null) {
    $description = "Error del sistema: $errorMessage";
    logAuditAction($module, 'Error', $description, $errorData);
}
?>