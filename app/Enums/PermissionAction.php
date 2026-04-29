<?php

namespace App\Enums;

enum PermissionAction: string
{
    case Read = 'read';
    case Write = 'write';
    case Comment = 'comment';
    case Admin = 'admin';
}
