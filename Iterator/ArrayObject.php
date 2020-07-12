<?php declare(strict_types=1);
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Utilities\Iterator;

use Windwalker\Utilities\Arr;

/**
 * The ArrayObject class. Based on ZF2.
 *
 * @since  2.1.1
 */
class ArrayObject implements \IteratorAggregate, \ArrayAccess, \Serializable, \Countable, \JsonSerializable
{
    /**
     * Properties of the object have their normal functionality
     * when accessed as list (var_dump, foreach, etc.).
     */
    public const STD_PROP_LIST = 1;

    /**
     * Entries can be accessed as properties (read and write).
     */
    public const ARRAY_AS_PROPS = 2;

    /**
     * @var array
     */
    protected $storage;

    /**
     * @var int
     */
    protected $flag;

    /**
     * @var string
     */
    protected $iteratorClass = \ArrayIterator::class;

    /**
     * @var array
     */
    protected $protectedProperties;

    /**
     * Constructor
     *
     * @param array  $input
     * @param int    $flags
     * @param string $iteratorClass
     */
    public function __construct($input = [], $flags = self::ARRAY_AS_PROPS, $iteratorClass = \Generator::class)
    {
        $this->setFlags($flags);
        $this->storage = Arr::toArray($input);
        $this->setIteratorClass($iteratorClass);
        $this->protectedProperties = array_keys(get_object_vars($this));
    }

    /**
     * Returns whether the requested key exists
     *
     * @param  mixed $key
     *
     * @throws \InvalidArgumentException
     * @return boolean
     */
    public function __isset($key)
    {
        if ($this->flag == self::ARRAY_AS_PROPS) {
            return $this->offsetExists($key);
        }

        if (in_array($key, $this->protectedProperties, true)) {
            throw new \InvalidArgumentException('$key is a protected property, use a different key');
        }

        return isset($this->$key);
    }

    /**
     * Sets the value at the specified key to value
     *
     * @param  mixed $key
     * @param  mixed $value
     *
     * @throws \InvalidArgumentException
     * @return void|mixed
     */
    public function __set($key, $value)
    {
        if ($this->flag == self::ARRAY_AS_PROPS) {
            $this->offsetSet($key, $value);

            return;
        }

        if (in_array($key, $this->protectedProperties, true)) {
            throw new \InvalidArgumentException("$key is a protected property, use a different key");
        }

        $this->$key = $value;
    }

    /**
     * Unsets the value at the specified key
     *
     * @param  mixed $key
     *
     * @throws \InvalidArgumentException
     * @return void|mixed
     */
    public function __unset($key)
    {
        if ($this->flag == self::ARRAY_AS_PROPS) {
            $this->offsetUnset($key);

            return;
        }

        if (in_array($key, $this->protectedProperties, true)) {
            throw new \InvalidArgumentException('$key is a protected property, use a different key');
        }

        unset($this->$key);
    }

    /**
     * Returns the value at the specified key by reference
     *
     * @param  mixed $key
     *
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public function &__get($key)
    {
        $ret = null;

        if ($this->flag == self::ARRAY_AS_PROPS) {
            $ret =& $this->offsetGet($key);

            return $ret;
        }

        if (in_array($key, $this->protectedProperties, true)) {
            throw new \InvalidArgumentException('$key is a protected property, use a different key');
        }

        return $this->$key;
    }

    /**
     * Sort the entries by value
     *
     * @param int $flags
     *
     * @return static
     */
    public function asort($flags = null)
    {
        asort($this->storage, $flags);

        return $this;
    }

    /**
     * Get the number of public properties in the ArrayObject
     *
     * @return int
     */
    public function count()
    {
        return count($this->storage);
    }

    /**
     * Exchange the array for another one.
     *
     * @param  array|ArrayObject $data
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function exchangeArray($data)
    {
        if (!is_array($data) && !is_object($data)) {
            throw new \InvalidArgumentException('Passed variable is not an array or object, using empty array instead');
        }

        if (is_object($data) && ($data instanceof self || $data instanceof \ArrayObject)) {
            $data = $data->getArrayCopy();
        }

        if (!is_array($data)) {
            $data = (array) $data;
        }

        $storage = $this->storage;

        $this->storage = $data;

        return $storage;
    }

    /**
     * Creates a copy of the ArrayObject.
     *
     * @return array
     */
    public function getArrayCopy()
    {
        return $this->storage;
    }

    /**
     * Gets the behavior flags.
     *
     * @return int
     */
    public function getFlags()
    {
        return $this->flag;
    }

    /**
     * Create a new iterator from an ArrayObject instance
     *
     * @return \Iterator
     */
    public function &getIterator()
    {
        $class = $this->iteratorClass;

        // If is not generator, loop it-self to prevent storage property be referenced and override.
        if ($class !== \Generator::class) {
            $storage = new $class($this->storage);
        } else {
            $storage = &$this->storage;
        }


        foreach ($storage as $key => &$item) {
            yield $key => $item;
        }
    }

