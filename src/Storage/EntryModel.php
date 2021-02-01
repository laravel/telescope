<?php

namespace Laravel\Telescope\Storage;


class EntryModel
{

    /* Return the model according to the database in use
     *
     * @return MongoModel | RegularModel
     */

    public static function getModelClass()
    {
        return env('DB_CONNECTION') === "mongodb" ? MongoModel::class : RegularModel::class;
    }
}
