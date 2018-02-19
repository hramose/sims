<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\UserResolver;
use Auth;

use Illuminate\Auth\Authenticatable as AuthenticableTrait;

class User extends \Eloquent implements Authenticatable, Auditable, UserResolver
{
	use AuthenticableTrait;
    use \OwenIt\Auditing\Auditable;

	//Database driver
	/*
		1 - Eloquent (MVC Driven)
		2 - DB (Directly query to SQL database, no model required)
	*/

	//The table in the database used by the model.
	protected $table  = 'users';

	//The attribute that used as primary key.
	protected $primaryKey = 'id';

	public $timestamps = true;

	protected $fillable = ['lastname','firstname','middlename','username','password','email','status','access' ,' position' ];

	protected $hidden = ['password','remember_token'];
	//Validation rules!
	public static $rules = array(
		'Username' => 'required_with:password|min:3|max:20|unique:Users,username',
		'Password' => 'required|min:8|max:50',
		'Firstname' => 'required|between:2,100|string',
		'Middlename' => 'min:2|max:50|string',
		'Lastname' => 'required|min:2|max:50|string',
		'Email' => 'email',
		'Office' => 'required|exists:offices,code'
	);
	public static $informationRules = array(
		'Firstname' => 'required|between:2,100|string',
		'Middlename' => 'min:2|max:50|string',
		'Lastname' => 'required|min:2|max:50|string',
		'Email' => 'email'
	);

	public function loginRules(){
		return [
			'username' => 'required',
			'password' => 'required'
		];
	}

	public static $passwordRules = array(
		'Current Password'=>'required|min:8|max:50',
		'New Password'=>'required|min:8|max:50',
		'Confirm Password'=>'required|min:8|max:50|same:New Password',
	);

	public function updateRules(){
		$username = $this->username;
		return array(
			'Username' => 'min:3|max:20|unique:Users,username,'.$username.',username',
			'First name' => 'min:2|max:100|string',
			'Middle name' => 'min:2|max:50|string',
			'Last name' => 'min:2|max:50|string',
			'Email' => 'email',
			'Office' => 'required|exists:offices,code'
		);
	}

	public $action;

	protected $appends = [
		'accessName'
	];

	public static $access_list = [
		0 => "Administrator",
		1 => "AMO",
		2 => "Accounting",
		3 => "Offices", 
		4 => "Chief",
		5  => "Director"
	];

	public function setAccessNameAttribute($value)
	{
		$this->accessName = $this->access_list[$value];
	}

	public function getAccessNameAttribute($value)
	{
		return $this->access_list[$value];
	}


	public function getRememberToken()
	{
		return null; // not supported
	}

	public function setRememberToken($value)
	{
		// not supported
	}

	public function getRememberTokenName()
	{
		return null; // not supported
	}

	/**
	* Overrides the method to ignore the remember token.
	*/
	public function setAttribute($key, $value)
	{
		$isRememberTokenAttribute = $key == $this->getRememberTokenName();
		if (!$isRememberTokenAttribute)
		{
		 parent::setAttribute($key, $value);
		}
	}

	/**
	* {@inheritdoc}
	*/
	public static function resolveId()
	{
		return Auth::check() ? Auth::user()->getAuthIdentifier() : null;
	}


	public function officeInfo()
	{
		return $this->belongsTo('App\Office','office','code');
	}

	public function comments()
    {
        return $this->hasMany('App\RequestComments');
    }

    public function scopeFindByUserName($query, $value)
    {
    	$query->where('username', '=', $value);
    }
}
