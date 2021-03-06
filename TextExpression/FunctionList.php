<?php

namespace Slov\Expression\TextExpression;
use Slov\Expression\Type\TypeName;

/** Список функций */
class FunctionList
{
    /** @var FunctionStructure[] ассоциативный массив: название функции => структура функции */
    protected $list = [];

    /** добавление функции в список
     * @param string $name название функции
     * @param callable $function функция
     * @param TypeName $returnType  Тип возвращаймого значения.
     * @return $this */
    public function append($name, $function, TypeName $returnType)
    {
        $functionStructure = new FunctionStructure();
        $this->list[$name] = $functionStructure
            ->setName($name)
            ->setFunction($function)
            ->setReturnType($returnType);
        return $this;
    }

    /**
     * @param string $name название функции
     * @return bool true - функция существует
     */
    public function exists($name)
    {
        return array_key_exists($name, $this->list);
    }

    /**
     * @param string $name название функции
     * @return FunctionStructure структура функции
     */
    public function get($name)
    {
        return $this->list[$name];
    }
}