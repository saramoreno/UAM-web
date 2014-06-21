<?php
class Debug
{
    private static $starts = array();
    private static $time_log = array();
    private static $totals = array();
    private static $counts = array();
    private static $values = array();


    /**
     * values
     * -------------------------------------------------------------------------------------- */

    /**
     * Set a value
     * 
     * @param string  $key  Key of value
     * @param mixed  $value  Value of value
     * @return void
     */
    public static function setValue($key, $value)
    {
        self::$values[$key] = $value;
    }


    /**
     * counting
     * -------------------------------------------------------------------------------------- */

    /**
     * Increment a count to a given $action
     * 
     * @param string  $namespace  Namespace of action
     * @param string  $action  Action to count
     * @return void
     */
    public static function increment($namespace, $action)
    {
        $namespace = strtolower($namespace);
        $action = strtolower($action);
        
        if (!isset(self::$counts[$namespace]) || !is_array(self::$counts[$namespace]) || !isset(self::$counts[$namespace][$action])) {
            self::$counts[$namespace][$action] = 1;
            return;
        } else {
            self::$counts[$namespace][$action]++;
        }
    }


    /**
     * marking
     * -------------------------------------------------------------------------------------- */

    /**
     * Marks a starting point for an operation, returns unique hash
     * 
     * @param string  $type  Type of operation being measured
     * @return string|null
     */
    public static function markStart($type)
    {
        $type = strtolower($type);
        
        if (($type !== "yaml" || substr($type, -6) !== '_cache') && !Config::get('_display_debug_panel', false)) {
            return 0;
        }
        
        $hash = $type . '---' . md5(time());
        self::$starts[$hash] = microtime(true);
        return $hash;
    }


    /**
     * Marks an ending point for an operation, calculates totals, returns time operation took
     * 
     * @param string  $hash  Operation hash to end
     * @return float|bool
     */
    public static function markEnd($hash)
    {
        // do this immediately to skew results as little as possible
        $end = microtime(true);
        
        // we're not measuring this
        if ($hash === 0) {
            return null;
        }

        // we don't know about this start, abort
        if (!isset(self::$starts[$hash])) {
            // we're done
            return false;
        }
        
        // calculate diff
        $diff = $end - self::$starts[$hash];
        
        // parse hash
        list($type, $hash) = explode('---', $hash);
        
        // ensure time_log exists for type
        if (!isset(self::$time_log[$type])) {
            self::$time_log[$type] = array();
        }
        
        // add to time_log
        array_push(self::$time_log[$type], $diff);
        
        // ensure totals exist
        if (!isset(self::$totals[$type])) {
            self::$totals[$type] = 0.0;
        }
        
        // add to totals
        self::$totals[$type] += $diff;
        
        // we can delete start now
        unset(self::$starts[$hash]);
        
        // return diff in case dev wants to do something with it
        return $diff;
    }
    
    
    
    /**
     * reporting
     * -------------------------------------------------------------------------------------- */
    
    /**
     * Get a time log for a given type
     * 
     * @param string  $type  Type of operation to get log for
     * @return array
     */
    public static function getTimeLog($type)
    {
        return array_get(self::$time_log, strtolower($type), array());
    }


    /**
     * Get a time log for all types
     *
     * @param boolean  $refer_to_config  Should we refer to configured options when nothing was timed?
     * @return array
     */
    public static function getAllTimeLogs($refer_to_config=false)
    {
        $time_logs = self::$time_log;

        if (count($time_logs) === 0 && $refer_to_config) {
            return "None of the configured events occurred.";
        }

        foreach ($time_logs as $key => $value) {
            $time_logs[$key] = $value;
            
            foreach ($value as $sub_key => $sub_value) {
                $time_logs[$key][$sub_key] = number_format($sub_value, 6) . 's';
            }
        }

        return $time_logs;
    }
    
    
    /**
     * Get the total time for a given type
     * 
     * @param string  $type  Type of operation to get total for
     * @return float
     */
    public static function getTotalTime($type)
    {
        return array_get(self::$totals, strtolower($type), 0.0);
    }
    
    
    /**
     * Get the total time for all types
     * 
     * @param boolean  $refer_to_config  Should we refer to configured options when nothing was timed?
     * @return array|string
     */
    public static function getAllTotals($refer_to_config=false)
    {
        $totals = self::$totals;
        
        if (count($totals) === 0 && $refer_to_config) {
            return "None of the configured events occurred.";
        }
        
        foreach ($totals as $key => $value) {
            $totals[$key] = number_format($value, 6) . 's';
        }
        
        return $totals;
    }
    
    
    /**
     * Get the count for a given action
     * 
     * @param string  $action  Action to retrieve a count for
     * @return int
     */
    public static function getCount($action)
    {
        return array_get(self::$counts, strtolower($action), 0);
    }
    
    
    /**
     * Get counts for all actions
     * 
     * @return array
     */
    public static function getAllCounts()
    {
        return self::$counts;
    }
    
    
    /**
     * Get a value for a given key
     * 
     * @param string  $key  Key of value to retrieve
     * @return mixed
     */
    public static function getValue($key)
    {
        return array_get(self::$values, $key, null);
    }
    
    
    /**
     * Get all values
     * 
     * @return array
     */
    public static function getAllValues()
    {
        return self::$values;
    }
    
    
    /**
     * Get everything
     * 
     * @return array
     */
    public static function getAll()
    {
        return array(
            'values' => self::getAllValues(),
            'timing' => self::getAllTotals(),
            'counts' => self::getAllCounts(),
            'time_logs' => self::getAllTimeLogs()
        );
    }
}