<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-1
 * Time: 下午5:18
 */

namespace PhDownloader\Contracts;


/**
 * Class RequestFieldProperties
 *
 * @package PhDownloader\Contracts
 */
trait handleRequestField
{
    /**
     * @name Field Name
     * @var string
     */
    public $name;
    /**
     * @name Field Value
     * @var
     */
    public $value;

    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value= $value;
    }

    public function __toString()
    {
        return join(': ', array_filter([$this->name, $this->value]));
    }
}
