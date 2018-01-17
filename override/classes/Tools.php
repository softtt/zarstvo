<?php


class Tools extends ToolsCore
{
    /**
     * Get all values from $_POST/$_GET
     * @return mixed
     */
    public static function getAllValues()
    {
        return $_POST + $_GET;
    }
}