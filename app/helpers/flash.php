<?php

function flash(string $key, string $message = ''): ?string
{
    if ($message === '') {
        if (!empty($_SESSION['flash'][$key])) {
            $text = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $text;
        }
        return null;
    }

    $_SESSION['flash'][$key] = $message;
    return null;
}

function hasFlash(string $key): bool
{
    return !empty($_SESSION['flash'][$key]);
}
