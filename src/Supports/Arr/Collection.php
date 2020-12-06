<?php

namespace Edison\Supports\Arr;

use Traversable;
use ArrayIterator;
use JsonSerializable;

class Collection implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    /**
     * @var array
     */
    protected $items = [];

    /**
     * Collection constructor.
     * @param array $items
     */
    public function __construct($items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }

    /**
     * @param array $items
     * @return static
     */
    public static function make($items = [])
    {
        return new static($items);
    }

    public function all()
    {
        return $this->items;
    }

    /**
     * Get average value of a given key.
     * @param null $callback
     * @param null $formatted
     * @return float|string
     */
    public function avg($callback = null, $formatted = null)
    {
        if ($count = $this->count()) {
            $result = $this->sum($callback) / $count;
            return empty($formatted) ? $result : sprintf($formatted, $result);
        }
    }

    /**
     * Wrapper of array_map.
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback)
    {
        $keys = array_keys($this->items);

        $items = array_map($callback, $this->items, $keys);

        return new static(array_combine($keys, $items));
    }

    /*
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param callable $callback
     * @return static
     */
    public function mapWithKeys(callable $callback)
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $assoc = $callback($value, $key);

            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }

        return new static($result);
    }

    public function median($key = null)
    {
        $count = $this->count();

        if ($count == 0) {
            return;
        }

        $values = with(isset($key) ? $this->pluck($key) : $this)->sort()->values();
        $middle = (int)($count / 2);

        if ($count % 2) {
            return $values->get($middle);
        }

        return (new static([
            $values->get($middle - 1), $values->get($middle)
        ]))->avg();
    }

    /**
     * @return static
     */
    public function collapse()
    {
        return new static(Arr::collapse($this->items));
    }

    /**
     * @param mixed $key
     * @return bool
     */
    public function contains($key)
    {
        if ($this->useAsCallable($key)) {
            return !is_null($this->first($key));
        }
        return in_array($key, $this->items);
    }

    /**
     * 对每个item调用callback.
     * @param callable $callback
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }
        return $this;
    }

    /**
     * @param $key
     * @return bool
     */
    public function every($key)
    {
        $callback = $this->valueRetriever($key);

        foreach ($this->items as $k => $v) {
            if (!$callback($v, $k)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $keys
     * @return static
     */
    public function except($keys)
    {
        $keys = is_array($keys) ?: func_get_args();

        return new static(Arr::except($this->items, $keys));
    }

    /**
     * @param callable|null $callback
     * @return static
     */
    public function filter(callable $callback = null)
    {
        if ($callback) {
            return new static(Arr::where($this->items, $callback));
        }

        return new static(array_filter($this->items));
    }

    /**
     * First Wrapper.
     * @param callable|null $callback
     * @param null $default
     * @return mixed
     */
    public function first(callable $callback = null, $default = null)
    {
        return Arr::first($this->items, $callback, $default);
    }

    /**
     * 翻转.
     * @return static
     */
    public function flip()
    {
        return new static(array_flip($this->items));
    }

    /**
     * Remove keys.
     * @param $keys
     * @return $this
     */
    public function forget($keys)
    {
        foreach ((array)$keys as $key) {
            $this->offsetUnset($key);
        }

        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->items[$key];
        }

        return $default;
    }

    /**
     * @param $groupBy
     * @param bool $preserveKeys
     * @return static
     */
    public function groupBy($groupBy, $preserveKeys = false)
    {
        $groupBy = $this->valueRetriever($groupBy);

        $results = [];

        foreach ($this->items as $key => $value) {
            $groupKeys = $groupBy($value, $key);

            if (!is_array($groupKeys)) {
                $groupKeys = [$groupKeys];
            }

            foreach ($groupKeys as $groupKey) {
                $groupKey = is_bool($groupKey) ? (int) $groupKey : $groupKey;

                if (!array_key_exists($groupKey, $results)) {
                    $results[$groupKey] = new static;
                }

                $results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
            }
        }

        return new static($results);
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Arr Last Wrapper.
     * @param callable|null $callback
     * @param null $default
     * @return mixed
     */
    public function last(callable $callback = null, $default = null)
    {
        return Arr::last($this->items, $callback, $default);
    }

    /**
     * Get the max value of a given key.
     * @param callable|string|null $callback
     * @param string|null $formatted
     * @return mixed
     */
    public function max($callback = null, $formatted = null)
    {
        $callback = $this->valueRetriever($callback);

        return $this->filter(function ($value) {
            return ! is_null($value);
        })->reduce(function ($result, $item) use ($callback, $formatted) {
            $value = $callback($item);
            $result = is_null($result) || $value > $result ? $value : $result;
            return empty($formatted) ? $result : sprintf($formatted, $result);
        });
    }

    /**
     * Get the min value of a given key.
     * @param null $callback
     * @param null|string $formatted
     * @return mixed
     */
    public function min($callback = null, $formatted = null)
    {
        $callback = $this->valueRetriever($callback);

        return $this->filter(function($value) {
            return !is_null($value);
        })->reduce(function($result, $item) use ($callback, $formatted) {
            $value = $callback($item);
            $result = is_null($result) || $value < $result ? $value : $result;
            return empty($formatted) ? $result : sprintf($formatted, $result);
        });
    }

    /**
     * 获取多维数组中指定key的值并封装.
     * @param $value
     * @param null $key
     * @return static
     */
    public function pluck($value, $key = null)
    {
        return new static(Arr::pluck($this->items, $value, $key));
    }

    /**
     * 取出并去除items最后一个值
     *
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Prepend Wrapper.
     * @param $value
     * @param null $key
     * @return $this
     */
    public function prepend($value, $key = null)
    {
        $this->items = Arr::prepend($this->items, $value, $key);

        return $this;
    }

    /**
     * Push an item to the end of the collection.
     * @param $value
     * @return $this
     */
    public function push($value)
    {
        $this->offsetSet(null, $value);

        return $this;
    }

    /**
     * Wrapper of array_reduce.
     * @param callable $callback
     * @param null $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Sort.
     * @param callable|null $callback
     * @return static
     */
    public function sort(callable $callback = null)
    {
        $items = $this->items;

        $callback ? uasort($items, $callback) : asort($items);

        return new static($items);
    }

    /**
     * @param $callback
     * @param int $options
     * @param bool $descending
     * @return static
     */
    public function sortBy($callback, $descending = false, $options = SORT_REGULAR)
    {
        $results = [];

        $callback = $this->valueRetriever($callback);

        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value, $key);
        }
        $descending ? arsort($results, $options) : asort($results, $options);
        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }
        return new static($results);
    }

    /**
     * 获取 items 之和
     * @param null|callable|string $callback
     * @return float|int
     */
    public function sum($callback = null)
    {
        if (is_null($callback)) {
            return array_sum($this->items);
        }

        $callback = $this->valueRetriever($callback);

        return $this->reduce(function ($result, $item) use ($callback) {
            return $result + $callback($item);
        }, 0);
    }

    /**
     * items转为下标数组
     * @return static
     */
    public function values()
    {
        return new static(array_values($this->items));
    }

    /**
     * 取值回调
     * @param $value
     * @return \Closure
     */
    protected function valueRetriever($value)
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }

        return function ($item) use ($value) {
            return data_get($item, $value);
        };
    }

    /**
     * @param $value
     * @return bool
     */
    protected function useAsCallable($value)
    {
       return !is_string($value) &&  is_callable($value);
    }

    /**
     * Get value by key.
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    /**
     * Set key value.
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Check key exists.
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Unset key.
     * @param mixed $key
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

    /**
     * Get count of items.
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Convert the object into something JSON serializable.
     * @return array
     */
    public function jsonSerialize()
    {
        return array_map(function ($value) {
            if ($value instanceof  \JsonSerializable) {
                return $value->jsonSerialize();
            }
            return $value;
        }, $this->items);
    }

    public function toArray() : array
    {
        return array_map(function ($value) {
            return $value instanceof Arrayable ? $value->toArray() : $value;
        }, $this->items);
    }

    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * @param $items
     * @return array|mixed
     */
    protected function getArrayableItems($items)
    {
        if (is_array($items)) {
            return $items;
        } elseif ($items instanceof  self) {
            return $items->all();
        } elseif ($items instanceof Arrayable) {
            return $items->toArray();
        } elseif ($items instanceof JsonSerializable) {
            return $items->jsonSerialize();
        } elseif ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        return (array) $items;
    }

    /**
     * items转json
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}