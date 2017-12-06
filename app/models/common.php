<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

require_once dirname(__FILE__) . '/../functions.php';

Class Common extends Model {

	protected $table;

	public static function getAllByUserId($user_id){
	//  returns array of records
		$records = self::where('user_id', $user_id)->get();

		foreach ($records as $first_key => $record) {

			if (isset($record->user_id))
				unset($record->user_id);

			unset($record->created_at, $record->updated_at);
		}

		return $records;
	}

	public function updateRecord($params){
	//  returns true / message
		if (isset($params['id'])) { unset($params['id']); }
		$collection = collect($this);

		foreach($params as $key => $value){
			if($collection->has($key))
				$this->$key = $value;
		}

		$this->updated_at = date("Y-m-d H:i:s", time());
		$this->save();

		$found = self::find($this->id);

		if ($this->updated_at != $found->updated_at){
			$class = class_basename($this);
			displayMessage('Izmjena zapisa klase '.$class.' neuspješna.', 503);
		}

		return true;
	}

	public function deleteRecord(){
	//  returns true / message
		$id = $this->id;
		$class = class_basename($this);

		$this->delete();
		if(self::find($id))
            displayMessage('Brisanje zapisa '.$id.' klase '.$class.' neuspješno.', 503);
		
		return true;
	}

    public function getQueueableConnection()
    {
        // TODO: Implement getQueueableConnection() method.
    }

    public function resolveRouteBinding($value)
    {
        // TODO: Implement resolveRouteBinding() method.
    }
}