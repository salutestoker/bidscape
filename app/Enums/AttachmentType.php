<?php

namespace App\Enums;

enum AttachmentType: string
{
    case Image = 'image';
    case Document = 'document';
    case Spreadsheet = 'spreadsheet';
    case Archive = 'archive';
}
