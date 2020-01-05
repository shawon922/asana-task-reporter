<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChatworkMessage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'message_id',
        'room_id',
        'account_id',
        'body',
        'account_name',
        'task_id',
        'task_status',
        'project_name',
        'task_url',
        'send_time',
        'update_time',
    ];
}
