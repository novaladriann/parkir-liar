<?php

function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}