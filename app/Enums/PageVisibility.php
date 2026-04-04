<?php
namespace App\Enums;

enum PageVisibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';
    case PASSWORD = 'password';
}