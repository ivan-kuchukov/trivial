<?php


namespace trivial\models;


class PostgreSQLDatabaseWithLog extends PostgreSQLDatabase
{
    use DatabaseLoggerTrait;

}