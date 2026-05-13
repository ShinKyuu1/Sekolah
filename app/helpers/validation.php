<?php

function sanitize_input($value)
{
    return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
}

function escape_html($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function old_value(string $key): string
{
    return $_POST[$key] ?? '';
}

function validate_required(string $value): bool
{
    return trim($value) !== '';
}

function validate_integer($value): bool
{
    return filter_var($value, FILTER_VALIDATE_INT) !== false;
}
