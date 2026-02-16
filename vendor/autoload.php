<?php
spl_autoload_register(function($class){
    $prefix = 'PHPSecureForm\\';
    if (strpos($class, $prefix) === 0) {
        $path = __DIR__ . '/../src/lib/' . substr($class, strlen($prefix)) . '.php';
        if (file_exists($path)) require $path;
    }
});
?>
