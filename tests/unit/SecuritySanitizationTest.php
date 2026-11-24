<?php
// tests/unit/SecuritySanitizationTest.php

class SecuritySanitizationTest {

    private function phpEscapeHtml($str): string {
        if ($str === null) return '';
        return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function testHtmlEscapingBasicEntities() {
        $raw = '<script>alert("XSS")</script>';
        $escaped = $this->phpEscapeHtml($raw);

        Assert::false(str_contains($escaped, '<script>'), "Escaped output must not contain unescaped open script tags");
        Assert::contains('&lt;script&gt;', $escaped, "Angle brackets must be converted to HTML entities");
    }

    public function testHtmlEscapingQuotesAndAmps() {
        $raw = 'John & "Doe" <\'test\'>';
        $escaped = $this->phpEscapeHtml($raw);

        Assert::contains('&amp;', $escaped, "Ampersands must be escaped");
        Assert::contains('&quot;', $escaped, "Double quotes must be escaped");
        Assert::contains('&#039;', $escaped, "Single quotes must be escaped");
    }

    public function testSessionCookieSecuritySettings() {
        $params = session_get_cookie_params();
        Assert::true(isset($params['httponly']), "Cookie params must define httponly");
        Assert::true(isset($params['samesite']), "Cookie params must define samesite");
        Assert::equals('Lax', $params['samesite'], "Samesite must be set to Lax for CSRF mitigation");
    }

    public function testSignupBindingParameterTypes() {
        // Inspect public/signup.php and api/save_user.php for correct bind_param types
        $signupContent = file_get_contents(__DIR__ . '/../../public/signup.php');
        $saveUserContent = file_get_contents(__DIR__ . '/../../api/save_user.php');

        // Verify that 'sssssiis' (the buggy pattern that cast hash to integer) does NOT exist
        Assert::false(
            str_contains($signupContent, '"sssssiis"'),
            "public/signup.php must not contain buggy 'sssssiis' binding"
        );
        Assert::false(
            str_contains($saveUserContent, '"sssssiis"'),
            "api/save_user.php must not contain buggy 'sssssiis' binding"
        );

        // Verify correct 'sssssisi' pattern exists
        Assert::true(
            str_contains($signupContent, '"sssssisi"'),
            "public/signup.php must bind password hash as string and is_active as int/string"
        );
        Assert::true(
            str_contains($saveUserContent, '"sssssisi"'),
            "api/save_user.php must bind password hash as string and is_active as int/string"
        );
    }
}
?>
