<?php

namespace MVC\Core;

abstract class Model
{
    /**
     * Получает запись из БД
     */
    public abstract function getData(int $index);
    /**
     * Добавляет или обновляет запись в БД
     */
    public abstract function write();
}