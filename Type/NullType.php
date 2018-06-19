<?php

namespace Slov\Expression\Type;


class NullType extends Type
{
    /**
     * @return TypeName
     */
    public function getType()
    {
        return new TypeName(TypeName::NULL);
    }

    /**
     * @return null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param null $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @param string $string строковое представление значения
     * @return null значение
     */
    public function stringToValue($string)
    {
        return null;
    }

}