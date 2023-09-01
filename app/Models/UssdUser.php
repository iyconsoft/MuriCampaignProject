<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UssdUser extends Model
{
	//Table name
    protected $table = 'ussd_users';
    //primary key
    public $primaryKey = 'id';
    //timestamps
    public $timestamps = false;
}
