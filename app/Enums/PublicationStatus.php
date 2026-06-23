<?php

namespace App\Enums;

enum PublicationStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Archived = 'archived';
}