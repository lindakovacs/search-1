<?php
namespace Search\Model\Filter\Escaper;

use Cake\Core\InstanceConfigTrait;

class DefaultEscaper implements EscaperInterface
{
    use InstanceConfigTrait;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'fromWildCardAny' => '%',
        'toWildCardAny' => '\%',
        'fromWildCardOne' => '_',
        'toWildCardOne' => '\_',
    ];

    /**
     * {@inheritDoc}
     *
     * @param array $config Config.
     */
    public function __construct(array $config = [])
    {
        $this->config($config);
    }

    /**
     * Replace substitutions with original wildcards
     * but first, escape the original wildcards in the text to use them as normal search text
     *
     * @param string $value Value.
     * @return string Value
     */
    public function formatWildcards($value)
    {
        $from = $to = $substFrom = $substTo = [];
        if ($this->config('wildcardAny') !== '%') {
            $from[] = $this->config('fromWildCardAny');
            $to[] = $this->config('toWildCardAny');
            $substFrom[] = $this->config('wildcardAny');
            $substTo[] = '%';
        }
        if ($this->config('wildcardOne') !== '_') {
            $from[] = $this->config('fromWildCardOne');
            $to[] = $this->config('toWildCardOne');
            $substFrom[] = $this->config('wildcardOne');
            $substTo[] = '_';
        }
        if ($from) {
            // Escape first
            $value = str_replace($from, $to, $value);
            // Replace wildcards
            $value = str_replace($substFrom, $substTo, $value);
        }

        return $value;
    }
}
