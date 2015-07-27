<?php

namespace EcomDev\TranslateTool;

/**
 * Translation expression message
 *
 */
class Expression
    implements ExpressionInterface
{
    /**
     * @var string
     */
    private $message;

    /**
     * Scope of the translation expression
     *
     * @var null|string
     */
    private $scope;

    /**
     * @param string $message
     * @param null|string $scope
     */
    public function __construct($message, $scope = null)
    {
        $this->message = $message;
        $this->scope = $scope;
    }

    /**
     * Returns message of translation
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Returns scope of the translation message
     *
     * @return null|string
     */
    public function getScope()
    {
        return $this->scope;
    }
}
