<?php


namespace Slov\Expression;


use Slov\Expression\Interfaces\Operand;
use Slov\Expression\OperationCache\Interfaces\OperationCache;
use Slov\Expression\TemplateProcessor\TemplateProcessor;
use Slov\Expression\TextExpression\Config;
use Slov\Expression\TextExpression\FunctionList;
use Slov\Expression\TextExpression\VariableList;
use Slov\Expression\Type\TypeName;

class ExpressionCache extends Expression implements Operand
{



    const template = 'expression';

    /** @var string */
    protected $functionName;

    /** @var string */
    protected $className;

    /** @var string результат рассчёта первого операнда */
    protected $firstOperandCodeResult;

    /** @var string результат рассчёта второго операнда */
    protected $secondOperandCodeResult;

    /** @var FunctionList */
    protected $functionList;

    /** @var VariableList */
    protected $variableList;

    /** @var  string */
    protected $expressionText;

    /** @var bool  */
    protected static $isAutoloadRegister = false;


    /**
     * @return string выражение в текстовом представлении
     */
    public function getExpressionText(): string
    {
        return $this->expressionText;
    }

    /**
     * @param string $expressionText выражение в текстовом представлении
     * @return $this
     */
    public function setExpressionText(string $expressionText)
    {
        $this->expressionText = $expressionText;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * @param string $className
     * @return $this
     */
    public function setClassName(?string $className)
    {
        $this->className = $className;
        return $this;
    }



    /**
     * @return mixed|null
     */
    public function getFunctionName()
    {
        return $this->functionName;
    }

    /**
     * @param mixed $functionName
     * @return $this
     */
    public function setFunctionName($functionName)
    {
        $this->functionName = $functionName;
        return $this;
    }


    /**
     * @return string|null
     */
    public function getFirstOperandCodeResult(): ?string
    {
        return $this->firstOperandCodeResult;
    }

    /**
     * @param string $firstOperandCodeResult
     * @return $this
     */
    public function setFirstOperandCodeResult(?string $firstOperandCodeResult)
    {
        $this->firstOperandCodeResult = $firstOperandCodeResult;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSecondOperandCodeResult(): ?string
    {
        return $this->secondOperandCodeResult;
    }

    /**
     * @param string $secondOperandCodeResult
     * @return $this
     */
    public function setSecondOperandCodeResult(?string $secondOperandCodeResult)
    {
        $this->secondOperandCodeResult = $secondOperandCodeResult;
        return $this;
    }


    /**
     * @return FunctionList|null
     */
    public function getFunctionList(): ?FunctionList
    {
        return $this->functionList;
    }

    /**
     * @param FunctionList $functionList
     * @return $this
     */
    public function setFunctionList(?FunctionList $functionList)
    {
        $this->functionList = $functionList;
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getVariableList()
    {
        return $this->variableList;
    }

    /**
     * @param mixed $variableList
     * @return $this
     */
    public function setVariableList($variableList)
    {
        $this->variableList = $variableList;
        return $this;
    }


    /**
     * @param array $variables
     * @return string
     */
    protected function render($variables = [])
    {
        return TemplateProcessor::getInstance()->render(TemplateProcessor::getInstance()->getTemplateByName(static::template),$variables);
    }

    /**
     * @return bool|null
     */
    public static function isAutoloadRegister(): ?bool
    {
        return static::$isAutoloadRegister;
    }

    /**
     * @param bool $isAutoloadRegister
     */
    public static function setIsAutoloadRegister(?bool $isAutoloadRegister)
    {
        static::$isAutoloadRegister = $isAutoloadRegister;
    }

    /**
     * @param $name
     * @return bool
     */
    public static function  expressionAutoload($name)
    {
        $fileName =  Config::getInstance()->getCacheFolder().DIRECTORY_SEPARATOR.str_replace(Config::getInstance()->getCacheClassPrefix(),'',$name).'.php';
        if(is_file($fileName))
            require_once $fileName;
        return false;
    }


    public function expressionAutoloadRegister()
    {
        if(!Config::getInstance()->isExpressionAsSingleMethod() && !static::isAutoloadRegister()){
            $a = spl_autoload_register([static::class,'expressionAutoload']);
        }
    }


    /**
     * @return mixed
     */
    public function calculate()
    {
        $this->expressionAutoloadRegister();
        $this->createExpressionCacheFile();
        $functionName = $this ->getFunctionName();
        $className = $this ->getClassName();
        return call_user_func([$className,$functionName],$this->getFunctionList(),$this->getVariableList());
    }

    /**
     * @return $this
     */
    public function createExpressionCacheFile()
    {
        $fileName = md5($this->getExpressionText());
        $this->setFunctionName(Config::getInstance()->getCacheFunctionPrefix().$fileName);
        $this->setClassName(Config::getInstance()->getCacheClassPrefix().$fileName);
        $fileFullName = implode(DIRECTORY_SEPARATOR,[Config::getInstance()->getCacheFolder(),$fileName.'.php']);
        if(!file_exists($fileFullName)) {
            $type = $this->getType();
            $code = $this->generatePhpCode();
            $this->saveFile($fileFullName,$code,$type);
            if($type == TypeName::UNKNOWN()) {
                $type =  $this->calculate()->getType();
                if($type == TypeName::UNKNOWN()) {
                    dump($this->calculate());unlink($fileFullName);exit;
                }
                $this->saveFile($fileFullName, $code,$type);
            }
        }
        return $this;

    }

    protected function saveFile($fileFullName,$code,TypeName $type)
    {
        $phpExpressionText =$this->render(
            [
                'class_name' => $this->getClassName(),
                'function_name' => $this->getFunctionName(),
                'body' => $code,
                'type' => $type->getValue()
            ]);
        file_put_contents($fileFullName, $phpExpressionText);
    }


    /**
     * @return string
     * @throws CalculationException
     */
    public function generatePhpCode()
    {
        $this->setFirstOperandCodeResult($this->getFirstOperand()->generatePhpCode());
        $this->setSecondOperandCodeResult($this->getSecondOperand()->generatePhpCode());
        try {
            return $this
                    ->getOperation()
                    ->setFirstOperand($this->getFirstOperand())
                    ->setSecondOperand($this->getSecondOperand())
                    ->setFirstOperandCode($this->getFirstOperandCodeResult())
                    ->setSecondOperandCode($this->getSecondOperandCodeResult())
                    ->generatePhpCode();
        } catch (CalculationException $exception)
        {
            throw new CalculationException(
                $exception->getMessage().
                "\n===\n".
                $this->getTextDescription()
            );
        }
    }


    /**
     * @return TypeName
     */
    public function getType()
    {
        if(!method_exists($this->getClassName(),'getType')) {
            $operation = $this
                ->getOperation();
            /** @var OperationCache $operation */
            return $operation
                ->setFirstOperand($this->getFirstOperand())
                ->setSecondOperand($this->getSecondOperand())
                ->resolveReturnTypeName();
        }else{
            return call_user_func([$this->getClassName(),'getType']);
        }
    }


}