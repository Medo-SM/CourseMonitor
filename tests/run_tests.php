<?php
// tests/run_tests.php
// Lightweight, zero-download test runner for CourseMonitor

define('TEST_MODE', true);
define('CM_SKIP_DB', true);

// Configure session before any output is sent
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Color codes for CLI output
$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$blue = "\033[34m";
$bold = "\033[1m";
$reset = "\033[0m";

class Assert {
    public static int $assertions = 0;
    public static int $failures = 0;
    public static array $failureMessages = [];

    public static function true($condition, string $message = ''): void {
        self::$assertions++;
        if (!$condition) {
            self::$failures++;
            self::$failureMessages[] = $message ?: "Expected true, got false";
        }
    }

    public static function false($condition, string $message = ''): void {
        self::$assertions++;
        if ($condition) {
            self::$failures++;
            self::$failureMessages[] = $message ?: "Expected false, got true";
        }
    }

    public static function equals($expected, $actual, string $message = ''): void {
        self::$assertions++;
        if ($expected !== $actual) {
            self::$failures++;
            $expStr = var_export($expected, true);
            $actStr = var_export($actual, true);
            self::$failureMessages[] = $message ?: "Expected $expStr, got $actStr";
        }
    }

    public static function contains(string $needle, string $haystack, string $message = ''): void {
        self::$assertions++;
        if (!str_contains($haystack, $needle)) {
            self::$failures++;
            self::$failureMessages[] = $message ?: "Expected string to contain '{$needle}'";
        }
    }

    public static function matches(string $pattern, string $subject, string $message = ''): void {
        self::$assertions++;
        if (!preg_match($pattern, $subject)) {
            self::$failures++;
            self::$failureMessages[] = $message ?: "Expected string to match pattern '{$pattern}'";
        }
    }
}

echo "{$bold}{$blue}======================================================={$reset}\n";
echo "{$bold}{$blue}      CourseMonitor Automated Verification Suite       {$reset}\n";
echo "{$bold}{$blue}======================================================={$reset}\n\n";

$testDir = __DIR__ . '/unit';
$testFiles = glob($testDir . '/*Test.php');

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

foreach ($testFiles as $file) {
    require_once $file;
    $className = basename($file, '.php');

    if (!class_exists($className)) {
        continue;
    }

    echo "{$bold}Running {$className}:{$reset}\n";
    $instance = new $className();
    $methods = get_class_methods($instance);

    foreach ($methods as $method) {
        if (!str_starts_with($method, 'test')) {
            continue;
        }

        $totalTests++;
        $startFailures = Assert::$failures;

        try {
            $instance->$method();
            $methodFailed = Assert::$failures > $startFailures;

            if ($methodFailed) {
                $failedTests++;
                echo "  {$red}✗{$reset} {$method}\n";
                while (!empty(Assert::$failureMessages)) {
                    $msg = array_shift(Assert::$failureMessages);
                    echo "      {$red}-> {$msg}{$reset}\n";
                }
            } else {
                $passedTests++;
                echo "  {$green}✓{$reset} {$method}\n";
            }
        } catch (Throwable $t) {
            $failedTests++;
            echo "  {$red}✗{$reset} {$method} (Exception: {$t->getMessage()})\n";
        }
    }
    echo "\n";
}

echo "{$bold}-------------------------------------------------------{$reset}\n";
echo "Summary: {$totalTests} tests executed, {$bold}{$green}{$passedTests} passed{$reset}, ";
if ($failedTests > 0) {
    echo "{$bold}{$red}{$failedTests} failed{$reset}.\n";
    echo "Total assertions: " . Assert::$assertions . "\n";
    exit(1);
} else {
    echo "{$bold}{$green}0 failed{$reset}.\n";
    echo "Total assertions: " . Assert::$assertions . "\n";
    echo "{$bold}{$green}All automated tests passed successfully!{$reset}\n\n";
    exit(0);
}
?>
