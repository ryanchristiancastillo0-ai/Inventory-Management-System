<?php

declare(strict_types=1);

// ==========================================
// LOAD APPLICATION DEPENDENCIES
// ==========================================

require __DIR__ . "/../bootstrap/app.php";


// ==========================================
// REQUEST METHOD AND PATH
// ==========================================

$method = $_SERVER["REQUEST_METHOD"];

$path = parse_url(
    $_SERVER["REQUEST_URI"],
    PHP_URL_PATH
) ?: "/";


// ==========================================
// REMOVE BASE PATH
// ==========================================

$basePath = rtrim(
    str_replace(
        "\\",
        "/",
        dirname($_SERVER["SCRIPT_NAME"])
    ),
    "/"
);

/*
 * Only strip a base path when SCRIPT_NAME points at a real script
 * (Apache: /subdir/index.php). The PHP CLI dev server fills
 * SCRIPT_NAME with the request path itself (e.g. /face/login-options),
 * which must NOT be treated as a base path.
 */
if (!str_ends_with($_SERVER["SCRIPT_NAME"], ".php")) {
    $basePath = "";
}

if ($basePath !== "" && $basePath !== "/") {

    if ($path === $basePath) {
        $path = "/";

    } elseif (str_starts_with(
        $path,
        $basePath . "/"
    )) {
        $path = substr(
            $path,
            strlen($basePath)
        );
    }
}

$path = rtrim($path, "/") ?: "/";

$route = $method . " " . $path;


// ==========================================
// JSON RESPONSE HELPER
// ==========================================

if (!function_exists("jsonResponse")) {

    function jsonResponse(
        mixed $data,
        int $status = 200
    ): void {

        http_response_code($status);

        header(
            "Content-Type: application/json; charset=utf-8"
        );

        header("Cache-Control: no-store");

        echo json_encode($data);

        exit;
    }
}


// ==========================================
// HOME ROUTE
// ==========================================

if ($route === "GET /") {
    
    // Ensure session is started for authentication check
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    
    // Check if user is authenticated
    if (isset($_SESSION['user_id'])) {
        // User is authenticated, redirect to appropriate dashboard
        $redirectUrl = ($_SESSION['role'] ?? '') === 'ADMIN' 
            ? '/admin/dashboard' 
            : '/dashboard';
        header("Location: " . $redirectUrl);
        exit;
    } else {
        // User not authenticated, redirect to login page
        header("Location: /auth");
        exit;
    }
}


// ==========================================
// LOAD ROUTE FILES
// ==========================================

require __DIR__ . "/auth.php";

require __DIR__ . "/face.php";

require __DIR__ . "/admin.php";

require __DIR__ . "/inventory.php";

require __DIR__ . "/orders.php";

require __DIR__ . "/settings.php";
require __DIR__ . "/profile.php";

require __DIR__ . "/notifications.php";

require __DIR__ . "/api.php";




// ==========================================
// ROUTE NOT FOUND
// ==========================================

jsonResponse([
    "error" => "Route not found",
    "path" => $path
], 404);
