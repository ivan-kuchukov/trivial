<?php


namespace trivial\models;


class MySQLDatabaseWithLog extends MySQLDatabase
{
    use DatabaseLoggerTrait;

}