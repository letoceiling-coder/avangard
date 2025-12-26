<?php


namespace App\Http\Filters;


use Illuminate\Database\Eloquent\Builder;

class FolderFilter extends AbstractFilter
{


    public const USER_ID = 'user_id';
    public const NAME = 'name';
    public const FILES = 'files';




    protected function getCallbacks(): array
    {
        return [
            self::USER_ID => [$this, 'user'],
            self::NAME => [$this, 'name'],
            self::FILES => [$this, 'files'],
        ];
    }

    public function user(Builder $builder, $value)
    {

        $builder->where('user_id', $value);
    }

    public function name(Builder $builder, $value)
    {

        $builder->where('name', $value);
    }

    public function files(Builder $builder, $value)
    {

        $builder->with('files')->take($value);
    }

}
