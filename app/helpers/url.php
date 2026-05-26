<?php

function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}