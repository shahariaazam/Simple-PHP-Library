<?php
/**
 * Date: 2/27/13
 * Time: 4:39 PM
 */

namespace SPL\Validator;

abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * @var array
     */
    protected $options = array();

    /**
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     *
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }
}