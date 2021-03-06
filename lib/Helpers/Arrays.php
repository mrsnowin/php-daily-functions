<?php

namespace bfday\PHPDailyFunctions\Helpers;

/**
 * ToDO: not working
 * Model for help in common routines.
 */
class Arrays
{
    const SORT_ORDER_DESC      = 1;
    const SORT_ORDER_ASC       = 2;
    const DEFAULT_KEY_SPLITTER = '.';
    const FILTER_LOGIC__AND    = 1;
    const FILTER_LOGIC__OR     = 2;
    protected static $sortOrders;
    protected static $filterLogics;

    /**
     * [
     *  'first' => [ // $internalKeyLevel = 0
     *      'sort' => 3, // $internalKeyLevel = 1 ... etc
     *  ],
     *  'second' => [
     *      'sort' => 1,
     *  ],
     *  'third' => [
     *      'sort' => 2,
     *  ],
     * ];
     *
     * @param        $ar         array
     * @param        $specialKey - key 'some.internal.key' that wil be expanded to
     *                           $ar['FIRST_LEVEL_KEY']['some']['internal']['key']. You can change keys splitter by
     *                           setting $keySplitter param
     * @param int    $sortOrder
     * @param string $keySplitter
     *
     * @return array
     * @throws \ErrorException
     */
    public static function sortByInternalKeys(&$ar, $specialKey, $sortOrder = self::SORT_ORDER_ASC, $keySplitter = '.')
    {
        static::init();

        if (!in_array($sortOrder, static::$sortOrders)) {
            throw new \ErrorException('Wrong sort order type. Use static::$sortOrders to define them.');
        }
        $helpAr = [];
        foreach ($ar as $item) {
            $helpAr[] = static::getValueBySpecialKey($item, $specialKey, $keySplitter);
        }
        $sortOrder = ($sortOrder == static::SORT_ORDER_DESC ? SORT_DESC : SORT_ASC);
        array_multisort($helpAr, $sortOrder, $ar);

        return $ar;
    }

    public static function init()
    {
        static::$sortOrders = [
            self::SORT_ORDER_DESC,
            self::SORT_ORDER_ASC,
        ];

        static::$filterLogics = [
            static::FILTER_LOGIC__AND,
            static::FILTER_LOGIC__OR,
        ];
    }

    /**
     * @param        $ar          array -
     * @param        $specialKey  string - string like 'first.second.third' converts to ['first']['second']['third'].
     * @param string $keySplitter - key splitter. Dot by default. You can specify any.
     *
     * @return null|mixed - value by special key.
     * @throws \ErrorException
     */
    public static function getValueBySpecialKey(&$ar, $specialKey, $keySplitter = '.')
    {
        static::init();

        if (strlen($keySplitter) == 0) {
            throw new \ErrorException('Key splitter cannot be empty.');
        }
        if (!is_array($ar)) {
            throw new \ErrorException('$ar param must be an array type.');
        }
        $specialKeyArray = explode($keySplitter, $specialKey);
        foreach ($specialKeyArray as $specialKeyItem) {
            if (strlen($specialKeyItem) == 0) {
                throw new \ErrorException('Key item cannot be empty.');
            }
            if (!isset($ar[$specialKeyItem])) {
                return null;
            }
            $ar =& $ar[$specialKeyItem];
        }

        return $ar;
    }

    /**
     * Uses special key (key like 'AAA.BBB.CCC...') to find a place and replaces it with $value
     *
     * @param        $ar
     * @param        $specialKey
     * @param        $value
     * @param string $keySplitter
     *
     * @throws \ErrorException
     */
    public static function setValueBySpecialKey(&$ar, $specialKey, $value, $keySplitter = '.')
    {
        static::init();

        if (strlen($keySplitter) == 0) {
            throw new \ErrorException('Key splitter cannot be empty.');
        }
        if (!is_array($ar)) {
            throw new \ErrorException('$ar param must be an array type.');
        }
        $specialKeyArray = explode($keySplitter, $specialKey);
        foreach ($specialKeyArray as $specialKeyItem) {
            if (strlen($specialKeyItem) == 0) {
                throw new \ErrorException('Key item cannot be empty.');
            }
            if (!isset($ar[$specialKeyItem])) {
                $ar[$specialKeyItem] = [];
            }
            if (!is_array($ar[$specialKeyItem])) {
                throw new \ErrorException('Value should be of array type.');
            }
            $ar =& $ar[$specialKeyItem];
        }
        $ar = $value;
    }

