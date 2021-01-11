<?php

namespace Laravel\Telescope\Storage;


class EntryModel 
{
    public static function getModelClass()
    {
        return env('DB_CONNECTION') === "mongodb" ? MongoModel::class : RegularModel::class;
    }

}
