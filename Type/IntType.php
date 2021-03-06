<?php

namespace Slov\Expression\Type;

use Slov\Expression\TemplateProcessor\SingleTemplate;
use Slov\Helper\StringHelper;

/** Тип целое число */
class IntType extends Type{

    use SingleTemplate;

    const template = 'int';

    const templateFolder = 'type';
    /**
     * @return TypeName
     */
    function getType()
    {
        return new TypeName(TypeName::INT);
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int $value
     * @return $this;
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param string $string строковое представление значения
     * @return int значение
     */
    public function stringToValue($string)
    {
        return (int)$string;
    }

    public function generatePhpCode(): string
    {
        return StringHelper::replacePatterns(
            $this->getTemplate(),
            ['%value%' => $this->getValue()]
        );
    }

}