    /**
     * Gets all array values by SpecialKey
     *
     * @param        $ar
     * @param        $specialKey
     * @param string $keySplitter
     *
     * @return array - empty if no values found
     * @throws \ErrorException
     */
    public static function getAllValuesBySpecialKey(&$ar, $specialKey, $keySplitter = '.')
    {
        static::init();

        if (!is_array($ar)) {
            throw new \ErrorException('$ar param must be an array type.');
        }
        $values = [];
        foreach ($ar as $item) {
            $value = static::getValueBySpecialKey($item, $specialKey);
            if (!isset($value)) {
                return $values;
            }
            $values[] = static::getValueBySpecialKey($item, $specialKey);
        }

        return $values;
    }

    /**
     * Trying to find elements of $ar by $arrayFilter of quantity $quantityToFind using $filterLogic
     *
     * @param array    $ar
     * @param array    $arrayFilter
     * @param int      $quantityToFind
     * @param int|null $filterLogic
     *
     * @return array|null array of items or null - so if it's not null array have at least 1 element
     * @throws \Exception
     */
    public static function getValueByArrayFilter($ar, $arrayFilter, $quantityToFind = 1, $filterLogic = null)
    {
        static::init();

        if (!is_array($ar)) {
            throw new \ErrorException('$ar param must have array type.');
        }
        if (!is_array($arrayFilter) || count($arrayFilter) == 0) {
            throw new \Exception('Wrong value for $arrayFilter param - must be key-value array');
        }
        if (!is_int($quantityToFind) || $quantityToFind < 0) {
            throw new \Exception('Wrong value for $quantityToFind param - must have int type and be greater 0');
        }
        if ($filterLogic === null) {
            $filterLogic = static::FILTER_LOGIC__AND;
        }
        if (!in_array($filterLogic, static::$filterLogics)) {
            throw new \Exception('Wrong value for $filterLogic param - use class constants to setup appropriate value');
        }

        $quantityFound = 0;
        $foundItems = [];

        foreach ($ar as $item) {
            switch ($filterLogic) {
                case static::FILTER_LOGIC__AND:
                    $isMatch = false;
                    foreach ($arrayFilter as $key => $val) {
                        if ($item[$key] == $val) {
                            $isMatch = true;
                        } else {
                            $isMatch = false;
                        }
                    }
                    if ($isMatch) {
                        $foundItems[] = $item;
                        $quantityFound++;
                    }
                    if ($quantityToFind !== 0 && $quantityFound >= $quantityToFind) {
                        return $foundItems;
                    }
                    break;
                case static::FILTER_LOGIC__OR:
                    foreach ($arrayFilter as $key => $val) {
                        if ($item[$key] == $val) {
                            $foundItems[] = $item;
                            $quantityFound++;
                            break;
                        }
                    }
                    if ($quantityToFind !== 0 && $quantityFound >= $quantityToFind) {
                        return $foundItems;
                    }
                    break;
                    break;
            }
        }

        return count($foundItems) == 0 ? null : $foundItems;
    }

    /**
     * Pushes $needle into $acceptor only if $needle unique against $acceptor. Returns true if push is OK. False -
     * elsewhere.
     *
     * @param array $acceptor
     * @param       $needle
     * @param null  $key
     *
     * @return bool
     * @throws \ErrorException
     */
    public static function pushIfUnique(&$acceptor, $needle, &$key = null)
    {
        if (!is_array($acceptor)) {
            throw new \ErrorException('$acceptor param must be an array type.');
        }
        if (!in_array($needle, $acceptor)) {
            if ($key !== null && empty($key)) {
                $acceptor[$needle] = $needle;
            } elseif ($key !== null) {
                $acceptor[$key] = $needle;
            } else {
                $acceptor[] = $needle;
            }

            return true;
        }

        return false;
    }

