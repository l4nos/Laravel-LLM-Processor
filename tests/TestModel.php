<?php

namespace Lanos\LLMProcessor\Tests;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'test_models';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
    ];
}