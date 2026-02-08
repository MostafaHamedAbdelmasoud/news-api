<?php

namespace App\Enums;

enum FetchStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Success = 'success';
    case Failed = 'failed';
    case Partial = 'partial';
}