    /**
     * Creates new array from $source by leaving only $onlyKeys keys
     *
     * @param array $source
     * @param array $onlyKeys
     *
     * @return array
     * @throws \Exception
     */
    public static function fetchOnlyKeys(&$source, $onlyKeys = [])
    {
        if (!is_array($source) || !is_array($onlyKeys)) {
            throw new \Exception('params have to be an array type');
        }

        return array_intersect_key($source, array_flip($onlyKeys));
    }

    /**
     * Creates new array from $source by excluding $excludedKeys keys
     *
     * @param array $source
     * @param array $excludedKeys
     *
     * @return array
     * @throws \Exception
     */
    public static function fetchWithoutKeys(&$source, $excludedKeys = [])
    {
        if (!is_array($source) || !is_array($excludedKeys)) {
            throw new \Exception('params have to be an array type');
        }

        return array_intersect_key($source, array_diff_key($source, array_flip($excludedKeys)));
    }

    /**
     * Checks array $ar have keys from $keysCodes and returns redundant keys
     *
     * @param array $ar   - array to examine
     * @param array $keysCodes - array keys to examine
     * @param bool  $isIgnoreCase
     *
     * @return bool|array - array of redundant keys
     * @throws \Exception
     */
    public static function whatKeysAreRedundant($ar, $keysCodes, $isIgnoreCase = false)
    {
        if (!is_array($ar) || !is_array($keysCodes)) {
            throw new \Exception('Params must have array type.');
        }
        $diff = array_diff(array_keys($ar), $keysCodes);
        if (count($diff)) {
            return $diff;
        } else {
            return true;
        }
    }

    /**
     * Checks array $ar contains all keys from $keysCodes and returns missing keys
     *
     * $isIgnoreCase - not implemented yet
     *
     * @param array $ar   - array to examine
     * @param array $keysCodes - array keys to examine
     * @param bool  $isIgnoreCase
     *
     * @return bool|array - array of missing keys
     * @throws \Exception
     */
    public static function whatkeysAreMissing($ar, $keysCodes, $isIgnoreCase = false)
    {
        if (!is_array($ar) || !is_array($keysCodes)) {
            throw new \Exception('Params must have array type.');
        }
        $diff = array_diff($keysCodes, array_keys($ar));
        if (count($diff)) {
            return $diff;
        } else {
            return true;
        }
    }

    /**
     * Reorganizes array $ar according $keyStrategy (how to change every key) and $valueStrategy (how to change every
     * value)
     *
     * @param $ar            array
     * @param $keyStrategy   null|callable  - when is callable should have format "function($key, $val)"
     * @param $valueStrategy null|callable - when is callable should have format "function($key, $val)"
     *
     * @return array - false if no changes were applied
     * @throws \Exception
     */
    public static function reorganize($ar, $keyStrategy = null, $valueStrategy = null)
    {
        if (!is_array($ar)) {
            throw new \Exception('Param $ar must have array type.');
        }

        $res = [];
        switch (true) {
            case ($keyStrategy === null && $valueStrategy === null):
                return $ar;
                break;
            case (is_callable($keyStrategy) && $valueStrategy === null):
                $valueStrategy = function ($key, $value) {
                    return $value;
                };
                break;
            case ($keyStrategy === null && is_callable($valueStrategy)):
                $keyStrategy = function ($key, $value = null) {
                    return $key;
                };
                break;
            case (is_callable($keyStrategy) && is_callable($valueStrategy)):
                break;

            default:
                throw new \Exception('$keyStrategy and $valueStrategy must contain null value or to be callable');
        }

        foreach ($ar as $key => $value) {
            $res[$keyStrategy($key, $value)] = $valueStrategy($key, $value);
        }

        return $res;
    }
}