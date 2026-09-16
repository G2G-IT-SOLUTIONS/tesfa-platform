<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotConversation extends Model
{
    protected $fillable = ['user_id','session_id','message_role','message_content','detected_intent'];
}