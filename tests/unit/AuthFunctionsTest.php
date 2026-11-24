<?php
// tests/unit/AuthFunctionsTest.php

require_once __DIR__ . '/../../includes/auth_functions.php';

class AuthFunctionsTest {

    public function testBaseUrlGeneration() {
        // Test base_url returns a non-empty string
        $root = base_url();
        Assert::true(is_string($root), "base_url() should return a string");

        // Test base_url with paths
        $loginUrl = base_url('/public/login.php');
        Assert::contains('public/login.php', $loginUrl, "base_url should properly append path");

        $apiUrl = base_url('api/get_users.php');
        Assert::contains('api/get_users.php', $apiUrl, "base_url should handle paths without leading slash");
    }

    public function testCsrfTokenGenerationAndVerification() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token1 = generateCsrfToken();
        Assert::true(!empty($token1), "CSRF token should not be empty");
        Assert::equals(64, strlen($token1), "CSRF token should be 64 hex characters (32 bytes)");

        // Verification tests
        Assert::true(verifyCsrfToken($token1), "verifyCsrfToken should succeed with valid token");
        Assert::false(verifyCsrfToken('invalid_token'), "verifyCsrfToken should fail with invalid token");
        Assert::false(verifyCsrfToken(''), "verifyCsrfToken should fail with empty token");
    }

    public function testPasswordHashingIntegrity() {
        $password = 'TestP@ssw0rd!123';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        Assert::true(is_string($hash), "Password hash must be a string");
        Assert::true(strlen($hash) >= 60, "Bcrypt hash must be at least 60 characters");
        Assert::true(password_verify($password, $hash), "password_verify must succeed with correct password");
        Assert::false(password_verify('WrongPassword', $hash), "password_verify must fail with incorrect password");

        // Critical regression test for Bug 1: Password hash cast to integer '0'
        $castToInt = (int)$hash;
        Assert::equals(0, $castToInt, "String cast to integer in PHP evaluates to 0");
        Assert::false(password_verify($password, (string)$castToInt), "password_verify against '0' must fail");
    }

    public function testApiRequestDetection() {
        // Simulate normal script
        $_SERVER['SCRIPT_NAME'] = '/modules/admin/users.php';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        Assert::false(isApiRequest(), "Non-API script should not be detected as API request");

        // Simulate API script
        $_SERVER['SCRIPT_NAME'] = '/api/get_users.php';
        Assert::true(isApiRequest(), "Script in /api/ must be detected as API request");

        // Simulate JSON accept header
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['HTTP_ACCEPT'] = 'application/json, text/plain, */*';
        Assert::true(isApiRequest(), "Request with application/json accept header must be detected as API request");
    }
}
?>
