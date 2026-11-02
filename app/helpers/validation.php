<?php
function valid_email(string $email): bool { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }
function valid_password(string $password): bool { return strlen($password) >= 8; }
function required_value(mixed $value): bool { return trim((string)$value) !== ''; }
function positive_int(mixed $value): bool { return filter_var($value, FILTER_VALIDATE_INT) !== false && (int)$value > 0; }
