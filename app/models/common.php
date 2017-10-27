<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

require_once dirname(__FILE__) . '/../functions.php';

Class Common extends Model {

	protected $table;

	public static function getAllByUserId($user_id){
		
		$records = self::where('user_id', $user_id)->get();

		foreach ($records as $first_key => $record) {

			if (isset($record->user_id))
				unset($record->user_id);

			unset($record->created_at, $record->updated_at);
		}

		return !empty($records) ? $records : false;
	}

	public function updateRecord($params){

		if (isset($params['id'])) { unset($params['id']); }
		$collection = collect($this);

		foreach($params as $key => $value){
			if($collection->has($key))
				$this->$key = $value;
		}

		$this->updated_at = date("Y-m-d H:i:s", time());
		$this->save();

		$dbRecord = self::find($this->id);
		if($this->updated_at != $dbRecord->updated_at)
			return false;

		unset($this->created_at, $this->updated_at);
		return $this;
	}

	public function deleteRecord(){

		$id = $this->id;
		$record_name = class_basename($this);

		$this->delete();
		if(self::find($id))
			displayMessage('Brisanje rekorda '.$record_name.' neuspješno.');
		
		return true;
	}

}