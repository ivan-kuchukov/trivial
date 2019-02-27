<?php


namespace trivial\models;


class PDODatabaseWithLog extends PDODatabase
{
    use DatabaseLoggerTrait;
}