    /**
     * Gets the iterator classname for the ArrayObject.
     *
     * @return string
     */
    public function getIteratorClass()
    {
        return $this->iteratorClass;
    }

    /**
     * Sort Dataset by key.
     *
     * @param   integer $flags You may modify the behavior of the sort using the optional parameter flags.
     *
     * @return  static  Support chaining.
     *
     * @since   3.5.2
     */
    public function ksort($flags = null)
    {
        ksort($this->storage, ...func_get_args());

        return $this;
    }

    /**
     * Sort DataSet by key in reverse order
     *
     * @param   integer $flags You may modify the behavior of the sort using the optional parameter flags.
     *
     * @return  static  Support chaining.
     *
     * @since   3.5.2
     */
    public function krsort($flags = null)
    {
        krsort($this->storage, ...func_get_args());

        return $this;
    }

    /**
     * Sort data.
     *
     * @param integer $flags You may modify the behavior of the sort using the optional parameter flags.
     *
     * @return  static  Support chaining.
     *
     * @since   3.0
     */
    public function sort($flags = null)
    {
        sort($this->storage, ...func_get_args());

        return $this;
    }

    /**
     * Sort Data in reverse order.
     *
     * @param integer $flags You may modify the behavior of the sort using the optional parameter flags.
     *
     * @return  static  Support chaining.
     *
     * @since   3.0
     */
    public function rsort($flags = null)
    {
        rsort($this->storage, ...func_get_args());

        return $this;
    }

    /**
     * Sort DataSet by keys using a user-defined comparison function
     *
     * @param   callable $callable The compare function used for the sort.
     *
     * @return  static  Support chaining.
     *
     * @since   3.5.2
     */
    public function uksort($callable)
    {
        uksort($this->storage, $callable);

        return $this;
    }

    /**
     * Sort an array using a case insensitive "natural order" algorithm
     *
     * @return static
     */
    public function natcasesort()
    {
        natcasesort($this->storage);

        return $this;
    }

    /**
     * Sort entries using a "natural order" algorithm
     *
     * @return static
     */
    public function natsort()
    {
        natsort($this->storage);

        return $this;
    }

    /**
     * Returns whether the requested key exists
     *
     * @param  mixed $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return isset($this->storage[$key]);
    }

    /**
     * Returns the value at the specified key
     *
     * @param  mixed $key
     *
     * @return mixed
     */
    public function &offsetGet($key)
    {
        $ret = null;

        if (!$this->offsetExists($key)) {
            return $ret;
        }

        $ret =& $this->storage[$key];

        return $ret;
    }

    /**
     * Sets the value at the specified key to value
     *
     * @param  mixed $key
     * @param  mixed $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if ($key === null) {
            $this->storage[] = $value;

            return;
        }

        $this->storage[$key] = $value;
    }

    /**
     * Unsets the value at the specified key
     *
     * @param  mixed $key
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        if ($this->offsetExists($key)) {
            unset($this->storage[$key]);
        }
    }

    /**
     * Serialize an ArrayObject
     *
     * @return string
     */
    public function serialize()
    {
        return serialize(get_object_vars($this));
    }

    /**
     * Sets the behavior flags
     *
     * @param  int $flags
     *
     * @return void
     */
    public function setFlags($flags)
    {
        $this->flag = $flags;
    }

    /**
     * Sets the iterator classname for the ArrayObject
     *
     * @param  string $class
     *
     * @throws \InvalidArgumentException
     * @return void
     */
    public function setIteratorClass($class)
    {
        if (class_exists($class)) {
            $this->iteratorClass = $class;

            return;
        }

        if (strpos($class, '\\') === 0) {
            $class = '\\' . $class;

            if (class_exists($class)) {
                $this->iteratorClass = $class;

                return;
            }
        }

        throw new \InvalidArgumentException('The iterator class does not exist');
    }

    /**
     * Sort the entries with a user-defined comparison function and maintain key association
     *
     * @param  callable $function
     *
     * @return static
     */
    public function uasort($function)
    {
        uasort($this->storage, $function);

        return $this;
    }

    /**
     * Unserialize an ArrayObject
     *
     * @param  string $data
     *
     * @return void
     */
    public function unserialize($data)
    {
        $ar = unserialize($data);

        $this->protectedProperties = array_keys(get_object_vars($this));

        $this->setFlags($ar['flag']);
        $this->exchangeArray($ar['storage']);
        $this->setIteratorClass($ar['iteratorClass']);

        foreach ($ar as $k => $v) {
            switch ($k) {
                case 'flag':
                    $this->setFlags($v);
                    break;
                case 'storage':
                    $this->exchangeArray($v);
                    break;
                case 'iteratorClass':
                    $this->setIteratorClass($v);
                    break;
                case 'protectedProperties':
                    break;
                default:
                    $this->__set($k, $v);
            }
        }
    }

    /**
     * jsonSerialize
     *
     * @return  array
     *
     * @since  3.5.2
     */
    public function jsonSerialize()
    {
        return $this->storage;
    }
}
