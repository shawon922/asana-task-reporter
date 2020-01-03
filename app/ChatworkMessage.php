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
        'send_time',
        'update_time',
    ];
}
